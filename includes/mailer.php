<?php
require_once __DIR__ . '/../config.php';

/**
 * Outgoing email for Short Circuit Company.
 * One branded layout, one From identity, headers that help inbox placement.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function mailIdentity(): array
{
    $site = rtrim(defined('PUBLIC_SITE_URL') ? PUBLIC_SITE_URL : '', '/');
    $host = parse_url($site, PHP_URL_HOST) ?: 'blogs.shortcircuit.company';
    return [
        'brand'    => 'Short Circuit Company',
        'from_name'=> defined('MAIL_FROM_NAME') && MAIL_FROM_NAME !== '' ? MAIL_FROM_NAME : 'Short Circuit Company',
        'from'     => defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : '',
        'site'     => $site,
        'host'     => $host,
        'parent'   => 'https://shortcircuit.company',
        'logo'     => 'https://shortcircuit.company/assets/img/logo-dark.svg',
        'color'    => '#eb1b26',
    ];
}

function emailButton(string $url, string $label): string
{
    $id = mailIdentity();
    return '<p style="margin:28px 0 8px;text-align:left;">'
        . '<a href="' . e($url) . '" style="display:inline-block;background:' . $id['color'] . ';color:#ffffff;padding:12px 22px;'
        . 'border-radius:4px;text-decoration:none;font-size:14px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">'
        . e($label) . '</a></p>'
        . '<p style="font-size:12px;color:#888;word-break:break-all;margin:0 0 16px;">'
        . 'Or paste this link: <a href="' . e($url) . '" style="color:' . $id['color'] . ';">' . e($url) . '</a></p>';
}

/**
 * Branded HTML wrapper used by every outgoing message.
 *
 * @param array{preheader?:string,unsubscribe?:string} $opts
 */
