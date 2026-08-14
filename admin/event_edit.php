<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$event = ['id' => 0, 'name' => '', 'year' => date('Y'), 'description' => '', 'is_published' => 1, 'sort_order' => 0];
$images = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) redirect('events.php');
    $event = $found;

    $stmt = db()->prepare('SELECT * FROM event_images WHERE event_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $event['name']         = trim($_POST['name'] ?? '');
    $event['year']         = (int)($_POST['year'] ?? date('Y'));
    $event['description']  = trim($_POST['description'] ?? '');
    $event['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $event['sort_order']   = (int)($_POST['sort_order'] ?? 0);

    if ($event['name'] === '') $errors[] = 'Name is required.';

    if (!$errors) {
        try {
            if ($id) {
                $stmt = db()->prepare('UPDATE events SET name=?, year=?, description=?, is_published=?, sort_order=? WHERE id=?');
                $stmt->execute([$event['name'], $event['year'], $event['description'], $event['is_published'], $event['sort_order'], $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO events (name, year, description, is_published, sort_order) VALUES (?,?,?,?,?)');
                $stmt->execute([$event['name'], $event['year'], $event['description'], $event['is_published'], $event['sort_order']]);
                $id = (int)db()->lastInsertId();
            }

            // New image uploads (multiple files from one <input multiple>)
            if (!empty($_FILES['gallery']['name'][0])) {
                $count = count($_FILES['gallery']['name']);
                $insertImg = db()->prepare('INSERT INTO event_images (event_id, image_path, caption, sort_order) VALUES (?,?,?,?)');
                $maxOrder = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM event_images WHERE event_id = ' . (int)$id)->fetchColumn();
                for ($i = 0; $i < $count; $i++) {
                    if ($_FILES['gallery']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $single = [
                        'name' => $_FILES['gallery']['name'][$i],
                        'type' => $_FILES['gallery']['type'][$i],
                        'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                        'error' => $_FILES['gallery']['error'][$i],
                        'size' => $_FILES['gallery']['size'][$i],
                    ];
                    $_FILES['__single'] = $single;
                    $path = saveUpload('__single', 'events');
                    if ($path) {
                        $maxOrder++;
                        $insertImg->execute([$id, $path, null, $maxOrder]);
                    }
                }
            }

            redirect('event_edit.php?id=' . $id . '&saved=1');
        } catch (Throwable $ex) {
            $errors[] = 'Save failed: ' . $ex->getMessage();
        }
    }
}

$pageTitle = $id ? 'Edit Event' : 'New Event';
$activeNav = 'events';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>
<?php if (isset($_GET['saved'])): ?><div class="alert-ok">Saved.</div><?php endif; ?>

<form method="post" class="admin-form" enctype="multipart/form-data">
  <input type="hidden" name="id" value="<?= (int)$event['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <div class="form-grid-2">
    <label>Name
      <input type="text" name="name" value="<?= e($event['name']) ?>" placeholder="LedEXPO 4" required>
    </label>
    <label>Year
      <input type="number" name="year" value="<?= e((string)$event['year']) ?>" min="2000" max="2100">
    </label>
  </div>

  <label>Description
    <textarea name="description" rows="3"><?= e($event['description']) ?></textarea>
  </label>

  <div class="form-grid-2">
    <label>Sort order
      <input type="number" name="sort_order" value="<?= (int)$event['sort_order'] ?>">
    </label>
    <label class="checkbox-label" style="margin-top:28px;">
      <input type="checkbox" name="is_published" <?= $event['is_published'] ? 'checked' : '' ?>> Published
    </label>
  </div>

  <h3>Booth photos</h3>
  <label>Add photos <span class="hint">(JPG, PNG, or WEBP — you can select several at once, and add more later)</span>
    <input type="file" name="gallery[]" accept=".jpg,.jpeg,.png,.webp" multiple <?= $id ? '' : 'disabled title="Save the event first, then add photos"' ?>>
  </label>
  <?php if (!$id): ?><p class="hint">Save the event once first — then reopen it here to upload booth photos.</p><?php endif; ?>

  <?php if ($images): ?>
  <div class="image-grid">
    <?php foreach ($images as $img): ?>
    <div class="image-tile">
      <img src="<?= e(uploadUrl($img['image_path'])) ?>" alt="">
      <form method="post" action="event_image_delete.php" onsubmit="return confirm('Remove this photo?');">
        <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
        <input type="hidden" name="event_id" value="<?= (int)$id ?>">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
        <button type="submit" class="image-remove">✕</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Event</button>
    <a href="events" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
