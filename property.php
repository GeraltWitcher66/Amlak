<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$agent = agent();
$slug  = trim((string) ($_GET['id'] ?? ''));
$p     = $slug !== '' ? get_property($slug) : null;

$sent = ($_GET['sent'] ?? '') === '1';
$err  = ($_GET['err'] ?? '') === '1';

$activeNav = 'properties';
$ctaHref   = '#lead';
$ctaText   = 'هماهنگی بازدید';

if ($p === null) {
    http_response_code(404);
    $pageTitle = 'ملک یافت نشد | کیانی املاک';
    $metaDesc  = 'ملک موردنظر یافت نشد.';
    $robots    = 'noindex,follow';
    require __DIR__ . '/includes/header.php';
    ?>
  <section class="page-hero" style="padding-bottom:44px">
    <div class="container">
      <ol class="breadcrumb"><li><a href="index.php">خانه</a></li><li><a href="properties.php">املاک</a></li><li>یافت نشد</li></ol>
    </div>
  </section>
  <section class="section" style="padding-top:24px">
    <div class="container">
      <div class="empty">
        <h3>ملک موردنظر یافت نشد</h3>
        <p>ممکن است فایل حذف یا آدرس عوض شده باشد؛ فهرست به‌روز املاک را ببینید.</p>
        <a class="btn btn--primary" href="properties.php">مشاهده همه املاک</a>
      </div>
    </div>
  </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$images = $p['images'];
if ($images === []) {
    $images = ['https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1600&q=80'];
}
$similar  = similar_properties($p, 3);
$paragraphs = preg_split('/\n{2,}/u', $p['description']) ?: [];

$pageTitle = $p['title'] . ' | کیانی املاک';
$metaDesc  = $p['seo_description'] !== '' ? $p['seo_description'] : 'جزئیات ' . $p['type'] . ' در ' . $p['area'] . '، ' . $p['city'] . '؛ قیمت، مشخصات، امکانات و درخواست بازدید.';
$activeNav = 'properties';

/* ---------- سئو و داده ساختاریافته ملک ---------- */
$canonical = site_url('property.php?id=' . rawurlencode($p['slug']));
$ogImage   = abs_url(img_w($images[0], 1200));
$ogType    = 'product';

$jsonLd = [
    array_filter([
        '@type'         => 'RealEstateListing',
        'name'          => $p['title'],
        'description'   => $metaDesc,
        'url'           => $canonical,
        'image'         => array_map(static fn (string $im): string => abs_url(img_w($im, 1200)), array_slice($images, 0, 6)),
        'numberOfRooms' => $p['rooms'] ?: null,
        'floorSize'     => [
            '@type'    => 'QuantitativeValue',
            'value'    => $p['size'],
            'unitCode' => 'MTK',
        ],
        'address'       => [
            '@type'           => 'PostalAddress',
            'addressLocality' => $p['area'] . '، ' . $p['city'],
            'addressRegion'   => $p['city'],
            'addressCountry'  => 'TR',
        ],
        'offers'        => [
            '@type'         => 'Offer',
            'price'         => $p['price'],
            'priceCurrency' => 'USD',
            'availability'  => $p['status'] === 'فروش' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
            'url'           => $canonical,
        ],
    ], static fn ($v): bool => $v !== null),
    [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => site_url('index.php')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'املاک', 'item' => site_url('properties.php')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $p['title']],
        ],
    ],
];

