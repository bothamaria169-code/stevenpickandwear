<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * PHPMailer wrapper using the hosting account's own mail server.
 *
 * 1. SMTP with the mailbox login from config.local.php (SMTP_USER = full email
 *    address, SMTP_PASS = that mailbox's password, as set up in cPanel > Email Accounts).
 * 2. If SMTP isn't set up or fails, PHPMailer hands the message to the server's
 *    own mail program (exim on cPanel), so order emails still go out.
 * A page never hard-fails just because email sending failed.
 */
final class Mailer
{
    /** Why the last send() failed (empty on success), for the admin's test-email button. */
    public static string $lastError = '';
    /** How the last message went out: 'smtp', 'server' or '' (failed). */
    public static string $lastTransport = '';

    /** The SMTP server actually used: a Brevo relay host is ignored unless the login is a Brevo one. */
    public static function smtpHost(): string
    {
        $host = trim((string) SMTP_HOST);
        $brevoLogin = str_ends_with(strtolower((string) SMTP_USER), '@smtp-brevo.com');
        if ($host === '' || (str_contains($host, 'brevo') && !$brevoLogin)) {
            return 'localhost'; // the cPanel server's own SMTP
        }
        return $host;
    }

    public static function send(string $to, string $subject, string $htmlBody, string $altBody = '', string $replyTo = '', string $replyToName = ''): bool
    {
        self::$lastError = '';
        self::$lastTransport = '';
        $smtpError = '';

        if (SMTP_USER !== '' && SMTP_PASS !== '') {
            try {
                $mail = self::message($to, $subject, $htmlBody, $altBody, $replyTo, $replyToName);
                $host = self::smtpHost();
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = (int) SMTP_PORT === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int) SMTP_PORT;
                $mail->Timeout    = 15; // never let a slow mail server hang checkout
                if (!str_contains($host, 'brevo')) {
                    // cPanel's certificate is issued to the server's hostname, not
                    // localhost/mail.<domain>: keep encryption, skip the name check.
                    $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
                }
                if ($mail->send()) {
                    self::$lastTransport = 'smtp';
                    return true;
                }
            } catch (PHPMailerException $e) {
                $smtpError = $e->getMessage();
                error_log('Mailer SMTP failed (' . self::smtpHost() . '): ' . $smtpError);
            }
        }

        // Fallback: the server's own mail program.
        try {
            $mail = self::message($to, $subject, $htmlBody, $altBody, $replyTo, $replyToName);
            $mail->isMail();
            if ($mail->send()) {
                self::$lastTransport = 'server';
                if ($smtpError !== '') {
                    self::$lastError = 'SMTP login failed (' . $smtpError . '), sent through the server\'s mail instead.';
                }
                return true;
            }
        } catch (PHPMailerException $e) {
            self::$lastError = trim(($smtpError !== '' ? 'SMTP: ' . $smtpError . ' | ' : '') . 'Server mail: ' . $e->getMessage());
            error_log('Mailer server mail failed: ' . $e->getMessage());
            return false;
        }
        self::$lastError = $smtpError ?: 'Sending failed.';
        return false;
    }

    private static function message(string $to, string $subject, string $htmlBody, string $altBody, string $replyTo, string $replyToName): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        // From must be a real mailbox on the domain (SPF/DKIM), so prefer the SMTP login.
        $from = filter_var(SMTP_USER, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower((string) SMTP_USER), '@smtp-brevo.com')
            ? SMTP_USER : SMTP_FROM_EMAIL;
        $mail->setFrom($from, SMTP_FROM_NAME);
        $mail->Sender = $from; // envelope sender, so bounces come back to the shop
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
        return $mail;
    }
}
