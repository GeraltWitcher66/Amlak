<?php
declare(strict_types=1);

/* ==========================================================================
   هسته مشترک بک‌اند سایت کیانی املاک — پیکربندی، دیتابیس و توابع عمومی
   هر صفحه PHP اول این فایل را require می‌کند.
   ========================================================================== */

define('DB_PATH', __DIR__ . '/../storage/kiani.sqlite');

// حساب پیش‌فرض مدیر (فقط هنگام seed استفاده می‌شود؛ بعد از آن از دیتابیس خوانده می‌شود)
define('SEED_ADMIN_USERNAME', 'admin');
define('SEED_ADMIN_PASSWORD', 'admin123');

/* ------------------------------ دیتابیس ------------------------------ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('پوشه دیتابیس ساخته نشد: ' . $dir);
        }
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }
    return $pdo;
}

/** اجرای اسکیما (ایمن برای اجرای چندباره) */
function db_migrate(): void
{
    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema === false) {
        throw new RuntimeException('فایل schema.sql خوانده نشد.');
    }
    db()->exec($schema);
}

/* ------------------------------ خروجی و امنیت ------------------------------ */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

const FA_DIGITS = [
    '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
    '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
];

/** تبدیل ارقام لاتین یک رشته به فارسی */
function fa_digits(string $s): string
{
    return strtr($s, FA_DIGITS);
}

/** اعداد فارسی */
function fa_num(int|string $n): string
{
    return fa_digits((string) $n);
}

/** شماره تماس با ارقام فارسی — فقط برای نمایش؛ tel: با ارقام لاتین ساخته می‌شود */
function fa_phone(string $phone): string
{
    return fa_digits($phone);
}

/** قیمت به دلار با ارقام فارسی: $۱٬۲۵۰٬۰۰۰ */
function fa_price(int $n): string
{
    return '$' . fa_digits(number_format($n, 0, '.', '٬'));
}

