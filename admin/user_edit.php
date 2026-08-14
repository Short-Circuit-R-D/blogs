<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$user = ['id' => 0, 'name' => '', 'email' => '', 'role' => 'client', 'is_preapproved' => 0, 'is_active' => 1, 'is_subscribed' => 1];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) redirect('users.php');
    $user = $found;
}

$errors = [];
$isNew = !$id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $user['name']           = trim($_POST['name'] ?? '');
    $user['email']          = trim($_POST['email'] ?? '');
    $user['role']           = in_array($_POST['role'] ?? '', allRoles(), true) ? $_POST['role'] : 'client';
    $user['is_preapproved'] = isset($_POST['is_preapproved']) ? 1 : 0;
    $user['is_active']      = isset($_POST['is_active']) ? 1 : 0;
    $password                = $_POST['password'] ?? '';

    if ($user['name'] === '')  $errors[] = 'Name is required.';
    if ($user['email'] === '' || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($isNew && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters for a new account.';

    if (!$errors) {
        $dupStmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $dupStmt->execute([$user['email'], $id]);
        if ($dupStmt->fetch()) $errors[] = 'Another account already uses that email.';
    }

    if (!$errors) {
        $pdo = db();
        if ($id) {
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $errors[] = 'New password must be at least 8 characters.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_preapproved=?, is_active=?, password_hash=? WHERE id=?');
                    $stmt->execute([$user['name'], $user['email'], $user['role'], $user['is_preapproved'], $user['is_active'], password_hash($password, PASSWORD_DEFAULT), $id]);
                }
            } else {
                $stmt = $pdo->prepare('UPDATE users SET name=?, email=?, role=?, is_preapproved=?, is_active=? WHERE id=?');
                $stmt->execute([$user['name'], $user['email'], $user['role'], $user['is_preapproved'], $user['is_active'], $id]);
            }
            if (!$errors) redirect('users.php');
        } else {
            $token = bin2hex(random_bytes(24));
            $stmt = $pdo->prepare('INSERT INTO users (name, email, role, is_preapproved, is_active, password_hash, is_subscribed, unsubscribe_token) VALUES (?,?,?,?,?,?,0,?)');
            $stmt->execute([$user['name'], $user['email'], $user['role'], $user['is_preapproved'], $user['is_active'], password_hash($password, PASSWORD_DEFAULT), $token]);
            redirect('users.php');
        }
    }
}

$pageTitle = $id ? 'Edit User' : 'New User';
$activeNav = 'users';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <div class="form-grid-2">
    <label>Name
      <input type="text" name="name" value="<?= e($user['name']) ?>" required>
    </label>
    <label>Email
      <input type="text" name="email" value="<?= e($user['email']) ?>" required>
    </label>
  </div>

  <div class="form-grid-2">
    <label>Role
      <select name="role">
        <?php foreach (allRoles() as $r): ?>
          <option value="<?= e($r) ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= e(roleLabel($r)) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label><?= $isNew ? 'Password' : 'New password' ?> <span class="hint"><?= $isNew ? '(8+ characters)' : '(leave blank to keep current password)' ?></span>
      <input type="password" name="password" autocomplete="new-password">
    </label>
  </div>

  <label class="checkbox-label">
    <input type="checkbox" name="is_preapproved" <?= $user['is_preapproved'] ? 'checked' : '' ?>>
    Pre-approved — this account's topics always auto-publish, regardless of its role's setting
  </label>
  <label class="checkbox-label">
    <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
    Active (uncheck to block this account from logging in)
  </label>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save User</button>
    <a href="users" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
