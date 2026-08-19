<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';
$devResetLink = null; // shown only when CONNECT_DEBUG=1 — no SMTP is configured for this deployment yet

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = getPDO()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same success message regardless of whether the email exists,
    // so this form can't be used to enumerate registered accounts.
    $success = 'If an account exists for that email, a password reset link has been generated.';

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = getPDO()->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $token, $expires]);

        $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
        if (env('CONNECT_DEBUG', '0') === '1') {
            $devResetLink = $resetLink;
        }
        // TODO: send $resetLink by email once SMTP credentials are configured.
    }
}

$pageTitle = 'Forgot Password';
$layout = 'auth';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="auth-card">
  <div class="auth-intro">
    <h1>Forgot your password?</h1>
    <p>Enter the email on your account and we'll generate a link to reset it.</p>
  </div>
  <div class="auth-form-side">
    <h2>Reset password</h2>

    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= e($success) ?></div>
      <?php if ($devResetLink): ?>
        <div class="alert alert-error"><i class="fas fa-flask"></i> Dev mode (no email sending configured): <a href="<?= e($devResetLink) ?>"><?= e($devResetLink) ?></a></div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>

    <div class="auth-links"><a href="login.php">Back to log in</a></div>
  </div>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
