<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$now = gmdate('Y-m-d\TH:i:s+00:00');

$urls = [];

$add = static function (string $loc, string $lastmod, string $changefreq, string $priority) use (&$urls): void {
    $urls[] = [
        'loc' => $loc,
        'lastmod' => $lastmod,
        'changefreq' => $changefreq,
        'priority' => $priority,
    ];
};

$stamp = static function (?string $datetime) use ($now): string {
    if (!$datetime) return $now;
    $ts = strtotime($datetime);
    return $ts ? gmdate('Y-m-d\TH:i:s+00:00', $ts) : $now;
};

$add(publicSiteUrl(), $now, 'daily', '1.0');
$add(publicSiteUrl('articles'), $now, 'daily', '0.9');
$add(publicSiteUrl('topics'), $now, 'daily', '0.8');
$add(publicSiteUrl('search'), $now, 'weekly', '0.5');
$add(publicSiteUrl('subscribe'), $now, 'monthly', '0.6');

try {
    $articles = db()->query(
        'SELECT slug, updated_at, created_at FROM articles WHERE is_published = 1 ORDER BY sort_order ASC, id ASC'
    )->fetchAll();
    foreach ($articles as $a) {
        $add(articlePermalink($a['slug']), $stamp($a['updated_at'] ?: $a['created_at']), 'weekly', '0.8');
    }

    $topics = db()->query(
        "SELECT slug, updated_at, created_at FROM discussion_topics WHERE status = 'approved' ORDER BY created_at DESC"
    )->fetchAll();
    foreach ($topics as $t) {
        $add(topicPermalink($t['slug']), $stamp($t['updated_at'] ?: $t['created_at']), 'weekly', '0.6');
    }
} catch (\Throwable $e) {
    // Still emit the static URLs if the DB is down.
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
