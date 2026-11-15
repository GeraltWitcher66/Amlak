<?php
declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$agent = agent();

// فیلترهای دریافتی از URL
$f = [
    'q'     => trim((string) ($_GET['q'] ?? '')),
    'type'  => trim((string) ($_GET['type'] ?? '')),
    'area'  => trim((string) ($_GET['area'] ?? '')),
    'price' => trim((string) ($_GET['price'] ?? '')),
    'size'  => trim((string) ($_GET['size'] ?? '')),
    'sort'  => trim((string) ($_GET['sort'] ?? '')),
];

$result = query_properties($f);
$items  = $result['items'];
$total  = $result['total'];

$sent = ($_GET['sent'] ?? '') === '1';
$err  = ($_GET['err'] ?? '') === '1';

// آدرس بازگشت برای فرم درخواست (با همان فیلترها)
$back = '/properties.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
$back = preg_replace('/[?&]sent=[^&]*/', '', $back) ?? $back;
$back = preg_replace('/[?&]err=[^&]*/', '', $back) ?? $back;
$back = preg_replace('/\?$/', '', $back) ?? $back;

$pageTitle = 'املاک | جست‌وجوی ویلا، آپارتمان و زمین در آنتالیا و آلانیا';
$metaDesc  = 'جست‌وجو و فیلتر املاک بر اساس نوع ملک، محدوده، قیمت و متراژ؛ ویلا، آپارتمان ساحلی، پنت‌هاوس و زمین در آنتالیا و آلانیا.';
$activeNav = 'properties';
$ctaHref   = 'index.php#contact';

