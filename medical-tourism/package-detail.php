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

$itStmt = $pdo->prepare("SELECT * FROM package_itinerary WHERE package_id = ? ORDER BY day_number ASC, id ASC");
$itStmt->execute([$package['id']]);
$itinerary = $itStmt->fetchAll();

$galStmt = $pdo->prepare("SELECT * FROM package_gallery WHERE package_id = ? ORDER BY sort_order ASC, id ASC");
$galStmt->execute([$package['id']]);
$gallery = $galStmt->fetchAll();

$otherStmt = $pdo->prepare("
    SELECT p.*, t.name AS treatment_name, h.name AS hospital_name, d.name AS destination_name
    FROM packages p
    JOIN treatments t ON t.id = p.treatment_id
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE p.id != ? AND p.status = 'published'
    ORDER BY p.featured DESC, p.created_at DESC LIMIT 3
");
$otherStmt->execute([$package['id']]);
$otherPackages = $otherStmt->fetchAll();

$partners = $pdo->query("SELECT * FROM partners WHERE status = 'published' ORDER BY sort_order ASC, name ASC")->fetchAll();

// Valid-dates display text
if ($package['valid_from'] && $package['valid_until']) {
    $validText = date('M j, Y', strtotime($package['valid_from'])) . ' – ' . date('M j, Y', strtotime($package['valid_until']));
} elseif ($package['valid_from']) {
    $validText = 'From ' . date('M j, Y', strtotime($package['valid_from']));
} elseif ($package['valid_until']) {
    $validText = 'Until ' . date('M j, Y', strtotime($package['valid_until']));
} else {
    $validText = 'Year-round';
}

// "Request a Price" mailto link — prefills subject/body with the package title and location
$mailSubject = 'Price Request: ' . $package['title'];
$mailBody = "Hello,\n\nI would like to request a price quote for the following package:\n\n"
    . "Package: " . $package['title'] . "\n"
    . "Location: " . $package['location'] . "\n\n"
    . "Please send me more details.\n\nThank you.";
$mailtoHref = 'mailto:' . setting($pdo, 'site_email') . '?subject=' . rawurlencode($mailSubject) . '&body=' . rawurlencode($mailBody);

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
      <img src="<?= e(img_url('packages', $package['image'])) ?>" class="w-100 mb-4" alt="<?= e($package['title']) ?>" style="max-height:420px;object-fit:cover;">

      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="border p-3 text-center h-100">
            <i class="bi bi-geo-alt fs-4 text-primary-brand"></i>
            <div class="fw-semibold small mt-1"><?= e($package['location']) ?></div>
            <div class="text-muted small">Location</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border p-3 text-center h-100">
            <i class="bi bi-calendar3 fs-4 text-primary-brand"></i>
            <div class="fw-semibold small mt-1"><?= (int)$package['days'] ?> Days</div>
            <div class="text-muted small">Duration</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border p-3 text-center h-100">
            <i class="bi bi-hospital fs-4 text-primary-brand"></i>
            <div class="fw-semibold small mt-1"><a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($package['hospital_slug']) ?>" class="text-dark"><?= e($package['hospital_name']) ?></a></div>
            <div class="text-muted small">Hospital</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border p-3 text-center h-100">
            <i class="bi bi-calendar-check fs-4 text-primary-brand"></i>
            <div class="fw-semibold small mt-1"><?= e($validText) ?></div>
            <div class="text-muted small">Valid Dates</div>
          </div>
        </div>
      </div>

      <h3 class="fw-heading">Package Overview</h3>
      <p><?= nl2br(e($package['description'])) ?></p>

      <?php if ($itinerary): ?>
      <h3 class="fw-heading mt-5 mb-4">Day-by-Day Itinerary</h3>
      <?php foreach ($itinerary as $day): ?>
        <div class="d-flex gap-3 mb-4">
          <div class="itinerary-day-badge flex-shrink-0">Day <?= (int)$day['day_number'] ?></div>
          <div>
            <?php if ($day['title']): ?><h6 class="fw-semibold mb-1"><?= e($day['title']) ?></h6><?php endif; ?>
            <?php if ($day['description']): ?><p class="text-muted mb-0"><?= nl2br(e($day['description'])) ?></p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-card p-4 mb-4">
        <h5 class="fw-heading">Package Price</h5>
        <div class="price-tag fs-3"><?= price_or_contact($package['show_price'], format_price($package['price'])) ?></div>
        <p class="text-muted small mb-3">Request a personalized quote for this package — we'll reply by email.</p>
        <a href="<?= e($mailtoHref) ?>" class="btn btn-primary w-100"><i class="bi bi-envelope me-2"></i>Request a Price</a>
      </div>

      <?php if ($includesList): ?>
      <div class="card shadow-card p-4 mb-4">
        <h6 class="fw-heading">What's Included</h6>
        <ul class="list-unstyled mb-0">
          <?php foreach ($includesList as $item): ?>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-primary-brand me-2"></i><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($excludesList): ?>
      <div class="card shadow-card p-4">
        <h6 class="fw-heading">What's Excluded</h6>
        <ul class="list-unstyled mb-0">
          <?php foreach ($excludesList as $item): ?>
            <li class="mb-2 text-muted"><i class="bi bi-x-circle-fill me-2"></i><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($gallery): ?>
  <div class="mt-5">
    <h3 class="fw-heading mb-3">Photo Gallery</h3>
    <div class="row g-3">
      <?php foreach ($gallery as $img): ?>
        <div class="col-6 col-md-3">
          <a href="<?= e(img_url('package-gallery', $img['image'])) ?>" target="_blank" rel="noopener">
            <img src="<?= e(img_url('package-gallery', $img['image'])) ?>" class="w-100" style="height:140px;object-fit:cover;">
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($otherPackages): ?>
  <div class="mt-5">
    <h3 class="fw-heading mb-4">Other Packages You Might Like</h3>
    <div class="row g-4">
      <?php foreach ($otherPackages as $p): ?>
        <div class="col-md-4">
          <div class="card shadow-card package-card">
            <div class="card-img-wrap">
              <?php if ($p['featured']): ?><span class="badge-featured">Featured</span><?php endif; ?>
              <img src="<?= e(img_url('packages', $p['image'])) ?>" alt="<?= e($p['title']) ?>">
            </div>
            <div class="card-body">
              <span class="badge-category"><?= e($p['treatment_name']) ?></span>
              <h6 class="mt-2"><a href="<?= e(BASE_URL) ?>/package-detail.php?slug=<?= e($p['slug']) ?>" class="text-dark stretched-link"><?= e($p['title']) ?></a></h6>
              <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= e($p['location'] ?: $p['destination_name']) ?></p>
              <div class="price-tag"><?= price_or_contact($p['show_price'], format_price($p['price'])) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($partners): ?>
  <div class="mt-5 pt-4 text-center">
    <span class="section-title-badge">Our Partners</span>
    <h3 class="mt-3 mb-4 fw-heading">Airlines &amp; Travel Partners</h3>
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-4">
      <?php foreach ($partners as $partner): ?>
        <?php if ($partner['website_url']): ?><a href="<?= e($partner['website_url']) ?>" target="_blank" rel="noopener nofollow" title="<?= e($partner['name']) ?>"><?php endif; ?>
          <img src="<?= e(img_url('partners', $partner['logo'])) ?>" alt="<?= e($partner['name']) ?>" class="partner-logo">
        <?php if ($partner['website_url']): ?></a><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
