<?php
require_once __DIR__ . '/../includes/auth.php';

if (currentAdmin()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    ensureAdminUsersSchema();
    $stmt = db()->prepare('SELECT id, username, password_hash, name, email, is_active, email_verified_at FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash']) && empty($user['is_active'])) {
        adminAuditLog('login_fail', $username, 'Deactivated CMS admin login');
        $error = 'This admin account has been deactivated.';
    } elseif ($user && password_verify($password, $user['password_hash']) && empty($user['email_verified_at'])) {
        adminAuditLog('login_fail', $username, 'Unconfirmed CMS admin login');
        $error = 'This account must confirm the one-time link sent to its email before opening the dashboard.';
    } elseif ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'] ?? '',
            'email'    => $user['email'] ?? '',
        ];
        adminAuditLog('login_success', $user['username'], 'Admin signed in');
        redirect('dashboard.php');
    } else {
        adminAuditLog('login_fail', $username !== '' ? $username : 'guest', 'Failed admin login');
        $error = 'Incorrect username or password.';
    }
} else {
    adminAuditLog('login_page_open', 'guest', 'Opened the admin login page');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Lighting CMS</title>
<meta name="robots" content="noindex, nofollow">
<base href="<?= e(appUrl()) ?>">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="login-body">
  <form class="login-card" method="post" novalidate>
    <p class="eyebrow">Short Circuit Company</p>
    <h1>Lighting CMS</h1>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <label>Username
      <input type="text" name="username" autocomplete="username" required autofocus>
    </label>
    <label>Password
      <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button type="submit">Log In</button>
  </form>
</body>
</html>
