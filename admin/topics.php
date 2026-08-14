<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reject_reason'] ?? '');
    $admin  = currentAdmin();

    if ($id && in_array($action, ['approve', 'reject'], true)) {
        $stmt = db()->prepare('UPDATE discussion_topics SET status=?, reject_reason=?, decided_by=?, decided_at=NOW() WHERE id=?');
        $decidedStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt->execute([
            $decidedStatus,
            $action === 'reject' ? $reason : null,
            $admin['username'] . ' (High Board)',
            $id,
        ]);
        notifyTopicDecision($id, $decidedStatus, $reason ?: null);
    }
    redirect('topics.php');
}

$status = $_GET['status'] ?? '';
$where = '';
$params = [];
if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where = 'WHERE t.status = ?';
    $params[] = $status;
}
$stmt = db()->prepare("SELECT t.*, u.name AS author_name FROM discussion_topics t JOIN users u ON u.id = t.user_id $where ORDER BY t.created_at DESC");
$stmt->execute($params);
$topics = $stmt->fetchAll();

$pendingCount = (int)db()->query("SELECT COUNT(*) FROM discussion_topics WHERE status = 'pending'")->fetchColumn();

$pageTitle = 'Community Topics';
$activeNav = 'topics';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">User-submitted community topics. Accept, reject, edit, or delete — full control regardless of what leaders can do on the public site.</p>

<div class="toolbar" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
  <div class="topic-chip-row" style="margin:0;">
    <a href="topics" class="pill <?= $status === '' ? 'badge-ok' : '' ?>">All</a>
    <a href="topics?status=pending" class="pill <?= $status === 'pending' ? 'badge-ok' : '' ?>">Pending (<?= $pendingCount ?>)</a>
    <a href="topics?status=approved" class="pill <?= $status === 'approved' ? 'badge-ok' : '' ?>">Approved</a>
    <a href="topics?status=rejected" class="pill <?= $status === 'rejected' ? 'badge-ok' : '' ?>">Rejected</a>
  </div>
  <a href="topic/new" class="btn-primary">+ New Topic</a>
</div>

<table class="admin-table">
  <thead>
    <tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($topics as $t): ?>
    <tr>
      <td><?= e($t['title']) ?></td>
      <td><?= e($t['author_name']) ?></td>
      <td><span class="pill"><?= e($t['category']) ?></span></td>
      <td>
        <?php if ($t['status'] === 'approved'): ?><span class="badge-ok">Approved</span>
        <?php elseif ($t['status'] === 'rejected'): ?><span class="badge-off">Rejected</span>
        <?php else: ?><span class="badge-off" style="color:#b8860b;">Pending</span>
        <?php endif; ?>
      </td>
      <td><?= e(date('M j, Y', strtotime($t['created_at']))) ?></td>
      <td class="row-actions">
        <a href="topic/<?= (int)$t['id'] ?>">Edit</a>
        <?php if ($t['status'] !== 'approved'): ?>
        <form method="post" style="display:inline;">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="link-danger" style="color:#2ea34f;">Accept</button>
        </form>
        <?php endif; ?>
        <?php if ($t['status'] !== 'rejected'): ?>
        <form method="post" style="display:inline;" onsubmit="return confirm('Reject this topic?');">
          <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="hidden" name="action" value="reject">
          <button type="submit" class="link-danger">Reject</button>
        </form>
        <?php endif; ?>
        <form method="post" action="topic_delete.php" onsubmit="return confirm('Delete this topic? This cannot be undone.');" style="display:inline;">
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
