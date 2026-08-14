<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$article = ['id' => 0, 'slug' => '', 'tag' => 'Topic', 'icon' => 'standard', 'image_url' => '', 'title' => '', 'excerpt' => '',
    'intro' => '', 'why_text' => '', 'physical_text' => '', 'physio_text' => '', 'psycho_text' => '',
    'formula_text' => '', 'formula_note' => '', 'simulator_url' => 'https://shortcircuit.company/SChools/',
    'simulator_label' => 'Open the full live simulator', 'is_published' => 1, 'sort_order' => 0];
$ranges = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { redirect('articles.php'); }
    $article = $found;

    $stmt = db()->prepare('SELECT * FROM article_ranges WHERE article_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$id]);
    $ranges = $stmt->fetchAll();
}

$errors = [];
$notifiedCount = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'notify_subscribers') {
    csrfCheck();
    if ($id && $article['is_published']) {
        $notifiedCount = notifySubscribers($article);
        $article['notified_at'] = date('Y-m-d H:i:s');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $article['title']           = trim($_POST['title'] ?? '');
    $article['tag']              = trim($_POST['tag'] ?? 'Topic');
    $article['icon']              = trim($_POST['icon'] ?? 'standard');
    $article['image_url']         = trim($_POST['image_url'] ?? '') ?: null;
    $article['excerpt']           = trim($_POST['excerpt'] ?? '');
    $article['intro']             = trim($_POST['intro'] ?? '');
    $article['why_text']          = trim($_POST['why_text'] ?? '');
    $article['physical_text']     = trim($_POST['physical_text'] ?? '');
    $article['physio_text']       = trim($_POST['physio_text'] ?? '');
    $article['psycho_text']       = trim($_POST['psycho_text'] ?? '');
    $article['formula_text']      = trim($_POST['formula_text'] ?? '');
    $article['formula_note']      = trim($_POST['formula_note'] ?? '');
    $article['simulator_url']     = trim($_POST['simulator_url'] ?? '');
    $article['simulator_label']   = trim($_POST['simulator_label'] ?? '');
    $article['is_published']      = isset($_POST['is_published']) ? 1 : 0;
    $article['sort_order']        = (int)($_POST['sort_order'] ?? 0);
    $slugInput                    = trim($_POST['slug'] ?? '');
    $article['slug']              = $slugInput !== '' ? slugify($slugInput) : slugify($article['title']);

    if ($article['title'] === '')   $errors[] = 'Title is required.';
    if ($article['excerpt'] === '') $errors[] = 'Excerpt is required.';
    if ($article['intro'] === '')   $errors[] = 'Intro is required.';
    if ($article['slug'] === '')    $errors[] = 'Slug could not be generated — add a title or a manual slug.';

    // Repeatable range rows
    $stages = $_POST['range_stage'] ?? [];
    $envs   = $_POST['range_env'] ?? [];
    $vals   = $_POST['range_value'] ?? [];
    $notes  = $_POST['range_notes'] ?? [];
    $newRanges = [];
    for ($i = 0; $i < count($stages); $i++) {
        $s = trim($stages[$i]); $en = trim($envs[$i]); $v = trim($vals[$i]); $n = trim($notes[$i] ?? '');
        if ($s === '' && $en === '' && $v === '') continue; // skip blank rows
        $newRanges[] = ['stage' => $s, 'env' => $en, 'range' => $v, 'notes' => $n];
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE articles SET slug=?, tag=?, icon=?, image_url=?, title=?, excerpt=?, intro=?, why_text=?, physical_text=?, physio_text=?, psycho_text=?, formula_text=?, formula_note=?, simulator_url=?, simulator_label=?, is_published=?, sort_order=? WHERE id=?');
                $stmt->execute([$article['slug'], $article['tag'], $article['icon'], $article['image_url'], $article['title'], $article['excerpt'],
                    $article['intro'], $article['why_text'], $article['physical_text'], $article['physio_text'], $article['psycho_text'],
                    $article['formula_text'], $article['formula_note'], $article['simulator_url'], $article['simulator_label'],
                    $article['is_published'], $article['sort_order'], $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO articles (slug, tag, icon, image_url, title, excerpt, intro, why_text, physical_text, physio_text, psycho_text, formula_text, formula_note, simulator_url, simulator_label, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$article['slug'], $article['tag'], $article['icon'], $article['image_url'], $article['title'], $article['excerpt'],
                    $article['intro'], $article['why_text'], $article['physical_text'], $article['physio_text'], $article['psycho_text'],
                    $article['formula_text'], $article['formula_note'], $article['simulator_url'], $article['simulator_label'],
                    $article['is_published'], $article['sort_order']]);
                $id = (int)$pdo->lastInsertId();
            }

            $pdo->prepare('DELETE FROM article_ranges WHERE article_id = ?')->execute([$id]);
            $insertRange = $pdo->prepare('INSERT INTO article_ranges (article_id, stage_label, environment_label, range_text, notes, sort_order) VALUES (?,?,?,?,?,?)');
            foreach ($newRanges as $i => $r) {
                $insertRange->execute([$id, $r['stage'], $r['env'], $r['range'], $r['notes'], $i]);
            }

            $pdo->commit();
            redirect('articles.php');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Save failed: ' . $ex->getMessage();
        }
    } else {
        $ranges = array_map(fn($r) => ['stage_label' => $r['stage'], 'environment_label' => $r['env'], 'range_text' => $r['range'], 'notes' => $r['notes']], $newRanges);
    }
}

