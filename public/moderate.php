<?php
require_once __DIR__ . '/../includes/auth_user.php';

$me = requirePermission('can_moderate_topics');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $reason = trim($_POST['reject_reason'] ?? '');

    if ($id && in_array($action, ['approve', 'reject'], true)) {
        $stmt = db()->prepare(
            'UPDATE discussion_topics SET status = ?, reject_reason = ?, decided_by = ?, decided_at = NOW() WHERE id = ?'
        );
        $decidedStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt->execute([
            $decidedStatus,
            $action === 'reject' ? $reason : null,
            $me['name'] . ' (' . roleLabel($me['role']) . ')',
            $id,
        ]);
        notifyTopicDecision($id, $decidedStatus, $reason ?: null);
    }
    redirect('moderate.php');
}

$pending = db()->query(
    "SELECT t.*, u.name AS author_name, u.email AS author_email FROM discussion_topics t
     JOIN users u ON u.id = t.user_id
     WHERE t.status = 'pending' ORDER BY t.created_at ASC"
)->fetchAll();

$pageTitle = 'Moderate Topics';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section" style="border-top:none;">
  <p class="eyebrow">Community · Moderation</p>
  <h2 class="section-title">Pending Topics</h2>
  <p class="section-sub">Signed in as <?= e($me['name']) ?> (<?= e(roleLabel($me['role'])) ?>). Approve or reject topics submitted by clients and employees before they go public.</p>

  <?php if (!$pending): ?>
    <p class="hint">Nothing waiting for review right now.</p>
  <?php endif; ?>

  <?php foreach ($pending as $t): ?>
  <div class="mod-card">
    <div class="mod-card-head">
      <span class="pill"><?= e($t['category']) ?></span>
      <span class="hint">by <?= e($t['author_name']) ?> (<?= e($t['author_email']) ?>) · <?= e(date('M j, Y g:ia', strtotime($t['created_at']))) ?></span>
    </div>
    <h3><?= e($t['title']) ?></h3>
    <p><?= nl2br(e($t['body'])) ?></p>
    <div class="mod-card-actions">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <input type="hidden" name="action" value="approve">
        <button type="submit" class="btn-primary">Accept</button>
      </form>
      <form method="post" class="mod-reject-form">
        <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <input type="hidden" name="action" value="reject">
        <input type="text" name="reject_reason" placeholder="Reason (optional, shown to the author)">
        <button type="submit" class="btn-secondary">Reject</button>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
