<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Users';
$activeNav = 'users';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Every public account (clients, SC employees, and leaders). Change a role, mark a specific account as always-auto-publish, or deactivate/delete an account.</p>

<div class="toolbar">
  <a href="user/new" class="btn-primary">+ New User</a>
</div>

<table class="admin-table">
  <thead>
    <tr><th>Name</th><th>Email</th><th>Role</th><th>Pre-approved</th><th>Active</th><th>Joined</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['name']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><span class="pill"><?= e(roleLabel($u['role'])) ?></span></td>
      <td><?= $u['is_preapproved'] ? '<span class="badge-ok">Yes</span>' : '<span class="badge-off">No</span>' ?></td>
      <td><?= $u['is_active'] ? '<span class="badge-ok">Active</span>' : '<span class="badge-off">Disabled</span>' ?></td>
      <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
      <td class="row-actions">
        <a href="user/<?= (int)$u['id'] ?>">Edit</a>
        <form method="post" action="user_delete.php" onsubmit="return confirm('Delete this user account? This cannot be undone.');">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
