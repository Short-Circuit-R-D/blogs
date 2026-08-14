<div class="footer">
  <div class="footer-inner">
    <img src="https://shortcircuit.company/assets/img/logo-dark.svg" alt="Short Circuit Company" style="height:22px;width:auto;opacity:.85;">
    <p>© <?= date('Y') ?> Short Circuit Company — Lighting Standards Reference</p>
  </div>
</div>
<?php
if (!function_exists('seoMarketingBodyHtml')) {
    require_once __DIR__ . '/../includes/seo.php';
}
echo seoMarketingBodyHtml();
?>
<script src="assets/js/site.js"></script>
</body>
</html>
