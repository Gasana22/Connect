<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with our patient care team for any questions about treatment abroad.';
$ph = page_header_banner($pdo, 'contact_banner');
require_once __DIR__ . '/includes/header.php';
?>

<div class="<?= e($ph['class']) ?>" style="<?= e($ph['style']) ?>">
  <div class="container">
    <h1>Contact Us</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Contact</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-5">
      <h3 class="fw-heading">Get in Touch</h3>
      <p class="text-muted">Our patient care team typically responds within 24 hours. For urgent enquiries, reach us by phone or WhatsApp.</p>
      <ul class="list-unstyled mt-4">
        <li class="d-flex gap-3 mb-4">
          <div class="icon-box"><i class="bi bi-geo-alt-fill"></i></div>
          <div>
            <div class="fw-semibold">Address</div>
            <div class="text-muted small"><?= e(setting($pdo, 'site_address')) ?></div>
          </div>
        </li>
        <li class="d-flex gap-3 mb-4">
          <div class="icon-box"><i class="bi bi-telephone-fill"></i></div>
          <div>
            <div class="fw-semibold">Phone</div>
            <div class="text-muted small"><?= e(setting($pdo, 'site_phone')) ?></div>
          </div>
        </li>
        <li class="d-flex gap-3 mb-4">
          <div class="icon-box"><i class="bi bi-whatsapp"></i></div>
          <div>
            <div class="fw-semibold">WhatsApp</div>
            <div class="text-muted small"><?= e(setting($pdo, 'site_whatsapp')) ?></div>
          </div>
        </li>
        <li class="d-flex gap-3 mb-4">
          <div class="icon-box"><i class="bi bi-envelope-fill"></i></div>
          <div>
            <div class="fw-semibold">Email</div>
            <div class="text-muted small"><?= e(setting($pdo, 'site_email')) ?></div>
          </div>
        </li>
      </ul>
    </div>
    <div class="col-lg-7">
      <div class="shadow-card overflow-hidden h-100" style="min-height:450px;">
        <iframe src="<?= e(setting($pdo, 'google_maps_embed_url')) ?>" width="100%" height="100%" style="border:0;min-height:450px;display:block;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" title="Our location on Google Maps"></iframe>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
