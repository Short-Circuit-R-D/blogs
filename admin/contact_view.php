<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
$stmt->execute([$id]);
$msg = $stmt->fetch();
if (!$msg) {
    redirect('contacts.php');
}

if (empty($msg['is_read'])) {
    db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$id]);
    $msg['is_read'] = 1;
}

$pageTitle = 'Contact Message #' . $id;
$activeNav = 'contacts';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub"><a href="contacts">← All contact messages</a></p>

<dl class="contact-dl">
  <dt>Received</dt>
  <dd><?= e(date('j M Y, H:i', strtotime($msg['created_at']))) ?></dd>
  <dt>Name</dt>
  <dd><?= e($msg['name']) ?></dd>
  <dt>Email</dt>
  <dd><a href="mailto:<?= e($msg['email']) ?>"><?= e($msg['email']) ?></a></dd>
  <dt>Company</dt>
  <dd><?= e($msg['company'] ?: '—') ?></dd>
  <dt>Emailed to admins</dt>
  <dd><?= !empty($msg['emailed_at']) ? e(date('j M Y, H:i', strtotime($msg['emailed_at']))) : 'Not sent' ?></dd>
</dl>

<div class="contact-body"><?= nl2br(e($msg['message'])) ?></div>

<p class="row-actions" style="margin-top:20px;">
  <a class="btn-primary" href="mailto:<?= e($msg['email']) ?>?subject=<?= rawurlencode('Re: your message to Short Circuit Company') ?>">Reply by email</a>
</p>
<form method="post" action="contact_delete.php" onsubmit="return confirm('Delete this contact message?');" style="margin-top:12px;">
  <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
  <button type="submit" class="link-danger">Delete</button>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
