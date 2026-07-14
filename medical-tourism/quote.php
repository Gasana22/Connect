<?php
require_once __DIR__ . '/includes/init.php';

$treatments = $pdo->query("SELECT * FROM treatments WHERE status='published' ORDER BY name")->fetchAll();
$destinations = $pdo->query("SELECT * FROM destinations WHERE status='published' ORDER BY name")->fetchAll();
$preselectedTreatment = $_GET['treatment'] ?? '';

$pageTitle = 'Get a Free Quote';
$pageDescription = 'Request a free, no-obligation quote for your treatment abroad.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Get Your Free Quote</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Get Free Quote</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5 justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-card p-4 p-md-5">
        <h4 class="fw-heading mb-1">Tell us about your treatment needs</h4>
        <p class="text-muted mb-4">Fill out the form below and a patient coordinator will send you personalized hospital and pricing options within 24 hours &mdash; free of charge.</p>
        <form action="<?= e(BASE_URL) ?>/submit-lead.php" method="post" class="row g-3 needs-validation" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="source" value="quote_form">
          <input type="hidden" name="return_to" value="<?= e(BASE_URL . '/quote.php') ?>">
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
            <label class="form-label small fw-semibold">Country of Residence</label>
            <input type="text" name="country" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Treatment Interested In</label>
            <select name="treatment_id" class="form-select">
              <option value="">Not sure yet</option>
              <?php foreach ($treatments as $t): ?>
                <option value="<?= (int)$t['id'] ?>" <?= $preselectedTreatment === $t['slug'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Preferred Destination</label>
            <select name="preferred_destination" class="form-select">
              <option value="">No preference</option>
              <?php foreach ($destinations as $d): ?>
                <option value="<?= e($d['name']) ?>"><?= e($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Tell us more about your needs</label>
            <textarea name="message" rows="4" class="form-control" placeholder="e.g. medical history, preferred travel dates, budget" required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Request My Free Quote</button>
            <p class="text-muted small mt-3 mb-0"><i class="bi bi-lock-fill"></i> Your information is kept confidential and never shared with third parties.</p>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
