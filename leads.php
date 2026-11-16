<?php
declare(strict_types=1);

/**
 * پذیرش فرم‌های درخواست مشتری (سرنخ) از همه صفحات سایت
 * فیلدها: name, phone, topic, budget, message, property, source, back, anchor
 */

require_once __DIR__ . '/includes/init.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect('/index.php');
}

$name     = trim((string) ($_POST['name'] ?? ''));
$phone    = trim((string) ($_POST['phone'] ?? ''));
$topic    = trim((string) ($_POST['topic'] ?? ''));
$budget   = trim((string) ($_POST['budget'] ?? ''));
$message  = trim((string) ($_POST['message'] ?? ''));
$slug     = trim((string) ($_POST['property'] ?? ''));
$source   = (string) ($_POST['source'] ?? 'site');
$back     = (string) ($_POST['back'] ?? '/index.php');
$anchor   = (string) ($_POST['anchor'] ?? '');

// بازگشت فقط به مسیرهای داخلی سایت
if ($back === '' || $back[0] !== '/' || str_starts_with($back, '//')) {
    $back = '/index.php';
}
if (!preg_match('/^#[A-Za-z0-9_-]+$/', $anchor)) {
    $anchor = '';
}
if (!in_array($source, ['home', 'listing', 'property', 'site'], true)) {
    $source = 'site';
}

$valid = strlen($name) >= 2 && strlen($name) <= 160
    && (bool) preg_match('/^[0-9+()\- ]{5,32}$/', $phone)
    && strlen($topic) <= 200 && strlen($budget) <= 200 && strlen($message) <= 2000;

$sep  = str_contains($back, '?') ? '&' : '?';

if (!$valid) {
    redirect($back . $sep . 'err=1' . $anchor);
}

// لینک به ملک (در صورت ارسال اسلاگ معتبر)
$propertyId = null;
if ($slug !== '') {
    $stmt = db()->prepare('SELECT id FROM properties WHERE slug = ?');
    $stmt->execute([$slug]);
    $found = $stmt->fetchColumn();
    if ($found !== false) {
        $propertyId = (int) $found;
    }
}

$stmt = db()->prepare(
    'INSERT INTO leads (name, phone, topic, budget, message, property_id, source) VALUES (?,?,?,?,?,?,?)'
);
$stmt->execute([$name, $phone, $topic, $budget, $message, $propertyId, $source]);

redirect($back . $sep . 'sent=1' . $anchor);
