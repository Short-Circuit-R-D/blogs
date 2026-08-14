<?php
/**
 * Short Circuit Company — Lighting Technical Data CMS
 * Database configuration. Fill in your real credentials.
 */

if (is_readable(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$envKey, $envVal] = explode('=', $line, 2);
        $envKey = trim($envKey);
        $envVal = trim($envVal);
        $envVal = trim($envVal, "\"'");
        if ($envKey === '') continue;
        putenv($envKey . '=' . $envVal);
        $_ENV[$envKey] = $envVal;
    }
}

$host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
$isLocal = $host === 'localhost'
    || $host === '127.0.0.1'
    || $host === '::1'
    || (bool) preg_match('/^192\.168\.1\.\d+$/', $host)
    || ($host === '' && stripos(__DIR__, 'xampp') !== false);

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'tech_data');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'shorlrip_tech_data');
    define('DB_USER', 'shorlrip_abubakr');
    define('DB_PASS', 'luxsxaleai_abubakr');
    define('DB_CHARSET', 'utf8mb4');
}

// Base URL of the site, no trailing slash. Used to build upload links.
define('SITE_URL', 'https://shortcircuit.company/lightSCenter');

// Absolute filesystem path to the uploads folder (must be web-writable).
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');

// --- Outgoing email (article-published notifications, welcome emails,
// topic moderation decisions) — sent via PHPMailer/SMTP. ---
// define('MAIL_FROM_ADDRESS', 'no-reply@shortcircuit.company');
define('MAIL_FROM_ADDRESS', 'ahhmedabubakr1482@gmail.com');
define('MAIL_FROM_NAME', 'Short Circuit Company — Lighting Standards');

// Fill these in with your real SMTP provider (Gmail, SendGrid, Mailtrap,
// your host's SMTP, etc). If SMTP_HOST is left blank, includes/mailer.php
// falls back to PHP's mail() automatically — so you can leave this empty
// during early dev if you don't have SMTP creds yet.
define('SMTP_HOST', 'smtp.gmail.com');            // e.g. 'smtp.gmail.com' — leave blank to use PHP's mail() instead
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');      // 'tls' for port 587, 'ssl' for port 465
define('SMTP_USER', 'ahhmedabubakr1482@gmail.com');
define('SMTP_PASS', 'spsgfnvpvxvrijij');            // Gmail: use an App Password, not your normal password
define('SMTP_DEBUG', FALSE);       // true temporarily to print the SMTP conversation while testing

// External Short Circuit employee login. Public signup never creates this role —
// staff authenticate here, then land with role = employee (extra permissions).
// POST JSON: { "email": "...", "password": "..." }
// Expected JSON: { "ok": true, "name": "...", "email": "..." }
//             or { "ok": false, "error": "..." }
define('SC_LOGIN_API_URL', '');    // e.g. 'https://shortcircuit.company/api/staff-login'
define('SC_LOGIN_API_KEY', '');    // optional Bearer / API key header

// Composer autoload (PHPMailer and any other future dependency)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
