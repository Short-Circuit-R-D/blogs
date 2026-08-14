<?php
/** @var string $pageTitle */
require_once __DIR__ . '/../includes/auth_user.php';
$navUser = currentUser();
$navUserFull = $navUser ? currentUserFull() : null;
$navCanModerate = $navUserFull && roleCan($navUserFull['role'], 'can_moderate_topics');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Lighting Technical Data') ?> — Short Circuit Company</title>
<base href="<?= e(appUrl()) ?>">
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
