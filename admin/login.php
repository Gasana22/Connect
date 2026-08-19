<?php
require_once __DIR__ . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateAdminCsrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (attemptAdminLogin($email, $password)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

$pageTitle = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Connect</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<div class="auth-shell">
  <div class="card card-pad" style="max-width: 400px; width: 100%;">
    <div style="text-align:center; margin-bottom:1.5rem;">
      <i class="fas fa-shield-halved" style="font-size:2.5rem; color:var(--brand);"></i>
      <h1 class="mt-1">Admin Panel</h1>
      <p class="text-muted">Sign in to access the dashboard</p>
    </div>

    <?php if (($_GET['timeout'] ?? '') === '1'): ?><div class="alert alert-error"><i class="fas fa-clock"></i> Session expired. Please log in again.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-right-to-bracket"></i> Sign In</button>
    </form>
  </div>
</div>
</body>
</html>
