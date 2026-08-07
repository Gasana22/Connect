<?php
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM treatments WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$treatment = $stmt->fetch();

if (!$treatment) {
    http_response_code(404);
    $pageTitle = 'Treatment Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Treatment not found</h1><p class="text-muted">The treatment you are looking for does not exist or is no longer available.</p><a href="' . e(BASE_URL) . '/treatments.php" class="btn btn-primary rounded-pill">Browse Treatments</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$packagesStmt = $pdo->prepare("
    SELECT p.*, h.name AS hospital_name, h.slug AS hospital_slug, d.name AS destination_name
    FROM packages p
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE p.treatment_id = ? AND p.status = 'published'
    ORDER BY p.featured DESC
");
$packagesStmt->execute([$treatment['id']]);
$packages = $packagesStmt->fetchAll();

$hospitalsStmt = $pdo->prepare("
    SELECT DISTINCT h.* FROM hospitals h
    JOIN packages p ON p.hospital_id = h.id
    WHERE p.treatment_id = ? AND h.status = 'published'
");
$hospitalsStmt->execute([$treatment['id']]);
$hospitals = $hospitalsStmt->fetchAll();

$related = $pdo->prepare("SELECT * FROM treatments WHERE category = ? AND id != ? AND status='published' LIMIT 3");
$related->execute([$treatment['category'], $treatment['id']]);
$relatedTreatments = $related->fetchAll();

$pageTitle = $treatment['name'];
$pageDescription = $treatment['summary'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <span class="section-title-badge"><?= e($treatment['category']) ?></span>
    <h1 class="mt-3"><?= e($treatment['name']) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <a href="<?= e(BASE_URL) ?>/treatments.php">Treatments</a> / <span class="active"><?= e($treatment['name']) ?></span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-8">
      <img src="<?= e(img_url('treatments', $treatment['image'])) ?>" class="w-100 rounded-4 mb-4" alt="<?= e($treatment['name']) ?>" style="max-height:420px;object-fit:cover;">
      <h3 class="fw-heading">About This Treatment</h3>
      <p><?= nl2br(e($treatment['description'])) ?></p>

      <?php if ($packages): ?>
      <h3 class="fw-heading mt-5">Available Packages</h3>
      <div class="row g-4">
        <?php foreach ($packages as $p): ?>
          <div class="col-md-6">
            <div class="card shadow-card package-card">
              <div class="card-img-wrap sm">
                <img src="<?= e(img_url('packages', $p['image'])) ?>" alt="<?= e($p['title']) ?>">
              </div>
              <div class="card-body">
                <h6><a href="<?= e(BASE_URL) ?>/package-detail.php?slug=<?= e($p['slug']) ?>" class="text-dark stretched-link"><?= e($p['title']) ?></a></h6>
                <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= e($p['hospital_name']) ?>, <?= e($p['destination_name']) ?></p>
                <div class="price-tag"><?= format_price($p['price']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($hospitals): ?>
      <h3 class="fw-heading mt-5">Hospitals Offering This Treatment</h3>
      <div class="list-group">
        <?php foreach ($hospitals as $h): ?>
          <a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($h['slug']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <img src="<?= e(img_url('hospitals', $h['image'])) ?>" class="thumb-sm" alt="<?= e($h['name']) ?>">
            <div>
              <div class="fw-semibold"><?= e($h['name']) ?></div>
              <div class="text-muted small"><?= e($h['city']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-card p-4 sticky-top" style="top: 90px;">
        <h5 class="fw-heading">Price Range</h5>
        <div class="price-tag fs-3"><?= format_price($treatment['min_price']) ?> - <?= format_price($treatment['max_price']) ?></div>
        <p class="text-muted small">Average duration: <?= e($treatment['avg_duration']) ?></p>
        <hr>
        <a href="<?= e(BASE_URL) ?>/quote.php?treatment=<?= e($treatment['slug']) ?>" class="btn btn-primary rounded-pill w-100 mb-2">Get Free Quote</a>
        <a href="<?= e(BASE_URL) ?>/contact.php" class="btn btn-outline-primary rounded-pill w-100">Ask a Question</a>
      </div>

      <?php if ($relatedTreatments): ?>
      <div class="mt-4">
        <h6 class="fw-heading">Related Treatments</h6>
        <?php foreach ($relatedTreatments as $rt): ?>
          <a href="<?= e(BASE_URL) ?>/treatment-detail.php?slug=<?= e($rt['slug']) ?>" class="d-flex align-items-center gap-3 py-2 text-dark border-bottom">
            <img src="<?= e(img_url('treatments', $rt['image'])) ?>" class="thumb-sm" alt="<?= e($rt['name']) ?>">
            <div>
              <div class="fw-semibold small"><?= e($rt['name']) ?></div>
              <div class="text-muted small">From <?= format_price($rt['min_price']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
