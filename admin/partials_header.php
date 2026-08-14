<?php
/** @var string $pageTitle */
/** @var string $activeNav */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Dashboard') ?> — Lighting CMS</title>
<meta name="robots" content="noindex, nofollow">
<base href="<?= e(appUrl()) ?>">
<link rel="stylesheet" href="assets/datatables.min.css">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="admin-shell">
  <div class="admin-backdrop" id="adminBackdrop" hidden></div>
  <aside class="admin-nav" id="adminNav">
    <div class="admin-nav-brand">
      <p class="eyebrow">Short Circuit Company</p>
      <h2>Lighting CMS</h2>
    </div>
    <nav>
      <a href="dashboard" class="<?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">Overview</a>
      <a href="articles" class="<?= ($activeNav ?? '') === 'articles' ? 'active' : '' ?>">Articles</a>
      <a href="standards" class="<?= ($activeNav ?? '') === 'standards' ? 'active' : '' ?>">Standards</a>
      <a href="standard_terms" class="<?= ($activeNav ?? '') === 'standard_terms' ? 'active' : '' ?>">Terminology Matrix</a>
      <a href="tools" class="<?= ($activeNav ?? '') === 'tools' ? 'active' : '' ?>">Tools</a>
      <a href="events" class="<?= ($activeNav ?? '') === 'events' ? 'active' : '' ?>">Events</a>
      <a href="topics" class="<?= ($activeNav ?? '') === 'topics' ? 'active' : '' ?>">Community Topics</a>
      <a href="contacts" class="<?= ($activeNav ?? '') === 'contacts' ? 'active' : '' ?>">Contact Messages</a>
      <a href="users" class="<?= ($activeNav ?? '') === 'users' ? 'active' : '' ?>">Users</a>
      <a href="admins" class="<?= ($activeNav ?? '') === 'admins' ? 'active' : '' ?>">CMS Admins</a>
      <a href="roles" class="<?= ($activeNav ?? '') === 'roles' ? 'active' : '' ?>">Roles & Permissions</a>
    </nav>
    <div class="admin-nav-foot">
      <p>Signed in as <strong><?php
        $navAdmin = currentAdmin();
        echo e(($navAdmin['name'] ?? '') !== '' ? $navAdmin['name'] : ($navAdmin['username'] ?? ''));
      ?></strong></p>
      <a href="logout" class="logout-link">Log out</a>
      <a href="../" class="logout-link" target="_blank">View live site ↗</a>
    </div>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar">
      <button type="button" class="admin-menu-btn" id="adminMenuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="adminNav">Menu</button>
    </div>
    <h1><?= e($pageTitle ?? '') ?></h1>
