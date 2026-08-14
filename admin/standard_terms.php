<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $params = $_POST['parameter'] ?? [];
    $en     = $_POST['en_12464'] ?? [];
    $iso    = $_POST['iso_8995'] ?? [];
    $ansi   = $_POST['ansi_ies'] ?? [];
    $well   = $_POST['well_v2'] ?? [];

    $rows = [];
    for ($i = 0; $i < count($params); $i++) {
        $p = trim($params[$i]);
        if ($p === '') continue; // skip blank rows
        $rows[] = [
            'parameter' => $p,
            'en_12464'  => trim($en[$i] ?? ''),
            'iso_8995'  => trim($iso[$i] ?? ''),
            'ansi_ies'  => trim($ansi[$i] ?? ''),
            'well_v2'   => trim($well[$i] ?? ''),
        ];
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM standard_terms');
            $insert = $pdo->prepare('INSERT INTO standard_terms (parameter, en_12464, iso_8995, ansi_ies, well_v2, is_published, sort_order) VALUES (?,?,?,?,?,?,?)');
            foreach ($rows as $i => $r) {
                $insert->execute([$r['parameter'], $r['en_12464'], $r['iso_8995'], $r['ansi_ies'], $r['well_v2'], 1, $i]);
            }
            $pdo->commit();
            redirect('standard_terms.php?saved=1');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'Save failed: ' . $ex->getMessage();
        }
    }
}

$terms = db()->query('SELECT * FROM standard_terms ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Terminology Matrix';
$activeNav = 'standard_terms';
include __DIR__ . '/partials_header.php';
?>

<p class="page-sub">How each parameter is named across the major frameworks (EN 12464-1, ISO 8995-1 / CIE S 008, ANSI / IES, WELL v2). Shown as one comparison table near the Standards section on the public page.</p>

<?php if (isset($_GET['saved'])): ?><div class="alert alert-ok">Saved.</div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" class="admin-form">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <table class="repeat-table" id="termsTable">
    <thead><tr><th>Parameter</th><th>EN 12464-1</th><th>ISO 8995-1 / CIE S 008</th><th>ANSI / IES</th><th>WELL v2</th><th></th></tr></thead>
    <tbody>
      <?php if ($terms): foreach ($terms as $t): ?>
      <tr>
        <td><input type="text" name="parameter[]" value="<?= e($t['parameter']) ?>" placeholder="e.g. CCT"></td>
        <td><input type="text" name="en_12464[]" value="<?= e($t['en_12464'] ?? '') ?>"></td>
        <td><input type="text" name="iso_8995[]" value="<?= e($t['iso_8995'] ?? '') ?>"></td>
        <td><input type="text" name="ansi_ies[]" value="<?= e($t['ansi_ies'] ?? '') ?>"></td>
        <td><input type="text" name="well_v2[]" value="<?= e($t['well_v2'] ?? '') ?>"></td>
        <td><button type="button" class="link-danger remove-row">✕</button></td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td><input type="text" name="parameter[]" placeholder="e.g. CCT"></td>
        <td><input type="text" name="en_12464[]"></td>
        <td><input type="text" name="iso_8995[]"></td>
        <td><input type="text" name="ansi_ies[]"></td>
        <td><input type="text" name="well_v2[]"></td>
        <td><button type="button" class="link-danger remove-row">✕</button></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
  <button type="button" id="addTermRow" class="btn-secondary">+ Add row</button>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Matrix</button>
  </div>
</form>

<script>
document.getElementById('addTermRow').addEventListener('click', () => {
  const tbody = document.querySelector('#termsTable tbody');
  const row = tbody.rows[0].cloneNode(true);
  row.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
  row.querySelectorAll('input[type="checkbox"]').forEach(i => i.checked = true);
  tbody.appendChild(row);
});
document.getElementById('termsTable').addEventListener('click', (e) => {
  if (e.target.classList.contains('remove-row')) {
    const tbody = document.querySelector('#termsTable tbody');
    if (tbody.rows.length > 1) e.target.closest('tr').remove();
  }
});
</script>

<?php include __DIR__ . '/partials_footer.php'; ?>
