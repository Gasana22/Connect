<?php
require_once __DIR__ . '/includes/init.php';

[$page, $perPage, $offset] = paginate_params(9);

$totalCount = (int)$pdo->query("SELECT COUNT(*) c FROM doctors WHERE status='published'")->fetch()['c'];
$stmt = $pdo->prepare("
    SELECT doc.*, h.name AS hospital_name, h.slug AS hospital_slug
    FROM doctors doc JOIN hospitals h ON h.id = doc.hospital_id
    WHERE doc.status = 'published' ORDER BY doc.name ASC LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$doctors = $stmt->fetchAll();

$pageTitle = 'Doctors';
$pageDescription = 'Meet our network of internationally trained specialist doctors.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Our Doctors</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Doctors</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <?php foreach ($doctors as $doc): ?>
      <div class="col-lg-4 col-md-6">
        <div class="card shadow-card doctor-card text-center p-4">
          <img src="<?= e(img_url('doctors', $doc['photo'])) ?>" class="avatar-circle mx-auto mb-3" style="width:100px;height:100px;" alt="<?= e($doc['name']) ?>">
          <h5><a href="<?= e(BASE_URL) ?>/doctor-detail.php?slug=<?= e($doc['slug']) ?>" class="text-dark"><?= e($doc['name']) ?></a></h5>
          <div class="text-primary-brand fw-semibold small mb-1"><?= e($doc['specialty']) ?></div>
          <div class="text-muted small mb-2"><?= e($doc['hospital_name']) ?></div>
          <span class="badge-category mx-auto"><?= (int)$doc['experience_years'] ?> yrs experience</span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= render_pagination($page, $totalCount, $perPage, BASE_URL . '/doctors.php') ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
