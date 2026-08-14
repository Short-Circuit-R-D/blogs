<?php
/** @var string $pageTitle */
/** @var string|null $pageDescription */
/** @var string|null $pageCanonical */
/** @var string|null $pageOgImage */
/** @var string|null $pageOgType */
/** @var string|null $pageRobots */
/** @var array|null $pageJsonLd */
require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../includes/seo.php';
$navUser = currentUser();
$navUserFull = $navUser ? currentUserFull() : null;
$navCanModerate = $navUserFull && roleCan($navUserFull['role'], 'can_moderate_topics');

$ogTitle = seoFullTitle($pageTitle ?? 'Lighting Technical Data');
$ogDescription = $pageDescription ?? seoDefaultDescription();
$ogImagePng = $pageOgImage ?? defaultOgImageUrl();
$ogImageSvg = defaultLogoUrl();
$ogUrl = $pageCanonical ?? publicSiteUrl();
$ogType = $pageOgType ?? 'website';
$robots = $pageRobots ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
$jsonLdNodes = is_array($pageJsonLd ?? null) ? $pageJsonLd : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($ogTitle) ?></title>
<base href="<?= e(appUrl()) ?>">
<link rel="icon" href="logo.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e($ogImagePng) ?>">
<meta name="theme-color" content="#eb1b26">
<meta name="robots" content="<?= e($robots) ?>">
<meta name="author" content="Short Circuit Company">
<meta name="description" content="<?= e($ogDescription) ?>">
<link rel="canonical" href="<?= e($ogUrl) ?>">
<link rel="alternate" hreflang="en" href="<?= e($ogUrl) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e($ogUrl) ?>">
<link rel="sitemap" type="application/xml" title="Sitemap" href="<?= e(publicSiteUrl('sitemap.xml')) ?>">

<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="Short Circuit Company">
<meta property="og:locale" content="en_US">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<meta property="og:description" content="<?= e($ogDescription) ?>">
<meta property="og:url" content="<?= e($ogUrl) ?>">
<meta property="og:image" content="<?= e($ogImagePng) ?>">
<meta property="og:image:secure_url" content="<?= e($ogImagePng) ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Short Circuit Company">

<meta name="twitter:card" content="summary_large_image">
<?php $twitterHandle = seoTwitterHandle(); if ($twitterHandle !== ''): ?>
<meta name="twitter:site" content="<?= e($twitterHandle) ?>">
<?php endif; ?>
<meta name="twitter:title" content="<?= e($ogTitle) ?>">
<meta name="twitter:description" content="<?= e($ogDescription) ?>">
<meta name="twitter:image" content="<?= e($ogImagePng) ?>">
<meta name="twitter:image:alt" content="Short Circuit Company">

<meta name="msapplication-TileColor" content="#eb1b26">
<meta name="msapplication-TileImage" content="<?= e($ogImagePng) ?>">
<meta itemprop="name" content="<?= e($ogTitle) ?>">
<meta itemprop="description" content="<?= e($ogDescription) ?>">
<meta itemprop="image" content="<?= e($ogImagePng) ?>">

<script type="application/ld+json"><?= seoJsonLd($jsonLdNodes) ?></script>
<?= seoMarketingHeadHtml() ?>
<link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<div class="header">
  <div class="header-inner">
    <div class="header-left">
      <button type="button" class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mainNav">
        <span></span><span></span><span></span>
      </button>
      <a class="header-logo" href="index.php"><img src="https://shortcircuit.company/assets/img/logo-dark.svg" alt="Short Circuit Company"></a>
      <nav class="main-nav" id="mainNav">
        <a href="articles">Articles</a>
        <a href="topics">Community</a>
        <a href="search">Search</a>
        <a href="contact">Contact</a>
        <?php if ($navCanModerate): ?><a href="moderate">Moderate</a><?php endif; ?>
        <?php if ($navUserFull && in_array($navUserFull['role'] ?? '', ['employee', 'leader', 'admin'], true)): ?>
          <a href="account">Staff</a>
        <?php endif; ?>
      </nav>
    </div>
    <div class="header-right">
      <span class="header-tag">Lighting Standards · Technical Reference</span>
      <?php if ($navUser): ?>
        <a class="nav-account" href="account"><?= e($navUser['name']) ?></a>
      <?php else: ?>
        <a class="nav-cta" href="subscribe">Subscribe</a>
        <a class="nav-account" href="login">Log In</a>
      <?php endif; ?>
      <button class="theme-btn" id="themeToggle">🌙 Dark</button>
    </div>
  </div>
</div>
<div class="nav-backdrop" id="navBackdrop" hidden></div>
