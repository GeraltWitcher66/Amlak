<?php
declare(strict_types=1);

require __DIR__ . '/includes/init.php';

start_session();

// اگر قبلاً وارد شده، مستقیم به پنل
if (current_admin() !== null) {
    redirect('/admin.php');
}

$error = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, username, full_name, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin === false || !password_verify($password, $admin['password_hash'])) {
        usleep(300000); // کند کردن حدس رمز
        $error = true;
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        redirect('/admin.php');
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود به پنل مدیریت | کیانی املاک</title>
<meta name="robots" content="noindex">
<meta name="theme-color" content="#ff5a45">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-body">

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
  <form class="form-card" method="post" action="login.php" style="width:100%;max-width:420px">
    <h3>ورود به پنل مدیریت</h3>
    <p>کیانی املاک · فقط برای مدیر سایت</p>

    <?= csrf_field() ?>

    <div class="form-grid">
      <div class="field">
        <label for="lg-user">نام کاربری</label>
        <input id="lg-user" name="username" type="text" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="lg-pass">رمز عبور</label>
        <input id="lg-pass" name="password" type="password" autocomplete="current-password" required>
      </div>

      <button class="btn btn--primary btn--block" type="submit">ورود</button>

      <div class="form-success<?= $error ? ' is-on' : '' ?>">
        <?= $error ? '✗ نام کاربری یا رمز عبور اشتباه است.' : '✓ ورود موفق.' ?>
      </div>

      <p class="form-note" style="text-align:center">
        <a href="index.php" style="color:inherit">بازگشت به سایت</a>
      </p>
    </div>
  </form>
</div>

</body>
</html>
