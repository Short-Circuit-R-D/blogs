<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$admins = db()->query('SELECT * FROM admin_users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'CMS Admins';
$activeNav = 'admins';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Dashboard logins in <code>admin_users</code>. Contact Us messages are emailed to every active admin who has an email address.</p>

<div class="toolbar">
  <a href="admin-user/new" class="btn-primary">+ New Admin</a>
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
      <th>Active</th>
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
      <td><?= !empty($a['is_active']) ? '<span class="badge-ok">Active</span>' : '<span class="badge-off">Off</span>' ?></td>
      <td class="row-actions">
        <a href="admin-user/<?= (int)$a['id'] ?>">Edit</a>
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
