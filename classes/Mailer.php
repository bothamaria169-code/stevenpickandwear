<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Thin PHPMailer wrapper: SMTP first, falls back to PHP's native mail()
 * so a page never hard-fails just because email sending failed.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, string $altBody = '', string $replyTo = '', string $replyToName = ''): bool
    {
        if (SMTP_USER !== '' && SMTP_PASS !== '') {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = (int) SMTP_PORT === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                // From must be the authenticated mailbox or the host will reject it.
                $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
                $mail->addAddress($to);
                if ($replyTo !== '') {
                    $mail->addReplyTo($replyTo, $replyToName ?: $replyTo);
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

                return $mail->send();
            } catch (PHPMailerException $e) {
                error_log('Mailer SMTP failed: ' . $e->getMessage());
            }
        }

        $fromAddr = (SMTP_USER !== '') ? SMTP_USER : SITE_EMAIL;
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . SMTP_FROM_NAME . ' <' . $fromAddr . ">\r\n";
        if ($replyTo !== '') {
            $headers .= 'Reply-To: ' . $replyTo . "\r\n";
        }
        return @mail($to, $subject, $htmlBody, $headers);
    }
}
