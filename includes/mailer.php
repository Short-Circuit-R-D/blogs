<?php
require_once __DIR__ . '/../config.php'; // ensures DB/mail constants + Composer autoload are loaded even if mailer.php is included on its own

/**
 * Outgoing email. sendMail() is the one function the rest of the app
 * calls — every caller (welcome emails, subscriber notifications, topic
 * moderation decisions, etc.) stays the same no matter how this sends.
 *
 * Uses PHPMailer/SMTP when SMTP_HOST is set in config.php (requires
 * `composer require phpmailer/phpmailer` — already done if you're
 * reading this after running that command). Falls back to PHP's
 * built-in mail() automatically if SMTP_HOST is left blank, so local
 * dev without SMTP creds still "works" (as much as mail() ever does).
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function sendMail(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
        return sendMailViaPHPMailer($to, $subject, $htmlBody, $replyTo);
    }
    return sendMailViaPhpMailFunction($to, $subject, $htmlBody, $replyTo);
}

function sendMailViaPHPMailer(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    if (!class_exists(PHPMailer::class)) {
        error_log('sendMail: PHPMailer not found — run `composer require phpmailer/phpmailer` and make sure config.php requires vendor/autoload.php.');
        return sendMailViaPhpMailFunction($to, $subject, $htmlBody);
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = SMTP_USER !== '';
        if ($mail->SMTPAuth) {
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
        }
        $mail->SMTPSecure = SMTP_SECURE; // 'tls' or 'ssl'
        $mail->CharSet    = 'UTF-8';
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            $mail->SMTPDebug = 2; // prints the SMTP conversation — turn off in production
        }

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo);
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = trim(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)));

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('sendMail (PHPMailer) failed for ' . $to . ': ' . $mail->ErrorInfo);
        return false;
    } catch (\Throwable $e) {
        error_log('sendMail (PHPMailer) failed for ' . $to . ': ' . $e->getMessage());
        return false;
    }
}

function sendMailViaPhpMailFunction(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . ">\r\n";
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . $replyTo . "\r\n";
    }
    return @mail($to, $subject, $htmlBody, $headers);
}

function siteEmailLayout(string $title, string $bodyHtml): string
{
    $logo = rtrim(PUBLIC_SITE_URL, '/') . '/logo.svg';
    return '<!DOCTYPE html><html><body style="margin:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
        . '<div style="max-width:560px;margin:0 auto;padding:32px 24px;">'
        . '<img src="' . e($logo) . '" alt="Short Circuit Company" style="height:28px;margin-bottom:24px;">'
        . '<h1 style="font-size:20px;margin:0 0 16px;color:#111;">' . e($title) . '</h1>'
        . '<div style="font-size:14px;line-height:1.6;color:#333;">' . $bodyHtml . '</div>'
        . '<p style="font-size:11px;color:#999;margin-top:32px;">Short Circuit Company — Lighting Standards Reference</p>'
        . '</div></body></html>';
}
