<?php
require_once __DIR__ . '/../includes/auth_user.php';

$category = trim($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = "status = 'approved'";
$params = [];
if ($category !== '') {
    $where .= ' AND category = ?';
    $params[] = $category;
}

$stmt = db()->prepare(
    "SELECT t.*, u.name AS author_name FROM discussion_topics t
     JOIN users u ON u.id = t.user_id
     WHERE $where ORDER BY t.created_at DESC LIMIT ? OFFSET ?"
);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i++, $offset, PDO::PARAM_INT);
$stmt->execute();
$topics = $stmt->fetchAll();

$countStmt = db()->prepare("SELECT COUNT(*) FROM discussion_topics WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$categories = getDiscussionCategories();

$pageTitle = $category !== '' ? $category . ' Lighting Topics' : 'Lighting Community Topics';
$pageDescription = 'Community lighting topics from engineers, consultants, and educators — discussed and moderated on the Short Circuit blog.';
$pageCanonical = $category !== ''
    ? publicSiteUrl('topics?category=' . rawurlencode($category))
    : publicSiteUrl('topics');
$pageRobots = $page > 1 ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$pageJsonLd = [[
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'url' => $pageCanonical,
    'description' => $pageDescription,
    'isPartOf' => ['@id' => publicSiteUrl() . '#website'],
    'inLanguage' => 'en',
]];
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section" style="border-top:none;">
  <p class="eyebrow">Community</p>
  <h2 class="section-title">Topics</h2>
  <p class="section-sub">Questions and discussions posted by Short Circuit clients, employees, and team leaders — reviewed before they go live.</p>

  <?php if ($categories): ?>
  <div class="topic-chip-row" style="margin-bottom:var(--space-lg);">
    <a href="topics.php" class="topic-chip<?= $category === '' ? ' active' : '' ?>">All</a>
    <?php foreach ($categories as $c): ?>
      <a href="topics.php?category=<?= urlencode($c) ?>" class="topic-chip<?= $category === $c ? ' active' : '' ?>"><?= e($c) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$topics): ?>
    <p class="hint">No topics yet<?= $category ? ' in ' . e($category) : '' ?>. <?= currentUser() ? '<a href="topic_new.php">Be the first to post one.</a>' : '<a href="login.php">Log in</a> to post one.' ?></p>
  <?php else: ?>
  <div class="my-topics-list">
    <?php foreach ($topics as $t): ?>
      <a class="topic-list-item" href="topic.php?slug=<?= urlencode($t['slug']) ?>">
        <span class="pill"><?= e($t['category']) ?></span>
        <span class="topic-list-title"><?= e($t['title']) ?></span>
        <span class="hint">by <?= e($t['author_name']) ?> · <?= e(date('M j, Y', strtotime($t['created_at']))) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?= renderPagination('topics.php', $category !== '' ? ['category' => $category] : [], $page, $totalPages) ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
