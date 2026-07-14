<?php
require_once __DIR__ . '/includes/init.php';

$stmt = $pdo->query("
    SELECT te.*, t.name AS treatment_name
    FROM testimonials te
    LEFT JOIN treatments t ON t.id = te.treatment_id
    WHERE te.status = 'published' ORDER BY te.created_at DESC
");
$testimonials = $stmt->fetchAll();

$pageTitle = 'Patient Testimonials';
$pageDescription = 'Real stories from patients who traveled abroad for treatment with us.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Patient Testimonials</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Testimonials</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <?php foreach ($testimonials as $t): ?>
      <div class="col-lg-4 col-md-6">
        <div class="testimonial-card p-4 h-100">
          <div class="mb-2"><?= star_rating($t['rating']) ?></div>
          <p class="fst-italic">&ldquo;<?= e($t['content']) ?>&rdquo;</p>
          <?php if ($t['treatment_name']): ?><span class="badge-category mb-3 d-inline-block"><?= e($t['treatment_name']) ?></span><?php endif; ?>
          <div class="d-flex align-items-center gap-3 mt-2">
            <img src="<?= e(img_url('testimonials', $t['photo'])) ?>" class="avatar-circle" alt="<?= e($t['patient_name']) ?>">
            <div>
              <div class="fw-semibold"><?= e($t['patient_name']) ?></div>
              <div class="text-muted small"><?= e($t['patient_country']) ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
