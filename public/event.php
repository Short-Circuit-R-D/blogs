<?php
require_once __DIR__ . '/../includes/auth_user.php';

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    redirect('index.php');
}

$stmt = db()->prepare('SELECT * FROM events WHERE id = ? AND is_published = 1');
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) {
    redirect('index.php');
}

$stmt = db()->prepare('SELECT * FROM event_images WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$id]);
$images = $stmt->fetchAll();

require_once __DIR__ . '/../includes/seo.php';

$pageTitle = $event['name'] . ($event['year'] ? ' (' . $event['year'] . ')' : '');
$pageDescription = $event['description'] ?: ('Short Circuit Company at ' . $event['name'] . '.');
$pageCanonical = eventPermalink((int)$event['id']);
$pageOgType = 'article';
$pageOgImage = defaultOgImageUrl();
if ($images) {
    $pageOgImage = mediaAbsUrl($images[0]['image_path']);
}
$pageJsonLd = [
    seoBreadcrumbList([
        ['name' => 'Home', 'url' => publicSiteUrl()],
        ['name' => $event['name'], 'url' => $pageCanonical],
    ]),
    [
        '@type' => 'Event',
        'name' => $event['name'],
        'description' => $pageDescription,
        'url' => $pageCanonical,
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'organizer' => [
            '@type' => 'Organization',
            'name' => 'Short Circuit Company',
            'url' => publicSiteUrl(),
        ],
        'image' => $pageOgImage ?? defaultOgImageUrl(),
    ],
];
if (!empty($event['year'])) {
    $pageJsonLd[1]['startDate'] = $event['year'] . '-01-01';
}
include __DIR__ . '/partials_header.php';
?>

<div class="wrap section" style="border-top:none;padding-top:var(--space-xl);">
  <a class="back-btn" href="<?= e(appUrl()) ?>#events">← Back to events</a>
  <p class="eyebrow">Event<?= !empty($event['year']) ? ' — ' . e((string)$event['year']) : '' ?></p>
  <h1 class="headline" style="font-size:42px;"><?= e($event['name']) ?></h1>
  <?php if ($event['description']): ?>
    <p class="sub" style="max-width:680px;"><?= e($event['description']) ?></p>
  <?php endif; ?>

  <?php renderShareBar(eventPermalink((int)$event['id']), $event['name'], (string)($event['description'] ?? '')); ?>

  <div class="event-gallery" style="margin-top:var(--space-lg);">
    <?php if ($images): foreach ($images as $img): ?>
      <img src="<?= e(uploadUrl($img['image_path'])) ?>" alt="<?= e($img['caption'] ?? $event['name']) ?>">
    <?php endforeach; else: ?>
      <div class="no-photos">Photos coming soon</div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials_footer.php'; ?>
