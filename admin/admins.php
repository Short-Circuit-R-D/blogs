<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$admins = db()->query('SELECT * FROM admin_users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'CMS Admins';
$activeNav = 'admins';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Dashboard logins in <code>admin_users</code>. New admins must confirm a one-time email link before they can open the dashboard. Contact Us messages go to confirmed, active admins.</p>

<?php if ($flash): ?><div class="alert-ok"><?= e($flash) ?></div><?php endif; ?>

<div class="toolbar">
  <a href="admin-user/new" class="btn-primary">+ Invite Admin</a>
</div>

<table class="admin-table">
  <thead>
    <tr>
      <th>Name</th>
      <th>Username</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Company</th>
      <th>Title</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($admins as $a): ?>
    <tr>
      <td><?= e($a['name'] ?: '—') ?></td>
      <td><?= e($a['username']) ?></td>
      <td><?= e($a['email'] ?: '—') ?></td>
      <td><?= e($a['phone'] ?: '—') ?></td>
      <td><?= e($a['company'] ?: '—') ?></td>
      <td><?= e($a['title'] ?: '—') ?></td>
      <td>
        <?php if (empty($a['is_active'])): ?>
          <span class="badge-off">Off</span>
        <?php elseif (empty($a['email_verified_at'])): ?>
          <span class="badge-pending">Awaiting confirm</span>
        <?php else: ?>
          <span class="badge-ok">Confirmed</span>
        <?php endif; ?>
      </td>
      <td class="row-actions">
        <a href="admin-user/<?= (int)$a['id'] ?>">Edit</a>
        <?php if ((int)$a['id'] !== (int)currentAdmin()['id'] && empty($a['email_verified_at'])): ?>
        <form method="post" action="admin_resend.php">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger" style="color:inherit;">Resend invite</button>
        </form>
        <?php endif; ?>
        <?php if ((int)$a['id'] !== (int)currentAdmin()['id']): ?>
        <form method="post" action="admin_delete.php" onsubmit="return confirm('Delete this CMS admin? They will no longer receive contact emails or be able to log in.');">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
