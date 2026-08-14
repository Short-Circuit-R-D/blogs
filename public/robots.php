<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: text/plain; charset=UTF-8');

$sitemap = publicSiteUrl('sitemap.xml');
echo "User-agent: *\n";
echo "Allow: /\n\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /public/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /storage/\n";
echo "Disallow: /vendor/\n";
echo "Disallow: /config.php\n";
echo "Disallow: /login\n";
echo "Disallow: /account\n";
echo "Disallow: /sc-login\n";
echo "Disallow: /staff-login\n";
echo "Disallow: /moderate\n";
echo "Disallow: /topic_new\n";
echo "Disallow: /unsubscribe\n";
echo "Disallow: /sc_security_log_printer.php\n\n";
echo "Allow: /logo.svg\n";
echo "Allow: /og-image.png\n";
echo "Allow: /sitemap.xml\n";
echo "Allow: /sitemap-pages.xml\n";
echo "Allow: /sitemap-articles.xml\n";
echo "Allow: /sitemap-topics.xml\n\n";
echo "Sitemap: {$sitemap}\n";
echo 'Sitemap: ' . publicSiteUrl('sitemap-pages.xml') . "\n";
echo 'Sitemap: ' . publicSiteUrl('sitemap-articles.xml') . "\n";
echo 'Sitemap: ' . publicSiteUrl('sitemap-topics.xml') . "\n";
