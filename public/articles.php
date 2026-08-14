<?php
require_once __DIR__ . '/../includes/auth_user.php';

$q     = trim($_GET['q'] ?? '');
$topic = trim($_GET['topic'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$result = paginateArticles($q, $page, 12, $topic);

if (isset($_GET['partial'])) {
    header('Content-Type: application/json; charset=utf-8');
    ob_start();
    foreach ($result['rows'] as $a) {
        include __DIR__ . '/partials_article_card.php';
    }
    $html = ob_get_clean();
    echo json_encode([
        'html'    => $html,
        'page'    => $result['page'],
        'hasMore' => $result['page'] < $result['totalPages'],
        'total'   => $result['total'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$topics         = getArticleTopics();
$followedTopics = currentUser() ? getUserSubscribedTopics((int)currentUser()['id']) : [];
$hasMore        = $result['page'] < $result['totalPages'];

$pageTitle = 'All Articles';
$pageDescription = 'Every lighting parameter, standard breakdown, and design guide in one place.';
$pageCanonical = publicSiteUrl('articles');
include __DIR__ . '/partials_header.php';
?>

<div class="wrap hero" style="padding-bottom:0;">
  <p class="eyebrow">SC Lighting Standards</p>
  <h1 class="headline">All <span style="color:var(--sc-red);">Articles</span></h1>
  <p class="sub">Every lighting parameter, standard breakdown, and design guide in one place — searchable, and growing. Follow a topic's bell to get emailed when a new one on it goes live.</p>
  <div class="divider"></div>
</div>

<div class="wrap section" style="border-top:none;">

  <div class="search-panel">
    <form method="get" action="articles" class="search-panel-form">
      <?php if ($topic !== ''): ?><input type="hidden" name="topic" value="<?= e($topic) ?>"><?php endif; ?>
      <div class="search-input-pill">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search articles by keyword…" autofocus>
        <?php if ($q !== ''): ?><a class="search-clear-x" href="<?= e('articles' . ($topic !== '' ? '?topic=' . urlencode($topic) : '')) ?>" title="Clear search">✕</a><?php endif; ?>
      </div>
      <button type="submit" class="search-submit-btn">Search</button>
    </form>

    <?php
    $basePath = 'articles';
    include __DIR__ . '/partials_topic_chips.php';
    ?>
  </div>

  <?php if ($q !== '' || $topic !== ''): ?>
    <p class="search-meta">
      <?= (int)$result['total'] ?> result<?= $result['total'] === 1 ? '' : 's' ?>
      <?= $q !== '' ? ' for "' . e($q) . '"' : '' ?>
      <?= $topic !== '' ? ' in <strong>' . e($topic) . '</strong>' : '' ?>
      <a class="clear-search" href="articles">Clear all filters</a>
    </p>
  <?php endif; ?>

  <div class="article-grid" id="articlesGrid">
    <?php foreach ($result['rows'] as $a): include __DIR__ . '/partials_article_card.php'; endforeach; ?>
    <?php if (!$result['rows']): ?><p class="empty-note">No articles matched. <a href="articles">Clear the search</a> to see everything.</p><?php endif; ?>
  </div>

  <?php if ($result['rows'] && $hasMore): ?>
  <div class="infinite-status" id="articlesSentinel"
       data-page="<?= (int)$result['page'] ?>"
       data-has-more="1"
       data-q="<?= e($q) ?>"
       data-topic="<?= e($topic) ?>">
    <div class="infinite-spinner" aria-hidden="true"></div>
    <p>Loading more articles…</p>
  </div>
  <?php elseif ($result['rows']): ?>
  <p class="infinite-status infinite-end">You've reached the end.</p>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/partials_footer.php'; ?>
