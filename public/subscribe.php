<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../includes/mailer.php';

$mode = ($_GET['mode'] ?? '') === 'signup' ? 'signup' : 'subscribe';
$error = null;
$success = null;
$name = $email = '';
$profession = '';
$professionOther = '';
$company = '';
$phoneDial = '20';
$phoneNumber = '';
$subscribeAlerts = true;

if (currentUser() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('account.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();
    $intent    = ($_POST['intent'] ?? '') === 'signup' ? 'signup' : 'subscribe';
    $mode      = $intent;
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $company   = substr(trim($_POST['company'] ?? ''), 0, 160);
    $company   = $company !== '' ? $company : null;
    $phoneDial = preg_replace('/\D+/', '', (string)($_POST['phone_cc'] ?? '20')) ?: '20';
    $phoneNumber = ltrim(preg_replace('/\D+/', '', (string)($_POST['phone_number'] ?? '')), '0');
    $password  = $_POST['password'] ?? '';
    $subscribeAlerts = isset($_POST['subscribe']) || $intent === 'subscribe';

    $phone = formatPhoneE164($phoneDial, $phoneNumber);
    $country = phoneCountryByDial($phoneDial);

    $parsedProfession = parseSubscribeProfession($_POST);
    $profession = $parsedProfession['profession'];
    $professionOther = (string)($parsedProfession['profession_other'] ?? '');

    if ($email === '') {
        $error = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'That email address doesn\'t look right.';
    } elseif ($name === '') {
        $error = 'Please fill in your name.';
    } elseif ($parsedProfession['error']) {
        $error = $parsedProfession['error'];
    } elseif ($phoneNumber !== '' && $phone === false) {
        $error = $country
            ? 'Check the mobile number: ' . phoneLengthHint($country)
            : 'Choose a country code and enter a valid mobile number.';
    } elseif ($intent === 'signup' && strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $phone = $phone ?: null;
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing && ($existing['role'] ?? '') === 'employee') {
            $error = 'This email is a Short Circuit staff account. Sign in on the staff page.';
        } elseif ($intent === 'subscribe') {
            if ($existing) {
                $stmt = db()->prepare(
                    'UPDATE users SET is_subscribed = 1, phone = COALESCE(?, phone),
                     name = CASE WHEN name = \'\' THEN ? ELSE name END,
                     profession = ?, profession_other = ?, company = COALESCE(?, company)
                     WHERE id = ?'
                );
                $stmt->execute([
                    $phone, $name,
                    $parsedProfession['profession'], $parsedProfession['profession_other'],
                    $company, $existing['id'],
                ]);
                $success = 'You\'re on the list. New lighting guides will land in your inbox.';
            } else {
                $token = bin2hex(random_bytes(24));
                $stmt = db()->prepare(
                    'INSERT INTO users (name, email, phone, profession, profession_other, company, role, password_hash, is_subscribed, unsubscribe_token)
                     VALUES (?,?,?,?,?,?,\'client\',?,1,?)'
                );
                $stmt->execute([
                    $name, $email, $phone,
                    $parsedProfession['profession'], $parsedProfession['profession_other'],
                    $company, null, $token,
                ]);
                $body = '<p>Hi ' . e($name) . ',</p>'
                      . '<p>You\'re subscribed to the Short Circuit lighting blog. We\'ll email you when a new guide is published.</p>'
                      . '<p>Want an account so you can follow topics and join discussions? '
                      . '<a href="' . e(publicSiteUrl('subscribe?mode=signup')) . '">Create one here</a>.</p>';
                sendMail($email, 'You\'re subscribed to the lighting blog', siteEmailLayout('Subscribed', $body));
                $success = 'You\'re subscribed. Check your inbox — we\'ll write when a new guide goes live.';
            }
            $name = $email = $phoneNumber = $profession = $professionOther = $company = '';
        } else {
            if ($existing && !empty($existing['password_hash'])) {
                $error = 'An account with that email already exists. Log in instead.';
            } elseif ($existing && empty($existing['password_hash'])) {
                $stmt = db()->prepare(
                    'UPDATE users SET name = ?, phone = ?, profession = ?, profession_other = ?, company = ?, password_hash = ?, is_subscribed = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $name, $phone,
                    $parsedProfession['profession'], $parsedProfession['profession_other'], $company,
                    password_hash($password, PASSWORD_DEFAULT), $subscribeAlerts ? 1 : 0, $existing['id'],
                ]);
                startUserSession(['id' => (int)$existing['id'], 'name' => $name, 'email' => $email]);
                $body = '<p>Hi ' . e($name) . ',</p><p>Your account is ready. You were already on the mailing list — you can now log in, follow topics, and join the discussion on each article.</p>';
                sendMail($email, 'Welcome to Short Circuit Lighting Standards', siteEmailLayout('Welcome', $body));
                redirect($_SESSION['post_login_redirect'] ?? 'account.php');
            } else {
                $token = bin2hex(random_bytes(24));
                $stmt = db()->prepare(
                    'INSERT INTO users (name, email, phone, profession, profession_other, company, role, password_hash, is_subscribed, unsubscribe_token)
                     VALUES (?,?,?,?,?,?,\'client\',?,?,?)'
                );
                $stmt->execute([
                    $name, $email, $phone,
                    $parsedProfession['profession'], $parsedProfession['profession_other'], $company,
                    password_hash($password, PASSWORD_DEFAULT), $subscribeAlerts ? 1 : 0, $token,
                ]);
                $userId = (int)db()->lastInsertId();
                startUserSession(['id' => $userId, 'name' => $name, 'email' => $email]);
                $body = '<p>Hi ' . e($name) . ',</p><p>Your account is ready. '
                      . ($subscribeAlerts ? 'You\'re subscribed to new-guide email alerts — turn this off any time from your account page.' : 'You can turn on new-guide email alerts any time from your account page.')
                      . '</p>';
                sendMail($email, 'Welcome to Short Circuit Lighting Standards', siteEmailLayout('Welcome', $body));
                redirect($_SESSION['post_login_redirect'] ?? 'index.php');
            }
        }
    }
}

$pageTitle = $mode === 'signup' ? 'Create Account' : 'Subscribe';
$pageDescription = 'Subscribe to the Short Circuit lighting blog or create an account.';
$pageCanonical = publicSiteUrl($mode === 'signup' ? 'subscribe?mode=signup' : 'subscribe');
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card auth-card-wide">
    <p class="eyebrow">Short Circuit Company</p>
    <h2 class="section-title">Join the Lighting Blog</h2>
    <p class="section-sub">Subscribe or create an account with your email and role. Add a company and mobile number if you like.</p>

    <div class="auth-mode-tabs" role="tablist">
      <a class="auth-mode-tab<?= $mode === 'subscribe' ? ' active' : '' ?>" href="subscribe" role="tab">Subscribe only</a>
      <a class="auth-mode-tab<?= $mode === 'signup' ? ' active' : '' ?>" href="subscribe?mode=signup" role="tab">Create an account</a>
    </div>

    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <?php if ($mode === 'subscribe'): ?>
    <form method="post" novalidate class="js-phone-form">
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <input type="hidden" name="intent" value="subscribe">
      <?php include __DIR__ . '/partials_profile_fields.php'; ?>
      <button type="submit" class="auth-submit">Subscribe to the Blog</button>
    </form>
    <p class="auth-switch">Want to comment and follow topics? <a href="subscribe?mode=signup">Create an account</a></p>
    <?php else: ?>
    <form method="post" novalidate class="js-phone-form">
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <input type="hidden" name="intent" value="signup">
      <?php include __DIR__ . '/partials_profile_fields.php'; ?>
      <label>Password <span class="hint">(8+ characters)</span>
        <input type="password" name="password" minlength="8" required>
      </label>
      <label class="checkbox-row">
        <input type="checkbox" name="subscribe" <?= $subscribeAlerts ? 'checked' : '' ?>>
        <span>Email me when a new lighting guide is published</span>
      </label>
      <button type="submit" class="auth-submit">Create Account</button>
    </form>
    <p class="auth-switch">Only want the emails? <a href="subscribe">Subscribe without an account</a></p>
    <?php endif; ?>

    <p class="auth-switch">Already have an account? <a href="login">Log in</a></p>
    <p class="auth-switch">Short Circuit team? <a href="sc-login">Sign in with company credentials</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
