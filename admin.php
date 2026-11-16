<?php
declare(strict_types=1);

/**
 * پنل مدیریت کیانی املاک — داشبورد، مدیریت املاک، درخواست‌ها و محتوای سایت
 * نیازمند ورود (login.php) و توکن CSRF برای همه عملیات نوشتن.
 */

require_once __DIR__ . '/includes/init.php';

$admin = require_admin();

const PROPERTY_STATUS_LIST = ['فروش', 'رزرو شده', 'فروخته شده', 'پیش‌نویس'];
const LEAD_STATUS_LIST     = ['جدید', 'در حال پیگیری', 'بسته شده'];

/* ================= پردازش عملیات POST ================= */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $action = (string) ($_POST['action'] ?? '');

    switch ($action) {
        case 'save_property':
            $id     = (int) ($_POST['id'] ?? 0);
            $intent = ($_POST['intent'] ?? 'publish') === 'draft' ? 'draft' : 'publish';

            $title   = trim((string) ($_POST['title'] ?? ''));
            $type    = trim((string) ($_POST['type'] ?? ''));
            $area    = trim((string) ($_POST['area'] ?? ''));
            // ارقام و جداکننده‌های فارسی فرم به لاتین تبدیل می‌شوند
            $num = static fn (string $v): string => to_latin_digits(str_replace(['٬', ',', ' '], '', trim($v)));
            $price = filter_var($num((string) ($_POST['price'] ?? '')), FILTER_VALIDATE_INT);
            $size  = filter_var($num((string) ($_POST['size'] ?? '')), FILTER_VALIDATE_INT);
            $rooms = filter_var($num((string) ($_POST['rooms'] ?? '0')), FILTER_VALIDATE_INT);
            $status  = in_array($_POST['status'] ?? '', PROPERTY_STATUS_LIST, true)
                ? (string) $_POST['status']
                : 'فروش';
            if ($intent === 'draft') {
                $status = 'پیش‌نویس';
            }
            $description = trim((string) ($_POST['description'] ?? ''));
            $seo         = trim((string) ($_POST['seo_description'] ?? ''));

            $images = array_values(array_filter(array_map('trim', explode("\n", (string) ($_POST['images'] ?? '')))));
            $features = array_values(array_filter(array_map('trim', explode("\n", (string) ($_POST['features'] ?? '')))));

            $bad = $title === '' || strlen($title) < 2
                || $type === '' || $area === ''
                || $price === false || $price < 0
                || $size === false || $size < 0
                || ($rooms !== false && $rooms < 0)
                || strlen($title) > 600 || strlen($description) > 8000 || strlen($seo) > 600
                || count($images) > 10 || count($features) > 40;

            foreach ($images as $u) {
                $okUrl = strlen($u) <= 600 && (
                    (bool) preg_match('#^https?://#i', $u) || str_starts_with($u, '/')
                );
                if (!$okUrl) {
                    $bad = true;
                    break;
                }
            }
            foreach ($features as $f) {
                if ($f === '' || strlen($f) > 300) {
                    $bad = true;
                    break;
                }
            }

            $formBack = '/admin.php?view=form' . ($id > 0 ? '&id=' . $id : '');
            if ($bad) {
                redirect($formBack . '&err=1');
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare('SELECT id FROM properties WHERE id = ?');
                    $stmt->execute([$id]);
                    if (!$stmt->fetchColumn()) {
                        $pdo->rollBack();
                        redirect('/admin.php?view=properties&err=1');
                    }
                    $pdo->prepare(
                        'UPDATE properties SET title=?, type=?, area=?, price=?, size=?, rooms=?, status=?,'
                        . ' description=?, seo_description=?, updated_at=datetime(\'now\') WHERE id=?'
                    )->execute([$title, $type, $area, $price, $size, (int) $rooms, $status, $description, $seo, $id]);
                } else {
                    $slug = '';
                    for ($i = 0; $i < 5 && $slug === ''; $i++) {
                        $candidate = 'p-' . bin2hex(random_bytes(5));
                        $chk = $pdo->prepare('SELECT COUNT(*) FROM properties WHERE slug = ?');
                        $chk->execute([$candidate]);
                        if (!$chk->fetchColumn()) {
                            $slug = $candidate;
                        }
                    }
                    if ($slug === '') {
                        throw new RuntimeException('slug generation failed');
                    }
                    $pdo->prepare(
                        'INSERT INTO properties (slug, title, type, area, city, price, size, rooms, status, description, seo_description)'
                        . ' VALUES (?,?,?,?,?,?,?,?,?,?,?)'
                    )->execute([$slug, $title, $type, $area, 'آنتالیا', $price, $size, (int) $rooms, $status, $description, $seo]);
                    $id = (int) $pdo->lastInsertId();
                }

                // جایگزینی تصاویر و امکانات
                $pdo->prepare('DELETE FROM property_images WHERE property_id = ?')->execute([$id]);
                $ins = $pdo->prepare('INSERT INTO property_images (property_id, url, position) VALUES (?,?,?)');
                foreach ($images as $i => $u) {
                    $ins->execute([$id, $u, $i]);
                }
                $pdo->prepare('DELETE FROM property_features WHERE property_id = ?')->execute([$id]);
                $ins = $pdo->prepare('INSERT INTO property_features (property_id, feature, position) VALUES (?,?,?)');
                foreach ($features as $i => $f) {
                    $ins->execute([$id, $f, $i]);
                }

                $pdo->commit();
            } catch (Throwable $ex) {
                $pdo->rollBack();
                error_log('[admin] save_property: ' . $ex->getMessage());
                redirect($formBack . '&err=1');
            }

            redirect('/admin.php?view=properties&saved=1');

        case 'delete_property':
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('DELETE FROM properties WHERE id = ?');
            $stmt->execute([$id]);
            redirect('/admin.php?view=properties&deleted=1');

        case 'update_lead':
            $id     = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['status'] ?? '');
            if (!in_array($status, LEAD_STATUS_LIST, true)) {
                redirect('/admin.php?view=leads&err=1');
            }
            $stmt = db()->prepare('UPDATE leads SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            redirect('/admin.php?view=leads&lead_updated=1');

        case 'save_settings':
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '' || strlen($name) > 80) {
                redirect('/admin.php?view=settings&err=1');
            }
            $updated = array_merge(agent(), [
                'name'      => $name,
                'role'      => mb_trim_limit((string) ($_POST['role'] ?? ''), 120),
                'phone'     => mb_trim_limit((string) ($_POST['phone'] ?? ''), 40),
                'phoneHref' => mb_trim_limit((string) ($_POST['phoneHref'] ?? ''), 40),
                'whatsapp'  => mb_trim_limit((string) ($_POST['whatsapp'] ?? ''), 30),
                'email'     => mb_trim_limit((string) ($_POST['email'] ?? ''), 120),
                'address'   => mb_trim_limit((string) ($_POST['address'] ?? ''), 160),
                'photo'     => mb_trim_limit((string) ($_POST['photo'] ?? ''), 600),
            ]);
            db()->prepare('INSERT OR REPLACE INTO settings (name, value) VALUES (?, ?)')
                ->execute(['agent', json_encode($updated, JSON_UNESCAPED_UNICODE)]);
            redirect('/admin.php?view=settings&settings_saved=1');

        default:
            redirect('/admin.php');
    }
}

