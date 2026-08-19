<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';
$email = '';
$accountType = 'personal';
$businessName = '';
$businessCategory = '';
$businessWebsite = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $accountType = ($_POST['account_type'] ?? 'personal') === 'business' ? 'business' : 'personal';
    $businessName = sanitize_input($_POST['business_name'] ?? '');
    $businessCategory = sanitize_input($_POST['business_category'] ?? '');
    $businessWebsite = sanitize_input($_POST['business_website'] ?? '');

    if ($username === '' || strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($accountType === 'business' && $businessName === '') {
        $error = 'Please enter your business name.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'That username or email is already registered.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password, account_type, business_name, business_category, business_website)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $accountType,
                $accountType === 'business' ? $businessName : null,
                $accountType === 'business' ? $businessCategory : null,
                $accountType === 'business' ? $businessWebsite : null,
            ]);
            loginUser((int) $pdo->lastInsertId(), $username);
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Sign Up';
$layout = 'auth';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="auth-card">
  <div class="auth-intro">
    <h1>Join Connect</h1>
    <p>Post short videos about your business or your day, follow the people and brands you care about, and reach real customers — as yourself or as a business.</p>
  </div>
  <div class="auth-form-side">
    <h2>Create your account</h2>

    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>

    <form method="POST" autocomplete="off">
      <?= csrfField() ?>

      <div class="form-group">
        <label class="form-label">Account type</label>
        <div class="radio-card-group">
          <label class="radio-card">
            <input type="radio" name="account_type" value="personal" <?= $accountType === 'personal' ? 'checked' : '' ?> onclick="document.getElementById('businessFields').style.display='none'">
            <strong>Personal</strong>
            <span>Share your own content</span>
          </label>
          <label class="radio-card">
            <input type="radio" name="account_type" value="business" <?= $accountType === 'business' ? 'checked' : '' ?> onclick="document.getElementById('businessFields').style.display='block'">
            <strong>Business</strong>
            <span>Promote products & services</span>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" type="text" value="<?= e($username) ?>" required minlength="3">
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="<?= e($email) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password" required minlength="8">
        <span class="form-hint">At least 8 characters.</span>
      </div>

      <div id="businessFields" style="display: <?= $accountType === 'business' ? 'block' : 'none' ?>;">
        <div class="form-group">
          <label class="form-label" for="business_name">Business name</label>
          <input class="form-control" id="business_name" name="business_name" type="text" value="<?= e($businessName) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="business_category">Category</label>
          <select class="form-control" id="business_category" name="business_category">
            <option value="">Select a category</option>
            <?php foreach (BUSINESS_CATEGORIES as $slug => $label): ?>
              <option value="<?= e($slug) ?>" <?= $businessCategory === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="business_website">Website (optional)</label>
          <input class="form-control" id="business_website" name="business_website" type="url" value="<?= e($businessWebsite) ?>" placeholder="https://">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>

    <div class="auth-links">Already have an account? <a href="login.php">Log in</a></div>
  </div>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