$icons = ['cri','cct','lux','lumens','ugr','flicker','uniformity','melanopic','vertical','exposure','standard','compare','xr','lux2','school','dialux'];

$pageTitle = $id ? 'Edit Article' : 'New Article';
$activeNav = 'articles';
include __DIR__ . '/partials_header.php';
?>

<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>
<?php if ($notifiedCount !== null): ?><div class="alert-ok">Sent to <?= (int)$notifiedCount ?> subscriber<?= $notifiedCount === 1 ? '' : 's' ?>.</div><?php endif; ?>

<?php if ($id && $article['is_published']): ?>
<div class="notify-box">
  <div>
    <strong>Subscriber email</strong>
    <p class="hint" style="margin:2px 0 0;">
      <?= !empty($article['notified_at']) ? 'Notified subscribers on ' . e(date('M j, Y g:ia', strtotime($article['notified_at']))) . '.' : 'Subscribers haven\'t been emailed about this article yet.' ?>
    </p>
  </div>
  <form method="post" onsubmit="return confirm('Email every subscribed user about this article now?');">
    <input type="hidden" name="id" value="<?= (int)$article['id'] ?>">
    <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
    <input type="hidden" name="action" value="notify_subscribers">
    <button type="submit" class="btn-secondary"><?= !empty($article['notified_at']) ? 'Notify Again' : 'Notify Subscribers' ?></button>
  </form>