/** بریدن رشته تا حداکثر طول (بدون وابستگی به mbstring) */
function mb_trim_limit(string $s, int $max): string
{
    $s = trim($s);
    return strlen($s) > $max ? substr($s, 0, $max) : $s;
}

/* ================= داده‌ها و نمای فعلی ================= */

$view = (string) ($_GET['view'] ?? 'dashboard');
if (!in_array($view, ['dashboard', 'properties', 'form', 'leads', 'settings'], true)) {
    $view = 'dashboard';
}
$editId = (int) ($_GET['id'] ?? 0);
$prop   = null;
if ($view === 'form' && $editId > 0) {
    $stmt = db()->prepare('SELECT slug FROM properties WHERE id = ?');
    $stmt->execute([$editId]);
    $slug = (string) $stmt->fetchColumn();
    $prop = $slug !== '' ? get_property($slug) : null;
    if ($prop === null) {
        redirect('/admin.php?view=properties&err=1');
    }
}

function admin_flash(): string
{
    $msgs = [
        'saved'           => '✓ ملک ذخیره شد و در سایت نمایش داده می‌شود.',
        'deleted'         => '✓ ملک حذف شد.',
        'lead_updated'    => '✓ وضعیت درخواست به‌روزرسانی شد.',
        'settings_saved'  => '✓ اطلاعات سایت ذخیره شد.',
        'err'             => '✗ ورودی‌ها معتبر نیستند؛ فیلدها را بررسی کنید.',
    ];
    foreach ($msgs as $key => $text) {
        if (isset($_GET[$key])) {
            return '<div class="form-success is-on">' . $text . '</div>';
        }
    }
    return '';
}

