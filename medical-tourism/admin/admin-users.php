<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';
admin_require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $deleteId = (int)$_POST['delete_id'];
    if ($deleteId === (int)$_SESSION['admin_id']) {
        flash_set('danger', 'You cannot delete your own account while logged in.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->execute([$deleteId]);
        flash_set('success', 'Admin user deleted.');
    }
    redirect(BASE_URL . '/admin/admin-users.php');
}

$admins = $pdo->query("SELECT * FROM admin_users ORDER BY created_at ASC")->fetchAll();

$pageTitle = 'Admin Users';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($admins) ?> admin user(s)</p>
  <a href="<?= e(BASE_URL) ?>/admin/admin-user-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Admin User</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($admins as $a): ?>
        <tr>
          <td><?= e($a['name']) ?> <?= (int)$a['id'] === (int)$_SESSION['admin_id'] ? '<span class="badge bg-info">You</span>' : '' ?></td>
          <td><?= e($a['email']) ?></td>
          <td><span class="badge bg-light text-dark"><?= e(str_replace('_', ' ', $a['role'])) ?></span></td>
          <td><span class="badge bg-<?= $a['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($a['status'])) ?></span></td>
          <td class="text-muted small"><?= $a['last_login'] ? e(date('M j, Y g:i A', strtotime($a['last_login']))) : 'Never' ?></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/admin/admin-user-form.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php if ((int)$a['id'] !== (int)$_SESSION['admin_id']): ?>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this admin user?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$a['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