require __DIR__ . '/includes/header.php';
?>

  <section class="page-hero" style="padding-bottom:44px">
    <div class="page-hero__bg">
      <img src="https://images.unsplash.com/photo-1583608205776-bfd35f0d9f83?auto=format&fit=crop&w=1800&q=80" alt="" width="1800" height="1200">
    </div>
    <div class="container">
      <ol class="breadcrumb">
        <li><a href="index.php">خانه</a></li>
        <li><a href="properties.php">املاک</a></li>
        <li id="crumb-prop"><?= e($p['title']) ?></li>
      </ol>
    </div>
  </section>

  <section class="detail">
    <div class="container">
      <!-- gallery -->
      <div class="gallery">
        <div class="gallery__main" id="gallery-main">
          <img id="gallery-main-img" src="<?= e(img_w($images[0], 1600)) ?>" alt="<?= e($p['title']) ?> — تصویر ۱" width="1600" height="1000">
          <button class="gallery__nav gallery__nav--prev" id="gallery-prev" type="button" aria-label="تصویر قبلی">›</button>
          <button class="gallery__nav gallery__nav--next" id="gallery-next" type="button" aria-label="تصویر بعدی">‹</button>
          <span class="gallery__count" id="gallery-count">۱ / <?= fa_num(count($images)) ?></span>
        </div>
        <div class="gallery__thumbs" id="gallery-thumbs">
          <?php foreach ($images as $i => $im) { ?>
          <button type="button" aria-label="تصویر <?= fa_num($i + 1) ?>" class="<?= $i === 0 ? 'is-active' : '' ?>" data-full="<?= e(img_w($im, 1600)) ?>">
            <img src="<?= e(img_w($im, 300)) ?>" alt="" loading="lazy">
          </button>
          <?php } ?>
        </div>
      </div>

      <!-- head -->
      <div class="detail__head">
        <div>
          <div class="prop-card__badges" style="position:static">
            <span class="pill pill--coral" id="d-type"><?= e($p['type']) ?></span>
            <span class="pill" id="d-badge"><?= e($p['badge'] !== '' ? $p['badge'] : $p['status']) ?></span>
          </div>
          <h1 id="d-title"><?= e($p['title']) ?></h1>
          <p class="detail__loc" id="d-loc"><?= ICO_PIN ?> <?= e($p['area']) ?>، <?= e($p['city']) ?>، ترکیه</p>
        </div>
        <div class="detail__price">
          <div class="price" id="d-price"><span dir="ltr"><?= e(fa_price($p['price'])) ?></span></div>
          <small class="muted">قیمت کل · قابل مذاکره</small>
          <a class="btn btn--primary" href="#lead">درخواست بازدید</a>
        </div>
      </div>

      <!-- specs -->
      <div class="specs" id="d-specs">
        <div class="spec"><span>متراژ</span><b><?= fa_num($p['size']) ?> متر</b></div>
        <div class="spec"><span>اتاق خواب</span><b><?= $p['rooms'] ? fa_num($p['rooms']) . ' خواب' : '—' ?></b></div>
        <div class="spec"><span>سرویس بهداشتی</span><b><?= $p['baths'] ? fa_num($p['baths']) . ' عدد' : '—' ?></b></div>
        <div class="spec"><span>سال ساخت</span><b><?= $p['year'] ? fa_num($p['year']) : '—' ?></b></div>
        <div class="spec"><span>نوع ملک</span><b><?= e($p['type']) ?></b></div>
      </div>

      <!-- body -->
      <div class="detail__body">
        <div>
          <div class="block">
            <h2 class="block__title">درباره این ملک</h2>
            <div class="prose" id="d-desc">
              <?php foreach ($paragraphs as $para) { if (trim($para) !== '') { ?><p><?= e($para) ?></p><?php } } ?>
            </div>
          </div>

          <div class="block">
            <h2 class="block__title">امکانات و ویژگی‌ها</h2>
            <ul class="features" id="d-features">
              <?php foreach ($p['features'] as $feat) { ?><li><?= e($feat) ?></li><?php } ?>
            </ul>
          </div>

          <div class="block">
            <h2 class="block__title">محاسبه بازده سرمایه‌گذاری</h2>
            <p class="muted" style="font-size:14px;margin:0">
              با اجاره تقریبی همین محدوده، درآمد سالانه و بازده خالص سرمایه را ببینید.
            </p>

            <div class="calc" id="roi-calc" data-price="<?= (int) $p['price'] ?>">
              <div class="calc__grid">
                <div class="field">
                  <label for="roi-rent">اجاره ماهانه (دلار)</label>
                  <input id="roi-rent" type="text" inputmode="numeric" placeholder="۱٬۲۰۰" autocomplete="off">
                </div>
                <div class="field">
                  <label for="roi-cost">هزینه سالانه (دلار)</label>
                  <input id="roi-cost" type="text" inputmode="numeric" placeholder="۴٬۰۰۰" autocomplete="off">
                </div>
                <div class="field">
                  <label for="roi-vacancy">خالی بودن سالانه (٪)</label>
                  <input id="roi-vacancy" type="text" inputmode="numeric" value="۱" autocomplete="off">
                </div>
              </div>

              <div class="calc__out" aria-live="polite">
                <div><span>درآمد ناخالص سالانه</span><b id="roi-gross" dir="ltr">—</b></div>
                <div><span>درآمد خالص سالانه</span><b id="roi-net" dir="ltr">—</b></div>
                <div><span>بازده خالص</span><b id="roi-yield" class="is-strong" dir="ltr">—</b></div>
              </div>

              <p class="calc__note">تخمین تقریبی بر اساس ورودی شماست؛ مالیات، هزینه انتقال سند و افزایش قیمت ملک در آن لحاظ نشده است.</p>
            </div>
          </div>

          <div class="block">
            <h2 class="block__title">موقعیت مکانی</h2>
            <div class="map">
              <img src="https://images.unsplash.com/photo-1524230572899-a752b3835840?auto=format&fit=crop&w=1400&q=80" alt="نقشه منطقه" loading="lazy" width="1400" height="600">
              <span class="map__pin" id="d-map-label"><?= e($p['area']) ?>، <?= e($p['city']) ?></span>
            </div>
            <p class="muted" style="font-size:14px;margin-top:14px">
              نشانگر دقیق ملک پس از هماهنگی بازدید ارسال می‌شود؛ نزدیکی به ساحل، مدرسه و فرودگاه در جلسه بازدید بررسی می‌شود.
            </p>
          </div>

          <div class="block">
            <h2 class="block__title">نکات حقوقی</h2>
            <div class="prose">
              <p>
                وضعیت سند، پروانه ساخت و عوارض شهرداری پیش از هر پرداختی به‌صورت رسمی استعلام می‌شود.
                برای خریداران خارجی، فرایند انتقال سند (تاپو) با حضور کارشناس رسمی و مترجم هماهنگ می‌گردد.
              </p>
            </div>
          </div>
        </div>

        <!-- contact card -->
        <aside class="contact-card" id="lead">
          <div class="contact-card__who">
            <img id="agent-photo" src="<?= e(img_w($agent['photo'], 200)) ?>" alt="<?= e($agent['name']) ?>" width="62" height="62">
            <div>
              <b id="agent-name"><?= e($agent['name']) ?></b>
              <span><?= e($agent['role']) ?></span>
            </div>
          </div>

          <div class="contact-card__actions">
            <a class="btn btn--dark btn--sm" href="tel:<?= e($agent['phoneHref']) ?>" data-agent-phone>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
              <span id="agent-phone" class="num"><?= e(fa_phone($agent['phone'])) ?></span>
            </a>
            <a class="btn btn-whatsapp btn--sm" href="https://wa.me/<?= e($agent['whatsapp']) ?>" data-agent-whatsapp>گفت‌وگو در واتس‌اپ</a>
          </div>

          <form class="form-grid" method="post" action="leads.php">
            <input type="hidden" name="property" value="<?= e($p['slug']) ?>">
            <input type="hidden" name="back" value="/property.php?id=<?= e(rawurlencode($p['slug'])) ?>">
            <input type="hidden" name="anchor" value="#lead">
            <input type="hidden" name="source" value="property">

            <div class="field">
              <label for="p-name">نام شما</label>
              <input id="p-name" name="name" type="text" placeholder="نام و نام خانوادگی" required>
            </div>
            <div class="field">
              <label for="p-phone">شماره تماس</label>
              <input id="p-phone" name="phone" type="tel" placeholder="۰۹۱۲ ۰۰۰ ۰۰۰۰" required>
            </div>
            <div class="field field--select">
              <label for="p-topic">درخواست</label>
              <select id="p-topic" name="topic">
                <option>هماهنگی بازدید</option>
                <option>دریافت اطلاعات تکمیلی</option>
                <option>ویدیو تور ملک</option>
                <option>استعلام قیمت نهایی</option>
              </select>
            </div>
            <div class="field">
              <label for="p-msg">توضیح (اختیاری)</label>
              <textarea id="p-msg" name="message" placeholder="زمان موردنظر بازدید، سؤال یا بودجه…"></textarea>
            </div>
            <button class="btn btn--primary btn--block" type="submit">ارسال درخواست</button>
            <div class="form-success<?= ($sent || $err) ? ' is-on' : '' ?>">
              <?= $sent ? '✓ درخواست ثبت شد؛ تا ۲۴ ساعت دیگر با شما تماس می‌گیرم.' : ($err ? '✗ فرم کامل نشد؛ نام و شماره تماس را بررسی کنید.' : '✓ درخواست ثبت شد؛ تا ۲۴ ساعت دیگر با شما تماس می‌گیرم.') ?>
            </div>
            <p class="contact-card__note">پاسخ‌گویی زیر ۲۴ ساعت · مشاوره بدون تعهد</p>
          </form>
        </aside>
      </div>

      <!-- similar -->
      <?php if ($similar !== []) { ?>
      <div class="section-head section-head--split" style="margin-top:64px">
        <div>
          <span class="eyebrow">پیشنهادهای مشابه</span>
          <h2 class="section-title" style="font-size:clamp(24px,2.6vw,32px)">ملک‌هایی که <em>احتمالاً بپسندید</em></h2>
        </div>
        <a class="link-arrow" href="properties.php">همه املاک</a>
      </div>

      <div class="grid grid--3" id="similar-grid">
        <?php foreach ($similar as $sp) { echo property_card($sp); } ?>
      </div>
      <?php } ?>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>
