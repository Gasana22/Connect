<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with our patient care team for any questions about treatment abroad.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
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
          <div class="icon-box"><i class="bi bi-envelope-fill"></i></div>
          <div>
            <div class="fw-semibold">Email</div>
            <div class="text-muted small"><?= e(setting($pdo, 'site_email')) ?></div>
          </div>
        </li>
      </ul>
    </div>
    <div class="col-lg-7">
      <div class="card shadow-card p-4 p-md-5">
        <form action="<?= e(BASE_URL) ?>/submit-lead.php" method="post" class="row g-3 needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="source" value="contact_form">
          <input type="hidden" name="return_to" value="<?= e(BASE_URL . '/contact.php') ?>">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Phone / WhatsApp</label>
            <input type="tel" name="phone" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Country</label>
            <input type="text" name="country" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Message</label>
            <textarea name="message" rows="5" class="form-control" required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Send Message</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
