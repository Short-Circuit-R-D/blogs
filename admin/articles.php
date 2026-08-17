<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$articles = db()->query('SELECT * FROM articles ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Articles';
$activeNav = 'articles';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Lighting parameter explainers, standards guides, and comparison articles shown on the public page.</p>

<div class="toolbar">
  <a href="article/new" class="btn-primary">+ New Article</a>
</div>

<table class="admin-table">
  <thead>
    <tr><th>Order</th><th>Title</th><th>Tag</th><th>Slug</th><th>Views</th><th>Published</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($articles as $a): ?>
    <tr>
      <td><?= (int)$a['sort_order'] ?></td>
      <td><?= e($a['title']) ?></td>
      <td><span class="pill"><?= e($a['tag']) ?></span></td>
      <td><code><?= e($a['slug']) ?></code></td>
      <td><?= number_format((int)($a['view_count'] ?? 0)) ?></td>
      <td><?= $a['is_published'] ? '<span class="badge-ok">Live</span>' : '<span class="badge-off">Hidden</span>' ?></td>
      <td class="row-actions">
        <a href="article/<?= (int)$a['id'] ?>">Edit</a>
        <form method="post" action="article_delete.php" onsubmit="return confirm('Delete this article? This cannot be undone.');">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
