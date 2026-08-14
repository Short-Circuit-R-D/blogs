<?php
require_once __DIR__ . '/../includes/auth_user.php';

$me = currentUserFull();
$error = null;
$success = null;
$name = $me['name'] ?? '';
$email = $me['email'] ?? '';
$company = $me['company'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();

    $honeypot = trim((string)($_POST['website'] ?? ''));
    $name = substr(trim((string)($_POST['name'] ?? '')), 0, 120);
    $email = substr(trim((string)($_POST['email'] ?? '')), 0, 190);
    $company = substr(trim((string)($_POST['company'] ?? '')), 0, 160);
    $message = trim((string)($_POST['message'] ?? ''));
    if (function_exists('mb_substr')) {
        $message = mb_substr($message, 0, 5000);
    } else {
        $message = substr($message, 0, 5000);
    }

    $lastSent = (int)($_SESSION['contact_sent_at'] ?? 0);
    if ($honeypot !== '') {
        $success = 'Thanks — your message has been sent.';
        $name = $email = $company = $message = '';
    } elseif ($name === '') {
        $error = 'Please enter your name.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($message === '') {
        $error = 'Please write a message.';
    } elseif ($lastSent && (time() - $lastSent) < 30) {
        $error = 'Please wait a moment before sending another message.';
    } else {
        $stmt = db()->prepare(
            'INSERT INTO contact_messages (name, email, company, message, ip, user_agent)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([
            $name,
            $email,
            $company !== '' ? $company : null,
            $message,
            substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        ]);
        $id = (int)db()->lastInsertId();
        $row = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'company' => $company !== '' ? $company : null,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $sent = notifyContactAdmins($row);
        if ($sent > 0) {
            db()->prepare('UPDATE contact_messages SET emailed_at = NOW() WHERE id = ?')->execute([$id]);
        }
        $_SESSION['contact_sent_at'] = time();
        $success = 'Thanks — your message has been sent. We will get back to you by email.';
        $name = $email = $company = $message = '';
        if ($me) {
            $name = $me['name'] ?? '';
            $email = $me['email'] ?? '';
            $company = $me['company'] ?? '';
        }
    }
}

$pageTitle = 'Contact Short Circuit Company';
$pageDescription = 'Contact Short Circuit Company about lighting standards, technical data, or the lighting blog.';
$pageCanonical = publicSiteUrl('contact');
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card auth-card-wide">
    <p class="eyebrow">Short Circuit Company</p>
    <h1 class="section-title">Contact Us</h1>
    <p class="section-sub">Questions about lighting standards, the blog, or working with Short Circuit? Send a message and the team will reply by email.</p>

    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <p class="hp-field" aria-hidden="true">
        <label>Website
          <input type="text" name="website" tabindex="-1" autocomplete="off">
        </label>
      </p>
      <label>Name <span class="hint">(required)</span>
        <input type="text" name="name" value="<?= e($name) ?>" maxlength="120" required autofocus>
      </label>
      <label>Email <span class="hint">(required)</span>
        <input type="email" name="email" value="<?= e($email) ?>" maxlength="190" required>
      </label>
      <label>Company <span class="hint">(optional)</span>
        <input type="text" name="company" value="<?= e($company) ?>" maxlength="160">
      </label>
      <label>Message <span class="hint">(required)</span>
        <textarea name="message" rows="6" maxlength="5000" required><?= e($message) ?></textarea>
      </label>
      <button type="submit" class="auth-submit">Send Message</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
