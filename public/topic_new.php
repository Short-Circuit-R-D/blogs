<?php
require_once __DIR__ . '/../includes/auth_user.php';

$me = requirePermission('can_post_topics');

$title = $category = $body = '';
$error = null;
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();
    $title    = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '') ?: 'General';
    $body     = trim($_POST['body'] ?? '');

    if ($title === '' || $body === '') {
        $error = 'Please fill in a title and the topic content.';
    } elseif (mb_strlen($title) > 160) {
        $error = 'Title is too long (max 160 characters).';
    } else {
        $autoPublish = userAutoPublishes($me);
        $slug = uniqueTopicSlug($title);
        $stmt = db()->prepare(
            'INSERT INTO discussion_topics (user_id, title, slug, category, body, status, decided_by, decided_at)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $me['id'], $title, $slug, $category, $body,
            $autoPublish ? 'approved' : 'pending',
            $autoPublish ? 'Auto-published' : null,
            $autoPublish ? date('Y-m-d H:i:s') : null,
        ]);
        redirect($autoPublish ? 'topic.php?slug=' . urlencode($slug) : 'account.php?posted=1');
    }
}

$pageTitle = 'Post a New Topic';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card" style="max-width:640px;">
    <p class="eyebrow">Community</p>
    <h2 class="section-title">Post a New Topic</h2>
    <p class="section-sub">
      <?= userAutoPublishes($me)
            ? 'Your account publishes topics immediately.'
            : 'Your topic is reviewed by a Short Circuit leader or admin before it appears on the Community page.' ?>
    </p>
    <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <label>Title
        <input type="text" name="title" value="<?= e($title) ?>" maxlength="160" required autofocus>
      </label>
      <label>Category <span class="hint">(e.g. Standards, Fixtures, Site Question)</span>
        <input type="text" name="category" value="<?= e($category) ?>" maxlength="60" placeholder="General">
      </label>
      <label>Topic content
        <textarea name="body" rows="8" required><?= e($body) ?></textarea>
      </label>
      <button type="submit" class="auth-submit">Submit Topic</button>
    </form>
    <p class="auth-switch"><a href="account.php">← Back to My Account</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