function status_tag(string $status): string
{
    $cls = $status === 'فروش' ? 'tag--live' : ($status === 'پیش‌نویس' ? 'tag--draft' : 'tag--hold');
    return '<span class="tag ' . $cls . '">' . e($status) . '</span>';
}

function lead_status_tag(string $status): string
{
    $cls = match ($status) {
        'جدید'         => 'tag--new',
        'در حال پیگیری' => 'tag--hold',
        default        => 'tag--draft',
    };
    return '<span class="tag ' . $cls . '">' . e($status) . '</span>';
}

function lead_when(string $createdAt): string
{
    $ts = strtotime($createdAt) ?: time();
    $days = (int) floor((time() - $ts) / 86400);
    return match (true) {
        $days <= 0  => 'امروز',
        $days == 1  => 'دیروز',
        default     => fa_num($days) . ' روز پیش',
    };
}

/** جدول املاک (مشترک بین داشبورد و صفحه املاک) */
function admin_table(array $items): string
{
    if ($items === []) {
        return '<div class="empty" style="padding:30px 0"><h3>ملکی ثبت نشده است</h3>'
            . '<p><a class="btn btn--primary" href="admin.php?view=form">افزودن اولین ملک</a></p></div>';
    }

    $html = '<div class="table-wrap"><table class="table"><thead><tr>'
        . '<th>ملک</th><th>نوع</th><th>قیمت</th><th>متراژ</th><th>وضعیت</th><th>عملیات</th>'
        . '</tr></thead><tbody>';

    foreach ($items as $p) {
        $html .= '<tr>'
            . '<td><div class="table__item">'
            . '<img src="' . e(img_w($p['image'] ?? '', 200)) . '" alt="" loading="lazy">'
            . '<div><b>' . e($p['title']) . '</b><span>' . e($p['area']) . '، ' . e($p['city']) . '</span></div>'
            . '</div></td>'
            . '<td>' . e($p['type']) . '</td>'
            . '<td class="tnum"><span dir="ltr">' . e(fa_price($p['price'])) . '</span></td>'
            . '<td class="tnum">' . fa_num($p['size']) . ' متر</td>'
            . '<td>' . status_tag($p['status']) . '</td>'
            . '<td><div class="row-actions">'
            . '<form method="get" action="admin.php" style="display:inline">'
            . '<input type="hidden" name="view" value="form"><input type="hidden" name="id" value="' . $p['id'] . '">'
            . '<button type="submit" title="ویرایش">✎</button></form>'
            . '<form method="get" action="property.php" target="_blank" style="display:inline">'
            . '<input type="hidden" name="id" value="' . e($p['slug']) . '">'
            . '<button type="submit" title="پیش‌نمایش">◉</button></form>'
            . '<form method="post" action="admin.php" style="display:inline" '
            . 'onsubmit="return confirm(\'ملک «' . e(addslashes($p['title'])) . '» حذف شود؟\')">'
            . csrf_field()
            . '<input type="hidden" name="action" value="delete_property">'
            . '<input type="hidden" name="id" value="' . $p['id'] . '">'
            . '<button type="submit" title="حذف">✕</button></form>'
            . '</div></td>'
            . '</tr>';
    }

    return $html . '</tbody></table></div>';
}

