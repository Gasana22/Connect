<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';
admin_require_role('super_admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$adminUser = ['name' => '', 'email' => '', 'role' => 'editor', 'status' => 'active'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Admin user not found.');
        redirect(BASE_URL . '/admin/admin-users.php');
    }
    $adminUser = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] === 'super_admin' ? 'super_admin' : 'editor';
    $status = $_POST['status'] === 'disabled' ? 'disabled' : 'active';

    $emailCheck = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $id]);

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('danger', 'A valid name and email are required.');
    } elseif ($emailCheck->fetch()) {
        flash_set('danger', 'That email address is already in use by another admin.');
    } elseif (!$id && strlen($password) < 8) {
        flash_set('danger', 'Password must be at least 8 characters.');
    } else {
        if ($id) {
            if ($password !== '') {
                if (strlen($password) < 8) {
                    flash_set('danger', 'Password must be at least 8 characters.');
                    redirect(BASE_URL . '/admin/admin-user-form.php?id=' . $id);
                }
                $stmt = $pdo->prepare("UPDATE admin_users SET name=?, email=?, password=?, role=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admin_users SET name=?, email=?, role=?, status=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $status, $id]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO admin_users (name, email, password, role, status) VALUES (?,?,?,?,?)");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $status]);
        }
        flash_set('success', 'Admin user saved successfully.');
        redirect(BASE_URL . '/admin/admin-users.php');
    }
}

$pageTitle = $id ? 'Edit Admin User' : 'Add Admin User';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Full Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($adminUser['name']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Email Address</label>
      <input type="email" name="email" class="form-control" value="<?= e($adminUser['email']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Password <?= $id ? '(leave blank to keep current)' : '' ?></label>
      <input type="password" name="password" class="form-control" <?= $id ? '' : 'required' ?> minlength="8">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Role</label>
      <select name="role" class="form-select">
        <option value="editor" <?= $adminUser['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
        <option value="super_admin" <?= $adminUser['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="active" <?= $adminUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="disabled" <?= $adminUser['status'] === 'disabled' ? 'selected' : '' ?>>Disabled</option>
      </select>
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Admin User</button>
  <a href="<?= e(BASE_URL) ?>/admin/admin-users.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
