<?php
require_once __DIR__ . '/../includes/auth_user.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') redirect('topics.php');

$stmt = db()->prepare(
    'SELECT t.*, u.name AS author_name FROM discussion_topics t
     JOIN users u ON u.id = t.user_id WHERE t.slug = ?'
);
$stmt->execute([$slug]);
$topic = $stmt->fetch();
if (!$topic) redirect('topics.php');

$me = currentUser();
$isOwner = $me && (int)$me['id'] === (int)$topic['user_id'];

// Only the author or a moderator/admin may view a topic that isn't approved yet.
if ($topic['status'] !== 'approved' && !$isOwner) {
    $full = $me ? currentUserFull() : null;
    if (!$full || !roleCan($full['role'], 'can_moderate_topics')) {
        http_response_code(404);
        die('Topic not found.');
    }
}

$pageTitle = $topic['title'];
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section" style="border-top:none;max-width:760px;">
  <a href="topics.php" class="back-btn">← Back to Community Topics</a>
  <div class="article-head">
    <span class="pill"><?= e($topic['category']) ?></span>
    <?php if ($topic['status'] !== 'approved'): ?>
      <span class="<?= $topic['status'] === 'pending' ? 'badge-pending' : 'badge-off' ?>"><?= e(ucfirst($topic['status'])) ?></span>
    <?php endif; ?>
  </div>
  <h1 class="headline"><?= e($topic['title']) ?></h1>
  <p class="hint">by <?= e($topic['author_name']) ?> · <?= e(date('M j, Y', strtotime($topic['created_at']))) ?></p>
  <div class="divider"></div>
  <div class="article-intro"><?= nl2br(e($topic['body'])) ?></div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
