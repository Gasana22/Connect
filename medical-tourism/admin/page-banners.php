<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$banners = [
    'treatments_banner' => 'Treatments Page',
    'destinations_banner' => 'Destinations Page',
    'packages_banner' => 'Packages Page',
    'blog_banner' => 'Blog Page',
    'about_banner' => 'About Us Page',
    'contact_banner' => 'Contact Page',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    foreach ($banners as $key => $label) {
        if (!empty($_POST['remove_' . $key])) {
            $current = setting($pdo, $key);
            if ($current) {
                $path = __DIR__ . '/../uploads/banners/' . $current;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $stmt->execute([$key, '']);
            continue;
        }
        $image = handle_image_upload($key, __DIR__ . '/../uploads/banners', setting($pdo, $key));
        $stmt->execute([$key, $image]);
    }

    flash_set('success', 'Page banners updated successfully.');
    redirect(BASE_URL . '/admin/page-banners.php');
}

$current = [];
foreach ($banners as $key => $label) {
    $current[$key] = setting($pdo, $key);
}

$pageTitle = 'Page Banners';
require_once __DIR__ . '/includes/admin_header.php';
?>

<p class="text-muted">Upload a banner photo for the header band of each page below. Pages without a banner keep the default plain color header.</p>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-4">
    <?php foreach ($banners as $key => $label): ?>
      <div class="col-md-6">
        <label class="form-label small fw-semibold"><?= e($label) ?></label>
        <?php if ($current[$key]): ?>
          <div class="mb-2">
            <img src="<?= e(img_url('banners', $current[$key])) ?>" class="w-100 rounded-3" style="height:120px;object-fit:cover;">
          </div>
          <div class="form-check mb-2">
            <input type="checkbox" name="remove_<?= e($key) ?>" value="1" class="form-check-input" id="remove_<?= e($key) ?>">
            <label class="form-check-label small" for="remove_<?= e($key) ?>">Remove current banner</label>
          </div>
        <?php endif; ?>
        <input type="file" name="<?= e($key) ?>" class="form-control" accept="image/*">
      </div>
    <?php endforeach; ?>
  </div>
  <button type="submit" class="btn btn-primary rounded-pill px-4 mt-4">Save Page Banners</button>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
