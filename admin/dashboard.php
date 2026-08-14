<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$counts = [
    'articles'  => db()->query('SELECT COUNT(*) FROM articles')->fetchColumn(),
    'standards' => db()->query('SELECT COUNT(*) FROM standards')->fetchColumn(),
    'tools'     => db()->query('SELECT COUNT(*) FROM tools')->fetchColumn(),
    'events'    => db()->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'pending_topics' => db()->query("SELECT COUNT(*) FROM discussion_topics WHERE status = 'pending'")->fetchColumn(),
    'users'     => db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'contacts_new' => db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(),
];

$pageTitle = 'Overview';
$activeNav = 'dashboard';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Manage everything shown on the public Lighting Technical Data page.</p>

<div class="stat-grid">
  <a href="articles" class="stat-card">
    <span class="stat-num"><?= (int)$counts['articles'] ?></span>
    <span class="stat-label">Articles</span>
  </a>
  <a href="standards" class="stat-card">
    <span class="stat-num"><?= (int)$counts['standards'] ?></span>
    <span class="stat-label">Standards</span>
  </a>
  <a href="tools" class="stat-card">
    <span class="stat-num"><?= (int)$counts['tools'] ?></span>
    <span class="stat-label">Tools</span>
  </a>
  <a href="events" class="stat-card">
    <span class="stat-num"><?= (int)$counts['events'] ?></span>
    <span class="stat-label">Events</span>
  </a>
  <a href="topics?status=pending" class="stat-card">
    <span class="stat-num"><?= (int)$counts['pending_topics'] ?></span>
    <span class="stat-label">Topics Awaiting Review</span>
  </a>
  <a href="users" class="stat-card">
    <span class="stat-num"><?= (int)$counts['users'] ?></span>
    <span class="stat-label">Users</span>
  </a>
  <a href="contacts" class="stat-card">
    <span class="stat-num"><?= (int)$counts['contacts_new'] ?></span>
    <span class="stat-label">New Contact Messages</span>
  </a>
</div>

<div class="quick-actions">
  <a href="article/new" class="btn-primary">+ New Article</a>
  <a href="standard/new" class="btn-secondary">+ New Standard</a>
  <a href="tool/new" class="btn-secondary">+ New Tool</a>
  <a href="event/new" class="btn-secondary">+ New Event</a>
  <a href="topics" class="btn-secondary">Moderate Topics</a>
  <a href="roles" class="btn-secondary">Roles & Permissions</a>
</div>

<?php include __DIR__ . '/partials_footer.php'; ?>
