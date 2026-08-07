<?php
require_once __DIR__ . '/includes/init.php';

$hospitalCount = (int)$pdo->query("SELECT COUNT(*) c FROM hospitals WHERE status='published'")->fetch()['c'];
$destinationCount = (int)$pdo->query("SELECT COUNT(*) c FROM destinations WHERE status='published'")->fetch()['c'];
$doctorCount = (int)$pdo->query("SELECT COUNT(*) c FROM doctors WHERE status='published'")->fetch()['c'];
$partners = $pdo->query("SELECT * FROM partners WHERE status = 'published' ORDER BY sort_order ASC, name ASC")->fetchAll();

$pageTitle = 'About Us';
$pageDescription = 'Learn about our mission to make quality healthcare abroad accessible and transparent.';
$ph = page_header_banner($pdo, 'about_banner');
require_once __DIR__ . '/includes/header.php';
?>

<div class="<?= e($ph['class']) ?>" style="<?= e($ph['style']) ?>">
  <div class="container">
    <h1>About <?= e(setting($pdo, 'site_name')) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">About</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5 align-items-center">
    <div class="col-lg-6">
      <span class="section-title-badge"><?= e(setting($pdo, 'about_badge_text', 'Our Story')) ?></span>
      <h2 class="mt-3 fw-heading"><?= e(setting($pdo, 'about_heading', 'Making Global Healthcare Simple & Transparent')) ?></h2>
      <p class="text-muted"><?= nl2br(e(setting($pdo, 'about_content'))) ?></p>
      <p class="text-muted"><?= nl2br(e(setting($pdo, 'about_content_2'))) ?></p>
    </div>
    <div class="col-lg-6">
      <div class="row g-3">
        <div class="col-6">
          <div class="card shadow-card p-4 text-center">
            <div class="price-tag fs-2"><?= $hospitalCount ?>+</div>
            <div class="text-muted small">Partner Hospitals</div>
          </div>
        </div>
        <div class="col-6">
          <div class="card shadow-card p-4 text-center">
            <div class="price-tag fs-2"><?= $doctorCount ?>+</div>
            <div class="text-muted small">Specialist Doctors</div>
          </div>
        </div>
        <div class="col-6">
          <div class="card shadow-card p-4 text-center">
            <div class="price-tag fs-2"><?= $destinationCount ?>+</div>
            <div class="text-muted small">Destinations</div>
          </div>
        </div>
        <div class="col-6">
          <div class="card shadow-card p-4 text-center">
            <div class="price-tag fs-2"><?= e(setting($pdo, 'about_stat_number', '12k+')) ?></div>
            <div class="text-muted small"><?= e(setting($pdo, 'about_stat_label', 'Patients Helped')) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mt-5">
    <div class="col-md-4">
      <div class="icon-box mb-3"><i class="bi bi-shield-check"></i></div>
      <h6 class="fw-semibold">Our Mission</h6>
      <p class="text-muted small"><?= e(setting($pdo, 'mission_text')) ?></p>
    </div>
    <div class="col-md-4">
      <div class="icon-box mb-3"><i class="bi bi-eye"></i></div>
      <h6 class="fw-semibold">Our Vision</h6>
      <p class="text-muted small"><?= e(setting($pdo, 'vision_text')) ?></p>
    </div>
    <div class="col-md-4">
      <div class="icon-box mb-3"><i class="bi bi-heart"></i></div>
      <h6 class="fw-semibold">Our Values</h6>
      <p class="text-muted small"><?= e(setting($pdo, 'values_text')) ?></p>
    </div>
  </div>

  <?php if ($partners): ?>
  <div class="mt-5 pt-4 text-center">
    <span class="section-title-badge">Our Partners</span>
    <h2 class="mt-3 mb-4 fw-heading">Organizations We Work With</h2>
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-4">
      <?php foreach ($partners as $partner): ?>
        <?php if ($partner['website_url']): ?><a href="<?= e($partner['website_url']) ?>" target="_blank" rel="noopener nofollow" title="<?= e($partner['name']) ?>"><?php endif; ?>
          <img src="<?= e(img_url('partners', $partner['logo'])) ?>" alt="<?= e($partner['name']) ?>" class="partner-logo">
        <?php if ($partner['website_url']): ?></a><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="cta-banner p-5 text-center mt-5">
    <h2 class="fw-heading">Have questions about your treatment options?</h2>
    <p class="mb-4">Speak to one of our patient coordinators today, free of charge.</p>
    <a href="<?= e(BASE_URL) ?>/contact.php" class="btn btn-light rounded-pill px-4 fw-semibold">Contact Us</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
