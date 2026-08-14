<?php
/**
 * Shared name / email / profession / company / phone fields for
 * subscribe and create-account. SC Employee is never listed here.
 *
 * @var string $name
 * @var string $email
 * @var string $profession
 * @var string $professionOther
 * @var string|null $company
 */
?>
<label>Name
  <input type="text" name="name" value="<?= e($name) ?>" required autofocus>
</label>
<label>Email
  <input type="email" name="email" value="<?= e($email) ?>" required>
</label>
<label>Role
  <select name="profession" id="professionSelect" required>
    <option value="" disabled <?= $profession === '' ? 'selected' : '' ?>>Select your role</option>
    <?php foreach (subscribeProfessions() as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $profession === $key ? 'selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>
</label>
<label class="js-profession-other"<?= $profession === 'other' ? '' : ' hidden' ?>>
  Specify role
  <input type="text" name="profession_other" value="<?= e($professionOther) ?>" maxlength="120" placeholder="Specify role">
</label>
<label>Company <span class="hint">(optional)</span>
  <input type="text" name="company" value="<?= e((string)$company) ?>" maxlength="160" placeholder="please enter company name">
</label>
<?php include __DIR__ . '/partials_phone_field.php'; ?>