function siteEmailLayout(string $title, string $bodyHtml, array $opts = []): string
{
    $id = mailIdentity();
    $preheader = trim((string)($opts['preheader'] ?? $title));
    $unsub = trim((string)($opts['unsubscribe'] ?? ''));
    $unsubHtml = $unsub !== ''
        ? '<p style="margin:10px 0 0;">If you no longer want these emails, <a href="' . e($unsub) . '" style="color:' . $id['color'] . ';">unsubscribe here</a>.</p>'
        : '';
    $hero = trim((string)($opts['hero'] ?? ''));
    $heroAlt = trim((string)($opts['hero_alt'] ?? $title));
    $eyebrow = trim((string)($opts['eyebrow'] ?? ''));
    $heroRow = $hero !== ''
        ? '<tr><td style="padding:0;line-height:0;font-size:0;">'
            . '<img src="' . e($hero) . '" alt="' . e($heroAlt) . '" width="560" '
            . 'style="width:100%;max-width:560px;height:auto;display:block;border:0;">'
            . '</td></tr>'
        : '';
    $eyebrowHtml = $eyebrow !== ''
        ? '<p style="margin:0 0 8px;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:' . $id['color'] . ';">' . e($eyebrow) . '</p>'
        : '';

    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="color-scheme" content="light">'
        . '<title>' . e($title) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#111;">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . e($preheader) . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #ececec;">'
        . '<tr><td style="background:#000000;padding:18px 28px;">'
        . '<p style="margin:0;font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:' . $id['color'] . ';">' . e($id['brand']) . '</p>'
        . '<p style="margin:4px 0 0;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:.04em;">Lighting Standards</p>'
        . '</td></tr>'
        . $heroRow
        . '<tr><td style="padding:28px 28px 8px;">'
        . $eyebrowHtml
        . '<h1 style="font-size:22px;line-height:1.3;margin:0 0 16px;color:#111;">' . e($title) . '</h1>'
        . '<div style="font-size:14px;line-height:1.65;color:#333;">' . $bodyHtml . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:8px 28px 28px;font-size:12px;line-height:1.55;color:#888;border-top:1px solid #f0f0f0;">'
        . '<p style="margin:16px 0 0;">' . e($id['brand']) . ' — Lighting Standards Reference<br>'
        . '<a href="' . e($id['site']) . '" style="color:' . $id['color'] . ';">' . e($id['host']) . '</a>'
        . ' · <a href="' . e($id['parent']) . '" style="color:' . $id['color'] . ';">shortcircuit.company</a></p>'
        . $unsubHtml
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Branded “To read more, visit” block with a visible URL. */
function emailReadMoreBlock(string $url, string $label = 'To read more, visit'): string
{
    $id = mailIdentity();
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:28px 0 8px;">'
        . '<tr><td style="background:#111111;padding:20px 18px;">'
        . '<p style="margin:0 0 10px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:' . $id['color'] . ';">' . e($label) . '</p>'
        . '<a href="' . e($url) . '" style="display:inline-block;background:' . $id['color'] . ';color:#ffffff;padding:12px 20px;'
        . 'border-radius:4px;text-decoration:none;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;">Open the page</a>'
        . '<p style="font-size:12px;color:#cccccc;word-break:break-all;margin:12px 0 0;">'
        . '<a href="' . e($url) . '" style="color:#ffffff;text-decoration:underline;">' . e($url) . '</a></p>'
        . '</td></tr></table>';
}

/**
 * @param string|array|null $opts Reply-To string (legacy) or options:
 *   reply_to, reply_name, category (transactional|marketing), unsubscribe, list_id
 */
function sendMail(string $to, string $subject, string $htmlBody, $opts = null): bool
{
    if (is_string($opts)) {
        $opts = ['reply_to' => $opts];
    }
    $opts = is_array($opts) ? $opts : [];

    if (defined('SMTP_HOST') && SMTP_HOST !== '') {
        return sendMailViaPHPMailer($to, $subject, $htmlBody, $opts);
    }
    return sendMailViaPhpMailFunction($to, $subject, $htmlBody, $opts);
}

function sendMailViaPHPMailer(string $to, string $subject, string $htmlBody, array $opts = []): bool
{
    if (!class_exists(PHPMailer::class)) {
        error_log('sendMail: PHPMailer not found — run `composer require phpmailer/phpmailer`.');
        return sendMailViaPhpMailFunction($to, $subject, $htmlBody, $opts);
    }

    $id = mailIdentity();
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
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->Hostname   = $id['host'];
        if (defined('SMTP_DEBUG') && SMTP_DEBUG) {
            $mail->SMTPDebug = 2;
        }

        $mail->setFrom($id['from'], $id['from_name']);
        $mail->Sender = $id['from'];
        $mail->addAddress($to);
        $replyTo = trim((string)($opts['reply_to'] ?? ''));
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, (string)($opts['reply_name'] ?? ''));
        } else {
            $mail->addReplyTo($id['from'], $id['from_name']);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = emailPlainText($htmlBody);
        $mail->MessageID = '<' . bin2hex(random_bytes(12)) . '@' . $id['host'] . '>';

        $category = ($opts['category'] ?? 'transactional') === 'marketing' ? 'marketing' : 'transactional';
        $mail->addCustomHeader('Organization', $id['brand']);
        $mail->addCustomHeader('X-Entity-Ref-ID', bin2hex(random_bytes(8)));
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'All');
        $mail->addCustomHeader('List-Id', $id['brand'] . ' Lighting Blog <blog.' . $id['host'] . '>');

        $unsub = trim((string)($opts['unsubscribe'] ?? ''));
        if ($category === 'marketing' && $unsub !== '') {
            $mail->addCustomHeader('List-Unsubscribe', '<' . $unsub . '>');
            $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            $mail->addCustomHeader('Precedence', 'bulk');
        } else {
            $mail->addCustomHeader('Auto-Submitted', 'auto-generated');
        }

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

function sendMailViaPhpMailFunction(string $to, string $subject, string $htmlBody, array $opts = []): bool
{
    $id = mailIdentity();
    $from = $id['from_name'] . ' <' . $id['from'] . '>';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $from . "\r\n";
    $headers .= 'Sender: ' . $id['from'] . "\r\n";
    $replyTo = trim((string)($opts['reply_to'] ?? ''));
    $headers .= 'Reply-To: ' . (($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) ? $replyTo : $id['from']) . "\r\n";
    $headers .= 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $id['host'] . ">\r\n";
    $headers .= 'Organization: ' . $id['brand'] . "\r\n";
    $headers .= 'List-Id: ' . $id['brand'] . ' Lighting Blog <blog.' . $id['host'] . ">\r\n";
    $unsub = trim((string)($opts['unsubscribe'] ?? ''));
    if (($opts['category'] ?? '') === 'marketing' && $unsub !== '') {
        $headers .= 'List-Unsubscribe: <' . $unsub . ">\r\n";
        $headers .= "List-Unsubscribe-Post: List-Unsubscribe=One-Click\r\n";
    }
    return @mail($to, $subject, $htmlBody, $headers);
}

function emailPlainText(string $html): string
{
    $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
    $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
    $text = preg_replace('/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $text) ?? $text;
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    return preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
}