/** آیتم درخواست مشتری */
function lead_item(array $l, bool $withStatusForm): string
{
    $initial = preg_match('/^./u', $l['name'], $m) ? $m[0] : '؟';
    $sub = '<span class="num">' . e(fa_phone($l['phone'])) . '</span>'
        . ($l['topic'] !== '' ? ' · ' . e($l['topic']) : '')
        . ($l['property_title'] ? ' · ' . e($l['property_title']) : '');

    $html = '<div class="lead-item">'
        . '<div class="lead-item__av">' . e($initial) . '</div>'
        . '<div><b>' . e($l['name']) . '</b><span>' . $sub . '</span></div>';

    if ($withStatusForm) {
        $html .= '<span class="tag" style="font-size:11.5px">' . e(lead_when($l['created_at'])) . '</span>'
            . '<form method="post" action="admin.php" style="margin-inline-start:8px;display:flex;gap:6px;align-items:center">'
            . csrf_field()
            . '<input type="hidden" name="action" value="update_lead">'
            . '<input type="hidden" name="id" value="' . $l['id'] . '">'
            . '<select name="status" data-autosubmit aria-label="وضعیت پیگیری" '
            . 'style="font-size:12.5px;height:32px;border:1px solid var(--line);border-radius:8px;padding:0 8px;background:#fff">';
        foreach (LEAD_STATUS_LIST as $s) {
            $html .= '<option value="' . e($s) . '"' . ($l['status'] === $s ? ' selected' : '') . '>' . e($s) . '</option>';
        }
        $html .= '</select></form>';
    } else {
        $html .= lead_status_tag($l['status']);
    }

    return $html . '</div>';
}

/* ================= داده‌های نمای فعلی ================= */

$stats = [
    'total'      => (int) db()->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
    'active'     => (int) db()->query("SELECT COUNT(*) FROM properties WHERE status IN ('فروش', 'رزرو شده')")->fetchColumn(),
    'published'  => (int) db()->query('SELECT COUNT(*) FROM properties WHERE ' . str_replace('p.', '', PROPERTY_PUBLIC_STATUS))->fetchColumn(),
    'leads'      => (int) db()->query('SELECT COUNT(*) FROM leads')->fetchColumn(),
    'leads_new'  => (int) db()->query("SELECT COUNT(*) FROM leads WHERE status = 'جدید'")->fetchColumn(),
    'leads_week' => (int) db()->query("SELECT COUNT(*) FROM leads WHERE created_at >= datetime('now', '-7 days')")->fetchColumn(),
];

$titles = [
    'dashboard' => 'داشبورد',
    'properties' => 'املاک من',
    'form'      => $prop !== null ? 'ویرایش ملک' : 'افزودن ملک',
    'leads'     => 'درخواست مشتریان',
    'settings'  => 'محتوای سایت',
];
$agent = agent();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>پنل مدیریت | کیانی املاک</title>
<meta name="robots" content="noindex">
<meta name="theme-color" content="#ff5a45">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">

