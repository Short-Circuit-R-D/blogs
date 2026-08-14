<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$standards = db()->query('SELECT * FROM standards ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Standards';
$activeNav = 'standards';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Lighting codes and standards referenced on the public page (e.g. EN 12464-1), each linking to the official document.</p>

<div class="toolbar">
  <a href="standard/new" class="btn-primary">+ New Standard</a>
</div>

<table class="admin-table">
  <thead><tr><th>Order</th><th>Code</th><th>Name</th><th>Region</th><th>Published</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($standards as $s): ?>
    <tr>
      <td><?= (int)$s['sort_order'] ?></td>
      <td><code><?= e($s['code']) ?></code></td>
      <td><?= e($s['name']) ?></td>
      <td><?= e($s['region'] ?? '—') ?></td>
      <td><?= $s['is_published'] ? '<span class="badge-ok">Live</span>' : '<span class="badge-off">Hidden</span>' ?></td>
      <td class="row-actions">
        <a href="standard/<?= (int)$s['id'] ?>">Edit</a>
        <form method="post" action="standard_delete.php" onsubmit="return confirm('Delete this standard?');">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
