<?php
/**
 * Standalone admin-audit log printer.
 * Not linked from the dashboard — open by filename only:
 *   /sc_security_log_printer.php
 * Password is AUDIT_PRINTER_PASSWORD in the project-root .env file.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/admin_audit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ensureAdminAuditSchema();

$expected = (string)($_ENV['AUDIT_PRINTER_PASSWORD'] ?? getenv('AUDIT_PRINTER_PASSWORD') ?: '');
$error = null;
$authed = !empty($_SESSION['audit_printer_ok']);

if (isset($_GET['logout'])) {
    unset($_SESSION['audit_printer_ok']);
    header('Location: sc_security_log_printer.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fails = (int)($_SESSION['audit_printer_fails'] ?? 0);
    $lockedUntil = (int)($_SESSION['audit_printer_lock'] ?? 0);
    if ($lockedUntil > time()) {
        $error = 'Too many attempts. Try again in a few minutes.';
    } elseif ($expected === '') {
        $error = 'AUDIT_PRINTER_PASSWORD is not set in .env.';
    } elseif (!hash_equals($expected, (string)($_POST['password'] ?? ''))) {
        $fails++;
        $_SESSION['audit_printer_fails'] = $fails;
        if ($fails >= 5) {
            $_SESSION['audit_printer_lock'] = time() + 900;
            $fails = 0;
            $_SESSION['audit_printer_fails'] = 0;
        }
        usleep(400000);
        $error = 'Incorrect password.';
        adminAuditLog('printer_login_fail', 'guest', 'Failed password on security log printer');
    } else {
        $_SESSION['audit_printer_ok'] = true;
        $_SESSION['audit_printer_fails'] = 0;
        unset($_SESSION['audit_printer_lock']);
        $authed = true;
        adminAuditLog('printer_login_ok', 'printer', 'Opened security log printer');
    }
}

$logs = [];
$files = [];
$selectedFile = '';
$fileBody = '';

if ($authed) {
    $selectedFile = basename((string)($_GET['file'] ?? ''));
    if ($selectedFile !== '' && !preg_match('/^admin-audit-\d{4}-\d{2}-\d{2}\.log$/', $selectedFile)) {
        $selectedFile = '';
    }

    try {
        $sql = 'SELECT * FROM admin_audit_logs';
        $params = [];
        if ($selectedFile !== '') {
            $sql .= ' WHERE log_file = ?';
            $params[] = $selectedFile;
        }
        $sql .= ' ORDER BY occurred_at DESC, id DESC LIMIT 500';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $files = db()->query(
            'SELECT log_file, COUNT(*) AS n, MIN(occurred_at) AS first_at, MAX(occurred_at) AS last_at
             FROM admin_audit_logs GROUP BY log_file ORDER BY log_file DESC'
        )->fetchAll();
    } catch (\Throwable $e) {
        $error = 'Could not read audit table. Run migration_009_admin_audit.sql.';
    }

    if ($selectedFile !== '') {
        $path = adminAuditLogDir() . DIRECTORY_SEPARATOR . $selectedFile;
        if (is_readable($path)) {
            $fileBody = (string)file_get_contents($path);
        }
    }
}

function printerDetailSummary(?string $json): string
{
    $data = json_decode((string)$json, true);
    if (!is_array($data)) {
        return '';
    }
    $summary = trim((string)($data['summary'] ?? ''));
    $post = $data['post'] ?? [];
    unset($post['csrf']);
    $bits = [];
    if ($summary !== '') $bits[] = $summary;
    if (is_array($post) && $post) {
        $bits[] = 'POST ' . json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    return implode(' — ', $bits);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security log printer</title>
<style>
  :root { --red:#eb1b26; --bg:#111; --card:#1c1c1c; --line:#333; --muted:#999; --text:#f3f3f3; }
  * { box-sizing: border-box; }
  body { margin:0; font-family: Segoe UI, sans-serif; background:var(--bg); color:var(--text); }
  .wrap { max-width: 1180px; margin: 0 auto; padding: 28px 18px 60px; }
  h1 { font-size: 22px; margin: 0 0 6px; letter-spacing: .04em; text-transform: uppercase; }
  .sub { color: var(--muted); margin: 0 0 22px; font-size: 13px; }
  .card { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 22px; max-width: 380px; }
  label { display:flex; flex-direction:column; gap:6px; font-size:13px; color:var(--muted); }
  input { font: inherit; padding: 10px 12px; border-radius: 6px; border: 1px solid var(--line); background:#111; color:var(--text); }
  button, .btn { font: inherit; background: var(--red); color:#fff; border:0; border-radius:6px; padding:10px 14px; cursor:pointer; text-decoration:none; display:inline-block; }
  button:hover, .btn:hover { filter: brightness(.92); }
  .alert { background:#3a1515; border:1px solid #7a2a2a; color:#f3b4b4; padding:10px 12px; border-radius:8px; margin-bottom:14px; font-size:13px; }
  .toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin: 0 0 16px; }
  .toolbar a { color:#ddd; font-size:13px; }
  .files { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
  .files a { font-size:12px; color:#ddd; border:1px solid var(--line); border-radius:20px; padding:4px 10px; text-decoration:none; }
  .files a.on { background: var(--red); border-color: var(--red); color:#fff; }
  table { width:100%; border-collapse: collapse; font-size:12px; background: var(--card); }
  th, td { text-align:left; padding:8px 10px; border-bottom:1px solid var(--line); vertical-align:top; }
  th { color: var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.04em; }
  .muted { color: var(--muted); }
  pre { white-space: pre-wrap; word-break: break-word; background:#0a0a0a; border:1px solid var(--line); padding:14px; border-radius:8px; font-size:11px; max-height: 420px; overflow:auto; }
  @media print {
    body { background:#fff; color:#000; }
    .no-print { display:none !important; }
    table, th, td, pre { background:#fff; color:#000; border-color:#ccc; }
  }
</style>
</head>
<body>
<div class="wrap">
  <h1>Security log printer</h1>
  <p class="sub">Admin page opens and actions — device, account, date, IP, location, log file.</p>

  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>

  <?php if (!$authed): ?>
    <form class="card" method="post" autocomplete="off">
      <label>Password from .env
        <input type="password" name="password" required autofocus>
      </label>
      <p style="margin:14px 0 0;"><button type="submit">Open logs</button></p>
    </form>
  <?php else: ?>
    <div class="toolbar no-print">
      <button type="button" onclick="window.print()">Print</button>
      <a class="btn" href="?logout=1" style="background:#333;">Lock</a>
      <span class="muted"><?= count($logs) ?> rows<?= $selectedFile ? ' in ' . e($selectedFile) : '' ?></span>
    </div>

    <div class="files no-print">
      <a href="sc_security_log_printer.php" class="<?= $selectedFile === '' ? 'on' : '' ?>">All files</a>
      <?php foreach ($files as $f): ?>
        <a href="?file=<?= e(urlencode($f['log_file'])) ?>" class="<?= $selectedFile === $f['log_file'] ? 'on' : '' ?>">
          <?= e($f['log_file']) ?> (<?= (int)$f['n'] ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Account</th>
          <th>Action</th>
          <th>Page</th>
          <th>IP</th>
          <th>Location</th>
          <th>Device</th>
          <th>Log file</th>
          <th>Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $row): ?>
        <tr>
          <td><?= e($row['occurred_at']) ?></td>
          <td><?= e($row['account']) ?></td>
          <td><?= e($row['action']) ?></td>
          <td><?= e($row['page']) ?></td>
          <td><?= e($row['ip']) ?></td>
          <td><?= e($row['location']) ?></td>
          <td><?= e($row['device']) ?></td>
          <td><?= e($row['log_file']) ?></td>
          <td><?= e(printerDetailSummary($row['details'] ?? '')) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
        <tr><td colspan="9" class="muted">No audit rows yet. Open /admin/ once, then refresh this page.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($selectedFile !== ''): ?>
      <h1 style="margin-top:28px;font-size:16px;">File <?= e($selectedFile) ?></h1>
      <pre><?= e($fileBody !== '' ? $fileBody : '(file missing on disk — database rows still listed above)') ?></pre>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
