<?php
declare(strict_types=1);

/**
 * ساخت فایل گزارش «امکانات سایت» به‌صورت فایل ورد (docx).
 *
 * اجرا:
 *   php -d extension=zip docs/build-features-doc.php
 * (در صورت نیاز extension_dir را هم با -d مسیر درست پوشه ext بدهید)
 *
 * خروجی: docs/امکانات-سایت.docx
 */

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "خطا: پلاگین zip در PHP فعال نیست.\n");
    exit(1);
}

/* ------------------------------------------------------------------
 * محتوای سند — در سطح کلی، بدون ورود به جزئیات فنی ریز
 * ------------------------------------------------------------------ */

$title    = 'امکانات وب‌سایت کیانی املاک';
$subtitle = 'گزارش کلی امکانات · آماده ارائه به کارفرما · آبان ۱۴۰۴';

$sections = [
    '۱. معرفی کلی' => [
        'وب‌سایت معرفی و بازاریابی املاک برای یک مشاور تخصصی املاک ساحلی در آنتالیا و آلانیا',
        'کاملاً فارسی و راست‌چین، با طراحی مدرن و واکنش‌گرا برای موبایل، تبلت و دسکتاپ',
        'هدف اصلی: جذب درخواست مشاوره از بازدیدکنندگان و ساختن اعتماد پیش از تماس',
    ],
    '۲. امکانات بخش عمومی سایت' => [
        'صفحه اصلی: معرفی مشاور، آمارهای اعتماد، املاک منتخب، مناطق فعالیت، فرایند همکاری و نظرات مشتریان',
        'صفحه املاک: جست‌وجو و فیلتر بر اساس نوع ملک، منطقه، قیمت و متراژ به همراه مرتب‌سازی نتایج',
        'صفحه جزئیات ملک: گالری تصاویر، مشخصات کامل، امکانات، توضیحات، نقشه موقعیت و پیشنهادهای مشابه',
        'فرم‌های جذب مشتری: درخواست مشاوره، هماهنگی بازدید و دریافت فایل اختصاصی',
        'دسترسی سریع به تماس تلفنی، واتس‌اپ و شبکه‌های اجتماعی',
        'بهینه‌سازی برای سئو، سرعت بارگذاری بالا و نمایش صحیح در نتایج جست‌وجوی گوگل',
    ],
    '۳. امکانات پنل مدیریت' => [
        'ورود امن با نام کاربری و رمز عبور',
        'داشبورد آماری شامل ملک‌های فعال، درخواست‌های جدید و وضعیت کلی سایت',
        'مدیریت کامل املاک: افزودن، ویرایش، انتشار و حذف فایل‌ها به همراه گالری تصاویر',
        'مشاهده و پیگیری درخواست‌های ثبت‌شده توسط بازدیدکنندگان سایت',
        'تعیین وضعیت هر ملک (فروش، رزرو شده، فروخته‌شده یا پیش‌نویس)',
    ],
    '۴. بک‌اند وب‌سایت (PHP)' => [
        'بک‌اند اختصاصی با PHP و دیتابیس برای ذخیره املاک، تصاویر و درخواست‌های مشتریان',
        'سرویس API برای نمایش زنده و یکپارچه املاک در صفحات سایت (جست‌وجو، فیلتر و جزئیات)',
        'سیستم ورود و احراز هویت برای پنل مدیریت',
        'اعتبارسنجی ورودی‌ها و پیشگیری از حملات رایج مانند SQL Injection',
        'ذخیره‌سازی امن اطلاعات در دیتابیس، جدا از فایل‌های ظاهری سایت',
    ],
];

$note = 'توضیح: این گزارش در سطح کلی امکانات تهیه شده است؛ جزئیات فنی، زمان‌بندی و هزینه اجرا در جلسه بعدی قابل ارائه است.';

/* ------------------------------------------------------------------
 * کمک‌تابع‌های ساخت XML سند ورد
 * ------------------------------------------------------------------ */

function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * ساخت یک پاراگراف.
 *
 * $o: align, size (نیم‌نقاط), bold, italic, color, before, after, rtl, bidi, border_top
 */