require __DIR__ . '/includes/header.php';
?>

  <section class="page-hero">
    <div class="page-hero__bg">
      <img src="https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=1800&q=80" alt="" width="1800" height="1200">
    </div>
    <div class="container">
      <ol class="breadcrumb"><li><a href="index.php">خانه</a></li><li>املاک</li></ol>
      <h1>جست‌وجوی املاک ساحلی</h1>
      <p>بر اساس نوع ملک، محدوده، قیمت و متراژ فیلتر کنید؛ همه فایل‌ها بازدید حضوری و مستندات کامل دارند.</p>
    </div>
  </section>

  <section class="listing">
    <div class="container">
      <form id="filter-form" method="get" action="properties.php">
        <!-- filters -->
        <div class="filters">
          <div class="filters__top">
            <div class="search">
              <span class="search__icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
              <input id="f-q" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="جست‌وجو در نام، منطقه یا نوع ملک…">
            </div>
            <div class="chips" id="quick-chips">
              <button class="chip<?= $f['type'] === '' ? ' is-active' : '' ?>" type="button" data-type="">همه</button>
              <?php foreach (types() as $t) { ?>
              <button class="chip<?= $f['type'] === $t ? ' is-active' : '' ?>" type="button" data-type="<?= e($t) ?>"><?= e($t) ?></button>
              <?php } ?>
            </div>
          </div>

          <div class="filters__grid">
            <div class="field field--select">
              <label for="f-type">نوع ملک</label>
              <select id="f-type" name="type">
                <option value="">همه انواع</option>
                <?php foreach (types() as $t) { ?>
                <option value="<?= e($t) ?>"<?= $f['type'] === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                <?php } ?>
              </select>
            </div>

            <div class="field field--select">
              <label for="f-area">محدوده</label>
              <select id="f-area" name="area">
                <option value="">همه مناطق</option>
                <?php foreach (areas() as $a) { ?>
                <option value="<?= e($a) ?>"<?= $f['area'] === $a ? ' selected' : '' ?>><?= e($a) ?></option>
                <?php } ?>
              </select>
            </div>

            <div class="field field--select">
              <label for="f-price">محدوده قیمت</label>
              <select id="f-price" name="price">
                <option value="">همه قیمت‌ها</option>
                <option value="lt300"<?= $f['price'] === 'lt300' ? ' selected' : '' ?>>کمتر از ۳۰۰ هزار دلار</option>
                <option value="300-600"<?= $f['price'] === '300-600' ? ' selected' : '' ?>>۳۰۰ تا ۶۰۰ هزار دلار</option>
                <option value="600-1000"<?= $f['price'] === '600-1000' ? ' selected' : '' ?>>۶۰۰ هزار تا ۱ میلیون دلار</option>
                <option value="gt1000"<?= $f['price'] === 'gt1000' ? ' selected' : '' ?>>بالای ۱ میلیون دلار</option>
              </select>
            </div>

            <div class="field field--select">
              <label for="f-size">متراژ</label>
              <select id="f-size" name="size">
                <option value="">همه متراژها</option>
                <option value="lt100"<?= $f['size'] === 'lt100' ? ' selected' : '' ?>>کمتر از ۱۰۰ متر</option>
                <option value="100-250"<?= $f['size'] === '100-250' ? ' selected' : '' ?>>۱۰۰ تا ۲۵۰ متر</option>
                <option value="gt250"<?= $f['size'] === 'gt250' ? ' selected' : '' ?>>بالای ۲۵۰ متر</option>
              </select>
            </div>
          </div>

          <div class="filters__foot">
            <span class="muted" style="font-size:14px"><?= fa_num($total) ?> فایل فعال در آنتالیا و آلانیا</span>
            <a class="link-arrow" id="reset-filters" href="properties.php">پاک کردن فیلترها</a>
          </div>
        </div>

        <!-- results -->
        <div class="result-bar">
          <div class="result-bar__count"><b id="result-count"><?= fa_num($total) ?></b> ملک یافت شد</div>
          <div class="field field--select" style="min-width:200px">
            <select id="f-sort" name="sort" class="sort-select" aria-label="مرتب‌سازی">
              <option value="default"<?= $f['sort'] === 'default' || $f['sort'] === '' ? ' selected' : '' ?>>پیش‌فرض</option>
              <option value="price-asc"<?= $f['sort'] === 'price-asc' ? ' selected' : '' ?>>ارزان‌ترین</option>
              <option value="price-desc"<?= $f['sort'] === 'price-desc' ? ' selected' : '' ?>>گران‌ترین</option>
              <option value="size-desc"<?= $f['sort'] === 'size-desc' ? ' selected' : '' ?>>بزرگ‌ترین متراژ</option>
            </select>
          </div>
        </div>
      </form>

      <!-- results grid -->
      <div class="grid grid--3" id="listing-grid"<?= $total === 0 ? ' hidden' : '' ?>>
        <?php foreach ($items as $p) { echo property_card($p); } ?>
      </div>

      <div class="empty" id="empty-state"<?= $total === 0 ? '' : ' hidden' ?>>
        <h3>ملکی با این فیلترها پیدا نشد</h3>
        <p>فیلترها را باز کنید یا اجازه دهید با بودجه شما فایل جست‌وجو کنم.</p>
        <a class="btn btn--primary" href="index.php#contact">درخواست فایل اختصاصی</a>
      </div>
    </div>
  </section>

  <!-- inline CTA -->
  <section class="section--tight" style="padding-bottom:96px">
    <div class="container">
      <div class="cta reveal" style="padding:44px 36px">
        <div class="cta__bg">
          <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1600&q=80" alt="" loading="lazy" width="1600" height="1066">
        </div>
        <div class="cta__grid">
          <div>
            <span class="eyebrow" style="color:#fff">فایل سفارشی</span>
            <h2>بودجه و خواسته‌تان را بگویید، فایل را من پیدا می‌کنم.</h2>
            <p>گاهی بهترین گزینه‌ها در سایت نیستند؛ با یک تماس کوتاه فهرستی از ملک‌های خارج از ویترین برایتان می‌فرستم.</p>
          </div>
          <form class="form-card" method="post" action="leads.php">
            <h3>دریافت فایل اختصاصی</h3>

            <input type="hidden" name="back" value="<?= e($back) ?>">
            <input type="hidden" name="source" value="listing">

            <div class="form-grid">
              <div class="form-row">
                <div class="field">
                  <label for="l-name">نام</label>
                  <input id="l-name" name="name" type="text" placeholder="نام شما" required>
                </div>
                <div class="field">
                  <label for="l-phone">شماره تماس</label>
                  <input id="l-phone" name="phone" type="tel" placeholder="۰۹۱۲ ۰۰۰ ۰۰۰۰" required>
                </div>
              </div>
              <button class="btn btn--primary btn--block" type="submit">ارسال درخواست</button>
              <div class="form-success<?= ($sent || $err) ? ' is-on' : '' ?>">
                <?= $sent ? '✓ ثبت شد؛ فایل‌های منتخب تا ۲۴ ساعت دیگر ارسال می‌شود.' : ($err ? '✗ فرم کامل نشد؛ نام و شماره تماس را بررسی کنید.' : '✓ ثبت شد؛ فایل‌های منتخب تا ۲۴ ساعت دیگر ارسال می‌شود.') ?>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>
