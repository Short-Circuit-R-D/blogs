<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO role_permissions (role, can_post_topics, can_moderate_topics, auto_publish_topics)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE can_post_topics = VALUES(can_post_topics),
             can_moderate_topics = VALUES(can_moderate_topics), auto_publish_topics = VALUES(auto_publish_topics)'
    );
    foreach (['client', 'employee', 'leader'] as $role) {
        // The `admin` row is intentionally never editable from this form —
        // full admin / high board always keeps every permission, enforced
        // in code (see roleCan()/getRolePermissions() in functions.php)
        // regardless of what's stored, so it can never be locked out.
        $stmt->execute([
            $role,
            isset($_POST["post_$role"]) ? 1 : 0,
            isset($_POST["moderate_$role"]) ? 1 : 0,
            isset($_POST["auto_$role"]) ? 1 : 0,
        ]);
    }
    $saved = true;
}

$perms = getRolePermissions();

$pageTitle = 'Roles & Permissions';
$activeNav = 'roles';
include __DIR__ . '/partials_header.php';
?>
<p class="page-sub">Control what each account role can do — nothing here needs a code change. Full Admin / High Board always has every permission and can't be edited away from this screen.</p>

<?php if ($saved): ?><div class="alert-ok">Permissions saved.</div><?php endif; ?>

<form method="post" class="admin-form" style="max-width:900px;">
  <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

  <table class="admin-table">
    <thead>
      <tr>
        <th>Role</th>
        <th>Can post a new topic</th>
        <th>Can moderate (accept/reject) topics</th>
        <th>Topics auto-publish (skip review)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (['client', 'employee', 'leader', 'admin'] as $role): $p = $perms[$role]; $editable = $role !== 'admin'; ?>
      <tr>
        <td><strong><?= e(roleLabel($role)) ?></strong></td>
        <td>
          <label class="checkbox-label">
            <input type="checkbox" name="post_<?= $role ?>" <?= $p['can_post_topics'] ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>>
          </label>
        </td>
        <td>
          <label class="checkbox-label">
            <input type="checkbox" name="moderate_<?= $role ?>" <?= $p['can_moderate_topics'] ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>>
          </label>
        </td>
        <td>
          <label class="checkbox-label">
            <input type="checkbox" name="auto_<?= $role ?>" <?= $p['auto_publish_topics'] ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>>
          </label>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="hint">
    Tip: turn on "Can moderate" for <strong>Leader</strong> so leaders can accept/reject client and employee topics themselves
    (they'll see a Moderate link in the site nav). Leave it off and only Full Admin / High Board can accept posts, from this dashboard's
    <a href="topics">Community Topics</a> screen. You can also mark an individual account as always-auto-publish from
    <a href="users">Users</a>, regardless of its role.
  </p>

  <div class="form-actions">
    <button type="submit" class="btn-primary">Save Permissions</button>
  </div>
</form>

<?php include __DIR__ . '/partials_footer.php'; ?>
