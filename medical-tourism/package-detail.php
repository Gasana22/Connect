<?php
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("
    SELECT p.*, t.name AS treatment_name, t.slug AS treatment_slug,
           h.name AS hospital_name, h.slug AS hospital_slug,
           d.name AS destination_name
    FROM packages p
    JOIN treatments t ON t.id = p.treatment_id
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE p.slug = ? AND p.status = 'published'
");
$stmt->execute([$slug]);
$package = $stmt->fetch();

if (!$package) {
    http_response_code(404);
    $pageTitle = 'Package Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Package not found</h1><a href="' . e(BASE_URL) . '/packages.php" class="btn btn-primary rounded-pill">Browse Packages</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$includesList = array_filter(array_map('trim', explode("\n", (string)$package['includes'])));
$excludesList = array_filter(array_map('trim', explode("\n", (string)$package['excludes'])));

$pageTitle = $package['title'];
$pageDescription = $package['summary'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <span class="section-title-badge"><?= e($package['treatment_name']) ?></span>
    <h1 class="mt-3"><?= e($package['title']) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <a href="<?= e(BASE_URL) ?>/packages.php">Packages</a> / <span class="active"><?= e($package['title']) ?></span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-8">
      <img src="<?= e(img_url('packages', $package['image'])) ?>" class="w-100 rounded-4 mb-4" alt="<?= e($package['title']) ?>" style="max-height:420px;object-fit:cover;">
      <h3 class="fw-heading">Package Overview</h3>
      <p><?= nl2br(e($package['description'])) ?></p>

      <?php if ($includesList || $excludesList): ?>
      <div class="row g-4 mt-1">
        <?php if ($includesList): ?>
        <div class="col-md-6">
          <h3 class="fw-heading">What's Included</h3>
          <ul class="list-unstyled">
            <?php foreach ($includesList as $item): ?>
              <li class="mb-2"><i class="bi bi-check-circle-fill text-primary-brand me-2"></i><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if ($excludesList): ?>
        <div class="col-md-6">
          <h3 class="fw-heading">What's Excluded</h3>
          <ul class="list-unstyled">
            <?php foreach ($excludesList as $item): ?>
              <li class="mb-2 text-muted"><i class="bi bi-x-circle-fill me-2"></i><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="row g-3 mt-3">
        <div class="col-md-6">
          <div class="border rounded-3 p-3 d-flex align-items-center gap-3">
            <i class="bi bi-hospital fs-3 text-primary-brand"></i>
            <div>
              <div class="fw-semibold"><a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($package['hospital_slug']) ?>" class="text-dark"><?= e($package['hospital_name']) ?></a></div>
              <div class="text-muted small"><?= e($package['destination_name']) ?></div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="border rounded-3 p-3 d-flex align-items-center gap-3">
            <i class="bi bi-clock-history fs-3 text-primary-brand"></i>
            <div>
              <div class="fw-semibold">Estimated Duration</div>
              <div class="text-muted small"><?= e($package['duration']) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-card p-4 sticky-top" style="top: 90px;">
        <h5 class="fw-heading">Package Price</h5>
        <div class="price-tag fs-3"><?= price_or_contact($package['show_price'], format_price($package['price'])) ?></div>
        <p class="text-muted small mb-4">Get a personalized quote for this package.</p>
        <form action="<?= e(BASE_URL) ?>/submit-lead.php" method="post" class="needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="source" value="package_form">
          <input type="hidden" name="package_id" value="<?= (int)$package['id'] ?>">
          <input type="hidden" name="treatment_id" value="<?= (int)$package['treatment_id'] ?>">
          <input type="hidden" name="return_to" value="<?= e(BASE_URL . '/package-detail.php?slug=' . $package['slug']) ?>">
          <div class="mb-2">
            <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
          </div>
          <div class="mb-2">
            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
          </div>
          <div class="mb-2">
            <input type="tel" name="phone" class="form-control" placeholder="Phone / WhatsApp">
          </div>
          <div class="mb-2">
            <input type="text" name="country" class="form-control" placeholder="Country">
          </div>
          <div class="mb-3">
            <textarea name="message" class="form-control" rows="3" placeholder="Any questions or preferred dates?" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary rounded-pill w-100">Request This Package</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
