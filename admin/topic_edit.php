<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$topic = ['id' => 0, 'user_id' => 0, 'title' => '', 'category' => 'General', 'body' => '', 'status' => 'approved', 'reject_reason' => ''];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM discussion_topics WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) redirect('topics.php');
    $topic = $found;
}

$errors = [];
$admin = currentAdmin();

// House account used when admin authors a topic directly with no existing user tied to it.
$houseUserId = null;
if (!$id) {
    $houseStmt = db()->prepare("SELECT id FROM users WHERE email = ?");
    $houseStmt->execute(['admin@shortcircuit.company']);
    $house = $houseStmt->fetch();
    $houseUserId = $house ? (int)$house['id'] : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $topic['title']    = trim($_POST['title'] ?? '');
    $topic['category'] = trim($_POST['category'] ?? '') ?: 'General';
    $topic['body']      = trim($_POST['body'] ?? '');
    $topic['status']    = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_POST['status'] : 'approved';
    $topic['reject_reason'] = trim($_POST['reject_reason'] ?? '');

    if ($topic['title'] === '') $errors[] = 'Title is required.';
    if ($topic['body'] === '')  $errors[] = 'Body is required.';

    if (!$errors) {
        $pdo = db();
        $decidedBy = $topic['status'] === 'pending' ? null : $admin['username'] . ' (High Board)';
        $decidedAt = $topic['status'] === 'pending' ? null : date('Y-m-d H:i:s');
        $previousStatus = $id ? $topic['status'] : null; // overwritten below once we know the true prior value

        if ($id) {
            $prevStmt = $pdo->prepare('SELECT status FROM discussion_topics WHERE id = ?');
            $prevStmt->execute([$id]);
            $previousStatus = $prevStmt->fetchColumn();

            $slug = uniqueTopicSlug($topic['title'], $id);
            $stmt = $pdo->prepare('UPDATE discussion_topics SET title=?, slug=?, category=?, body=?, status=?, reject_reason=?, decided_by=?, decided_at=? WHERE id=?');
            $stmt->execute([$topic['title'], $slug, $topic['category'], $topic['body'], $topic['status'], $topic['status'] === 'rejected' ? $topic['reject_reason'] : null, $decidedBy, $decidedAt, $id]);

            // Only notify when the status actually changed into approved/rejected —
            // saving other edits to an already-decided topic shouldn't re-notify.
            if ($previousStatus !== $topic['status'] && in_array($topic['status'], ['approved', 'rejected'], true)) {
                notifyTopicDecision($id, $topic['status'], $topic['status'] === 'rejected' ? $topic['reject_reason'] : null);
            }
        } else {
            $userId = (int)($_POST['user_id'] ?? 0) ?: $houseUserId;
            if (!$userId) {
                $errors[] = 'No account is available to attribute this topic to. Create at least one user first, or set MAIL admin@shortcircuit.company as a user.';
            } else {
                $slug = uniqueTopicSlug($topic['title']);
                $stmt = $pdo->prepare('INSERT INTO discussion_topics (user_id, title, slug, category, body, status, reject_reason, decided_by, decided_at) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$userId, $topic['title'], $slug, $topic['category'], $topic['body'], $topic['status'], $topic['status'] === 'rejected' ? $topic['reject_reason'] : null, $decidedBy, $decidedAt]);
            }
        }
        if (!$errors) redirect('topics.php');
    }
}

$users = db()->query('SELECT id, name, email FROM users ORDER BY name ASC')->fetchAll();

$pageTitle = $id ? 'Edit Topic' : 'New Topic';
$activeNav = 'topics';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <?php if (!$id): ?>
  <label>Attribute to user
    <select name="user_id">
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?> — <?= e($u['email']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <?php endif; ?>

  <div class="form-grid-2">
    <label>Title
      <input type="text" name="title" value="<?= e($topic['title']) ?>" required>
    </label>
    <label>Category
      <input type="text" name="category" value="<?= e($topic['category']) ?>">
    </label>
  </div>

  <label>Body
    <textarea name="body" rows="8" required><?= e($topic['body']) ?></textarea>
  </label>

  <div class="form-grid-2">
    <label>Status
      <select name="status">
        <option value="pending" <?= $topic['status'] === 'pending' ? 'selected' : '' ?>>Pending review</option>
        <option value="approved" <?= $topic['status'] === 'approved' ? 'selected' : '' ?>>Approved (live)</option>
        <option value="rejected" <?= $topic['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </label>
    <label>Rejection reason <span class="hint">(shown to the author, only used if Status = Rejected)</span>
      <input type="text" name="reject_reason" value="<?= e($topic['reject_reason'] ?? '') ?>">
    </label>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Topic</button>
    <a href="topics" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
