<?php $siteName = setting($pdo, 'site_name', 'MedJourney'); ?>
<footer class="site-footer mt-5">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="navbar-brand fw-bold text-white" href="<?= e(BASE_URL) ?>/index.php">
          <i class="bi bi-heart-pulse-fill brand-icon"></i> <?= e($siteName) ?>
        </a>
        <p class="mt-3 text-light-emphasis small"><?= e(setting($pdo, 'site_tagline')) ?>. We help patients find internationally accredited hospitals and trusted doctors abroad, with transparent pricing and dedicated support at every step.</p>
        <div class="d-flex gap-3 fs-5">
          <a href="<?= e(setting($pdo, 'facebook_url', '#')) ?>" class="text-white"><i class="bi bi-facebook"></i></a>
          <a href="<?= e(setting($pdo, 'instagram_url', '#')) ?>" class="text-white"><i class="bi bi-instagram"></i></a>
          <a href="<?= e(setting($pdo, 'youtube_url', '#')) ?>" class="text-white"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="text-white fw-semibold mb-3">Explore</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= e(BASE_URL) ?>/treatments.php">Treatments</a></li>
          <li><a href="<?= e(BASE_URL) ?>/destinations.php">Destinations</a></li>
          <li><a href="<?= e(BASE_URL) ?>/hospitals.php">Hospitals</a></li>
          <li><a href="<?= e(BASE_URL) ?>/doctors.php">Doctors</a></li>
          <li><a href="<?= e(BASE_URL) ?>/packages.php">Packages</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="text-white fw-semibold mb-3">Company</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= e(BASE_URL) ?>/about.php">About Us</a></li>
          <li><a href="<?= e(BASE_URL) ?>/blog.php">Blog</a></li>
          <li><a href="<?= e(BASE_URL) ?>/testimonials.php">Testimonials</a></li>
          <li><a href="<?= e(BASE_URL) ?>/contact.php">Contact</a></li>
          <li><a href="<?= e(BASE_URL) ?>/admin/login.php">Admin Login</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6 class="text-white fw-semibold mb-3">Get in touch</h6>
        <ul class="list-unstyled footer-links">
          <li><i class="bi bi-geo-alt-fill me-2"></i><?= e(setting($pdo, 'site_address')) ?></li>
          <li><i class="bi bi-telephone-fill me-2"></i><?= e(setting($pdo, 'site_phone')) ?></li>
          <li><i class="bi bi-envelope-fill me-2"></i><?= e(setting($pdo, 'site_email')) ?></li>
        </ul>
        <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-outline-light btn-sm rounded-pill mt-2">Request a Free Quote</a>
      </div>
    </div>
    <hr class="border-secondary mt-4">
    <div class="d-flex flex-wrap justify-content-between small text-light-emphasis">
      <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
      <span>Designed for patients, built on trust.</span>
    </div>
  </div>
</footer>

<a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting($pdo, 'site_whatsapp', ''))) ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/main.js"></script>
</body>
</html>
