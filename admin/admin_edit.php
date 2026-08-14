<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$admin = [
    'id' => 0,
    'username' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
    'title' => '',
    'is_active' => 1,
];
$found = null;

if ($id) {
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) redirect('admins.php');
    $admin = $found;
}

$errors = [];
$isNew = !$id;
$isSelf = $id && (int)$id === (int)currentAdmin()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $admin['username'] = substr(trim((string)($_POST['username'] ?? '')), 0, 190);
    $admin['name']     = substr(trim((string)($_POST['name'] ?? '')), 0, 120);
    $admin['email']    = substr(trim((string)($_POST['email'] ?? '')), 0, 190);
    $admin['phone']    = substr(trim((string)($_POST['phone'] ?? '')), 0, 40);
    $admin['company']  = substr(trim((string)($_POST['company'] ?? '')), 0, 160);
    $admin['title']    = substr(trim((string)($_POST['title'] ?? '')), 0, 120);
    $admin['is_active'] = ($isSelf || isset($_POST['is_active'])) ? 1 : 0;
    $password = (string)($_POST['password'] ?? '');

    if ($admin['username'] === '') $errors[] = 'Username is required.';
    if ($admin['name'] === '') $errors[] = 'Name is required.';
    if ($admin['email'] === '' || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required. The invite and Contact Us messages are sent here.';
    }
    if (!$isNew && $password !== '' && strlen($password) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    if (!$errors) {
        $dupUser = db()->prepare('SELECT id FROM admin_users WHERE username = ? AND id != ?');
        $dupUser->execute([$admin['username'], $id]);
        if ($dupUser->fetch()) $errors[] = 'Another CMS admin already uses that username.';

        $dupEmail = db()->prepare('SELECT id FROM admin_users WHERE email = ? AND id != ?');
        $dupEmail->execute([$admin['email'], $id]);
        if ($dupEmail->fetch()) $errors[] = 'Another CMS admin already uses that email.';
    }

    if (!$errors) {
        $pdo = db();
        $phone = $admin['phone'] !== '' ? $admin['phone'] : null;
        $company = $admin['company'] !== '' ? $admin['company'] : null;
        $title = $admin['title'] !== '' ? $admin['title'] : null;

        if ($id) {
            $emailChanged = strcasecmp((string)($found['email'] ?? ''), $admin['email']) !== 0;
            if ($password !== '') {
                $stmt = $pdo->prepare(
                    'UPDATE admin_users SET username=?, name=?, email=?, phone=?, company=?, title=?, is_active=?, password_hash=? WHERE id=?'
                );
                $stmt->execute([
                    $admin['username'], $admin['name'], $admin['email'], $phone, $company, $title,
                    $admin['is_active'], password_hash($password, PASSWORD_DEFAULT), $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE admin_users SET username=?, name=?, email=?, phone=?, company=?, title=?, is_active=? WHERE id=?'
                );
                $stmt->execute([
                    $admin['username'], $admin['name'], $admin['email'], $phone, $company, $title,
                    $admin['is_active'], $id,
                ]);
            }
            if ($emailChanged && !$isSelf) {
                $token = issueAdminInviteToken($id);
                $inviter = currentAdmin();
                sendAdminInviteEmail(
                    ['name' => $admin['name'], 'email' => $admin['email']],
                    $token,
                    (string)(($inviter['name'] ?? '') !== '' ? $inviter['name'] : ($inviter['username'] ?? ''))
                );
                $_SESSION['admin_flash'] = 'Email changed. A new confirmation link was sent to ' . $admin['email'] . '. They cannot log in until they confirm.';
            }
            if ($isSelf) {
                $_SESSION['admin']['username'] = $admin['username'];
                $_SESSION['admin']['name'] = $admin['name'];
                $_SESSION['admin']['email'] = $admin['email'];
            }
            redirect('admins.php');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO admin_users (username, password_hash, name, email, phone, company, title, is_active, email_verified_at)
                 VALUES (?,?,?,?,?,?,?,?,NULL)'
            );
            $stmt->execute([
                $admin['username'],
                password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                $admin['name'], $admin['email'], $phone, $company, $title, $admin['is_active'],
            ]);
            $newId = (int)$pdo->lastInsertId();
            $token = issueAdminInviteToken($newId);
            $inviter = currentAdmin();
            $sent = sendAdminInviteEmail(
                ['name' => $admin['name'], 'email' => $admin['email']],
                $token,
                (string)(($inviter['name'] ?? '') !== '' ? $inviter['name'] : ($inviter['username'] ?? ''))
            );
            $_SESSION['admin_flash'] = $sent
                ? 'Invite sent to ' . $admin['email'] . '. They must confirm the one-time email link before they can open the dashboard.'
                : 'Admin saved, but the invite email could not be sent. Use Resend invite.';
            redirect('admins.php');
        }
    }
}

$pageTitle = $id ? 'Edit CMS Admin' : 'New CMS Admin';
$activeNav = 'admins';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="id" value="<?= (int)$admin['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <div class="form-grid-2">
    <label>Full name
      <input type="text" name="name" value="<?= e($admin['name'] ?? '') ?>" required>
    </label>
    <label>Email <span class="hint">(invite + Contact Us messages)</span>
      <input type="email" name="email" value="<?= e($admin['email'] ?? '') ?>" required>
    </label>
  </div>

  <div class="form-grid-2">
    <label>Username <span class="hint">(CMS login)</span>
      <input type="text" name="username" value="<?= e($admin['username'] ?? '') ?>" required>
    </label>
    <?php if (!$isNew): ?>
    <label>New password <span class="hint">(leave blank to keep current password)</span>
      <input type="password" name="password" autocomplete="new-password">
    </label>
    <?php endif; ?>
  </div>

  <div class="form-grid-2">
    <label>Phone <span class="hint">(optional)</span>
      <input type="text" name="phone" value="<?= e($admin['phone'] ?? '') ?>">
    </label>
    <label>Job title <span class="hint">(optional)</span>
      <input type="text" name="title" value="<?= e($admin['title'] ?? '') ?>">
    </label>
  </div>

  <label>Company <span class="hint">(optional)</span>
    <input type="text" name="company" value="<?= e($admin['company'] ?? '') ?>">
  </label>

  <?php if ($isSelf): ?>
    <input type="hidden" name="is_active" value="1">
    <p class="hint">This is your own account — it stays active.</p>
  <?php else: ?>
    <label class="checkbox-label">
      <input type="checkbox" name="is_active" <?= !empty($admin['is_active']) ? 'checked' : '' ?>>
      Active (uncheck to block login and contact emails)
    </label>
  <?php endif; ?>

<?php if ($isNew): ?>
  <p class="hint">They will receive a one-time confirmation link and cannot open the dashboard until they confirm and set a password.</p>
<?php endif; ?>

  <div class="form-actions">
    <button type="submit" class="btn-primary"><?= $isNew ? 'Send invite' : 'Save Admin' ?></button>
    <a href="admins" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