/** تبدیل ارقام فارسی/عربی ورودی فرم‌ها به لاتین (برای اعتبارسنجی عددی) */
function to_latin_digits(string $s): string
{
    return strtr($s, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

/** تنظیم عرض تصویر unsplash در URL (ویترین‌سایزها) */
function img_w(string $url, int $w): string
{
    if ($url === '') {
        return '';
    }
    $out = preg_replace('/([?&])w=\d+/', '$1w=' . $w, $url, 1, $count);
    if ($count === 0) {
        $out = $url . (str_contains($url, '?') ? '&' : '?') . 'w=' . $w;
    }
    return $out;
}

function redirect(string $to): never
{
    // فقط مسیرهای داخلی سایت
    if ($to === '' || $to[0] !== '/' || str_starts_with($to, '//')) {
        $to = '/index.php';
    }
    header('Location: ' . $to, true, 302);
    exit;
}

/* ------------------------------ نشست و ورود مدیر ------------------------------ */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('kiani_admin');
    session_set_cookie_params([
        'lifetime' => 28800,
        'path'     => '/',
        'secure'   => false, // در استقرار HTTPS باید true شود
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_admin(): ?array
{
    start_session();
    $id = $_SESSION['admin_id'] ?? null;
    if (!$id) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username, full_name FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function require_admin(): array
{
    $admin = current_admin();
    if ($admin === null) {
        redirect('/login.php');
    }
    return $admin;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** اعتبارسنجی توکن CSRF برای همه عملیات نوشتن پنل مدیریت */
function csrf_verify(): void
{
    start_session();
    $token = (string) ($_POST['csrf'] ?? '');
    if ($token === '' || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        exit('درخواست نامعتبر است (CSRF). لطفاً دوباره صفحه را باز کنید.');
    }
}

/* ------------------------------ تنظیمات و اطلاعات سایت ------------------------------ */

function setting(string $name, mixed $default = null): mixed
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT name, value FROM settings') as $row) {
            $cache[$row['name']] = json_decode($row['value'], true);
        }
    }
    return $cache[$name] ?? $default;
}

function agent(): array
{
    $defaults = [
        'name'      => 'آرش کیانی',
        'brand'     => 'کیانی املاک',
        'role'      => 'مشاور تخصصی املاک ساحلی',
        'regions'   => 'آنتالیا · آلانیا',
        'phone'     => '+90 552 000 00 00',
        'phoneHref' => '+905520000000',
        'whatsapp'  => '905520000000',
        'email'     => 'info@kiani-estate.com',
        'address'   => 'چیلیوسلو، آنتالیا، ترکیه',
        'photo'     => 'assets/img/agent.jpg',
        'photoAlt'  => 'assets/img/agent.jpg',
    ];
    $a = setting('agent', []);
    $a = is_array($a) ? array_merge($defaults, $a) : $defaults;
    // اگر عکس در تنظیمات خالی مانده باشد، عکس پیش‌فرض نمایش داده می‌شود
    foreach (['photo', 'photoAlt'] as $key) {
        if (trim((string) ($a[$key] ?? '')) === '') {
            $a[$key] = $defaults[$key];
        }
    }
    return $a;
}

function types(): array
{
    return setting('types', ['ویلا', 'آپارتمان', 'پنت‌هاوس', 'زمین']);
}

function areas(): array
{
    return setting('areas', []);
}

function testimonials(): array
{
    return db()->query('SELECT body, author, role, photo FROM testimonials WHERE active = 1 ORDER BY position, id')->fetchAll();
}

/* ------------------------------ کوئری‌های املاک ------------------------------ */

const PROPERTY_PUBLIC_STATUS = "p.status NOT IN ('پیش‌نویس', 'فروخته شده')";

const PROPERTY_SELECT = "SELECT p.*,
    (SELECT url FROM property_images i WHERE i.property_id = p.id ORDER BY i.position, i.id LIMIT 1) AS image,
    (SELECT COUNT(*) FROM property_images i WHERE i.property_id = p.id) AS image_count
    FROM properties p";

/**
 * جست‌وجوی املاک با فیلترهای صفحه لیست.
 * فیلترها: type, area, price (lt300|300-600|600-1000|gt1000),
 *          size (lt100|100-250|gt250), q, sort, featured
 * خروجی: ['total' => int, 'items' => array]
 */
function query_properties(array $f = [], bool $publicOnly = true): array
{
    $where  = [];
    $params = [];

    if ($publicOnly) {
        $where[] = PROPERTY_PUBLIC_STATUS;
    }

    if (!empty($f['type'])) {
        $where[] = 'p.type = ?';
        $params[] = $f['type'];
    }
    if (!empty($f['area'])) {
        $where[] = 'p.area = ?';
        $params[] = $f['area'];
    }

    $priceBuckets = [
        'lt300'    => [null, 300000],
        '300-600'  => [300000, 600000],
        '600-1000' => [600000, 1000000],
        'gt1000'   => [1000000, null],
    ];
    $price = $f['price'] ?? '';
    if (isset($priceBuckets[$price])) {
        [$min, $max] = $priceBuckets[$price];
        if ($min !== null) {
            $where[] = 'p.price >= ?';
            $params[] = $min;
        }
        if ($max !== null) {
            $where[] = 'p.price < ?';
            $params[] = $max;
        }
    }

    $sizeBuckets = [
        'lt100'   => [null, 100],
        '100-250' => [100, 250],
        'gt250'   => [250, null],
    ];
    $size = $f['size'] ?? '';
    if (isset($sizeBuckets[$size])) {
        [$min, $max] = $sizeBuckets[$size];
        if ($min !== null) {
            $where[] = 'p.size >= ?';
            $params[] = $min;
        }
        if ($max !== null) {
            $where[] = 'p.size < ?';
            $params[] = $max;
        }
    }

    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . str_replace(['%', '_'], '', $q) . '%';
        $where[] = '(p.title LIKE ? OR p.description LIKE ? OR p.area LIKE ? OR p.type LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }

    if (!empty($f['featured'])) {
        $where[] = 'p.featured = 1';
    }

    $order = match ($f['sort'] ?? '') {
        'price-asc'  => 'p.price ASC',
        'price-desc' => 'p.price DESC',
        'size-desc'  => 'p.size DESC',
        default      => 'p.id ASC',   // همان ترتیب نسخه طراحی (به‌ترتیب ورود داده‌ها)
    };

    $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

    $countStmt = db()->prepare("SELECT COUNT(*) FROM properties p$whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $stmt = db()->prepare(PROPERTY_SELECT . $whereSql . " ORDER BY $order");
    $stmt->execute($params);

    return ['total' => $total, 'items' => array_map('normalize_property', $stmt->fetchAll())];
}

function normalize_property(array $row): array
{
    foreach (['id', 'price', 'size', 'rooms', 'baths', 'year', 'image_count'] as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null) {
            $row[$k] = (int) $row[$k];
        }
    }
    $row['featured'] = (bool) ($row['featured'] ?? 0);
    return $row;
}

function featured_properties(int $limit = 4): array
{
    $items = query_properties(['featured' => 1])['items'];
    return array_slice($items, 0, $limit);
}

function get_property(string $slug): ?array
{
    $stmt = db()->prepare(PROPERTY_SELECT . ' WHERE p.slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if ($row === false) {
        return null;
    }
    $p = normalize_property($row);
    $p['images']   = property_images($p['id']);
    $p['features'] = property_features($p['id']);
    return $p;
}

function property_images(int $id): array
{
    $stmt = db()->prepare('SELECT url FROM property_images WHERE property_id = ? ORDER BY position, id');
    $stmt->execute([$id]);
    return array_column($stmt->fetchAll(), 'url');
}

function property_features(int $id): array
{
    $stmt = db()->prepare('SELECT feature FROM property_features WHERE property_id = ? ORDER BY position, id');
    $stmt->execute([$id]);
    return array_column($stmt->fetchAll(), 'feature');
}

/** پیشنهادهای مشابه: اول هم‌نوع، بعد بقیه — همان منطق نسخه طراحی */
function similar_properties(array $p, int $limit = 3): array
{
    $base = PROPERTY_SELECT . ' WHERE p.id != ? AND ' . PROPERTY_PUBLIC_STATUS;

    $stmt = db()->prepare($base . ' AND p.type = ? ORDER BY p.id ASC LIMIT ' . $limit);
    $stmt->execute([$p['id'], $p['type']]);
    $items = array_map('normalize_property', $stmt->fetchAll());

    if (count($items) < $limit) {
        $stmt = db()->prepare($base . ' AND p.type != ? ORDER BY p.id ASC LIMIT ' . ($limit - count($items)));
        $stmt->execute([$p['id'], $p['type']]);
        $items = array_merge($items, array_map('normalize_property', $stmt->fetchAll()));
    }
    return $items;
}

/* ------------------------------ آیکون‌های SVG (از نسخه طراحی) ------------------------------ */

const ICO_PIN = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
const ICO_AREA = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3h18v18H3z"/><path d="M3 9h6V3"/></svg>';
const ICO_BED = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/></svg>';
const ICO_HEART = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8Z"/></svg>';
const ICO_ARROW = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>';

/** رندر کارت ملک — همان ساختار نسخه طراحی (cardHTML در app.js) */
function property_card(array $p): string
{
    $specs = [];
    if (!empty($p['rooms'])) {
        $specs[] = ICO_BED . ' <b>' . fa_num($p['rooms']) . '</b> خواب';
    }
    $specs[] = ICO_AREA . ' <b>' . fa_num($p['size']) . '</b> متر';
    if (!empty($p['year'])) {
        $specs[] = '<b>' . fa_num($p['year']) . '</b> ساخت';
    }

    $href = 'property.php?id=' . rawurlencode($p['slug']);
    $img  = img_w($p['image'] ?? '', 900);

    return '<article class="prop-card reveal">'
        . '<div class="prop-card__media">'
        . '<img src="' . e($img) . '" alt="' . e($p['title']) . '" loading="lazy" width="900" height="675">'
        . '<div class="prop-card__badges">'
        . '<span class="pill pill--coral">' . e($p['type']) . '</span>'
        . (!empty($p['badge']) ? '<span class="pill">' . e($p['badge']) . '</span>' : '')
        . '</div>'
        . '<button class="prop-card__fav" type="button" aria-label="ذخیره ملک">' . ICO_HEART . '</button>'
        . '</div>'
        . '<div class="prop-card__body">'
        . '<div class="prop-card__loc">' . ICO_PIN . ' ' . e($p['area']) . '، ' . e($p['city']) . '</div>'
        . '<h3 class="prop-card__title"><a href="' . e($href) . '">' . e($p['title']) . '</a></h3>'
        . '<div class="prop-card__specs">' . implode('<span>·</span>', $specs) . '</div>'
        . '<div class="prop-card__foot">'
        . '<div class="price"><span dir="ltr">' . e(fa_price($p['price'])) . '</span><small>قیمت کل</small></div>'
        . '<a class="card-link" href="' . e($href) . '" aria-label="مشاهده جزئیات">' . ICO_ARROW . '</a>'
        . '</div></div></article>';
}

/** رندر نمودار نظر مشتری */
function testimonial_card(array $t): string
{
    return '<figure class="quote reveal">'
        . '<div class="quote__stars">★★★★★</div>'
        . '<p>«' . e($t['body']) . '»</p>'
        . '<figcaption class="quote__who">'
        . '<img src="' . e(img_w($t['photo'], 160)) . '" alt="' . e($t['author']) . '" loading="lazy" width="46" height="46">'
        . '<div><b>' . e($t['author']) . '</b><span>' . e($t['role']) . '</span></div>'
        . '</figcaption></figure>';
}
