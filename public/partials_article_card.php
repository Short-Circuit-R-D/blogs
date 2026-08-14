<?php
/**
 * @var array $a  the article row (must have slug, tag, icon, image_url, title, excerpt)
 * Renders one card. Include this inside a foreach — it uses $a directly
 * so callers don't need to build a string and echo it.
 */
$hasCover = !empty($a['image_url']);
?>
<div class="article-card<?= $hasCover ? '' : ' no-cover' ?>">
  <?php if ($hasCover): ?>
    <div class="card-banner"><span class="card-tag"><?= e($a['tag']) ?></span><?= mediaTag($a['image_url'], $a['icon'], $a['title'], 'card-banner-img') ?></div>
  <?php else: ?>
    <div class="card-banner card-banner-typographic"><span class="card-tag"><?= e($a['tag']) ?></span><span class="card-banner-glyph"><?= iconSvg($a['icon']) ?></span></div>
  <?php endif; ?>
  <div class="card-body">
    <h3><?= e($a['title']) ?></h3>
    <p><?= e($a['excerpt']) ?></p>
    <a class="read-more" href="<?= e(articleUrl($a['slug'])) ?>">Read More →</a>
  </div>
</div>
