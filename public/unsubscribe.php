<?php
require_once __DIR__ . '/../includes/functions.php';

$token = trim($_GET['token'] ?? '');
$done  = false;

if ($token !== '') {
    $stmt = db()->prepare('UPDATE users SET is_subscribed = 0 WHERE unsubscribe_token = ?');
    $stmt->execute([$token]);
    $done = $stmt->rowCount() > 0;
}

$pageTitle = 'Unsubscribe';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card">
    <p class="eyebrow">Email Preferences</p>
    <h2 class="section-title"><?= $done ? 'Unsubscribed' : 'Link Not Valid' ?></h2>
    <p class="section-sub">
      <?= $done
        ? 'You won\'t get any more new-guide emails from us. You can turn them back on any time from your account page.'
        : 'This unsubscribe link is invalid or already used.' ?>
    </p>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
