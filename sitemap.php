<?php
declare(strict_types=1);

/**
 * نقشه سایت پویا — /sitemap.php
 *
 * آدرس را یک بار در Google Search Console ثبت کنید تا همه فایل‌های فعال
 * به‌صورت خودکار معرفی شوند. صفحات پیش‌نویس و فروخته‌شده در آن نمی‌آیند.
 */

require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/xml; charset=utf-8');

$statuses = str_replace('p.', '', PROPERTY_PUBLIC_STATUS);
$rows = db()->query("SELECT slug, updated_at FROM properties WHERE $statuses ORDER BY id")->fetchAll();

$urls = [
    ['loc' => site_url('index.php'), 'priority' => '1.0', 'lastmod' => ''],
    ['loc' => site_url('properties.php'), 'priority' => '0.9', 'lastmod' => ''],
];

foreach ($rows as $row) {
    $ts = strtotime((string) $row['updated_at'] . ' UTC');
    $urls[] = [
        'loc'      => site_url('property.php?id=' . rawurlencode((string) $row['slug'])),
        'priority' => '0.8',
        'lastmod'  => $ts !== false ? date('c', $ts) : '',
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>', e($url['loc']), "</loc>\n";
    if ($url['lastmod'] !== '') {
        echo '    <lastmod>', e($url['lastmod']), "</lastmod>\n";
    }
    echo '    <priority>', $url['priority'], "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>', "\n";
