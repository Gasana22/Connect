<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM hospitals WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Hospital deleted.');
    redirect(BASE_URL . '/marinka/hospitals.php');
}

$hospitals = $pdo->query("
    SELECT h.*, d.name AS destination_name FROM hospitals h
    JOIN destinations d ON d.id = h.destination_id
    ORDER BY h.created_at DESC
")->fetchAll();

$pageTitle = 'Hospitals';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($hospitals) ?> hospital(s)</p>
  <a href="<?= e(BASE_URL) ?>/marinka/hospital-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Hospital</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Image</th><th>Name</th><th>Destination</th><th>City</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($hospitals as $h): ?>
        <tr>
          <td><img src="<?= e(img_url('hospitals', $h['image'])) ?>" class="thumb-sm"></td>
          <td><?= e($h['name']) ?></td>
          <td><?= e($h['destination_name']) ?></td>
          <td><?= e($h['city']) ?></td>
          <td><span class="badge bg-<?= $h['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($h['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/marinka/hospital-form.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this hospital? Related doctors and packages will also be removed.');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$h['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$hospitals): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No hospitals yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
