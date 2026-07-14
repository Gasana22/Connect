<?php
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("
    SELECT h.*, d.name AS destination_name, d.slug AS destination_slug
    FROM hospitals h JOIN destinations d ON d.id = h.destination_id
    WHERE h.slug = ? AND h.status = 'published'
");
$stmt->execute([$slug]);
$hospital = $stmt->fetch();

if (!$hospital) {
    http_response_code(404);
    $pageTitle = 'Hospital Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Hospital not found</h1><a href="' . e(BASE_URL) . '/hospitals.php" class="btn btn-primary rounded-pill">Browse Hospitals</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$doctorsStmt = $pdo->prepare("SELECT * FROM doctors WHERE hospital_id = ? AND status = 'published'");
$doctorsStmt->execute([$hospital['id']]);
$doctors = $doctorsStmt->fetchAll();

$packagesStmt = $pdo->prepare("SELECT p.*, t.name AS treatment_name FROM packages p JOIN treatments t ON t.id = p.treatment_id WHERE p.hospital_id = ? AND p.status = 'published'");
$packagesStmt->execute([$hospital['id']]);
$packages = $packagesStmt->fetchAll();

$pageTitle = $hospital['name'];
$pageDescription = $hospital['summary'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <span class="section-title-badge bg-white bg-opacity-10 text-white"><?= e($hospital['destination_name']) ?></span>
    <h1 class="mt-3"><?= e($hospital['name']) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <a href="<?= e(BASE_URL) ?>/hospitals.php">Hospitals</a> / <span class="active"><?= e($hospital['name']) ?></span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-8">
      <img src="<?= e(img_url('hospitals', $hospital['image'])) ?>" class="w-100 rounded-4 mb-4" alt="<?= e($hospital['name']) ?>" style="max-height:420px;object-fit:cover;">
      <h3 class="fw-heading">About This Hospital</h3>
      <p><?= nl2br(e($hospital['description'])) ?></p>
      <div class="row g-3 my-3">
        <div class="col-4"><div class="border rounded-3 p-3 text-center"><div class="fw-bold text-primary-brand fs-5"><?= (int)$hospital['established_year'] ?></div><div class="small text-muted">Established</div></div></div>
        <div class="col-4"><div class="border rounded-3 p-3 text-center"><div class="fw-bold text-primary-brand fs-5"><?= (int)$hospital['bed_count'] ?>+</div><div class="small text-muted">Beds</div></div></div>
        <div class="col-4"><div class="border rounded-3 p-3 text-center"><div class="fw-bold text-primary-brand fs-6"><?= e($hospital['accreditations']) ?></div><div class="small text-muted">Accreditations</div></div></div>
      </div>

      <?php if ($doctors): ?>
      <h3 class="fw-heading mt-5">Our Doctors</h3>
      <div class="row g-4">
        <?php foreach ($doctors as $doc): ?>
          <div class="col-md-6">
            <div class="card shadow-card doctor-card">
              <div class="card-body d-flex gap-3 align-items-center">
                <img src="<?= e(img_url('doctors', $doc['photo'])) ?>" class="avatar-circle" style="width:70px;height:70px;" alt="<?= e($doc['name']) ?>">
                <div>
                  <h6 class="mb-0"><a href="<?= e(BASE_URL) ?>/doctor-detail.php?slug=<?= e($doc['slug']) ?>" class="text-dark"><?= e($doc['name']) ?></a></h6>
                  <div class="text-muted small"><?= e($doc['specialty']) ?></div>
                  <div class="text-muted small"><?= (int)$doc['experience_years'] ?> years experience</div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($packages): ?>
      <h3 class="fw-heading mt-5">Packages at This Hospital</h3>
      <div class="row g-4">
        <?php foreach ($packages as $p): ?>
          <div class="col-md-6">
            <div class="card shadow-card package-card">
              <div class="card-img-wrap sm">
                <img src="<?= e(img_url('packages', $p['image'])) ?>" alt="<?= e($p['title']) ?>">
              </div>
              <div class="card-body">
                <span class="badge-category"><?= e($p['treatment_name']) ?></span>
                <h6 class="mt-2"><a href="<?= e(BASE_URL) ?>/package-detail.php?slug=<?= e($p['slug']) ?>" class="text-dark stretched-link"><?= e($p['title']) ?></a></h6>
                <div class="price-tag"><?= format_price($p['price']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-card p-4 sticky-top" style="top: 90px;">
        <h5 class="fw-heading">Interested in this hospital?</h5>
        <p class="text-muted small">Get a personalized quote for treatment at <?= e($hospital['name']) ?>.</p>
        <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-primary rounded-pill w-100 mb-2">Get Free Quote</a>
        <a href="<?= e(BASE_URL) ?>/contact.php" class="btn btn-outline-primary rounded-pill w-100">Ask a Question</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
