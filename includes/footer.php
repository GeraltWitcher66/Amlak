<?php
/** فوتر مشترک صفحات عمومی — بعد از محتوای صفحه require می‌شود. */
$agent ??= agent();
?>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <a class="brand" href="index.php">
          <span class="brand__mark">K</span>
          <span class="brand__text">
            <span class="brand__name">کیانی املاک</span>
            <span class="brand__tag">ANTALYA · ALANYA</span>
          </span>
        </a>
        <p class="footer__about">
          ویترین دیجیتال معرفی املاک ساحلی ترکیه، ساخته‌شده بر پایه اعتماد، شفافیت قیمت و پشتیبانی شخصی
          از اولین تماس تا تحویل کلید.
        </p>
        <div class="footer__social">
          <a href="#" aria-label="اینستاگرام">IG</a>
          <a href="#" aria-label="تلگرام">TG</a>
          <a href="#" aria-label="واتس‌اپ">WA</a>
          <a href="#" aria-label="لینکدین">IN</a>
        </div>
      </div>

      <div>
        <h4>دسترسی سریع</h4>
        <ul class="footer__links">
          <li><a href="index.php">صفحه اصلی</a></li>
          <li><a href="properties.php">همه املاک</a></li>
          <li><a href="index.php#about">درباره من</a></li>
          <li><a href="admin.php">پنل مدیریت</a></li>
        </ul>
      </div>

      <div>
        <h4>دسته‌بندی املاک</h4>
        <ul class="footer__links">
          <li><a href="properties.php?type=<?= e(rawurlencode('ویلا')) ?>">ویلا</a></li>
          <li><a href="properties.php?type=<?= e(rawurlencode('آپارتمان')) ?>">آپارتمان</a></li>
          <li><a href="properties.php?type=<?= e(rawurlencode('پنت‌هاوس')) ?>">پنت‌هاوس</a></li>
          <li><a href="properties.php?type=<?= e(rawurlencode('زمین')) ?>">زمین</a></li>
        </ul>
      </div>

      <div>
        <h4>تماس</h4>
        <ul class="footer__contact">
          <li><span>⌖</span><span><?= e($agent['address']) ?></span></li>
          <li><span>✆</span><a class="num" href="tel:<?= e($agent['phoneHref']) ?>"><?= e(fa_phone($agent['phone'])) ?></a></li>
          <li><span>✉</span><a href="mailto:<?= e($agent['email']) ?>"><?= e($agent['email']) ?></a></li>
          <li><span>◷</span><span>شنبه تا پنجشنبه، ۹ صبح تا ۷ عصر</span></li>
        </ul>
      </div>
    </div>

    <div class="footer__bottom">
      <span>© ۱۴۰۴ کیانی املاک — تمام حقوق محفوظ است.</span>
      <span>طراحی شده با تمرکز بر تجربه موبایل و سرعت بارگذاری</span>
    </div>
  </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
