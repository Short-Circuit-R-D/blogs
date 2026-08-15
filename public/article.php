<?php
require_once __DIR__ . '/../includes/auth_user.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') redirect('index.php');

$stmt = db()->prepare('SELECT * FROM articles WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$article = $stmt->fetch();
if (!$article) redirect('index.php');

$stmt = db()->prepare('SELECT * FROM article_ranges WHERE article_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$article['id']]);
$ranges = $stmt->fetchAll();

// Prev/next by the same ordering used on the listing pages, for quick reading flow.
$stmt = db()->prepare('SELECT slug, title FROM articles WHERE is_published = 1 AND (sort_order, id) < (?, ?) ORDER BY sort_order DESC, id DESC LIMIT 1');
$stmt->execute([$article['sort_order'], $article['id']]);
$prevArticle = $stmt->fetch();
$stmt = db()->prepare('SELECT slug, title FROM articles WHERE is_published = 1 AND (sort_order, id) > (?, ?) ORDER BY sort_order ASC, id ASC LIMIT 1');
$stmt->execute([$article['sort_order'], $article['id']]);
$nextArticle = $stmt->fetch();

$hasCover = !empty($article['image_url']);
$commentError = null;
$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
    userCsrfCheck();
    if (!$me) {
        $_SESSION['post_login_redirect'] = articleUrl($article['slug']);
        redirect('login');
    }
    $body = trim($_POST['body'] ?? '');
    if ($body === '') {
        $commentError = 'Write something before posting.';
    } elseif (!addArticleComment((int)$article['id'], (int)$me['id'], $body)) {
        $commentError = 'That comment is empty or too long (max 4000 characters).';
    } else {
        redirect(articleUrl($article['slug']) . '#discussion');
    }
}

$comments = getArticleComments((int)$article['id']);
require_once __DIR__ . '/../includes/seo.php';

$pageTitle = $article['title'];
$pageDescription = $article['excerpt'] ?: seoDefaultDescription();
$pageOgType = 'article';
$pageCanonical = articlePermalink($article['slug']);
$pageOgImage = !empty($article['image_url']) ? mediaAbsUrl($article['image_url']) : defaultOgImageUrl();
$pageJsonLd = [
    seoBreadcrumbList([
        ['name' => 'Home', 'url' => publicSiteUrl()],
        ['name' => 'Articles', 'url' => publicSiteUrl('articles')],
        ['name' => $article['title'], 'url' => $pageCanonical],
    ]),
    [
        '@type' => 'Article',
        'headline' => $article['title'],
        'description' => $pageDescription,
        'image' => $pageOgImage,
        'datePublished' => date('c', strtotime($article['created_at'] ?? 'now')),
        'dateModified' => date('c', strtotime($article['updated_at'] ?? $article['created_at'] ?? 'now')),
        'mainEntityOfPage' => $pageCanonical,
        'author' => [
            '@type' => 'Organization',
            'name' => 'Short Circuit Company',
            'url' => publicSiteUrl(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Short Circuit Company',
            'logo' => ['@type' => 'ImageObject', 'url' => defaultLogoUrl()],
        ],
        'articleSection' => $article['tag'] ?: 'Lighting',
        'inLanguage' => 'en',
    ],
];
include __DIR__ . '/partials_header.php';
?>

<?php if ($hasCover): ?>
<div class="article-hero-cover" style="background-image:url('<?= e(mediaSrc($article['image_url'])) ?>');">
  <div class="article-hero-overlay">
    <div class="wrap">
      <a class="back-btn back-btn-light" href="articles">← Back to all articles</a>
      <p class="eyebrow">Article — <?= e($article['tag']) ?></p>
      <h1 class="article-hero-title"><?= e($article['title']) ?></h1>
    </div>
  </div>
</div>
<?php else: ?>
<div class="article-hero-typographic">
  <div class="wrap">
    <a class="back-btn back-btn-light" href="articles">← Back to all articles</a>
    <p class="eyebrow">Article — <?= e($article['tag']) ?></p>
    <h1 class="article-hero-title"><?= e($article['title']) ?></h1>
    <span class="article-hero-glyph"><?= iconSvg($article['icon']) ?></span>
  </div>
</div>
<?php endif; ?>

<div class="wrap section" style="border-top:none;">
  <?php renderShareBar(articlePermalink($article['slug']), $article['title'], (string)$article['excerpt']); ?>

  <p class="article-intro"><?= nl2br(e($article['intro'])) ?></p>

  <?php if ($article['tag']):
    $followingThis = currentUser() && isSubscribedToTopic((int)currentUser()['id'], $article['tag']);
    $followReturn  = articleUrl($article['slug']);
  ?>
  <form method="post" action="topic_subscribe.php" class="topic-follow-banner">
    <input type="hidden" name="topic" value="<?= e($article['tag']) ?>">
    <input type="hidden" name="return" value="<?= e($followReturn) ?>">
    <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3C9.79 3 8 4.79 8 7v3.28c0 .57-.2 1.12-.57 1.55L6 13.5c-.9 1.05-.16 2.68 1.22 2.68h9.56c1.38 0 2.12-1.63 1.22-2.68l-1.43-1.67A2.4 2.4 0 0 1 16 10.28V7c0-2.21-1.79-4-4-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9.5 18a2.5 2.5 0 0 0 5 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    <span><?= $followingThis ? 'You\'re following <strong>' . e($article['tag']) . '</strong> — new articles on this topic land in your inbox.' : 'Get emailed when a new <strong>' . e($article['tag']) . '</strong> article is published.' ?></span>
    <button type="submit"><?= $followingThis ? 'Following ✓' : 'Follow Topic' ?></button>
  </form>
  <?php endif; ?>

  <?php if ($article['why_text']): ?>
  <div class="article-why"><p><?= nl2br(e($article['why_text'])) ?></p></div>
  <?php endif; ?>

  <?php
    $sciTabs = [];
    if (!empty($article['physical_text'])) $sciTabs['physical'] = ['label' => 'Physical Mechanism', 'text' => $article['physical_text']];
    if (!empty($article['physio_text']))   $sciTabs['physio']   = ['label' => 'Physiological Impact', 'text' => $article['physio_text']];
    if (!empty($article['psycho_text']))   $sciTabs['psycho']   = ['label' => 'Psychological Impact', 'text' => $article['psycho_text']];
  ?>
  <?php if ($sciTabs): ?>
  <div class="science-block">
    <p class="section-label">The Science</p>
    <div class="science-tabs" role="tablist">
      <?php foreach ($sciTabs as $key => $tab): ?>
      <button type="button" class="sci-tab<?= $key === array_key_first($sciTabs) ? ' active' : '' ?>" data-sci-tab="<?= e($key) ?>"><?= e($tab['label']) ?></button>
      <?php endforeach; ?>
    </div>
    <?php if ($article['formula_text']): ?>
    <div class="formula-box">
      <code><?= e($article['formula_text']) ?></code>
      <?php if ($article['formula_note']): ?><p><?= e($article['formula_note']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php foreach ($sciTabs as $key => $tab): ?>
    <div class="sci-panel<?= $key === array_key_first($sciTabs) ? ' active' : '' ?>" data-sci-panel="<?= e($key) ?>">
      <p><?= nl2br(e($tab['text'])) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($ranges): ?>
  <p class="section-label">Recommended Ranges</p>
  <table class="spec-table">
    <thead><tr><th>Stage</th><th>Environment</th><th>Recommended Range</th><th>Notes</th></tr></thead>
    <tbody>
      <?php foreach ($ranges as $r): ?>
      <tr>
        <td><?= e($r['stage_label']) ?></td>
        <td><?= e($r['environment_label']) ?></td>
        <td><?= e($r['range_text']) ?></td>
        <td><?= e($r['notes'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if ($article['simulator_url']): ?>
  <div class="sim-cta">
    <h4>Test It Live</h4>
    <p>See this parameter in action, across every stage and environment, in the full interactive simulator.</p>
    <a href="<?= e($article['simulator_url']) ?>" target="_blank" rel="noopener"><?= e($article['simulator_label'] ?: 'Open the full live simulator') ?> →</a>
  </div>
  <?php endif; ?>

  <?php if ($prevArticle || $nextArticle): ?>
  <div class="article-nav">
    <?php if ($prevArticle): ?>
      <a class="article-nav-link prev" href="<?= e(articleUrl($prevArticle['slug'])) ?>"><span>← Previous</span><strong><?= e($prevArticle['title']) ?></strong></a>
    <?php else: ?><span></span><?php endif; ?>
    <?php if ($nextArticle): ?>
      <a class="article-nav-link next" href="<?= e(articleUrl($nextArticle['slug'])) ?>"><span>Next →</span><strong><?= e($nextArticle['title']) ?></strong></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <section class="discussion" id="discussion">
    <p class="section-label">Discussion</p>
    <h2 class="section-title" style="font-size:24px;">Join the conversation</h2>
    <p class="section-sub"><?= count($comments) ?> comment<?= count($comments) === 1 ? '' : 's' ?> on this guide.</p>

    <?php if ($comments): ?>
      <div class="comment-list">
        <?php foreach ($comments as $c): ?>
        <article class="comment">
          <p class="comment-meta">
            <strong><?= e($c['user_name'] ?: 'Reader') ?></strong>
            · <?= e(date('M j, Y g:ia', strtotime($c['created_at']))) ?>
          </p>
          <p><?= nl2br(e($c['body'])) ?></p>
        </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty-note">No comments yet — start the discussion.</p>
    <?php endif; ?>

    <?php if ($me): ?>
      <?php if ($commentError): ?><div class="alert" style="max-width:760px;margin-top:var(--space-md);"><?= e($commentError) ?></div><?php endif; ?>
      <form method="post" action="<?= e(articleUrl($article['slug'])) ?>" class="comment-form">
        <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
        <input type="hidden" name="action" value="comment">
        <label for="comment-body">Your comment</label>
        <textarea id="comment-body" name="body" maxlength="4000" required placeholder="Share a question, a field note, or a correction…"><?= isset($_POST['body']) ? e($_POST['body']) : '' ?></textarea>
        <button type="submit" class="auth-submit" style="width:auto;padding:10px 18px;">Post comment</button>
      </form>
    <?php else: ?>
      <div class="discussion-cta">
        <p>Log in or create an account to join the discussion.</p>
        <a class="hero-cta" href="subscribe?mode=signup">Create an account</a>
        <a class="btn-secondary" href="login">Log In</a>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php include __DIR__ . '/partials_footer.php'; ?>