<div class="admin">
  <!-- ============ SIDEBAR ============ -->
  <aside class="sidebar">
    <a class="brand" href="index.php">
      <span class="brand__mark">K</span>
      <span class="brand__text">
        <span class="brand__name">کیانی املاک</span>
        <span class="brand__tag">پنل مدیریت</span>
      </span>
    </a>

    <div class="sidebar__label">مدیریت</div>
    <a class="<?= $view === 'dashboard' ? 'is-active' : '' ?>" href="admin.php"><span class="ico">▦</span><span class="txt">داشبورد</span></a>
    <a class="<?= $view === 'properties' || $view === 'form' ? 'is-active' : '' ?>" href="admin.php?view=properties"><span class="ico">⌂</span><span class="txt">املاک من</span></a>
    <a class="<?= $view === 'leads' ? 'is-active' : '' ?>" href="admin.php?view=leads"><span class="ico">✉</span><span class="txt">درخواست مشتریان</span></a>
    <a class="<?= $view === 'settings' ? 'is-active' : '' ?>" href="admin.php?view=settings"><span class="ico">✎</span><span class="txt">محتوای سایت</span></a>

    <div class="sidebar__label">عمومی</div>
    <a href="properties.php"><span class="ico">◎</span><span class="txt">مشاهده سایت</span></a>
    <a href="logout.php"><span class="ico">↩</span><span class="txt">خروج از پنل</span></a>

    <div class="sidebar__foot">
      <b>دیتابیس زنده</b>
      همه تغییرات بلافاصله در سایت اعمال می‌شوند.
    </div>
  </aside>

  <!-- ============ MAIN ============ -->
  <div class="admin__main">
    <div class="topbar">
      <div>
        <h1><?= e($titles[$view]) ?></h1>
        <p>به‌روزرسانی لحظه‌ای از دیتابیس سایت</p>
      </div>
      <div class="topbar__side">
        <form class="search" method="get" action="admin.php">
          <input type="hidden" name="view" value="properties">
          <span class="search__icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
          <input name="q" type="search" value="<?= e((string) ($_GET['q'] ?? '')) ?>" placeholder="جست‌وجوی ملک…" style="height:44px;border-radius:999px">
        </form>
        <a class="btn btn--primary btn--sm" href="admin.php?view=form">+ افزودن ملک</a>
        <div class="topbar__user">
          <img src="<?= e(img_w($agent['photo'], 120)) ?>" alt="<?= e($agent['name']) ?>" width="40" height="40">
          <div><b><?= e($admin['full_name'] !== '' ? $admin['full_name'] : $agent['name']) ?></b><span>مدیر سایت</span></div>
        </div>
      </div>
    </div>

    <?= admin_flash() ?>

