<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$events = db()->query('
  SELECT e.*, COUNT(i.id) AS image_count
  FROM events e
  LEFT JOIN event_images i ON i.event_id = e.id
  GROUP BY e.id
  ORDER BY e.sort_order ASC, e.id ASC
')->fetchAll();

$pageTitle = 'Events';
$activeNav = 'events';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Booth galleries shown in the events carousel on the public page (LedEXPO 1/2/3 and any future events). Add as many as you like.</p>

<div class="toolbar">
  <a href="event/new" class="btn-primary">+ New Event</a>
</div>

<table class="admin-table">
  <thead><tr><th>Order</th><th>Name</th><th>Year</th><th>Images</th><th>Views</th><th>Published</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($events as $ev): ?>
    <tr>
      <td><?= (int)$ev['sort_order'] ?></td>
      <td><?= e($ev['name']) ?></td>
      <td><?= e((string)$ev['year']) ?></td>
      <td><?= (int)$ev['image_count'] ?></td>
      <td><?= number_format((int)($ev['view_count'] ?? 0)) ?></td>
      <td><?= $ev['is_published'] ? '<span class="badge-ok">Live</span>' : '<span class="badge-off">Hidden</span>' ?></td>
      <td class="row-actions">
        <a href="event/<?= (int)$ev['id'] ?>">Edit</a>
        <form method="post" action="event_delete.php" onsubmit="return confirm('Delete this event and all its images?');">
          <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
