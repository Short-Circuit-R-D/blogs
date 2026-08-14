<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sitemap_build.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$type = strtolower(trim((string)($_GET['type'] ?? 'all')));
if (!in_array($type, ['all', 'pages', 'articles', 'topics'], true)) {
    $type = 'all';
}

echo sitemapXmlFor($type);
sitemapWriteFiles();
