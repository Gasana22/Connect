<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Testimonial deleted.');
    redirect(BASE_URL . '/marinka/testimonials.php');
}

$testimonials = $pdo->query("
    SELECT te.*, t.name AS treatment_name FROM testimonials te
    LEFT JOIN treatments t ON t.id = te.treatment_id
    ORDER BY te.created_at DESC
")->fetchAll();

$pageTitle = 'Testimonials';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($testimonials) ?> testimonial(s)</p>
  <a href="<?= e(BASE_URL) ?>/marinka/testimonial-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Testimonial</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Photo</th><th>Patient</th><th>Country</th><th>Treatment</th><th>Rating</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($testimonials as $t): ?>
        <tr>
          <td><img src="<?= e(img_url('testimonials', $t['photo'])) ?>" class="thumb-sm" style="border-radius:50%;"></td>
          <td><?= e($t['patient_name']) ?></td>
          <td><?= e($t['patient_country']) ?></td>
          <td><?= e($t['treatment_name'] ?? '-') ?></td>
          <td><?= str_repeat('★', (int)$t['rating']) ?></td>
          <td><span class="badge bg-<?= $t['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/marinka/testimonial-form.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this testimonial?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$t['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$testimonials): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No testimonials yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
