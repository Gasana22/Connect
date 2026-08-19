<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;

$stmt = getPDO()->prepare('SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()');
$stmt->execute([$token]);
$resetRecord = $stmt->fetch();

if (!$resetRecord) {
    $error = 'This reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = getPDO();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $resetRecord['user_id']]);
        $pdo->prepare('UPDATE password_reset_tokens SET used = 1 WHERE id = ?')
            ->execute([$resetRecord['id']]);
        $pdo->commit();
        $success = true;
    }
}

$pageTitle = 'Reset Password';
$layout = 'auth';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="auth-card">
  <div class="auth-intro">
    <h1>Choose a new password</h1>
    <p>Make it something you don't use anywhere else.</p>
  </div>
  <div class="auth-form-side">
    <h2>Set new password</h2>

    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-circle-check"></i> Your password has been reset.</div>
      <a class="btn btn-primary btn-block" href="login.php">Log in</a>
    <?php elseif ($resetRecord): ?>
      <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
          <label class="form-label" for="password">New password</label>
          <input class="form-control" id="password" name="password" type="password" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm password</label>
          <input class="form-control" id="confirm_password" name="confirm_password" type="password" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Reset password</button>
      </form>
    <?php else: ?>
      <a class="btn btn-outline btn-block" href="forgot-password.php">Request a new link</a>
    <?php endif; ?>

    <div class="auth-links"><a href="login.php">Back to log in</a></div>
  </div>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