<?php if ($view === 'dashboard'): ?>

    <!-- stats -->
    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-card__ico">⌂</div>
        <div><span>ملک‌های فعال</span><b><?= fa_num($stats['active']) ?></b><small>از <?= fa_num($stats['total']) ?> فایل کل</small></div>
      </div>
      <div class="stat-card">
        <div class="stat-card__ico">✉</div>
        <div><span>درخواست جدید</span><b><?= fa_num($stats['leads_new']) ?></b><small>از <?= fa_num($stats['leads']) ?> درخواست کل</small></div>
      </div>
      <div class="stat-card">
        <div class="stat-card__ico">☏</div>
        <div><span>شماره تماس ثبت‌شده</span><b><?= fa_num($stats['leads']) ?></b><small><?= fa_num($stats['leads_week']) ?> مورد این هفته</small></div>
      </div>
      <div class="stat-card">
        <div class="stat-card__ico">◈</div>
        <div><span>املاک منتشرشده</span><b><?= fa_num($stats['published']) ?></b><small><?= fa_num(max(0, $stats['total'] - $stats['published'])) ?> پیش‌نویس/فروخته‌شده</small></div>
      </div>
    </div>

    <div class="admin__cols">
      <div>
        <!-- listings table -->
        <section class="panel">
          <div class="panel__head">
            <div>
              <h2>املاک منتشرشده</h2>
              <p>برای ویرایش روی هر ردیف کلیک کنید</p>
            </div>
            <a class="link-arrow" href="admin.php?view=properties" style="font-size:13px">همه</a>
          </div>
          <?php $dashProps = array_slice(query_properties([])['items'], 0, 6); ?>
          <?= admin_table($dashProps) ?>
        </section>
      </div>

      <!-- right column -->
      <div>
        <section class="panel" style="margin-top:0">
          <div class="panel__head">
            <div>
              <h2>درخواست‌های اخیر</h2>
              <p>شماره تماس‌های دریافتی از سایت</p>
            </div>
            <a class="link-arrow" href="admin.php?view=leads" style="font-size:13px">همه</a>
          </div>
          <div class="panel__body">
            <?php
            $recentLeads = db()->query(
                'SELECT l.*, p.title AS property_title FROM leads l'
                . ' LEFT JOIN properties p ON p.id = l.property_id ORDER BY l.id DESC LIMIT 5'
            )->fetchAll();
            if ($recentLeads === []) {
                echo '<p class="muted" style="font-size:14px">هنوز درخواستی ثبت نشده است.</p>';
            }
            foreach ($recentLeads as $l) {
                echo lead_item($l, false);
            }
            ?>
          </div>
        </section>

        <section class="panel">
          <div class="panel__head">
            <div>
              <h2>دسترسی سریع</h2>
              <p>صفحات پرمخاطب سایت</p>
            </div>
          </div>
          <div class="panel__body" style="display:grid;gap:10px">
            <a class="btn btn--dark btn--sm btn--block" href="properties.php">مشاهده صفحه املاک</a>
            <a class="btn btn--ghost btn--sm btn--block" href="index.php">مشاهده صفحه اصلی</a>
            <a class="btn btn--ghost btn--sm btn--block" href="admin.php?view=leads">درخواست‌های مشتریان</a>
          </div>
        </section>
      </div>
    </div>

<?php elseif ($view === 'properties'): ?>

    <section class="panel">
      <div class="panel__head">
        <div>
          <h2>همه املاک</h2>
          <p>افزودن، ویرایش و حذف فایل‌ها</p>
        </div>
        <div class="chips">
          <?php
          $st = (string) ($_GET['status'] ?? '');
          foreach (['' => 'همه', 'فروش' => 'فروش', 'رزرو شده' => 'رزرو', 'پیش‌نویس' => 'پیش‌نویس'] as $val => $label) {
              $qs = http_build_query(array_filter(['view' => 'properties', 'status' => $val, 'q' => (string) ($_GET['q'] ?? '')]));
              echo '<a class="chip' . ($st === $val ? ' is-active' : '') . '" href="admin.php?' . e($qs) . '">' . e($label) . '</a>';
          }
          ?>
        </div>
      </div>

      <?php
      $where  = [];
      $params = [];
      $st = (string) ($_GET['status'] ?? '');
      if (in_array($st, PROPERTY_STATUS_LIST, true)) {
          $where[] = 'p.status = ?';
          $params[] = $st;
      }
      $q = trim((string) ($_GET['q'] ?? ''));
      if ($q !== '') {
          $where[] = '(p.title LIKE ? OR p.area LIKE ? OR p.city LIKE ?)';
          $like = '%' . str_replace(['%', '_'], '', $q) . '%';
          array_push($params, $like, $like, $like);
      }
      $sql = PROPERTY_SELECT . ($where ? (' WHERE ' . implode(' AND ', $where)) : '') . ' ORDER BY p.id DESC';
      $stmt = db()->prepare($sql);
      $stmt->execute($params);
      $allProps = array_map('normalize_property', $stmt->fetchAll());
      ?>
      <?= admin_table($allProps) ?>
    </section>

