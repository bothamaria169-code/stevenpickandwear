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
    /** Why the last send() failed (empty on success), for the admin's test-email button. */
    public static string $lastError = '';

    public static function send(string $to, string $subject, string $htmlBody, string $altBody = '', string $replyTo = '', string $replyToName = ''): bool
    {
        self::$lastError = '';
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
                $mail->Timeout    = 15; // never let a slow mail server hang checkout
                $mail->CharSet    = 'UTF-8';

                // From = the shop address. With Brevo, SMTP_USER is a login like
                // "xxxx@smtp-brevo.com", so the From address is separate and must be
                // a sender/domain verified in Brevo.
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($to);
                if ($replyTo !== '') {
                    $mail->addReplyTo($replyTo, $replyToName ?: $replyTo);
                } else {
                    $mail->addReplyTo(SITE_EMAIL, SMTP_FROM_NAME);
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlBody);

                return $mail->send();
            } catch (PHPMailerException $e) {
                self::$lastError = $e->getMessage();
                error_log('Mailer SMTP failed: ' . $e->getMessage());
                return false;
            }
        }

        $fromAddr = SMTP_FROM_EMAIL;
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: ' . SMTP_FROM_NAME . ' <' . $fromAddr . ">\r\n";
        if ($replyTo !== '') {
            $headers .= 'Reply-To: ' . $replyTo . "\r\n";
        }
        $ok = @mail($to, $subject, $htmlBody, $headers);
        if (!$ok) {
            self::$lastError = 'SMTP is not configured and PHP mail() failed.';
        }
        return $ok;
    }
}
