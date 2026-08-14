<?php
require_once __DIR__ . '/../includes/auth_user.php';

requireUserLogin();
$user = currentUser();

$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();
if (!$row) redirect('logout.php');

$saved = false;
$error = null;
$phoneDial = '20';
$phoneNumber = '';
if (!empty($row['phone'])) {
    $parts = splitPhone($row['phone']);
    $phoneDial = $parts['dial'];
    $phoneNumber = $parts['number'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    userCsrfCheck();
    $subscribe = isset($_POST['subscribe']) ? 1 : 0;
    $phoneDial = preg_replace('/\D+/', '', (string)($_POST['phone_cc'] ?? '20')) ?: '20';
    $phoneNumber = ltrim(preg_replace('/\D+/', '', (string)($_POST['phone_number'] ?? '')), '0');
    $phone = formatPhoneE164($phoneDial, $phoneNumber);
    $country = phoneCountryByDial($phoneDial);

    if ($phoneNumber !== '' && $phone === false) {
        $error = $country
            ? 'Check the mobile number: ' . phoneLengthHint($country)
            : 'Choose a country code and enter a valid mobile number.';
        $row['profession'] = $_POST['profession'] ?? ($row['profession'] ?? '');
        $row['profession_other'] = $_POST['profession_other'] ?? ($row['profession_other'] ?? '');
        $row['company'] = $_POST['company'] ?? ($row['company'] ?? '');
    } else {
        $phone = $phone ?: null;
        if (($row['role'] ?? '') === 'employee') {
            $professionParsed = [
                'profession' => $row['profession'] ?? null,
                'profession_other' => $row['profession_other'] ?? null,
                'error' => null,
            ];
        } elseif (trim((string)($_POST['profession'] ?? '')) === '') {
            $professionParsed = [
                'profession' => $row['profession'] ?? null,
                'profession_other' => $row['profession_other'] ?? null,
                'error' => null,
            ];
        } else {
            $professionParsed = parseSubscribeProfession($_POST);
        }
        if ($professionParsed['error']) {
            $error = $professionParsed['error'];
        } else {
            $company = substr(trim($_POST['company'] ?? ''), 0, 160);
            $company = $company !== '' ? $company : null;
            $stmt = db()->prepare('UPDATE users SET is_subscribed = ?, phone = ?, profession = ?, profession_other = ?, company = ? WHERE id = ?');
            $stmt->execute([
                $subscribe, $phone,
                $professionParsed['profession'], $professionParsed['profession_other'],
                $company, $user['id'],
            ]);
            $row['is_subscribed'] = $subscribe;
            $row['phone'] = $phone;
            $row['profession'] = $professionParsed['profession'];
            $row['profession_other'] = $professionParsed['profession_other'];
            $row['company'] = $company;
            $saved = true;
        }
    }
}

$followedTopics = getUserSubscribedTopics((int)$user['id']);
$allTopics      = getArticleTopics();

$canPost = roleCan($row['role'], 'can_post_topics');
$canModerate = roleCan($row['role'], 'can_moderate_topics');
$autoPublish = userAutoPublishes($row);

$myTopics = db()->prepare('SELECT * FROM discussion_topics WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$myTopics->execute([$row['id']]);
$myTopics = $myTopics->fetchAll();

$statusBadge = [
    'pending'  => '<span class="badge-pending">Pending review</span>',
    'approved' => '<span class="badge-ok">Approved</span>',
    'rejected' => '<span class="badge-off">Rejected</span>',
];

$pageTitle = 'My Account';
$pageRobots = 'noindex, nofollow';
include __DIR__ . '/partials_header.php';
?>
<div class="wrap section auth-section" style="border-top:none;">
  <div class="auth-card" style="max-width:640px;">
    <p class="eyebrow">Account · <?= e(roleLabel($row['role'])) ?></p>
    <h2 class="section-title"><?= e($row['name']) ?></h2>
    <p class="section-sub"><?= e($row['email']) ?><?php
      if (($row['role'] ?? '') !== 'employee') {
          $profLabel = subscribeProfessionLabel($row['profession'] ?? null, $row['profession_other'] ?? null);
          echo $profLabel ? ' · ' . e($profLabel) : '';
      }
      echo !empty($row['company']) ? ' · ' . e($row['company']) : '';
    ?></p>
    <?php if (($row['role'] ?? '') === 'employee'): ?>
      <div class="alert alert-success">Signed in with Short Circuit credentials. Staff features follow your Employee (SC) role.</div>
    <?php endif; ?>
    <?php if ($saved): ?><div class="alert alert-success">Preferences saved.</div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate class="js-phone-form">
      <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
      <?php if (($row['role'] ?? '') === 'employee'): ?>
      <label>Role
        <input type="text" value="Employee (SC)" disabled>
      </label>
      <?php else: ?>
      <label>Role
        <select name="profession" id="professionSelect">
          <option value="" disabled <?= empty($row['profession']) ? 'selected' : '' ?>>Select your role</option>
          <?php foreach (subscribeProfessions() as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($row['profession'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="js-profession-other"<?= ($row['profession'] ?? '') === 'other' ? '' : ' hidden' ?>>
        Specify role
        <input type="text" name="profession_other" value="<?= e((string)($row['profession_other'] ?? '')) ?>" maxlength="120" placeholder="Specify role">
      </label>
      <?php endif; ?>
      <label>Company <span class="hint">(optional)</span>
        <input type="text" name="company" value="<?= e((string)($row['company'] ?? '')) ?>" maxlength="160" placeholder="please enter company name">
      </label>
      <?php include __DIR__ . '/partials_phone_field.php'; ?>
      <label class="checkbox-row">
        <input type="checkbox" name="subscribe" <?= $row['is_subscribed'] ? 'checked' : '' ?>>
        <span>Email me when any new lighting guide is published</span>
      </label>
      <button type="submit" class="auth-submit">Save Preferences</button>
    </form>

    <div class="account-topics">
      <p class="eyebrow" style="margin-top:var(--space-lg);">Community Topics</p>
      <?php if ($canPost): ?>
        <p class="hint" style="margin:0 0 var(--space-sm);">
          <?= $autoPublish
                ? 'Your topics publish immediately — no review needed.'
                : 'New topics are reviewed before they go live on the Community page.' ?>
        </p>
        <a href="topic_new.php" class="auth-submit" style="display:inline-block;width:auto;text-decoration:none;padding:10px 18px;">+ Post a New Topic</a>
      <?php else: ?>
        <p class="hint" style="margin:0 0 var(--space-sm);">Your role doesn't currently have posting enabled. Contact a Short Circuit admin if you think this is wrong.</p>
      <?php endif; ?>
      <?php if ($canModerate): ?>
        <p class="hint" style="margin:var(--space-sm) 0 0;"><a href="moderate.php">Go to the moderation queue →</a></p>
      <?php endif; ?>

      <?php if ($myTopics): ?>
      <table class="my-topics-table">
        <thead><tr><th>Title</th><th>Status</th><th>Submitted</th></tr></thead>
        <tbody>
          <?php foreach ($myTopics as $t): ?>
          <tr>
            <td><?= $t['status'] === 'approved' ? '<a href="topic.php?slug=' . e($t['slug']) . '">' . e($t['title']) . '</a>' : e($t['title']) ?>
              <?php if ($t['status'] === 'rejected' && $t['reject_reason']): ?>
                <div class="hint"><?= e($t['reject_reason']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= $statusBadge[$t['status']] ?? e($t['status']) ?></td>
            <td class="hint"><?= e(date('M j, Y', strtotime($t['created_at']))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <div class="account-topics">
      <p class="eyebrow" style="margin-top:var(--space-lg);">Topics You Follow</p>
      <?php if (!$followedTopics): ?>
        <p class="hint" style="margin:0 0 var(--space-sm);">Not following any topic yet — follow one from an article page or the Articles list to get emailed only about that subject.</p>
      <?php endif; ?>
      <div class="topic-chip-row">
        <?php foreach ($allTopics as $t): $isFollowed = in_array($t, $followedTopics, true); ?>
          <form method="post" action="topic_subscribe.php" class="topic-follow-form">
            <input type="hidden" name="topic" value="<?= e($t) ?>">
            <input type="hidden" name="return" value="account.php">
            <input type="hidden" name="csrf" value="<?= e(userCsrfToken()) ?>">
            <button type="submit" class="topic-chip topic-chip-toggle<?= $isFollowed ? ' active' : '' ?>">
              <?= e($t) ?><?= $isFollowed ? ' ✕' : '' ?>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    </div>

    <p class="auth-switch"><a href="logout.php">Log out</a></p>
  </div>
</div>
<?php include __DIR__ . '/partials_footer.php'; ?>
