<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$fields = [
    'about_badge_text', 'about_heading', 'about_content', 'about_content_2',
    'about_stat_number', 'about_stat_label', 'mission_text', 'vision_text', 'values_text',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($fields as $field) {
        $stmt->execute([$field, trim($_POST[$field] ?? '')]);
    }
    flash_set('success', 'About page updated successfully.');
    redirect(BASE_URL . '/marinka/about-page.php');
}

$current = [];
foreach ($fields as $field) {
    $current[$field] = setting($pdo, $field);
}

$pageTitle = 'About Page';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" class="stat-card bg-white p-4">
  <?= csrf_field() ?>

  <h6 class="fw-heading mb-3">Our Story</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Section Badge</label>
      <input type="text" name="about_badge_text" class="form-control" value="<?= e($current['about_badge_text']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Heading</label>
      <input type="text" name="about_heading" class="form-control" value="<?= e($current['about_heading']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">First Paragraph</label>
      <textarea name="about_content" rows="4" class="form-control"><?= e($current['about_content']) ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Second Paragraph</label>
      <textarea name="about_content_2" rows="4" class="form-control"><?= e($current['about_content_2']) ?></textarea>
    </div>
  </div>

  <h6 class="fw-heading mb-3">Highlight Stat</h6>
  <p class="text-muted small">The hospitals, doctors and destinations counts are calculated automatically. This fourth stat is set manually.</p>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Number</label>
      <input type="text" name="about_stat_number" class="form-control" value="<?= e($current['about_stat_number']) ?>" placeholder="e.g. 12k+">
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Label</label>
      <input type="text" name="about_stat_label" class="form-control" value="<?= e($current['about_stat_label']) ?>" placeholder="e.g. Patients Helped">
    </div>
  </div>

  <h6 class="fw-heading mb-3">Mission, Vision &amp; Values</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Our Mission</label>
      <textarea name="mission_text" rows="3" class="form-control"><?= e($current['mission_text']) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Our Vision</label>
      <textarea name="vision_text" rows="3" class="form-control"><?= e($current['vision_text']) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Our Values</label>
      <textarea name="values_text" rows="3" class="form-control"><?= e($current['values_text']) ?></textarea>
    </div>
  </div>

  <button type="submit" class="btn btn-primary rounded-pill px-4">Save About Page</button>
  <a href="<?= e(BASE_URL) ?>/about.php" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">Preview Page</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
