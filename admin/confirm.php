<?php
require_once __DIR__ . '/../includes/auth.php';

ensureAdminUsersSchema();

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$error = null;
$success = null;
$needsPassword = false;
$admin = null;

if ($token === '') {
    $error = 'This confirmation link is missing its token.';
} else {
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE invite_token_hash = ?');
    $stmt->execute([$hash]);
    $admin = $stmt->fetch() ?: null;

    if (!$admin) {
        $error = 'This confirmation link is not valid or has already been used.';
    } elseif (!empty($admin['invite_expires_at']) && strtotime((string)$admin['invite_expires_at']) < time()) {
        $error = 'This confirmation link has expired. Ask a CMS admin to send a new invite.';
        $admin = null;
    } elseif (!empty($admin['email_verified_at']) && empty($admin['invite_token_hash'])) {
        $success = 'This account is already confirmed. You can log in.';
        $admin = null;
    }
}

if ($admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
        $needsPassword = true;
    } elseif ($password !== $confirm) {
        $error = 'The two passwords do not match.';
        $needsPassword = true;
    } else {
        db()->prepare(
            'UPDATE admin_users
             SET password_hash = ?, email_verified_at = NOW(), invite_token_hash = NULL, invite_expires_at = NULL
             WHERE id = ?'
        )->execute([password_hash($password, PASSWORD_DEFAULT), (int)$admin['id']]);
        $success = 'Your email is confirmed. You can now log in to the dashboard.';
        $admin = null;
        $token = '';
    }
} elseif ($admin) {
    $needsPassword = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm CMS access — Lighting CMS</title>
<meta name="robots" content="noindex, nofollow">
<base href="<?= e(appUrl()) ?>">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="login-body">
  <div class="login-card">
    <p class="eyebrow">Short Circuit Company</p>
    <h1>Confirm access</h1>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
      <div class="alert-ok"><?= e($success) ?></div>
      <p><a class="logout-link" href="login">Go to CMS login</a></p>
    <?php elseif ($needsPassword && $admin): ?>
      <p style="font-size:13px;color:#666;margin:0 0 12px;">Hi <?= e($admin['name'] ?: $admin['email']) ?>. Set a password to finish confirming this email. This link works only once.</p>
      <form method="post" novalidate>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>Password
          <input type="password" name="password" minlength="8" required autofocus>
        </label>
        <label>Confirm password
          <input type="password" name="password_confirm" minlength="8" required>
        </label>
        <button type="submit">Confirm and activate</button>
      </form>
    <?php else: ?>
      <p><a class="logout-link" href="login">Go to CMS login</a></p>
    <?php endif; ?>
  </div>
</body>
</html>
