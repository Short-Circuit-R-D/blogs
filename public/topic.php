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

require_once __DIR__ . '/../includes/seo.php';
$pageTitle = $topic['title'];
$plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$topic['body'])));
$pageDescription = function_exists('mb_substr') ? mb_substr($plain, 0, 180) : substr($plain, 0, 180);
$pageOgType = 'article';
$pageCanonical = topicPermalink($topic['slug']);
if ($topic['status'] !== 'approved') {
    $pageRobots = 'noindex, nofollow';
}
$pageJsonLd = [
    seoBreadcrumbList([
        ['name' => 'Home', 'url' => publicSiteUrl()],
        ['name' => 'Community', 'url' => publicSiteUrl('topics')],
        ['name' => $topic['title'], 'url' => $pageCanonical],
    ]),
    [
        '@type' => 'DiscussionForumPosting',
        'headline' => $topic['title'],
        'text' => $pageDescription,
        'datePublished' => date('c', strtotime($topic['created_at'] ?? 'now')),
        'dateModified' => date('c', strtotime($topic['updated_at'] ?? $topic['created_at'] ?? 'now')),
        'url' => $pageCanonical,
        'author' => [
            '@type' => 'Person',
            'name' => $topic['author_name'] ?: 'Short Circuit community',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Short Circuit Company',
            'url' => publicSiteUrl(),
        ],
        'inLanguage' => 'en',
    ],
];
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
