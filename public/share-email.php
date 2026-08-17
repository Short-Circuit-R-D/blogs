<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_once __DIR__ . '/../includes/mailer.php';

$type = strtolower(trim((string)($_POST['type'] ?? $_GET['type'] ?? 'article')));
$slug = trim((string)($_POST['slug'] ?? $_GET['slug'] ?? ''));
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

$article = null;
$event = null;
$ranges = [];
$images = [];
$backUrl = appUrl();
$backLabel = '← Back';

if ($type === 'event') {
    if ($id < 1) {
        redirect('index.php');
    }
    $stmt = db()->prepare('SELECT * FROM events WHERE id = ? AND is_published = 1');
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    if (!$event) {
        redirect('index.php');
    }
    $stmt = db()->prepare('SELECT * FROM event_images WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    $backUrl = eventUrl($id);
    $backLabel = '← Back to event';
} else {
    $type = 'article';
    if ($slug === '') {
        redirect('index.php');
    }
    $stmt = db()->prepare('SELECT * FROM articles WHERE slug = ? AND is_published = 1');
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
    if (!$article) {
        redirect('index.php');
    }
    $stmt = db()->prepare('SELECT * FROM article_ranges WHERE article_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$article['id']]);
    $ranges = $stmt->fetchAll();
    $backUrl = articleUrl($article['slug']);
    $backLabel = '← Back to article';
}

$me = currentUser();
$error = null;
$success = null;
$to = '';
$fromName = $me['name'] ?? '';
$fromEmail = $me['email'] ?? '';
$note = '';

$shareOpts = ['from_name' => $fromName, 'note' => $note];
$preview = $type === 'event'
    ? buildEventShareEmail($event, $images, $shareOpts)
    : buildArticleShareEmail($article, $ranges, $shareOpts);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();

    $honeypot = trim((string)($_POST['website'] ?? ''));
    $to = substr(trim((string)($_POST['to'] ?? '')), 0, 190);
    $fromName = substr(trim((string)($_POST['from_name'] ?? '')), 0, 120);
    $fromEmail = substr(trim((string)($_POST['from_email'] ?? '')), 0, 190);
    $note = trim((string)($_POST['note'] ?? ''));
    if (function_exists('mb_substr')) {
        $note = mb_substr($note, 0, 500);
    } else {
        $note = substr($note, 0, 500);
    }

    $shareOpts = ['from_name' => $fromName, 'note' => $note];
    $preview = $type === 'event'
        ? buildEventShareEmail($event, $images, $shareOpts)
        : buildArticleShareEmail($article, $ranges, $shareOpts);

    $hour = $_SESSION['share_email_hour'] ?? ['start' => 0, 'count' => 0];
    if (!is_array($hour) || (time() - (int)($hour['start'] ?? 0)) > 3600) {
        $hour = ['start' => time(), 'count' => 0];
    }
    $lastSent = (int)($_SESSION['share_email_sent_at'] ?? 0);

    if ($honeypot !== '') {
        $success = 'Sent. They’ll get the designed article in their inbox.';
        $to = $note = '';
    } elseif ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid recipient email address.';
    } elseif ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Your email looks invalid. Leave it blank, or enter a real address for Reply-To.';
    } elseif ($lastSent && (time() - $lastSent) < 20) {
        $error = 'Please wait a moment before sending another share.';
    } elseif ((int)$hour['count'] >= 5) {
        $error = 'You have reached the share limit for this hour. Try again later.';
    } else {
        $mailOpts = ['category' => 'transactional'];
        if ($fromEmail !== '') {
            $mailOpts['reply_to'] = $fromEmail;
            $mailOpts['reply_name'] = $fromName;
        }
        if (sendMail($to, $preview['subject'], $preview['html'], $mailOpts)) {
            $_SESSION['share_email_sent_at'] = time();
            $hour['count'] = (int)$hour['count'] + 1;
            $_SESSION['share_email_hour'] = $hour;
            $success = 'Sent. They’ll get the designed article in their inbox, with a link to read more on the site.';
            $to = $note = '';
        } else {
            $error = 'The email could not be sent. Please try again in a moment.';
        }
    }
}

$pageTitle = 'Share by email — ' . ($article['title'] ?? $event['name'] ?? 'Short Circuit');
$pageDescription = 'Send this lighting guide as a designed email.';
$pageCanonical = publicSiteUrl('share-email');
$pageRobots = 'noindex, nofollow';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section share-email-page" style="border-top:none;">
  <a class="back-btn" href="<?= e($backUrl) ?>"><?= e($backLabel) ?></a>
  <p class="eyebrow">Share</p>
  <h1 class="section-title">Email this <?= $type === 'event' ? 'event' : 'article' ?></h1>
  <p class="section-sub">We’ll send a branded HTML message that looks like the page — full content, then <strong>To read more, visit</strong> with the live link.</p>

  <div class="share-email-layout">
    <div class="auth-card auth-card-wide">
      <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

      <form method="post" action="<?= e(appUrl('share-email')) ?>" novalidate>
        <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <input type="hidden" name="id" value="<?= e((string)$id) ?>">
        <p class="hp-field" aria-hidden="true">
          <label>Website
            <input type="text" name="website" tabindex="-1" autocomplete="off">
          </label>
        </p>
        <label>Recipient email <span class="hint">(required)</span>
          <input type="email" name="to" value="<?= e($to) ?>" maxlength="190" required autofocus placeholder="colleague@company.com">
        </label>
        <label>Your name <span class="hint">(optional)</span>
          <input type="text" name="from_name" value="<?= e($fromName) ?>" maxlength="120" placeholder="Shown at the top of the email">
        </label>
        <label>Your email <span class="hint">(optional, for Reply-To)</span>
          <input type="email" name="from_email" value="<?= e($fromEmail) ?>" maxlength="190">
        </label>
        <label>Note <span class="hint">(optional)</span>
          <textarea name="note" rows="3" maxlength="500" placeholder="A short line to the recipient…"><?= e($note) ?></textarea>
        </label>
        <button type="submit" class="auth-submit">Send designed email</button>
      </form>
    </div>

    <div class="share-email-preview">
      <p class="share-email-preview-label">Preview — this is what they receive</p>
      <iframe class="share-email-frame" title="Email preview" sandbox="" srcdoc="<?= e($preview['html']) ?>"></iframe>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
