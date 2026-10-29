<?php
declare(strict_types=1);

final class NewsletterService
{
    public function subscribe(string $email, ?string $name = null, string $source = 'footer'): array
    {
        $email = safe_email($email);
        if (!$email) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        $stmt = db()->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = :e");
        $stmt->execute([':e' => $email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'unsubscribed') {
                db_update('newsletter_subscribers', (int) $existing['id'], ['status' => 'active', 'unsubscribed_at' => null]);
            }
            return ['success' => true, 'message' => 'You\'re already on the list — welcome back.'];
        }

        db_insert('newsletter_subscribers', [
            'email'   => $email,
            'name'    => $name ?: null,
            'token'   => bin2hex(random_bytes(24)),
            'source'  => $source,
            'status'  => 'active',
        ]);

        return ['success' => true, 'message' => 'You\'re on the list. Thank you for joining our journey.'];
    }

    public function unsubscribe(string $token): bool
    {
        $stmt = db()->prepare("SELECT id FROM newsletter_subscribers WHERE token = :t LIMIT 1");
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        return db_update('newsletter_subscribers', (int) $row['id'], [
            'status'          => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function renderPlaceholders(string $html, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
        return $html;
    }

    /** Wrap Quill-authored HTML with the branded email shell. */
    public function wrapEmail(string $innerHtml): string
    {
        $siteUrl = SITE_URL;
        return <<<HTML
        <!doctype html>
        <html>
        <body style="margin:0;padding:0;background:#F1E7DA;font-family:'Manrope',Helvetica,Arial,sans-serif;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1E7DA;padding:32px 0;">
            <tr><td align="center">
              <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#FAF6F0;border-radius:12px;overflow:hidden;max-width:600px;width:92%;">
                <tr><td style="background:#4A0507;padding:28px 32px;text-align:center;">
                  <span style="color:#FAF6F0;font-size:22px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;">Steven Picknwear</span>
                </td></tr>
                <tr><td style="padding:32px;color:#1F0A0B;font-size:15px;line-height:1.6;">
                  {$innerHtml}
                </td></tr>
                <tr><td style="background:#F1E7DA;padding:20px 32px;text-align:center;font-size:12px;color:#6E5452;">
                  &copy; {$this->year()} Steven Picknwear &middot; <a href="{$siteUrl}" style="color:#A50E14;">{$siteUrl}</a><br>
                  <a href="{$siteUrl}/modules/newsletter/unsubscribe.php?token={{unsubscribe_token}}" style="color:#6E5452;">Unsubscribe</a>
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }

    /** @return array{sent:int,failed:int} */
    public function sendCampaign(int $campaignId): array
    {
        $stmt = db()->prepare("SELECT * FROM newsletter_campaigns WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $campaignId]);
        $campaign = $stmt->fetch();
        if (!$campaign) {
            return ['sent' => 0, 'failed' => 0];
        }

        db_update('newsletter_campaigns', $campaignId, ['status' => 'sending']);

        $subscribers = db()->query("SELECT * FROM newsletter_subscribers WHERE status = 'active'")->fetchAll();
        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $sub) {
            $personalized = $this->renderPlaceholders($campaign['body_html'], [
                'name'               => $sub['name'] ?: 'there',
                'site_url'           => SITE_URL,
                'unsubscribe_token'  => $sub['token'],
            ]);
            $html = $this->renderPlaceholders($this->wrapEmail($personalized), ['unsubscribe_token' => $sub['token']]);

            if (Mailer::send($sub['email'], $campaign['subject'], $html)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        db_update('newsletter_campaigns', $campaignId, [
            'status'     => 'sent',
            'sent_count' => $sent,
            'sent_at'    => date('Y-m-d H:i:s'),
        ]);

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function year(): string
    {
        return date('Y');
    }
}
