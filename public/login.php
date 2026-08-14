<?php
require_once __DIR__ . '/../includes/auth_user.php';

if (currentUser()) redirect('account.php');

$error = null;
$staffHint = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT id, name, email, password_hash, is_active, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && ($user['role'] ?? '') === 'employee') {
        $error = 'SC employees sign in with company credentials.';
        $staffHint = true;
    } elseif ($user && empty($user['password_hash'])) {
        $error = 'This email is on the mailing list but has no account yet. Create an account to log in.';
    } elseif ($user && password_verify($password, $user['password_hash']) && !$user['is_active']) {
        $error = 'This account has been deactivated. Contact a Short Circuit admin.';
    } elseif ($user && password_verify($password, $user['password_hash'])) {
        startUserSession($user);
        $redirectTo = $_SESSION['post_login_redirect'] ?? 'index.php';
        unset($_SESSION['post_login_redirect']);
        redirect($redirectTo);
    } else {
        $error = 'Incorrect email or password.';
    }
}

$pageTitle = 'Log In';
$pageRobots = 'noindex, nofollow';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card">
    <p class="eyebrow">Short Circuit Company</p>
    <h2 class="section-title">Log In</h2>
    <?php if ($error): ?><div class="alert"><?= e($error) ?><?php if (!empty($staffHint)): ?> <a href="sc-login">Open staff login →</a><?php endif; ?></div><?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <label>Email
        <input type="email" name="email" value="<?= e($email) ?>" required autofocus>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button type="submit" class="auth-submit">Log In</button>
    </form>
    <p class="auth-switch">No account yet? <a href="subscribe?mode=signup">Create one</a> · <a href="subscribe">Subscribe to the blog</a></p>
    <p class="auth-switch">Short Circuit team? <a href="sc-login">Sign in with company credentials</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
