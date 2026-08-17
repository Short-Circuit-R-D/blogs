<?php
require_once __DIR__ . '/../includes/auth_user.php';

$articles  = db()->query('SELECT * FROM articles WHERE is_published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$standards = db()->query('SELECT * FROM standards WHERE is_published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$terms     = db()->query('SELECT * FROM standard_terms WHERE is_published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$tools     = db()->query('SELECT * FROM tools WHERE is_published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();

$events = db()->query('SELECT * FROM events WHERE is_published = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$eventImages = [];
if ($events) {
    $ids = array_column($events, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM event_images WHERE event_id IN ($in) ORDER BY sort_order ASC, id ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $img) {
        $eventImages[$img['event_id']][] = $img;
    }
}

$pageTitle = 'Lighting Technical Data & Standards';
$pageDescription = 'Lighting technical data, CRI, CCT, lux, UGR, EN 12464-1, and design guides from Short Circuit Company.';
$pageCanonical = publicSiteUrl();
$pageJsonLd = [[
    '@type' => 'WebPage',
    '@id' => publicSiteUrl() . '#webpage',
    'url' => publicSiteUrl(),
    'name' => 'Lighting Technical Data & Standards Blog',
    'description' => $pageDescription,
    'isPartOf' => ['@id' => publicSiteUrl() . '#website'],
    'about' => ['@id' => publicSiteUrl() . '#organization'],
    'inLanguage' => 'en',
]];
include __DIR__ . '/partials_header.php';
?>

<div class="wrap hero">
  <p class="eyebrow">SC Lighting Standards</p>
  <h1 class="headline">Lighting <span style="color:var(--sc-red);">Technical Data</span></h1>
  <p class="sub">A field guide to the parameters that define lighting quality — colour, brightness, comfort and circadian health — the standards behind them, and the tools used to design for them.</p>
  <div class="hero-actions">
    <?php if (currentUser()): ?>
      <a class="hero-cta" href="account">Manage your subscription</a>
    <?php else: ?>
      <a class="hero-cta" href="subscribe">Subscribe to the Blog</a>
    <?php endif; ?>
  </div>
  <div class="divider"></div>
</div>

<div class="wrap section" style="border-top:none;padding-top:0;">
  <p class="section-label">Topics</p>
  <div class="section-title-row">
    <h2 class="section-title">Lighting Parameters &amp; Guides</h2>
    <a class="view-all-link" href="articles">View all articles →</a>
  </div>
  <p class="section-sub">Open any topic for its full definition, why it matters, and recommended ranges — then jump to the full live simulator to test it.</p>
  <div class="article-grid">
    <?php foreach (array_slice($articles, 0, 6) as $a): include __DIR__ . '/partials_article_card.php'; endforeach; ?>
    <?php if (!$articles): ?><p class="empty-note">No articles published yet.</p><?php endif; ?>
  </div>
  <?php if (count($articles) > 6): ?><div class="view-all-row"><a class="read-more" href="articles">View All <?= count($articles) ?> Articles →</a></div><?php endif; ?>
</div>

<div class="wrap section">
  <p class="section-label">Codes &amp; Standards</p>
  <h2 class="section-title">Lighting Standards</h2>
  <p class="section-sub">The codes behind the recommended ranges throughout this reference.</p>
  <div class="standards-grid">
    <?php foreach ($standards as $s): ?>
    <div class="standard-card">
      <p class="code"><?= e($s['code']) ?></p>
      <?php if ($s['region']): ?><p class="region"><?= e($s['region']) ?></p><?php endif; ?>
      <p><?= e($s['name']) ?> — <?= e($s['description']) ?></p>
      <?php if ($s['official_url']): ?><a class="standard-link" href="<?= e($s['official_url']) ?>" target="_blank" rel="noopener">View the standard →</a><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php if (!$standards): ?><p class="empty-note">No standards published yet.</p><?php endif; ?>
  </div>

  <?php if ($terms): ?>
  <p class="section-label" style="margin-top:var(--space-xl);">Cross-Reference</p>
  <h2 class="section-title" style="font-size:24px;">Terminology Across Global Standards</h2>
  <p class="section-sub">The same parameter, named differently by each framework — use this to translate a spec sheet from one region to another.</p>
  <div class="terms-table-wrap">
    <table class="terms-table">
      <thead><tr><th>Parameter</th><th>EN 12464-1</th><th>ISO 8995-1 / CIE S 008</th><th>ANSI / IES</th><th>WELL v2</th></tr></thead>
      <tbody>
        <?php foreach ($terms as $t): ?>
        <tr>
          <td><?= e($t['parameter']) ?></td>
          <td><?= e($t['en_12464'] ?? '') ?></td>
          <td><?= e($t['iso_8995'] ?? '') ?></td>
          <td><?= e($t['ansi_ies'] ?? '') ?></td>
          <td><?= e($t['well_v2'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="wrap section">
  <p class="section-label">Explore More</p>
  <h2 class="section-title">Lighting Design Tools</h2>
  <p class="section-sub">The tools that put these parameters and standards into practice — ours and the wider industry's.</p>
  <?php if ($tools): ?>
  <div id="toolsCarousel">
    <div class="carousel">
      <div class="carousel-track">
        <?php foreach ($tools as $t): ?>
        <div class="tool-slide">
          <div class="tool-icon"><?= mediaTag($t['image_url'] ?? null, $t['icon'], $t['name'], 'tool-icon-img') ?></div>
          <div>
            <p class="tool-kind"><?= $t['is_external'] ? 'Industry Tool' : 'SC Tool' ?></p>
            <h3><?= e($t['name']) ?></h3>
            <p><?= e($t['description']) ?></p>
            <?php if ($t['url']): ?><a class="tool-link" href="<?= e($t['url']) ?>" target="_blank" rel="noopener">Visit →</a><?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="carousel-nav">
      <div class="dots"></div>
      <div class="carousel-arrows">
        <button class="arrow-btn prev-slide">‹</button>
        <button class="arrow-btn next-slide">›</button>
      </div>
    </div>
  </div>
  <?php else: ?><p class="empty-note">No tools published yet.</p><?php endif; ?>
</div>

<div class="wrap section" id="events">
  <p class="section-label">On The Ground</p>
  <h2 class="section-title">Events &amp; Exhibitions</h2>
  <p class="section-sub">Short Circuit Company at LedEXPO and other lighting events.</p>
  <?php if ($events): ?>
  <div id="eventsCarousel">
    <div class="carousel">
      <div class="carousel-track">
        <?php foreach ($events as $ev): ?>
        <div class="event-slide">
          <h3><?= e($ev['name']) ?></h3>
          <?php if ($ev['year']): ?><p class="event-year"><?= e((string)$ev['year']) ?></p><?php endif; ?>
          <?php if ($ev['description']): ?><p><?= e($ev['description']) ?></p><?php endif; ?>
          <div class="card-meta"><?php renderViewCount((int)($ev['view_count'] ?? 0), true); ?></div>
          <?php renderShareBar(eventPermalink((int)$ev['id']), $ev['name'], (string)($ev['description'] ?? ''), true, 'event', (string)$ev['id']); ?>
          <a class="read-more" href="<?= e(eventUrl((int)$ev['id'])) ?>" style="margin:0 0 12px;">View event →</a>
          <div class="event-gallery">
            <?php if (!empty($eventImages[$ev['id']])): foreach ($eventImages[$ev['id']] as $img): ?>
              <img src="<?= e(uploadUrl($img['image_path'])) ?>" alt="<?= e($img['caption'] ?? $ev['name']) ?>">
            <?php endforeach; else: ?>
              <div class="no-photos">Photos coming soon</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="carousel-nav">
      <div class="dots"></div>
      <div class="carousel-arrows">
        <button class="arrow-btn prev-slide">‹</button>
        <button class="arrow-btn next-slide">›</button>
      </div>
    </div>
  </div>
  <?php else: ?><p class="empty-note">No events published yet.</p><?php endif; ?>
</div>

<?php include __DIR__ . '/partials_footer.php'; ?>
