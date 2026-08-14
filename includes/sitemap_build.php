<?php
/**
 * Build sitemap XML for the public blog.
 */

function sitemapNow(): string
{
    return gmdate('Y-m-d\TH:i:s+00:00');
}

function sitemapStamp(?string $datetime): string
{
    if (!$datetime) {
        return sitemapNow();
    }
    $ts = strtotime($datetime);
    return $ts ? gmdate('Y-m-d\TH:i:s+00:00', $ts) : sitemapNow();
}

function sitemapUrlsetXml(array $urls): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        $xml .= '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
        $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
        $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= "</urlset>\n";
    return $xml;
}

function sitemapCollect(string $type): array
{
    $now = sitemapNow();
    $urls = [];
    $add = static function (string $loc, string $lastmod, string $changefreq, string $priority) use (&$urls): void {
        $urls[] = compact('loc', 'lastmod', 'changefreq', 'priority');
    };

    if ($type === 'pages' || $type === 'all') {
        $add(publicSiteUrl(), $now, 'daily', '1.0');
        $add(publicSiteUrl('articles'), $now, 'daily', '0.9');
        $add(publicSiteUrl('topics'), $now, 'daily', '0.8');
        $add(publicSiteUrl('search'), $now, 'weekly', '0.5');
        $add(publicSiteUrl('subscribe'), $now, 'monthly', '0.6');
        $add(publicSiteUrl('contact'), $now, 'monthly', '0.6');
    }

    try {
        if ($type === 'articles' || $type === 'all') {
            $articles = db()->query(
                'SELECT slug, updated_at, created_at FROM articles WHERE is_published = 1 ORDER BY sort_order ASC, id ASC'
            )->fetchAll();
            foreach ($articles as $a) {
                $add(articlePermalink($a['slug']), sitemapStamp($a['updated_at'] ?: $a['created_at']), 'weekly', '0.8');
            }
        }
        if ($type === 'topics' || $type === 'all') {
            $topics = db()->query(
                "SELECT slug, updated_at, created_at FROM discussion_topics WHERE status = 'approved' ORDER BY created_at DESC"
            )->fetchAll();
            foreach ($topics as $t) {
                $add(topicPermalink($t['slug']), sitemapStamp($t['updated_at'] ?: $t['created_at']), 'weekly', '0.6');
            }
        }
    } catch (\Throwable $e) {
        // Keep whatever URLs we already collected.
    }

    return $urls;
}

function sitemapXmlFor(string $type): string
{
    return sitemapUrlsetXml(sitemapCollect($type));
}

function sitemapWriteFiles(): void
{
    $files = [
        'sitemap.xml' => sitemapXmlFor('all'),
        'sitemap-pages.xml' => sitemapXmlFor('pages'),
        'sitemap-articles.xml' => sitemapXmlFor('articles'),
        'sitemap-topics.xml' => sitemapXmlFor('topics'),
    ];
    $root = dirname(__DIR__);
    foreach ([$root, $root . DIRECTORY_SEPARATOR . 'public'] as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach ($files as $name => $xml) {
            @file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $xml);
        }
    }
}
