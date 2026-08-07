<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM treatments WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Treatment deleted.');
    redirect(BASE_URL . '/admin/treatments.php');
}

$treatments = $pdo->query("SELECT * FROM treatments ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Treatments';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($treatments) ?> treatment(s)</p>
  <a href="<?= e(BASE_URL) ?>/admin/treatment-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Treatment</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Image</th><th>Name</th><th>Category</th><th>Price Range</th><th>Featured</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($treatments as $t): ?>
        <tr>
          <td><img src="<?= e(img_url('treatments', $t['image'])) ?>" class="thumb-sm"></td>
          <td><?= e($t['name']) ?></td>
          <td><?= e($t['category']) ?></td>
          <td><?= format_price($t['min_price']) ?> - <?= format_price($t['max_price']) ?><?= $t['show_price'] ? '' : ' <span class="badge bg-secondary">Hidden on site</span>' ?></td>
          <td><?= $t['featured'] ? '<span class="badge bg-warning">Featured</span>' : '' ?></td>
          <td><span class="badge bg-<?= $t['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/admin/treatment-form.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this treatment? This cannot be undone.');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$t['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$treatments): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No treatments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
