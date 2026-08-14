<?php
/**
 * Optional mobile field: country-code select + national number.
 *
 * @var string $phoneDial
 * @var string $phoneNumber
 */
$phoneDial   = $phoneDial ?: '20';
$phoneNumber = $phoneNumber ?? '';
$selectedCountry = phoneCountryByDial($phoneDial) ?: countryCallingCodes()[0];
?>
<label class="phone-field">Mobile number <span class="hint">(optional)</span>
  <div class="phone-row">
    <select name="phone_cc" aria-label="Country code">
      <?php foreach (countryCallingCodes() as $c): ?>
        <option value="<?= e($c['dial']) ?>"
                data-min="<?= (int)$c['min'] ?>"
                data-max="<?= (int)$c['max'] ?>"
                data-name="<?= e($c['name']) ?>"
                <?= $phoneDial === $c['dial'] ? 'selected' : '' ?>>
          <?= e($c['name']) ?> +<?= e($c['dial']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="tel" name="phone_number" value="<?= e($phoneNumber) ?>"
           inputmode="numeric" autocomplete="tel-national"
           placeholder="1xxxxxxxxx" maxlength="<?= (int)$selectedCountry['max'] ?>">
  </div>
  <span class="phone-len-hint hint"><?= e(phoneLengthHint($selectedCountry)) ?></span>
</label>
