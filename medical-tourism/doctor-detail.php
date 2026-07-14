<?php
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("
    SELECT doc.*, h.name AS hospital_name, h.slug AS hospital_slug, d.name AS destination_name
    FROM doctors doc
    JOIN hospitals h ON h.id = doc.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE doc.slug = ? AND doc.status = 'published'
");
$stmt->execute([$slug]);
$doctor = $stmt->fetch();

if (!$doctor) {
    http_response_code(404);
    $pageTitle = 'Doctor Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Doctor not found</h1><a href="' . e(BASE_URL) . '/doctors.php" class="btn btn-primary rounded-pill">Browse Doctors</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $doctor['name'];
$pageDescription = $doctor['specialty'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1><?= e($doctor['name']) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <a href="<?= e(BASE_URL) ?>/doctors.php">Doctors</a> / <span class="active"><?= e($doctor['name']) ?></span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-4 text-center">
      <img src="<?= e(img_url('doctors', $doctor['photo'])) ?>" class="rounded-4 w-100 mb-3" style="max-height:340px;object-fit:cover;" alt="<?= e($doctor['name']) ?>">
      <span class="badge-category"><?= (int)$doctor['experience_years'] ?> years experience</span>
    </div>
    <div class="col-lg-8">
      <div class="text-primary-brand fw-semibold mb-2"><?= e($doctor['specialty']) ?></div>
      <p class="text-muted"><i class="bi bi-hospital"></i> <a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($doctor['hospital_slug']) ?>"><?= e($doctor['hospital_name']) ?></a>, <?= e($doctor['destination_name']) ?></p>
      <h3 class="fw-heading mt-4">Biography</h3>
      <p><?= nl2br(e($doctor['bio'])) ?></p>
      <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-primary rounded-pill px-4 mt-3">Request Consultation</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
