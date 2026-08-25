<?php
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$destination = $stmt->fetch();

if (!$destination) {
    http_response_code(404);
    $pageTitle = 'Destination Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Destination not found</h1><a href="' . e(BASE_URL) . '/destinations.php" class="btn btn-primary rounded-pill">Browse Destinations</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$hospitalsStmt = $pdo->prepare("SELECT * FROM hospitals WHERE destination_id = ? AND status = 'published' ORDER BY name");
$hospitalsStmt->execute([$destination['id']]);
$hospitals = $hospitalsStmt->fetchAll();

$packagesStmt = $pdo->prepare("
    SELECT p.*, t.name AS treatment_name, h.name AS hospital_name
    FROM packages p
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN treatments t ON t.id = p.treatment_id
    WHERE h.destination_id = ? AND p.status = 'published'
    LIMIT 6
");
$packagesStmt->execute([$destination['id']]);
$packages = $packagesStmt->fetchAll();

$galStmt = $pdo->prepare("SELECT * FROM destination_gallery WHERE destination_id = ? ORDER BY sort_order ASC, id ASC");
$galStmt->execute([$destination['id']]);
$gallery = $galStmt->fetchAll();

$pageTitle = $destination['name'];
$pageDescription = $destination['summary'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1><?= e($destination['name']) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <a href="<?= e(BASE_URL) ?>/destinations.php">Destinations</a> / <span class="active"><?= e($destination['name']) ?></span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-8">
      <img src="<?= e(img_url('destinations', $destination['image'])) ?>" class="w-100 rounded-4 mb-4" alt="<?= e($destination['name']) ?>" style="max-height:420px;object-fit:cover;">
      <h3 class="fw-heading">About <?= e($destination['name']) ?></h3>
      <div class="rich-content"><?= $destination['description'] ?></div>

      <?php if ($hospitals): ?>
      <h3 class="fw-heading mt-5">Hospitals in <?= e($destination['name']) ?></h3>
      <div class="row g-4">
        <?php foreach ($hospitals as $h): ?>
          <div class="col-md-6">
            <div class="card shadow-card hospital-card">
              <div class="card-img-wrap sm">
                <img src="<?= e(img_url('hospitals', $h['image'])) ?>" alt="<?= e($h['name']) ?>">
              </div>
              <div class="card-body">
                <h6><a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($h['slug']) ?>" class="text-dark stretched-link"><?= e($h['name']) ?></a></h6>
                <p class="text-muted small mb-0"><?= e($h['summary']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-card p-4">
        <h5 class="fw-heading">Average Savings</h5>
        <div class="price-tag fs-3"><?= e($destination['avg_savings']) ?></div>
        <p class="text-muted small">Compared to typical prices in the US, UK &amp; EU.</p>
        <hr>
        <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-primary rounded-pill w-100">Get Free Quote</a>
      </div>

      <?php if ($gallery): ?>
      <div class="mt-4">
        <?php foreach ($gallery as $img): ?>
          <a href="<?= e(img_url('destination-gallery', $img['image'])) ?>" target="_blank" rel="noopener" class="d-block mb-3">
            <img src="<?= e(img_url('destination-gallery', $img['image'])) ?>" class="w-100" style="height:180px;object-fit:cover;">
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($packages): ?>
      <div class="mt-4">
        <h6 class="fw-heading">Packages in <?= e($destination['name']) ?></h6>
        <?php foreach ($packages as $p): ?>
          <a href="<?= e(BASE_URL) ?>/package-detail.php?slug=<?= e($p['slug']) ?>" class="d-flex align-items-center gap-3 py-2 text-dark border-bottom">
            <img src="<?= e(img_url('packages', $p['image'])) ?>" class="thumb-sm" alt="<?= e($p['title']) ?>">
            <div>
              <div class="fw-semibold small"><?= e($p['title']) ?></div>
              <div class="text-muted small"><?= price_or_contact($p['show_price'], format_price($p['price'])) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
