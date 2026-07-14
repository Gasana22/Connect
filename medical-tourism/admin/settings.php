<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$fields = ['site_name', 'site_tagline', 'site_phone', 'site_whatsapp', 'site_email', 'site_address', 'facebook_url', 'instagram_url', 'youtube_url', 'about_content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($fields as $field) {
        $stmt->execute([$field, trim($_POST[$field] ?? '')]);
    }
    flash_set('success', 'Settings updated successfully.');
    redirect(BASE_URL . '/admin/settings.php');
}

$current = [];
foreach ($fields as $field) {
    $current[$field] = setting($pdo, $field);
}

$pageTitle = 'Site Settings';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <h6 class="fw-heading mb-3">General</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Site Name</label>
      <input type="text" name="site_name" class="form-control" value="<?= e($current['site_name']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Tagline</label>
      <input type="text" name="site_tagline" class="form-control" value="<?= e($current['site_tagline']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">About Us Content</label>
      <textarea name="about_content" rows="4" class="form-control"><?= e($current['about_content']) ?></textarea>
    </div>
  </div>

  <h6 class="fw-heading mb-3">Contact Details</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Phone</label>
      <input type="text" name="site_phone" class="form-control" value="<?= e($current['site_phone']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">WhatsApp Number</label>
      <input type="text" name="site_whatsapp" class="form-control" value="<?= e($current['site_whatsapp']) ?>" placeholder="+1 800 555 0134">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Email</label>
      <input type="email" name="site_email" class="form-control" value="<?= e($current['site_email']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Address</label>
      <input type="text" name="site_address" class="form-control" value="<?= e($current['site_address']) ?>">
    </div>
  </div>

  <h6 class="fw-heading mb-3">Social Media</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Facebook URL</label>
      <input type="url" name="facebook_url" class="form-control" value="<?= e($current['facebook_url']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Instagram URL</label>
      <input type="url" name="instagram_url" class="form-control" value="<?= e($current['instagram_url']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">YouTube URL</label>
      <input type="url" name="youtube_url" class="form-control" value="<?= e($current['youtube_url']) ?>">
    </div>
  </div>

  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Settings</button>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
