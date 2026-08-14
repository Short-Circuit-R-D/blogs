<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$standard = ['id' => 0, 'code' => '', 'name' => '', 'region' => '', 'description' => '', 'official_url' => '', 'is_published' => 1, 'sort_order' => 0];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM standards WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) redirect('standards.php');
    $standard = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $standard['code']          = trim($_POST['code'] ?? '');
    $standard['name']          = trim($_POST['name'] ?? '');
    $standard['region']        = trim($_POST['region'] ?? '');
    $standard['description']   = trim($_POST['description'] ?? '');
    $standard['official_url']  = trim($_POST['official_url'] ?? '');
    $standard['is_published']  = isset($_POST['is_published']) ? 1 : 0;
    $standard['sort_order']    = (int)($_POST['sort_order'] ?? 0);

    if ($standard['code'] === '') $errors[] = 'Code is required (e.g. EN 12464-1).';
    if ($standard['name'] === '') $errors[] = 'Name is required.';
    if ($standard['description'] === '') $errors[] = 'Description is required.';

    if (!$errors) {
        if ($id) {
            $stmt = db()->prepare('UPDATE standards SET code=?, name=?, region=?, description=?, official_url=?, is_published=?, sort_order=? WHERE id=?');
            $stmt->execute([$standard['code'], $standard['name'], $standard['region'], $standard['description'], $standard['official_url'], $standard['is_published'], $standard['sort_order'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO standards (code, name, region, description, official_url, is_published, sort_order) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$standard['code'], $standard['name'], $standard['region'], $standard['description'], $standard['official_url'], $standard['is_published'], $standard['sort_order']]);
        }
        redirect('standards.php');
    }
}

$pageTitle = $id ? 'Edit Standard' : 'New Standard';
$activeNav = 'standards';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="id" value="<?= (int)$standard['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <div class="form-grid-2">
    <label>Code
      <input type="text" name="code" value="<?= e($standard['code']) ?>" placeholder="EN 12464-1" required>
    </label>
    <label>Region <span class="hint">(optional)</span>
      <input type="text" name="region" value="<?= e($standard['region']) ?>" placeholder="Europe / North America / Global">
    </label>
  </div>

  <label>Name
    <input type="text" name="name" value="<?= e($standard['name']) ?>" placeholder="Light and lighting — Lighting of work places" required>
  </label>

  <label>Description
    <textarea name="description" rows="4" required><?= e($standard['description']) ?></textarea>
  </label>

  <label>Official standard link
    <input type="url" name="official_url" value="<?= e($standard['official_url']) ?>" placeholder="https://www.en-standard.eu/...">
  </label>

  <div class="form-grid-2">
    <label>Sort order
      <input type="number" name="sort_order" value="<?= (int)$standard['sort_order'] ?>">
    </label>
    <label class="checkbox-label" style="margin-top:28px;">
      <input type="checkbox" name="is_published" <?= $standard['is_published'] ? 'checked' : '' ?>> Published
    </label>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Standard</button>
    <a href="standards" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
