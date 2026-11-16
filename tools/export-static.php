<?php
declare(strict_types=1);

/**
 * ساخت نسخه استاتیک صفحات عمومی برای GitHub Pages.
 *
 * اجرا:
 *   php tools/export-static.php --base=https://USER.github.io/Amlak --out=_site
 *
 * فقط صفحات عمومی ساخته می‌شوند؛ پنل مدیریت، ثبت سرنخ در دیتابیس و کوئری‌های
 * سرور در نسخه استاتیک وجود ندارند. فرم‌ها با assets/js/static.js پیام واتس‌اپ
 * می‌سازند و فیلتر فهرست املاک در مرورگر انجام می‌شود.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/init.php';

$args = getopt('', ['base::', 'out::']);
$base = rtrim((string) ($args['base'] ?? 'https://example.github.io/Amlak'), '/') . '/';
$out  = rtrim((string) ($args['out'] ?? dirname(__DIR__) . '/_site'), '/\\');

$host   = (string) (parse_url($base, PHP_URL_HOST) ?: 'localhost');
$scheme = (string) (parse_url($base, PHP_URL_SCHEME) ?: 'https');
$origin = $scheme . '://' . $host;

$_SERVER['HTTP_HOST']      = $host;
$_SERVER['HTTPS']          = $scheme === 'https' ? 'on' : 'off';
$_SERVER['REQUEST_METHOD'] = 'GET';

/** رندر یک صفحه PHP و برگرداندن HTML آن (متغیرها بین صفحات قاطی نمی‌شوند) */
function render_page(string $file, array $get = [], string $uri = '/'): string
{
    $_GET = $get;
    $_SERVER['REQUEST_URI'] = $uri;

    return (function () use ($file): string {
        ob_start();
        require $file;
        return (string) ob_get_clean();
    })();
}

function remove_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? remove_tree($path) : unlink($path);
    }
    rmdir($dir);
}

function copy_tree(string $from, string $to): void
{
    if (!is_dir($to) && !mkdir($to, 0775, true) && !is_dir($to)) {
        throw new RuntimeException('پوشه ساخته نشد: ' . $to);
    }
    foreach (scandir($from) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $src = $from . DIRECTORY_SEPARATOR . $item;
        $dst = $to . DIRECTORY_SEPARATOR . $item;
        is_dir($src) ? copy_tree($src, $dst) : copy($src, $dst);
    }
}

/**
 * تبدیل HTML خروجی PHP به صفحه استاتیک:
 * حذف لینک پنل، تغییر پسوندها، اصلاح آدرس‌های مطلق و افزودن اسکریپت استاتیک.
 */
function staticize(string $html, array $slugToFile, string $base, string $origin): string
{
    // پنل مدیریت در نسخه استاتیک وجود ندارد
    $html = preg_replace('~<a\b[^>]*href="admin\.php"[^>]*>.*?</a>~s', '', $html) ?? $html;
    $html = preg_replace('~<li>\s*</li>~', '', $html) ?? $html;

    // فیلتر فهرست در نسخه استاتیک سمت مرورگر انجام می‌شود
    $html = str_replace('<form id="filter-form"', '<form id="filter-form" data-static="1"', $html);

    $html = preg_replace_callback(
        '~(href|src|action)="([^"]*)"~',
        static function (array $m) use ($slugToFile): string {
            [$all, $attr, $url] = $m;

            if ($url === '' || $url[0] === '#' || preg_match('~^(https?:)?//|^(mailto|tel|data):~i', $url)) {
                return $all;
            }
            if ($attr === 'action' && str_starts_with($url, 'leads.php')) {
                return $all; // فرم‌ها با static.js به واتس‌اپ متصل می‌شوند
            }
            if (str_starts_with($url, 'property.php?')) {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $slug = (string) ($query['id'] ?? '');
                unset($query['id']);
                if (isset($slugToFile[$slug])) {
                    $extra = $query !== [] ? '?' . http_build_query($query) : '';
                    return $attr . '="' . $slugToFile[$slug] . $extra . '"';
                }
            }
            if (str_starts_with($url, 'index.php') || str_starts_with($url, 'properties.php')) {
                return $attr . '="' . preg_replace('~\.php~', '.html', $url, 1) . '"';
            }

            return $all;
        },
        $html
    ) ?? $html;

    // آدرس‌های مطلق: canonical، Open Graph و داده ساختاریافته
    foreach ($slugToFile as $slug => $file) {
        $html = str_replace($origin . '/property.php?id=' . rawurlencode($slug), $base . $file, $html);
    }
    $html = str_replace(
        [$origin . '/index.php', $origin . '/properties.php', $origin . '/assets/'],
        [$base . 'index.html', $base . 'properties.html', $base . 'assets/'],
        $html
    );

    // پیکربندی و رفتارهای مخصوص نسخه استاتیک
    $suffix = '<script>window.STATIC_SITE='
        . json_encode([
            'whatsapp' => (string) agent()['whatsapp'],
            'phone'    => (string) agent()['phoneHref'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . ';</script>' . "\n"
        . '<script src="assets/js/static.js"></script>' . "\n";

    return str_replace('</body>', $suffix . '</body>', $html);
}

/* ---------- املاک منتشرشده ---------- */
$statuses = str_replace('p.', '', PROPERTY_PUBLIC_STATUS);
$rows = db()->query("SELECT slug FROM properties WHERE $statuses ORDER BY id")->fetchAll();

$slugToFile = [];
foreach ($rows as $row) {
    $slugToFile[(string) $row['slug']] = 'property-' . (string) $row['slug'] . '.html';
}

/* ---------- ساخت خروجی ---------- */
remove_tree($out);
if (!mkdir($out, 0775, true) && !is_dir($out)) {
    fwrite(STDERR, "✗ پوشه خروجی ساخته نشد: $out\n");
    exit(1);
}

copy_tree(dirname(__DIR__) . '/assets', $out . '/assets');

$pages = [
    ['index.php', [], '/index.php', 'index.html'],
    ['properties.php', [], '/properties.php', 'properties.html'],
];

$count = 0;
foreach ($pages as [$script, $get, $uri, $target]) {
    $html = render_page(dirname(__DIR__) . '/' . $script, $get, $uri);
    file_put_contents($out . '/' . $target, staticize($html, $slugToFile, $base, $origin));
    $count++;
}

foreach ($slugToFile as $slug => $file) {
    $html = render_page(dirname(__DIR__) . '/property.php', ['id' => $slug], '/property.php?id=' . $slug);
    file_put_contents($out . '/' . $file, staticize($html, $slugToFile, $base, $origin));
    $count++;
}

/* ---------- robots و sitemap برای نسخه استاتیک ---------- */
file_put_contents(
    $out . '/robots.txt',
    "User-agent: *\nAllow: /\n\nSitemap: " . $base . "sitemap.xml\n"
);

$urls = [$base . 'index.html', $base . 'properties.html'];
foreach ($slugToFile as $file) {
    $urls[] = $base . $file;
}
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    $xml .= "  <url>\n    <loc>" . e($url) . "</loc>\n  </url>\n";
}
$xml .= '</urlset>' . "\n";
file_put_contents($out . '/sitemap.xml', $xml);

// جلوگیری از پردازش Jekyll در GitHub Pages
touch($out . '/.nojekyll');

echo "✓ نسخه استاتیک ساخته شد\n";
echo "  فایل‌ها : $count صفحه + robots.txt + sitemap.xml\n";
echo "  املاک   : " . count($slugToFile) . " صفحه\n";
echo "  خروجی   : $out\n";
echo "  آدرس    : $base\n";
