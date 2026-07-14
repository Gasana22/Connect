<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$me = $stmt->fetch();

if (!$me) {
    redirect(BASE_URL . '/admin/logout.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if ($name === '') {
        flash_set('danger', 'Name is required.');
    } elseif ($newPassword !== '' && !password_verify($currentPassword, $me['password'])) {
        flash_set('danger', 'Your current password is incorrect.');
    } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
        flash_set('danger', 'New password must be at least 8 characters.');
    } else {
        if ($newPassword !== '') {
            $stmt = $pdo->prepare("UPDATE admin_users SET name=?, password=? WHERE id=?");
            $stmt->execute([$name, password_hash($newPassword, PASSWORD_BCRYPT), $me['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE admin_users SET name=? WHERE id=?");
            $stmt->execute([$name, $me['id']]);
        }
        $_SESSION['admin_name'] = $name;
        flash_set('success', 'Profile updated successfully.');
        redirect(BASE_URL . '/admin/profile.php');
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <form method="post" class="stat-card bg-white p-4">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= e($me['name']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" class="form-control" value="<?= e($me['email']) ?>" disabled>
      </div>
      <hr>
      <p class="text-muted small">Leave password fields blank to keep your current password.</p>
      <div class="mb-3">
        <label class="form-label small fw-semibold">Current Password</label>
        <input type="password" name="current_password" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold">New Password</label>
        <input type="password" name="new_password" class="form-control" minlength="8">
      </div>
      <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