function paragraph(string $text, array $o = []): string
{
    $align  = $o['align']  ?? 'right';
    $size   = $o['size']   ?? 24;      // 24 = 12pt
    $bold   = $o['bold']   ?? false;
    $italic = $o['italic'] ?? false;
    $color  = $o['color']  ?? '23282D';
    $before = $o['before'] ?? 0;
    $after  = $o['after']  ?? 140;
    $rtl    = $o['rtl']    ?? true;
    $bidi   = $o['bidi']   ?? true;

    $pPr = '<w:pPr>';
    if ($bidi) {
        $pPr .= '<w:bidi/>';
    }
    $pPr .= '<w:spacing w:before="' . $before . '" w:after="' . $after . '" w:line="300" w:lineRule="auto"/>';
    $pPr .= '<w:jc w:val="' . $align . '"/>';
    if ($o['border_top'] ?? false) {
        $pPr .= '<w:pBdr><w:top w:val="single" w:sz="6" w:space="10" w:color="D8DDE2"/></w:pBdr>';
    }
    $pPr .= '</w:pPr>';

    $rPr = '<w:rPr>'
        . '<w:rFonts w:ascii="Tahoma" w:hAnsi="Tahoma" w:cs="Tahoma"/>'
        . '<w:sz w:val="' . $size . '"/><w:szCs w:val="' . $size . '"/>'
        . '<w:color w:val="' . $color . '"/>'
        . ($bold   ? '<w:b/><w:bCs/>' : '')
        . ($italic ? '<w:i/><w:iCs/>' : '')
        . ($rtl    ? '<w:rtl/>' : '')
        . '</w:rPr>';

    return '<w:p>' . $pPr . '<w:r>' . $rPr
        . '<w:t xml:space="preserve">' . esc($text) . '</w:t></w:r></w:p>';
}

/* ------------------------------------------------------------------
 * مونتاژ اجزای بسته docx
 * ------------------------------------------------------------------ */

$body = '';

// عنوان و زیرعنوان
$body .= paragraph($title,    ['align' => 'center', 'size' => 40, 'bold' => true, 'color' => '1F2A33', 'after' => 60, 'before' => 200]);
$body .= paragraph($subtitle, ['align' => 'center', 'size' => 20, 'color' => '6B7680', 'after' => 480]);

// بخش‌ها
foreach ($sections as $heading => $bullets) {
    $body .= paragraph($heading, ['size' => 28, 'bold' => true, 'color' => 'D94325', 'before' => 320, 'after' => 140]);
    foreach ($bullets as $b) {
        $body .= paragraph('• ' . $b, ['size' => 24, 'after' => 100]);
    }
}

// جمع‌بندی
$body .= paragraph($note, ['size' => 20, 'italic' => true, 'color' => '6B7680', 'before' => 420, 'after' => 200, 'border_top' => true]);

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<w:document '
    . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
    . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<w:body>'
    . $body
    . '<w:sectPr>'
    . '<w:pgSz w:w="11906" w:h="16838"/>'
    . '<w:pgMar w:top="1418" w:right="1418" w:bottom="1418" w:left="1418" w:header="709" w:footer="709" w:gutter="0"/>'
    . '<w:bidi/>'
    . '</w:sectPr>'
    . '</w:body></w:document>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
    . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
    . '</Types>';

$rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
    . '</Relationships>';

$docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
    . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    . '<w:docDefaults>'
    . '<w:rPrDefault><w:rPr>'
    . '<w:rFonts w:ascii="Tahoma" w:hAnsi="Tahoma" w:cs="Tahoma"/>'
    . '<w:sz w:val="24"/><w:szCs w:val="24"/>'
    . '<w:lang w:val="fa-IR" w:eastAsia="fa-IR" w:bidi="fa-IR"/>'
    . '</w:rPr></w:rPrDefault>'
    . '<w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr></w:pPrDefault>'
    . '</w:docDefaults>'
    . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
    . '<w:name w:val="Normal"/><w:qFormat/>'
    . '</w:style>'
    . '</w:styles>';

$parts = [
    '[Content_Types].xml'    => $contentTypes,
    '_rels/.rels'            => $rootRels,
    'word/document.xml'      => $document,
    'word/styles.xml'        => $styles,
    'word/_rels/document.xml.rels' => $docRels,
];

// اعتبارسنجی XML هر بخش پیش از بسته‌بندی
$dom = new DOMDocument();
foreach ($parts as $name => $xml) {
    if (!$dom->loadXML($xml)) {
        fwrite(STDERR, "خطا: XML نامعتبر در بخش $name\n");
        exit(1);
    }
}

$out = __DIR__ . '/امکانات-سایت.docx';
$zip = new ZipArchive();
if ($zip->open($out, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "خطا: ساخت فایل ممکن نشد: $out\n");
    exit(1);
}
foreach ($parts as $name => $xml) {
    $zip->addFromString($name, $xml);
}
if (!$zip->close()) {
    fwrite(STDERR, "خطا: نهایی‌سازی فایل ممکن نشد.\n");
    exit(1);
}

echo "OK: $out (" . filesize($out) . " bytes)\n";
