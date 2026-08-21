<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Destination deleted.');
    redirect(BASE_URL . '/marinka/destinations.php');
}

$destinations = $pdo->query("SELECT * FROM destinations ORDER BY name ASC")->fetchAll();

$pageTitle = 'Destinations';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($destinations) ?> destination(s)</p>
  <a href="<?= e(BASE_URL) ?>/marinka/destination-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Destination</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Image</th><th>Name</th><th>Avg Savings</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($destinations as $d): ?>
        <tr>
          <td><img src="<?= e(img_url('destinations', $d['image'])) ?>" class="thumb-sm"></td>
          <td><?= e($d['name']) ?></td>
          <td><?= e($d['avg_savings']) ?></td>
          <td><span class="badge bg-<?= $d['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($d['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/marinka/destination-form.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this destination? Related hospitals will also be removed.');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$d['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$destinations): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No destinations yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
