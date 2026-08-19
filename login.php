<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = getPDO()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        loginUser((int) $user['id'], $user['username']);
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect email or password.';
}

$pageTitle = 'Log In';
$layout = 'auth';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="auth-card">
  <div class="auth-intro">
    <h1>Welcome back</h1>
    <p>Discover businesses and creators near you, catch up on what people you follow are posting, and keep building your own audience.</p>
  </div>
  <div class="auth-form-side">
    <h2>Log in to your account</h2>

    <?php if (($_GET['timeout'] ?? '') === '1'): ?>
      <div class="alert alert-error"><i class="fas fa-clock"></i> You were logged out due to inactivity.</div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>

    <form method="POST" autocomplete="off">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>

    <div class="auth-links">
      <a href="forgot-password.php">Forgot password?</a><br>
      Don't have an account? <a href="signup.php">Sign up</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