<?php elseif ($view === 'form'): ?>

    <section class="panel" id="new-property">
      <div class="panel__head">
        <div>
          <h2><?= $prop !== null ? 'ویرایش ملک' : 'افزودن ملک' ?></h2>
          <p>اطلاعات پس از ذخیره بلافاصله در صفحه املاک نمایش داده می‌شود</p>
        </div>
        <?php if ($prop !== null) { echo status_tag($prop['status']); } else { echo '<span class="tag tag--draft">پیش‌نویس</span>'; } ?>
      </div>

      <div class="panel__body">
        <form method="post" action="admin.php" class="form-cols">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save_property">
          <input type="hidden" name="id" value="<?= $prop !== null ? (int) $prop['id'] : 0 ?>">

          <div class="field field--full">
            <label for="a-title">عنوان ملک</label>
            <input id="a-title" name="title" type="text" required value="<?= e($prop['title'] ?? '') ?>" placeholder="مثلاً ویلای استخردار با ویو دریا در کمر">
          </div>

          <div class="field field--select">
            <label for="a-type">نوع ملک</label>
            <select id="a-type" name="type">
              <?php foreach (types() as $t) { ?>
              <option value="<?= e($t) ?>"<?= ($prop['type'] ?? 'ویلا') === $t ? ' selected' : '' ?>><?= e($t) ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="field field--select">
            <label for="a-area">محدوده</label>
            <select id="a-area" name="area">
              <?php foreach (areas() as $a) { ?>
              <option value="<?= e($a) ?>"<?= ($prop['area'] ?? '') === $a ? ' selected' : '' ?>><?= e($a) ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="field">
            <label for="a-price">قیمت (دلار)</label>
            <input id="a-price" name="price" type="text" inputmode="numeric" required value="<?= $prop !== null ? fa_num($prop['price']) : '' ?>" placeholder="۴۵۰٬۰۰۰">
          </div>

          <div class="field">
            <label for="a-size">متراژ (متر مربع)</label>
            <input id="a-size" name="size" type="text" inputmode="numeric" required value="<?= $prop !== null ? fa_num($prop['size']) : '' ?>" placeholder="۱۸۰">
          </div>

          <div class="field">
            <label for="a-rooms">تعداد اتاق خواب</label>
            <input id="a-rooms" name="rooms" type="text" inputmode="numeric" value="<?= $prop !== null && $prop['rooms'] ? fa_num($prop['rooms']) : '' ?>" placeholder="۳">
          </div>

          <div class="field field--select">
            <label for="a-status">وضعیت</label>
            <select id="a-status" name="status">
              <?php foreach (PROPERTY_STATUS_LIST as $s) { ?>
              <option value="<?= e($s) ?>"<?= ($prop['status'] ?? 'فروش') === $s ? ' selected' : '' ?>><?= e($s) ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="field field--full">
            <label for="a-desc">توضیحات ملک (پاراگراف‌ها با خط خالی جدا شوند)</label>
            <textarea id="a-desc" name="description" rows="5" placeholder="موقعیت، امتیازات، دسترسی‌ها و نکات حقوقی…"><?= e($prop['description'] ?? '') ?></textarea>
          </div>

          <div class="field field--full">
            <label for="a-images">گالری تصاویر (هر خط یک آدرس، حداکثر ۱۰)</label>
            <textarea id="a-images" name="images" rows="4" placeholder="https://…&#10;/uploads/villa-2.jpg"><?= e($prop !== null ? implode("\n", $prop['images']) : '') ?></textarea>
          </div>

          <div class="field field--full">
            <label for="a-features">امکانات و ویژگی‌ها (هر خط یک مورد)</label>
            <textarea id="a-features" name="features" rows="4" placeholder="استخر خصوصی&#10;ویو مستقیم دریا"><?= e($prop !== null ? implode("\n", $prop['features']) : '') ?></textarea>
          </div>

          <div class="field field--full">
            <label for="a-seo">توضیح سئو (متا دیسکریپشن)</label>
            <input id="a-seo" name="seo_description" type="text" maxlength="600" value="<?= e($prop['seo_description'] ?? '') ?>" placeholder="حداکثر ۱۶۰ کاراکتر برای نتایج گوگل">
          </div>

          <div style="grid-column:1/-1;display:flex;gap:12px;flex-wrap:wrap;margin-top:6px">
            <button class="btn btn--primary" type="submit" name="intent" value="publish">ذخیره و انتشار</button>
            <button class="btn btn--ghost" type="submit" name="intent" value="draft">ذخیره پیش‌نویس</button>
            <?php if ($prop !== null) { ?>
            <a class="btn btn--ghost" target="_blank" href="property.php?id=<?= e(rawurlencode($prop['slug'])) ?>" style="margin-inline-start:auto">پیش‌نمایش صفحه</a>
            <?php } ?>
          </div>
        </form>
      </div>
    </section>

<?php elseif ($view === 'leads'): ?>

    <section class="panel">
      <div class="panel__head">
        <div>
          <h2>درخواست‌های مشتریان</h2>
          <p>شماره تماس‌های دریافتی از فرم‌های سایت</p>
        </div>
        <span class="tag tag--new"><?= fa_num($stats['leads_new']) ?> درخواست جدید</span>
      </div>
      <div class="panel__body">
        <?php
        $allLeads = db()->query(
            'SELECT l.*, p.title AS property_title FROM leads l'
            . ' LEFT JOIN properties p ON p.id = l.property_id ORDER BY l.id DESC LIMIT 200'
        )->fetchAll();
        if ($allLeads === []) {
            echo '<p class="muted" style="font-size:14px">هنوز درخواستی ثبت نشده است؛ فرم‌های سایت اینجا نمایش داده می‌شوند.</p>';
        }
        foreach ($allLeads as $l) {
            echo lead_item($l, true);
        }
        ?>
      </div>
    </section>

<?php elseif ($view === 'settings'): $a = agent(); ?>

    <section class="panel">
      <div class="panel__head">
        <div>
          <h2>محتوای سایت</h2>
          <p>اطلاعات مشاور و راه‌های تماس که در همه صفحات نمایش داده می‌شوند</p>
        </div>
      </div>
      <div class="panel__body">
        <form method="post" action="admin.php" class="form-cols">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save_settings">

          <div class="field">
            <label for="s-name">نام مشاور</label>
            <input id="s-name" name="name" type="text" required value="<?= e($a['name']) ?>">
          </div>

          <div class="field">
            <label for="s-role">عنوان شغلی</label>
            <input id="s-role" name="role" type="text" value="<?= e($a['role']) ?>">
          </div>

          <div class="field">
            <label for="s-phone">شماره تماس (نمایشی)</label>
            <input id="s-phone" name="phone" type="text" value="<?= e($a['phone']) ?>">
          </div>

          <div class="field">
            <label for="s-phonehref">شماره تماس (لینک tel)</label>
            <input id="s-phonehref" name="phoneHref" type="text" value="<?= e($a['phoneHref']) ?>">
          </div>

          <div class="field">
            <label for="s-wa">شناسه واتس‌اپ</label>
            <input id="s-wa" name="whatsapp" type="text" value="<?= e($a['whatsapp']) ?>">
          </div>

          <div class="field">
            <label for="s-email">ایمیل</label>
            <input id="s-email" name="email" type="text" value="<?= e($a['email']) ?>">
          </div>

          <div class="field field--full">
            <label for="s-address">آدرس</label>
            <input id="s-address" name="address" type="text" value="<?= e($a['address']) ?>">
          </div>

          <div class="field field--full">
            <label for="s-photo">آدرس عکس پروفایل</label>
            <input id="s-photo" name="photo" type="text" value="<?= e($a['photo']) ?>" placeholder="https://…">
          </div>

          <div style="grid-column:1/-1;margin-top:6px">
            <button class="btn btn--primary" type="submit">ذخیره تغییرات</button>
          </div>
        </form>
      </div>
    </section>

<?php endif; ?>

  </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
