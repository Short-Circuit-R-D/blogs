<?php
/**
 * Standalone SMTP debug test — NOT part of the app flow.
 * Run it directly in the browser: http://localhost/lighting-cms/test_mail.php
 * or from CLI:                    php test_mail.php
 *
 * Delete this file once mail is working — it prints your SMTP
 * conversation and, briefly, whether auth succeeded.
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

// ---- EDIT THESE DIRECTLY (bypassing config.php on purpose, so you know
// exactly what's being used, with nothing else in the way) ----
$smtpHost   = 'smtp.gmail.com';
$smtpPort   = 587;
$smtpSecure = 'tls';                 // 'tls' for 587, 'ssl' for 465
$smtpUser   = 'ahhmedabubakr1482@gmail.com';
$smtpPass   = 'spsgfnvpvxvrijij';
$sendTo     = 'ahmed.mo.abubakr@gmail.com'; // send to yourself for the test
// ----------------------------------------------------------------

header('Content-Type: text/plain'); // so debug output isn't mangled by HTML

echo "PHP version: " . PHP_VERSION . "\n";
echo "PHPMailer class exists: " . (class_exists(PHPMailer::class) ? 'YES' : 'NO — composer install did not run correctly') . "\n";
echo "openssl loaded: " . (extension_loaded('openssl') ? 'YES' : 'NO — SMTPS/TLS will fail without this') . "\n\n";

if (!class_exists(PHPMailer::class)) {
    exit("Stopping — PHPMailer isn't autoloading. Check vendor/autoload.php exists and composer.json/lock are committed.\n");
}

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // prints every line of the SMTP conversation
    $mail->Debugoutput = function ($str, $level) {
        echo "[SMTP] $str\n";
    };

    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->Port       = $smtpPort;
    $mail->SMTPSecure = $smtpSecure;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;

    // Force TLS certificate checks off ONLY if you're on XAMPP and getting
    // a "could not verify certificate" error — remove this block once
    // confirmed it's just a local CA-bundle issue, don't ship it live.
    // $mail->SMTPOptions = [
    //     'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true],
    // ];

    $mail->setFrom($smtpUser, 'Test Sender');
    $mail->addAddress($sendTo);
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer test — ' . date('H:i:s');
    $mail->Body    = '<p>If you got this, SMTP is working.</p>';

    echo "--- Attempting send ---\n";
    $mail->send();
    echo "\n--- SUCCESS: mail sent to $sendTo ---\n";
} catch (PHPMailerException $e) {
    echo "\n--- FAILED ---\n";
    echo "Exception message: " . $e->getMessage() . "\n";
    echo "PHPMailer ErrorInfo: " . $mail->ErrorInfo . "\n";
} catch (\Throwable $e) {
    echo "\n--- FAILED (non-PHPMailer error) ---\n";
    echo get_class($e) . ": " . $e->getMessage() . "\n";
}
