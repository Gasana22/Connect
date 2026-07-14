<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Package deleted.');
    redirect(BASE_URL . '/admin/packages.php');
}

$packages = $pdo->query("
    SELECT p.*, t.name AS treatment_name, h.name AS hospital_name FROM packages p
    JOIN treatments t ON t.id = p.treatment_id
    JOIN hospitals h ON h.id = p.hospital_id
    ORDER BY p.created_at DESC
")->fetchAll();

$pageTitle = 'Packages';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($packages) ?> package(s)</p>
  <a href="<?= e(BASE_URL) ?>/admin/package-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Package</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Image</th><th>Title</th><th>Treatment</th><th>Hospital</th><th>Price</th><th>Featured</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($packages as $p): ?>
        <tr>
          <td><img src="<?= e(img_url('packages', $p['image'])) ?>" class="thumb-sm"></td>
          <td><?= e($p['title']) ?></td>
          <td><?= e($p['treatment_name']) ?></td>
          <td><?= e($p['hospital_name']) ?></td>
          <td><?= format_price($p['price']) ?></td>
          <td><?= $p['featured'] ? '<span class="badge bg-warning">Featured</span>' : '' ?></td>
          <td><span class="badge bg-<?= $p['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($p['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/admin/package-form.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this package?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$packages): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No packages yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
