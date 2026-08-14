<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$tools = db()->query('SELECT * FROM tools ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Tools';
$activeNav = 'tools';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">The tools carousel at the bottom of the public page — your own tools (LuxSCale, SChools, XR) and industry tools (Dialux, Relux, ...). No API details, just a short description and a link.</p>

<div class="toolbar">
  <a href="tool/new" class="btn-primary">+ New Tool</a>
</div>

<table class="admin-table">
  <thead><tr><th>Order</th><th>Name</th><th>External?</th><th>Published</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($tools as $t): ?>
    <tr>
      <td><?= (int)$t['sort_order'] ?></td>
      <td><?= e($t['name']) ?></td>
      <td><?= $t['is_external'] ? 'Industry tool' : 'Our tool' ?></td>
      <td><?= $t['is_published'] ? '<span class="badge-ok">Live</span>' : '<span class="badge-off">Hidden</span>' ?></td>
      <td class="row-actions">
        <a href="tool/<?= (int)$t['id'] ?>">Edit</a>
        <form method="post" action="tool_delete.php" onsubmit="return confirm('Delete this tool?');">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
