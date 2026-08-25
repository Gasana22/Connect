<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Thank You';
$pageDescription = 'Your enquiry has been received. Our patient care team will be in touch shortly.';
$pageNoIndex = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center">
  <div class="icon-box mx-auto mb-4" style="width:90px;height:90px;font-size:2.5rem;">
    <i class="bi bi-check-lg"></i>
  </div>
  <h1 class="fw-heading">Thank you for reaching out!</h1>
  <p class="text-muted col-lg-6 mx-auto">Your enquiry has been received. A patient coordinator from our team will contact you within 24 hours with personalized recommendations and pricing.</p>
  <div class="d-flex gap-3 justify-content-center mt-4">
    <a href="<?= e(BASE_URL) ?>/index.php" class="btn btn-primary rounded-pill px-4">Back to Home</a>
    <a href="<?= e(BASE_URL) ?>/treatments.php" class="btn btn-outline-primary rounded-pill px-4">Browse Treatments</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
