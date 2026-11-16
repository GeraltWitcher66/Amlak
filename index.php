<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$agent    = agent();
$featured = featured_properties(4);
$quotes   = testimonials();

$sent = ($_GET['sent'] ?? '') === '1';
$err  = ($_GET['err'] ?? '') === '1';

$pageTitle = 'آرش کیانی | مشاور تخصصی املاک ساحلی آنتالیا و آلانیا';
$metaDesc  = 'ویلا، آپارتمان و پنت‌هاوس ساحلی در آنتالیا و آلانیا با مشاوره شخصی، بازدید هماهنگ و بررسی حقوقی کامل. درخواست مشاوره رایگان.';
$activeNav = 'home';
$ogImage   = 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80';

require __DIR__ . '/includes/header.php';
?>

  <!-- ============ HERO ============ -->
  <section class="hero">
    <div class="hero__media">
      <img src="https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=2000&q=80" alt="ویلای ساحلی مدرن در آنتالیا" width="2000" height="1333" fetchpriority="high">
    </div>

    <div class="container">
      <div class="hero__grid">
        <div>
          <span class="hero__eyebrow"><i></i> مشاور تخصصی املاک ساحلی · آنتالیا و آلانیا</span>

          <h1 class="hero__title">
            خانه‌ای که می‌خرید،<br>
            باید <em>به اندازه اعتمادتان</em> ارزش داشته باشد.
          </h1>

          <p class="hero__text">
            من آرش کیانی هستم؛ ۱۲ سال است که در کنار خریداران ایرانی و سرمایه‌گذاران خارجی هستم تا در ساحل
            مدیترانه، ملک درست را با قیمت درست و سند سالم پیدا کنند.
          </p>

          <div class="hero__cta">
            <a class="btn btn--primary" href="#contact">درخواست مشاوره رایگان</a>
            <a class="btn btn--light" href="properties.php">مشاهده املاک</a>
          </div>

          <div class="hero__meta">
            <div><span data-count="340" data-suffix="+">۳۴۰+</span><small>معامله موفق</small></div>
            <div><span data-count="12" data-suffix=" سال">۱۲ سال</span><small>تجربه در ترکیه</small></div>
            <div><span data-count="98" data-suffix="٪">۹۸٪</span><small>رضایت مشتریان</small></div>
          </div>
        </div>

        <div class="hero__card">
          <img src="<?= e($agent['photo']) ?>" alt="<?= e($agent['name']) ?>" width="96" height="116">
          <div>
            <h3><?= e($agent['name']) ?></h3>
            <p>مشاور رسمی املاک ساحلی · عضو اتاق بازرگانی آنتالیا</p>
            <span class="verified">✓ پاسخ‌گویی زیر ۲۴ ساعت</span>
          </div>
        </div>
      </div>
    </div>

    <div class="hero__scroll">SCROLL</div>
  </section>

  <!-- ============ MARQUEE ============ -->
  <div class="strip" aria-hidden="true">
    <div class="strip__track">
      <span>ویلای استخردار</span><span>آپارتمان ساحلی</span><span>پنت‌هاوس رو به دریا</span><span>زمین سند آزاد</span><span>بازدید حضوری</span><span>بررسی حقوقی سند</span><span>اقامت ترکیه</span>
      <span>ویلای استخردار</span><span>آپارتمان ساحلی</span><span>پنت‌هاوس رو به دریا</span><span>زمین سند آزاد</span><span>بازدید حضوری</span><span>بررسی حقوقی سند</span><span>اقامت ترکیه</span>
    </div>
  </div>

  <!-- ============ ABOUT / PERSONAL BRAND ============ -->
  <section class="section" id="about">
    <div class="container">
      <div class="about">
        <div class="about__media reveal">
          <span class="about__blob"></span>
          <div class="frame">
            <img src="<?= e($agent['photo']) ?>" alt="<?= e($agent['name']) ?> در محل کار" loading="lazy" width="900" height="1125">
          </div>
          <div class="about__badge">
            <strong>۱۲</strong>
            <span>سال تجربه در بازار آنتالیا</span>
          </div>
        </div>

        <div class="reveal" data-delay="1">
          <span class="eyebrow">درباره من</span>
          <h2 class="section-title">یک نفر که تا آخر مسیر <em>کنارتان</em> می‌ماند.</h2>
          <p class="section-sub">
            از اولین جست‌وجو تا انتقال سند، همه کارها را خودم پیگیری می‌کنم؛ نه کارشناس فروش، نه واسطه.
            این یعنی یک شماره تماس، یک مسئول و یک تعهد.
          </p>

          <ul class="about__list">
            <li>
              <span class="tick">✓</span>
              <div>
                <b>بازدید اختصاصی و هماهنگ‌شده</b>
                <p>برنامه بازدید بر اساس بودجه و سلیقه شما چیده می‌شود؛ نه بر اساس موجودی انبار.</p>
              </div>
            </li>
            <li>
              <span class="tick">✓</span>
              <div>
                <b>بررسی کامل حقوقی سند</b>
                <p>استعلام شهرداری، تاپو و وضعیت عوارض پیش از پرداخت هر مبلغی انجام می‌شود.</p>
              </div>
            </li>
            <li>
              <span class="tick">✓</span>
              <div>
                <b>پشتیبانی زبان فارسی</b>
                <p>تمام قراردادها و مستندات به زبان فارسی توضیح داده می‌شوند تا چیزی از قلم نیفتد.</p>
              </div>
            </li>
          </ul>

          <div class="signature">
            <span class="name">Arash Kiani</span>
            <span class="role">بنیان‌گذار کیانی املاک · مشاور املاک ساحلی</span>
          </div>

          <div style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
            <a class="btn btn--dark" href="#contact">گفت‌وگو با من</a>
            <a class="btn btn--ghost" href="properties.php">دیدن فایل‌ها</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ STATS ============ -->
  <section class="section--tight">
    <div class="container">
      <div class="stats reveal">
        <div><strong data-count="340" data-suffix="+">۳۴۰+</strong><span>ملک فروخته‌شده</span></div>
        <div><strong data-count="145" data-suffix="+">۱۴۵+</strong><span>خانواده راضی</span></div>
        <div><strong data-count="9" data-suffix=" منطقه">۹ منطقه</strong><span>پوشش آنتالیا و آلانیا</span></div>
        <div><strong data-count="24" data-suffix=" ساعت">۲۴ ساعت</strong><span>زمان پاسخ‌گویی</span></div>
      </div>
    </div>
  </section>

  <!-- ============ FEATURED PROPERTIES ============ -->
  <section class="section" id="listings">
    <div class="container">
      <div class="section-head section-head--split">
        <div>
          <span class="eyebrow">فایل‌های منتخب</span>
          <h2 class="section-title">املاکی که <em>این هفته</em> دیدنی‌اند</h2>
        </div>
        <a class="link-arrow" href="properties.php">همه املاک را ببینید</a>
      </div>

      <div class="grid grid--4" id="featured-grid">
        <?php foreach ($featured as $p) { echo property_card($p); } ?>
      </div>
    </div>
  </section>

  <!-- ============ AREAS ============ -->
  <section class="section section--soft">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">محدوده‌های فعالیت</span>
        <h2 class="section-title">از کمر تا آلانیا، <em>کنار دریا</em></h2>
        <p class="section-sub">هر منطقه رفتار قیمتی و مستأجرین خودش را دارد؛ اینکه کجا بخرید به هدف شما بستگی دارد.</p>
      </div>

      <div class="areas">
        <a class="area-tile reveal" href="properties.php?area=<?= e(rawurlencode('کمر')) ?>">
          <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80" alt="کمر" loading="lazy" width="800" height="1066">
          <div class="area-tile__c"><b>کمر</b><span>ویلاهای استخردار با ویو دریا</span></div>
        </a>
        <a class="area-tile reveal" data-delay="1" href="properties.php?area=<?= e(rawurlencode('آلانیا')) ?>">
          <img src="https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=800&q=80" alt="آلانیا" loading="lazy" width="800" height="1066">
          <div class="area-tile__c"><b>آلانیا</b><span>پنت‌هاوس و آپارتمان ساحلی</span></div>
        </a>
        <a class="area-tile reveal" data-delay="2" href="properties.php?area=<?= e(rawurlencode('سیده')) ?>">
          <img src="https://images.unsplash.com/photo-1541432901042-2d8bd64b4a9b?auto=format&fit=crop&w=800&q=80" alt="سیده" loading="lazy" width="800" height="1066">
          <div class="area-tile__c"><b>سیده</b><span>آپارتمان نزدیک بازار و ساحل</span></div>
        </a>
        <a class="area-tile reveal" data-delay="3" href="properties.php?area=<?= e(rawurlencode('بِلِک')) ?>">
          <img src="https://images.unsplash.com/photo-1560185007-cde436f6a4d0?auto=format&fit=crop&w=800&q=80" alt="بِلِک" loading="lazy" width="800" height="1066">
          <div class="area-tile__c"><b>بِلِک</b><span>مجتمع‌های گلف و اقامتگاهی</span></div>
        </a>
      </div>
    </div>
  </section>

  <!-- ============ PROCESS ============ -->
  <section class="section" id="process">
    <div class="container">
      <div class="section-head section-head--center">
        <span class="eyebrow">فرایند همکاری</span>
        <h2 class="section-title">چهار قدم تا <em>تحویل کلید</em></h2>
      </div>

      <div class="steps">
        <div class="step reveal">
          <div class="step__n">۰۱</div>
          <h3>شنیدن خواسته شما</h3>
          <p>یک تماس کوتاه کافی است: بودجه، هدف سکونت یا سرمایه‌گذاری، و منطقه موردنظر را مشخص می‌کنیم.</p>
        </div>
        <div class="step reveal" data-delay="1">
          <div class="step__n">۰۲</div>
          <h3>فهرست اختصاصی</h3>
          <p>سه تا پنج گزینه واقعی و هم‌راستا با بودجه، همراه با ویدیو تور و مستندات اولیه ارسال می‌شود.</p>
        </div>
        <div class="step reveal" data-delay="2">
          <div class="step__n">۰۳</div>
          <h3>بازدید حضوری</h3>
          <p>برنامه بازدید در یک یا دو روز چیده می‌شود؛ من در کنار شما هستم و نکات فنی هر ملک را می‌گویم.</p>
        </div>
        <div class="step reveal" data-delay="3">
          <div class="step__n">۰۴</div>
          <h3>سند و قرارداد</h3>
          <p>بررسی حقوقی تاپو، مذاکبه قیمت و هماهنگی انتقال سند تا آخرین امضا همراهی‌تان می‌کنم.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ TESTIMONIALS ============ -->
  <section class="section section--soft">
    <div class="container">
      <div class="section-head section-head--split">
        <div>
          <span class="eyebrow">نظر مشتریان</span>
          <h2 class="section-title">اعتمادی که <em>پشت هر معامله</em> هست</h2>
        </div>
        <div class="quote__stars" style="font-size:15px">★★★★★ <span class="muted" style="font-size:13.5px">۴٫۹ از ۵ بر اساس ۸۷ نظر</span></div>
      </div>

      <div class="grid grid--3" id="quotes-grid">
        <?php foreach ($quotes as $t) { echo testimonial_card($t); } ?>
      </div>
    </div>
  </section>

  <!-- ============ CTA / LEAD FORM ============ -->
  <section class="section" id="contact">
    <div class="container">
      <div class="cta reveal">
        <div class="cta__bg">
          <img src="https://images.unsplash.com/photo-1505577058444-a3dab90d4253?auto=format&fit=crop&w=1800&q=80" alt="" loading="lazy" width="1800" height="1200">
        </div>

        <div class="cta__grid">
          <div>
            <span class="eyebrow" style="color:#fff">دریافت مشاوره</span>
            <h2>شماره‌تان را بگذارید؛ خودم تماس می‌گیرم.</h2>
            <p>
              بدون فرم‌های طولانی و بدون تماس‌های مزاحم. فقط یک شماره تا فایل‌های هم‌راستا با بودجه‌تان
              و برنامه بازدید را برایتان بفرستم.
            </p>
            <ul class="cta__points">
              <li>مشاوره رایگان و بدون تعهد</li>
              <li>ارسال ویدیو تور ملک</li>
              <li>پاسخ زیر ۲۴ ساعت</li>
            </ul>
          </div>

          <form class="form-card" method="post" action="leads.php">
            <h3>درخواست تماس</h3>
            <p>اطلاعاتتان فقط برای همین تماس استفاده می‌شود.</p>

            <input type="hidden" name="back" value="/index.php">
            <input type="hidden" name="anchor" value="#contact">
            <input type="hidden" name="source" value="home">

            <div class="form-grid">
              <div class="form-row">
                <div class="field">
                  <label for="c-name">نام و نام خانوادگی</label>
                  <input id="c-name" name="name" type="text" placeholder="مثلاً سارا محمدی" required>
                </div>
                <div class="field">
                  <label for="c-phone">شماره تماس</label>
                  <input id="c-phone" name="phone" type="tel" placeholder="۰۹۱۲ ۰۰۰ ۰۰۰۰" required>
                </div>
              </div>

              <div class="field field--select">
                <label for="c-topic">موضوع مشاوره</label>
                <select id="c-topic" name="topic">
                  <option>دریافت اطلاعات ملک</option>
                  <option>هماهنگی بازدید</option>
                  <option>خرید ویلا</option>
                  <option>سرمایه‌گذاری و اجاره</option>
                  <option>فروش ملک من</option>
                </select>
              </div>

              <div class="field field--select">
                <label for="c-budget">بودجه تقریبی</label>
                <select id="c-budget" name="budget">
                  <option>زیر ۳۰۰ هزار دلار</option>
                  <option>۳۰۰ تا ۶۰۰ هزار دلار</option>
                  <option>۶۰۰ هزار تا ۱ میلیون دلار</option>
                  <option>بالای ۱ میلیون دلار</option>
                </select>
              </div>

              <button class="btn btn--primary btn--block" type="submit">ثبت درخواست</button>

              <div class="form-success<?= ($sent || $err) ? ' is-on' : '' ?>">
                <?= $sent ? '✓ ثبت شد. تا کمتر از ۲۴ ساعت با شما تماس می‌گیرم.' : ($err ? '✗ فرم کامل نشد؛ نام و شماره تماس را بررسی کنید.' : '✓ ثبت شد. تا کمتر از ۲۴ ساعت با شما تماس می‌گیرم.') ?>
              </div>
              <p class="form-note">با ثبت درخواست، حریم خصوصی و قوانین وب‌سایت را می‌پذیرید.</p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>
