<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../includes/sc_auth.php';

if (currentUser()) redirect('account.php');

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $auth = scEmployeeAuthenticate($email, $password);
    if (!$auth['ok']) {
        $error = $auth['error'] ?? 'Those Short Circuit credentials were not accepted.';
    } else {
        $user = upsertScEmployeeUser($auth['email'], $auth['name']);
        startUserSession($user);
        $redirectTo = $_SESSION['post_login_redirect'] ?? 'account.php';
        unset($_SESSION['post_login_redirect']);
        redirect($redirectTo);
    }
}

$pageTitle = 'SC Staff Login';
$pageRobots = 'noindex, nofollow';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card">
    <p class="eyebrow">Short Circuit staff</p>
    <h2 class="section-title">Company Login</h2>
    <p class="section-sub">Sign in with your Short Circuit credentials. This is for SC employees — it unlocks staff features and is not the public account form.</p>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if (!scLoginApiConfigured()): ?>
      <div class="alert">Staff login is not connected yet. Set <code>SC_LOGIN_API_URL</code> in config.php when the company API is ready.</div>
    <?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <label>Work email
        <input type="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit" class="auth-submit"<?= scLoginApiConfigured() ? '' : ' disabled' ?>>Sign in as SC Employee</button>
    </form>
    <p class="auth-switch">Not on the SC team? <a href="login">Public log in</a> · <a href="subscribe?mode=signup">Create an account</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
