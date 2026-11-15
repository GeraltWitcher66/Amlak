<?php
/**
 * هدر مشترک صفحات عمومی — هر صفحه اول init.php و بعد این فایل را require می‌کند.
 * متغیرهای لازم: $pageTitle (الزامی)
 * متغیرهای اختیاری: $metaDesc، $activeNav ('home'|'properties'|'admin')،
 *                   $ctaHref، $ctaText، $ogImage، $canonical، $robots، $jsonLd
 */
$agent    ??= agent();
$pageTitle ??= 'کیانی املاک';
$metaDesc  ??= '';
$activeNav ??= '';
$ctaHref   ??= 'index.php#contact';
$ctaText   ??= 'درخواست مشاوره';
$ogType    ??= 'website';
$robots    ??= '';
$canonical ??= '';
$jsonLd    ??= [];

$ogImage   ??= 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=1200&q=80';
$ogImage   = abs_url((string) $ogImage);
$canonical = $canonical !== ''
    ? $canonical
    : site_url(strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?'));

/* داده ساختاریافته: معرفی مشاور در همه صفحات + موارد اختصاصی هر صفحه */
$siteLd = [
    '@context' => 'https://schema.org',
    '@graph'   => array_merge([
        [
            '@type'       => 'RealEstateAgent',
            '@id'         => site_url('index.php') . '#agent',
            'name'        => $agent['brand'],
            'description' => $agent['name'] . ' — ' . $agent['role'] . ' در ' . $agent['regions'],
            'url'         => site_url('index.php'),
            'telephone'   => $agent['phoneHref'],
            'email'       => $agent['email'],
            'image'       => abs_url((string) $agent['photo']),
            'priceRange'  => '$$$',
            'areaServed'  => $agent['regions'],
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $agent['address'],
                'addressLocality' => 'Antalya',
                'addressCountry'  => 'TR',
            ],
        ],
    ], array_values($jsonLd)),
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<meta name="theme-color" content="#ff5a45">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($robots !== '') { ?>
<meta name="robots" content="<?= e($robots) ?>">
<?php } ?>
<meta property="og:type" content="<?= e($ogType) ?>">
<meta property="og:site_name" content="کیانی املاک">
<meta property="og:locale" content="fa_IR">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<?php if ($ogImage !== '') { ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php } ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDesc) ?>">
<?php if ($ogImage !== '') { ?>
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php } ?>
<script type="application/ld+json"><?= json_encode($siteLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://images.unsplash.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="header">
  <div class="container header__inner">
    <a class="brand" href="index.php" aria-label="کیانی املاک">
      <span class="brand__mark">K</span>
      <span class="brand__text">
        <span class="brand__name">کیانی املاک</span>
        <span class="brand__tag">ANTALYA · ALANYA</span>
      </span>
    </a>

    <nav class="nav" aria-label="منوی اصلی">
      <a class="nav__link<?= $activeNav === 'home' ? ' is-active' : '' ?>" href="index.php">خانه</a>
      <a class="nav__link<?= $activeNav === 'properties' ? ' is-active' : '' ?>" href="properties.php">املاک</a>
      <a class="nav__link" href="index.php#about">درباره من</a>
      <a class="nav__link" href="index.php#process">فرایند همکاری</a>
      <a class="nav__link" href="index.php#contact">تماس</a>
      <a class="nav__link<?= $activeNav === 'admin' ? ' is-active' : '' ?>" href="admin.php">پنل مدیریت</a>
    </nav>

    <div class="header__actions">
      <a class="header__phone num" href="tel:<?= e($agent['phoneHref']) ?>"><?= e(fa_phone($agent['phone'])) ?></a>
      <a class="btn btn--primary btn--sm" href="<?= e($ctaHref) ?>"><?= e($ctaText) ?></a>
      <button class="burger" type="button" aria-label="باز کردن منو"><span></span></button>
    </div>
  </div>
</header>

<div class="mobile-nav">
  <div class="mobile-nav__top">
    <a class="brand" href="index.php">
      <span class="brand__mark">K</span>
      <span class="brand__text"><span class="brand__name" style="color:#fff">کیانی املاک</span></span>
    </a>
    <button class="mobile-nav__close" type="button" aria-label="بستن منو">×</button>
  </div>
  <nav class="mobile-nav__links">
    <a href="index.php">خانه <span>۰۱</span></a>
    <a href="properties.php">املاک <span>۰۲</span></a>
    <a href="index.php#about">درباره من <span>۰۳</span></a>
    <a href="index.php#process">فرایند همکاری <span>۰۴</span></a>
    <a href="admin.php">پنل مدیریت <span>۰۵</span></a>
  </nav>
  <div class="mobile-nav__foot">
    <a class="btn btn--primary btn--block" href="<?= e($ctaHref) ?>">درخواست مشاوره</a>
    <a class="btn btn--light btn--block" href="tel:<?= e($agent['phoneHref']) ?>">تماس مستقیم</a>
  </div>
</div>

<main>
