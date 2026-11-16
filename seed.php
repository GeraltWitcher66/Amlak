<?php
declare(strict_types=1);

/**
 * ساخت دیتابیس و داده اولیه سایت (ایمن برای اجرای چندباره).
 *
 * اجرا:
 *   php seed.php              → ساخت/تکمیل دیتابیس
 *   php seed.php --fresh      → حذف کامل و ساخت دوباره
 *   php seed.php --password=X → تغییر رمز عبور مدیر هنگام ساخت حساب
 */

require_once __DIR__ . '/includes/init.php';

$fresh  = in_array('--fresh', $argv ?? [], true);
$dbPath = DB_PATH;

if ($fresh) {
    foreach ([$dbPath, $dbPath . '-wal', $dbPath . '-shm'] as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
    echo "→ دیتابیس قبلی حذف شد.\n";
}

db_migrate();
echo "✓ اسکیمای دیتابیس اعمال شد.\n";

$seedFile = __DIR__ . '/seed-data.json';
if (!is_file($seedFile)) {
    fwrite(STDERR, "✗ فایل seed-data.json یافت نشد.\n");
    exit(1);
}
$data = json_decode((string) file_get_contents($seedFile), true);
if (!is_array($data) || !isset($data['PROPERTIES'])) {
    fwrite(STDERR, "✗ فایل seed-data.json معتبر نیست.\n");
    exit(1);
}

/* ---------- حساب مدیر ---------- */
if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
    $username = SEED_ADMIN_USERNAME;
    $password = SEED_ADMIN_PASSWORD;
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--password=')) {
            $password = substr($arg, strlen('--password='));
        }
    }
    if ($password === '') {
        fwrite(STDERR, "✗ رمز عبور مدیر نمی‌تواند خالی باشد.\n");
        exit(1);
    }
    $stmt = db()->prepare('INSERT INTO admins (username, password_hash, full_name) VALUES (?,?,?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'آرش کیانی']);
    echo "✓ حساب مدیر ساخته شد — نام کاربری: $username · رمز: $password\n";
} else {
    echo "• حساب مدیر از قبل موجود است.\n";
}

/* ---------- تنظیمات سایت ---------- */
if ((int) db()->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
    $ins = db()->prepare('INSERT INTO settings (name, value) VALUES (?,?)');
    $ins->execute(['agent', json_encode($data['AGENT'] ?? new stdClass(), JSON_UNESCAPED_UNICODE)]);
    $ins->execute(['types', json_encode($data['TYPES'] ?? [], JSON_UNESCAPED_UNICODE)]);
    $ins->execute(['areas', json_encode($data['AREAS'] ?? [], JSON_UNESCAPED_UNICODE)]);
    echo "✓ تنظیمات سایت (اطلاعات مشاور، انواع، مناطق) ثبت شد.\n";
}

/* ---------- املاک + تصاویر + امکانات ---------- */
if ((int) db()->query('SELECT COUNT(*) FROM properties')->fetchColumn() === 0) {
    $insProp = db()->prepare(
        'INSERT INTO properties (slug, title, type, area, city, price, size, rooms, baths, year,'
        . ' status, badge, featured, description, seo_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $insImg  = db()->prepare('INSERT INTO property_images (property_id, url, position) VALUES (?,?,?)');
    $insFeat = db()->prepare('INSERT INTO property_features (property_id, feature, position) VALUES (?,?,?)');

    $toUrl = static function (string $idOrUrl, int $w): string {
        if (preg_match('#^https?://#i', $idOrUrl)) {
            return $idOrUrl;
        }
        return 'https://images.unsplash.com/' . $idOrUrl . "?auto=format&fit=crop&w=$w&q=80";
    };

    $count = 0;
    foreach ($data['PROPERTIES'] as $p) {
        $insProp->execute([
            $p['id'],
            $p['title'],
            $p['type'],
            $p['area'],
            $p['city'] ?? 'آنتالیا',
            (int) ($p['price'] ?? 0),
            (int) ($p['size'] ?? 0),
            (int) ($p['rooms'] ?? 0),
            (int) ($p['baths'] ?? 0),
            (int) ($p['year'] ?? 0),
            $p['status'] ?? 'فروش',
            $p['badge'] ?? '',
            !empty($p['featured']) ? 1 : 0,
            implode("\n\n", $p['desc'] ?? []),
            '',
        ]);
        $pid = (int) db()->lastInsertId();

        foreach (($p['images'] ?? []) as $i => $img) {
            $insImg->execute([$pid, $toUrl((string) $img, 1200), $i]);
        }
        foreach (($p['features'] ?? []) as $i => $f) {
            $insFeat->execute([$pid, $f, $i]);
        }
        $count++;
    }
    echo "✓ $count ملک به همراه تصاویر و امکانات ثبت شد.\n";
} else {
    echo "• املاک از قبل موجود است (برای بازسازی کامل: --fresh).\n";
}

/* ---------- نظرات مشتریان ---------- */
if ((int) db()->query('SELECT COUNT(*) FROM testimonials')->fetchColumn() === 0 && !empty($data['TESTIMONIALS'])) {
    $insT = db()->prepare('INSERT INTO testimonials (body, author, role, photo, position) VALUES (?,?,?,?,?)');
    foreach ($data['TESTIMONIALS'] as $i => $t) {
        $photo = preg_match('#^https?://#i', (string) ($t['photo'] ?? ''))
            ? (string) $t['photo']
            : 'https://images.unsplash.com/' . ($t['photo'] ?? '') . '?auto=format&fit=crop&w=200&q=80';
        $insT->execute([$t['text'] ?? '', $t['name'] ?? '', $t['role'] ?? '', $photo, $i]);
    }
    echo '✓ ' . count($data['TESTIMONIALS']) . " نظر مشتری ثبت شد.\n";
}

echo "✓ دیتابیس آماده است: $dbPath\n";
