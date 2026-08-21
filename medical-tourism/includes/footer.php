<?php $siteName = setting($pdo, 'site_name', "Let's Go Medical"); ?>
<footer class="site-footer mt-5">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <a class="navbar-brand logo-badge" href="<?= e(BASE_URL) ?>/index.php">
          <img src="<?= e(logo_url($pdo)) ?>" alt="<?= e($siteName) ?>" class="brand-logo">
        </a>
        <p class="mt-3 text-light-emphasis small"><?= e(setting($pdo, 'site_tagline')) ?>. We help patients find internationally accredited hospitals and trusted doctors abroad, with transparent pricing and dedicated support at every step.</p>
        <div class="d-flex gap-3 fs-5 social-links">
          <a href="<?= e(setting($pdo, 'facebook_url', '#')) ?>"><i class="bi bi-facebook"></i></a>
          <a href="<?= e(setting($pdo, 'instagram_url', '#')) ?>"><i class="bi bi-instagram"></i></a>
          <a href="<?= e(setting($pdo, 'youtube_url', '#')) ?>"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="fw-semibold mb-3">Explore</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= e(BASE_URL) ?>/treatments.php">Treatments</a></li>
          <li><a href="<?= e(BASE_URL) ?>/destinations.php">Destinations</a></li>
          <li><a href="<?= e(BASE_URL) ?>/hospitals.php">Hospitals</a></li>
          <li><a href="<?= e(BASE_URL) ?>/doctors.php">Doctors</a></li>
          <li><a href="<?= e(BASE_URL) ?>/packages.php">Packages</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="fw-semibold mb-3">Company</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="<?= e(BASE_URL) ?>/about.php">About Us</a></li>
          <li><a href="<?= e(BASE_URL) ?>/blog.php">Blog</a></li>
          <li><a href="<?= e(BASE_URL) ?>/testimonials.php">Testimonials</a></li>
          <li><a href="<?= e(BASE_URL) ?>/contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6 class="fw-semibold mb-3">Get in touch</h6>
        <ul class="list-unstyled footer-links">
          <li><i class="bi bi-geo-alt-fill me-2"></i><?= e(setting($pdo, 'site_address')) ?></li>
          <li><i class="bi bi-telephone-fill me-2"></i><?= e(setting($pdo, 'site_phone')) ?></li>
          <li><i class="bi bi-envelope-fill me-2"></i><?= e(setting($pdo, 'site_email')) ?></li>
        </ul>
        <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-outline-light btn-sm mt-2">Request a Free Quote</a>
      </div>
    </div>
    <hr class="mt-4">
    <div class="d-flex flex-wrap justify-content-between small text-light-emphasis">
      <span>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</span>
      <span>Designed &amp; Maintained by: <a href="https://www.afrisap.com" target="_blank" rel="noopener" class="footer-credit-link">afrisap.com</a></span>
    </div>
  </div>
</footer>

<a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting($pdo, 'site_whatsapp', ''))) ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>

<script src="<?= e(BASE_URL) ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/main.js"></script>
</body>
</html>
