<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$messages = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Contact Messages';
$activeNav = 'contacts';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Messages sent from the public Contact Us page. Each submission is stored here and emailed to every active CMS admin in <code>admin_users</code>.</p>

<table class="admin-table">
  <thead>
    <tr>
      <th>Received</th>
      <th>Name</th>
      <th>Email</th>
      <th>Company</th>
      <th>Message</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($messages as $m): ?>
    <tr>
      <td data-order="<?= e($m['created_at']) ?>"><?= e(date('M j, Y H:i', strtotime($m['created_at']))) ?></td>
      <td><?= e($m['name']) ?></td>
      <td><?= e($m['email']) ?></td>
      <td><?= e($m['company'] ?: '—') ?></td>
      <td><?php
        $plain = trim(preg_replace('/\s+/', ' ', (string)$m['message']));
        echo e(function_exists('mb_strimwidth') ? mb_strimwidth($plain, 0, 80, '…') : (strlen($plain) > 80 ? substr($plain, 0, 77) . '…' : $plain));
      ?></td>
      <td>
        <?= !empty($m['is_read']) ? '<span class="badge-ok">Read</span>' : '<span class="badge-off">New</span>' ?>
        <?= !empty($m['emailed_at']) ? '<span class="badge-ok">Emailed</span>' : '<span class="badge-off">Not emailed</span>' ?>
      </td>
      <td class="row-actions">
        <a href="contact_view.php?id=<?= (int)$m['id'] ?>">View</a>
        <form method="post" action="contact_delete.php" onsubmit="return confirm('Delete this contact message?');">
          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <button type="submit" class="link-danger">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/partials_footer.php'; ?>
