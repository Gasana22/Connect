<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Doctor deleted.');
    redirect(BASE_URL . '/marinka/doctors.php');
}

$doctors = $pdo->query("
    SELECT doc.*, h.name AS hospital_name FROM doctors doc
    JOIN hospitals h ON h.id = doc.hospital_id
    ORDER BY doc.created_at DESC
")->fetchAll();

$pageTitle = 'Doctors';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($doctors) ?> doctor(s)</p>
  <a href="<?= e(BASE_URL) ?>/marinka/doctor-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Doctor</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Photo</th><th>Name</th><th>Specialty</th><th>Hospital</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($doctors as $doc): ?>
        <tr>
          <td><img src="<?= e(img_url('doctors', $doc['photo'])) ?>" class="thumb-sm" style="border-radius:50%;"></td>
          <td><?= e($doc['name']) ?></td>
          <td><?= e($doc['specialty']) ?></td>
          <td><?= e($doc['hospital_name']) ?></td>
          <td><span class="badge bg-<?= $doc['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($doc['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/marinka/doctor-form.php?id=<?= (int)$doc['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this doctor?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$doc['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$doctors): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No doctors yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
