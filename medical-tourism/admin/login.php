<?php
require_once __DIR__ . '/../includes/init.php';

if (!empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['admin_role'] = $admin['role'];
        $upd = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $upd->execute([$admin['id']]);
        redirect(BASE_URL . '/admin/index.php');
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | <?= e(setting($pdo, 'site_name')) ?></title>
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-brand-light d-flex align-items-center" style="min-height:100vh;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-card p-4 p-md-5">
        <div class="text-center mb-4">
          <img src="<?= e(BASE_URL) ?>/assets/img/logo.svg" alt="<?= e(setting($pdo, 'site_name')) ?>" style="height:52px;">
          <p class="text-muted small mt-3 mb-0">Sign in to manage your site</p>
        </div>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 rounded-pill">Sign In</button>
        </form>
        <p class="text-center text-muted small mt-4 mb-0"><a href="<?= e(BASE_URL) ?>/index.php">&larr; Back to website</a></p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
