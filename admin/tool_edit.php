<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$tool = ['id' => 0, 'name' => '', 'description' => '', 'url' => '', 'icon' => 'standard', 'image_url' => '', 'is_external' => 1, 'is_published' => 1, 'sort_order' => 0];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM tools WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) redirect('tools.php');
    $tool = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $tool['name']         = trim($_POST['name'] ?? '');
    $tool['description']  = trim($_POST['description'] ?? '');
    $tool['url']          = trim($_POST['url'] ?? '');
    $tool['icon']         = trim($_POST['icon'] ?? 'standard');
    $tool['image_url']    = trim($_POST['image_url'] ?? '') ?: null;
    $tool['is_external']  = isset($_POST['is_external']) ? 1 : 0;
    $tool['is_published'] = isset($_POST['is_published']) ? 1 : 0;
    $tool['sort_order']   = (int)($_POST['sort_order'] ?? 0);

    if ($tool['name'] === '') $errors[] = 'Name is required.';
    if ($tool['description'] === '') $errors[] = 'Description is required.';

    if (!$errors) {
        if ($id) {
            $stmt = db()->prepare('UPDATE tools SET name=?, description=?, url=?, icon=?, image_url=?, is_external=?, is_published=?, sort_order=? WHERE id=?');
            $stmt->execute([$tool['name'], $tool['description'], $tool['url'], $tool['icon'], $tool['image_url'], $tool['is_external'], $tool['is_published'], $tool['sort_order'], $id]);
        } else {
            $stmt = db()->prepare('INSERT INTO tools (name, description, url, icon, image_url, is_external, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$tool['name'], $tool['description'], $tool['url'], $tool['icon'], $tool['image_url'], $tool['is_external'], $tool['is_published'], $tool['sort_order']]);
        }
        redirect('tools.php');
    }
}

$icons = ['lux2','school','xr','dialux','standard','compare'];

$pageTitle = $id ? 'Edit Tool' : 'New Tool';
$activeNav = 'tools';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="id" value="<?= (int)$tool['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <div class="form-grid-2">
    <label>Name
      <input type="text" name="name" value="<?= e($tool['name']) ?>" placeholder="LuxSCale / Dialux / Relux ..." required>
    </label>
    <label>Fallback icon <span class="hint">(only used while Image URL below is empty)</span>
      <select name="icon">
        <?php foreach ($icons as $ic): ?>
          <option value="<?= e($ic) ?>" <?= $tool['icon'] === $ic ? 'selected' : '' ?>><?= e($ic) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <label>Description <span class="hint">(one line — what it does, keep it factual and short)</span>
    <textarea name="description" rows="3" required><?= e($tool['description']) ?></textarea>
  </label>

  <div class="form-grid-2">
    <label>Link
      <input type="url" name="url" value="<?= e($tool['url']) ?>" placeholder="https://...">
    </label>
    <label>Image URL <span class="hint">(shown in the tools carousel — 640×420 crops cleanest)</span>
      <input type="url" name="image_url" id="image_url_field" value="<?= e($tool['image_url'] ?? '') ?>"
             placeholder="https://.../dialux-example.jpg" oninput="document.getElementById('image_url_preview').src=this.value">
    </label>
  </div>

  <label>Preview
    <span class="image-preview">
      <img id="image_url_preview" src="<?= e($tool['image_url'] ?? '') ?>" alt="" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'">
    </span>
  </label>

  <div class="form-grid-3">
    <label>Sort order
      <input type="number" name="sort_order" value="<?= (int)$tool['sort_order'] ?>">
    </label>
    <label class="checkbox-label" style="margin-top:28px;">
      <input type="checkbox" name="is_external" <?= $tool['is_external'] ? 'checked' : '' ?>> Industry tool (not ours)
    </label>
    <label class="checkbox-label" style="margin-top:28px;">
      <input type="checkbox" name="is_published" <?= $tool['is_published'] ? 'checked' : '' ?>> Published
    </label>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Tool</button>
    <a href="tools" class="btn-secondary">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
