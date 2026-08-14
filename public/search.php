<?php
require_once __DIR__ . '/../includes/auth_user.php';

$q     = trim($_GET['q'] ?? '');
$type  = ($_GET['type'] ?? 'articles') === 'fixtures' ? 'fixtures' : 'articles';
$topic = trim($_GET['topic'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));

$articleResults = null;
$fixtureResults = null;

if ($type === 'articles') {
    $articleResults = paginateArticles($q, $page, 12, $topic);
} else {
    $fixtureResults = searchFixtures($q);
}

$topics         = getArticleTopics();
$followedTopics = currentUser() ? getUserSubscribedTopics((int)currentUser()['id']) : [];

$pageTitle = $q !== '' ? 'Search: ' . $q : 'Search';
include __DIR__ . '/partials_header.php';
?>

<div class="wrap hero" style="padding-bottom:0;">
  <p class="eyebrow">Search</p>
  <h1 class="headline">Find A <span style="color:var(--sc-red);">Guide or Fixture</span></h1>
  <div class="divider"></div>
</div>

<div class="wrap section" style="border-top:none;">
  <div class="search-tabs">
    <a class="search-tab<?= $type === 'articles' ? ' active' : '' ?>" href="?type=articles<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">Articles</a>
    <a class="search-tab<?= $type === 'fixtures' ? ' active' : '' ?>" href="?type=fixtures<?= $q !== '' ? '&q=' . urlencode($q) : '' ?>">Fixtures <span class="badge-soon">Coming Soon</span></a>
  </div>

  <div class="search-panel">
    <form method="get" class="search-panel-form">
      <input type="hidden" name="type" value="<?= e($type) ?>">
      <?php if ($topic !== ''): ?><input type="hidden" name="topic" value="<?= e($topic) ?>"><?php endif; ?>
      <div class="search-input-pill">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= $type === 'fixtures' ? 'Search fixtures by name, SKU, or spec…' : 'Search articles by keyword…' ?>" autofocus>
        <?php if ($q !== ''): ?><a class="search-clear-x" href="?type=<?= e($type) ?><?= $topic !== '' ? '&topic=' . urlencode($topic) : '' ?>" title="Clear search">✕</a><?php endif; ?>
      </div>
      <button type="submit" class="search-submit-btn">Search</button>
    </form>

    <?php if ($type === 'articles'):
      $basePath = 'search.php';
      $extraChipParams = ['type' => 'articles'];
      include __DIR__ . '/partials_topic_chips.php';
    endif; ?>
  </div>

  <?php if ($type === 'articles'): ?>
    <?php if ($q !== '' || $topic !== ''): ?>
      <p class="search-meta">
        <?= (int)$articleResults['total'] ?> result<?= $articleResults['total'] === 1 ? '' : 's' ?>
        <?= $q !== '' ? ' for "' . e($q) . '"' : '' ?>
        <?= $topic !== '' ? ' in <strong>' . e($topic) . '</strong>' : '' ?>
      </p>
    <?php endif; ?>
    <div class="article-grid">
      <?php foreach ($articleResults['rows'] as $a): include __DIR__ . '/partials_article_card.php'; endforeach; ?>
      <?php if (!$articleResults['rows']): ?><p class="empty-note">No articles matched. Try a different keyword.</p><?php endif; ?>
    </div>
    <?= renderPagination('search.php', ['q' => $q, 'type' => 'articles', 'topic' => $topic], $articleResults['page'], $articleResults['totalPages']) ?>
  <?php else: ?>
    <div class="fixtures-stub">
      <p class="section-label">Fixtures Search</p>
      <p><?= e($fixtureResults['message']) ?></p>
      <p class="fixtures-stub-note">Once the Short Circuit fixtures API is connected, results will appear here with the same search box — no UI changes needed on your end.</p>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/partials_footer.php'; ?>