</div>
<?php endif; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="id" value="<?= (int)$article['id'] ?>">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <div class="form-grid-2">
    <label>Title
      <input type="text" name="title" value="<?= e($article['title']) ?>" required>
    </label>
    <label>Slug <span class="hint">(leave blank to auto-generate)</span>
      <input type="text" name="slug" value="<?= e($article['slug']) ?>" placeholder="e.g. cri">
    </label>
  </div>

  <div class="form-grid-2">
    <label>Image URL <span class="hint">(shown on the card + article header — 800×450 or any 16:9 crops cleanest)</span>
      <input type="url" name="image_url" id="image_url_field" value="<?= e($article['image_url'] ?? '') ?>"
             placeholder="https://.../cri-example.jpg" oninput="document.getElementById('image_url_preview').src=this.value">
    </label>
    <label>Preview
      <span class="image-preview">
        <img id="image_url_preview" src="<?= e($article['image_url'] ?? '') ?>" alt="" onerror="this.style.visibility='hidden'" onload="this.style.visibility='visible'">
      </span>
    </label>
  </div>

  <div class="form-grid-3">
    <label>Tag
      <input type="text" name="tag" value="<?= e($article['tag']) ?>" placeholder="Colour / Comfort / Health / Standard / Guide">
    </label>
    <label>Fallback icon <span class="hint">(only used while Image URL above is empty)</span>
      <select name="icon">
        <?php foreach ($icons as $ic): ?>
          <option value="<?= e($ic) ?>" <?= $article['icon'] === $ic ? 'selected' : '' ?>><?= e($ic) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Sort order
      <input type="number" name="sort_order" value="<?= (int)$article['sort_order'] ?>">
    </label>
  </div>

  <label>Excerpt <span class="hint">(shown on the article card, keep it short)</span>
    <textarea name="excerpt" rows="2" required><?= e($article['excerpt']) ?></textarea>
  </label>

  <label>Intro <span class="hint">(main definition paragraph)</span>
    <textarea name="intro" rows="4" required><?= e($article['intro']) ?></textarea>
  </label>

  <label>Why it matters
    <textarea name="why_text" rows="3"><?= e($article['why_text']) ?></textarea>
  </label>

  <h3>The Science <span class="hint" style="font-weight:400;">(shown as a tabbed "deep dive" on the article page — leave any blank to hide that tab)</span></h3>

  <label>Physical mechanism <span class="hint">(the underlying physics / LED engineering)</span>
    <textarea name="physical_text" rows="4"><?= e($article['physical_text']) ?></textarea>
  </label>

  <div class="form-grid-2">
    <label>Formula <span class="hint">(optional, e.g. "E = I / d² × cos(θ)")</span>
      <input type="text" name="formula_text" value="<?= e($article['formula_text']) ?>">
    </label>
    <label>Formula note <span class="hint">(explain the variables)</span>
      <input type="text" name="formula_note" value="<?= e($article['formula_note']) ?>">
    </label>
  </div>

  <div class="form-grid-2">
    <label>Physiological impact
      <textarea name="physio_text" rows="4"><?= e($article['physio_text']) ?></textarea>
    </label>
    <label>Psychological impact
      <textarea name="psycho_text" rows="4"><?= e($article['psycho_text']) ?></textarea>
    </label>
  </div>

  <div class="form-grid-2">
    <label>Full simulator link <span class="hint">(e.g. the SChools tool)</span>
      <input type="url" name="simulator_url" value="<?= e($article['simulator_url']) ?>">
    </label>
    <label>Simulator button label
      <input type="text" name="simulator_label" value="<?= e($article['simulator_label']) ?>">
    </label>
  </div>

  <label class="checkbox-label">
    <input type="checkbox" name="is_published" <?= $article['is_published'] ? 'checked' : '' ?>> Published (visible on the public site)
  </label>

  <h3>Recommended range table</h3>
  <p class="hint">One row per Stage / Environment combination, e.g. "Secondary" / "Laboratory" / "5000–6500K" / "Optimal for alertness".</p>

  <table class="repeat-table" id="rangeTable">
    <thead><tr><th>Stage</th><th>Environment</th><th>Recommended range</th><th>Notes</th><th></th></tr></thead>
    <tbody>
      <?php if ($ranges): foreach ($ranges as $r): ?>
      <tr>
        <td><input type="text" name="range_stage[]" value="<?= e($r['stage_label']) ?>"></td>
        <td><input type="text" name="range_env[]" value="<?= e($r['environment_label']) ?>"></td>
        <td><input type="text" name="range_value[]" value="<?= e($r['range_text']) ?>"></td>
        <td><input type="text" name="range_notes[]" value="<?= e($r['notes'] ?? '') ?>"></td>
        <td><button type="button" class="link-danger remove-row">✕</button></td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td><input type="text" name="range_stage[]"></td>
        <td><input type="text" name="range_env[]"></td>
        <td><input type="text" name="range_value[]"></td>
        <td><input type="text" name="range_notes[]"></td>
        <td><button type="button" class="link-danger remove-row">✕</button></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
  <button type="button" id="addRangeRow" class="btn-secondary">+ Add row</button>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Article</button>
    <a href="articles" class="btn-secondary">Cancel</a>
  </div>
</form>

<script>
document.getElementById('addRangeRow').addEventListener('click', () => {
  const tbody = document.querySelector('#rangeTable tbody');
  const row = tbody.rows[0].cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  tbody.appendChild(row);
});
document.getElementById('rangeTable').addEventListener('click', (e) => {
  if (e.target.classList.contains('remove-row')) {
    const tbody = document.querySelector('#rangeTable tbody');
    if (tbody.rows.length > 1) e.target.closest('tr').remove();
  }
});
</script>

<?php include __DIR__ . '/partials_footer.php'; ?>
