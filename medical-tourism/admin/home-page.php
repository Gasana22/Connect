<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$heroFields = ['hero_badge_text', 'hero_heading', 'hero_subtext', 'hero_primary_btn_text', 'hero_secondary_btn_text', 'nav_cta_text'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save_hero_text') {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($heroFields as $field) {
            $stmt->execute([$field, trim($_POST[$field] ?? '')]);
        }
        flash_set('success', 'Hero section updated successfully.');
        redirect(BASE_URL . '/admin/home-page.php');
    }

    if ($formAction === 'add_slide') {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/hero-slides', null);
        if (!$image) {
            flash_set('danger', 'Please choose an image to upload.');
        } else {
            $maxOrder = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) m FROM hero_slides")->fetch()['m'];
            $stmt = $pdo->prepare("INSERT INTO hero_slides (image, sort_order, status) VALUES (?, ?, 'published')");
            $stmt->execute([$image, $maxOrder + 1]);
            flash_set('success', 'Slide added.');
        }
        redirect(BASE_URL . '/admin/home-page.php');
    }

    if ($formAction === 'update_slides') {
        $orders = $_POST['order'] ?? [];
        $statuses = $_POST['status'] ?? [];
        $stmt = $pdo->prepare("UPDATE hero_slides SET sort_order = ?, status = ? WHERE id = ?");
        foreach ($orders as $slideId => $order) {
            $slideId = (int)$slideId;
            $status = ($statuses[$slideId] ?? '') === 'published' ? 'published' : 'draft';
            $stmt->execute([(int)$order, $status, $slideId]);
        }
        flash_set('success', 'Slide order updated.');
        redirect(BASE_URL . '/admin/home-page.php');
    }

    if ($formAction === 'delete_slide') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM hero_slides WHERE id = ?");
        $stmt->execute([$slideId]);
        $slide = $stmt->fetch();
        if ($slide) {
            $del = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
            $del->execute([$slideId]);
            $path = __DIR__ . '/../uploads/hero-slides/' . $slide['image'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
        flash_set('success', 'Slide removed.');
        redirect(BASE_URL . '/admin/home-page.php');
    }
}

$hero = [];
foreach ($heroFields as $field) {
    $hero[$field] = setting($pdo, $field);
}
$slides = $pdo->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC")->fetchAll();

$pageTitle = 'Home Page';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="stat-card bg-white p-4 mb-4">
  <h6 class="fw-heading mb-3">Hero Section Content</h6>
  <p class="text-muted small">This text appears at the top of the homepage, above the search box.</p>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="save_hero_text">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Badge Text</label>
        <input type="text" name="hero_badge_text" class="form-control" value="<?= e($hero['hero_badge_text']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Top-Right "Get Free Quote" Button Text</label>
        <input type="text" name="nav_cta_text" class="form-control" value="<?= e($hero['nav_cta_text']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Headline</label>
        <input type="text" name="hero_heading" class="form-control" value="<?= e($hero['hero_heading']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Subtext</label>
        <textarea name="hero_subtext" rows="2" class="form-control"><?= e($hero['hero_subtext']) ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Primary Button Text</label>
        <input type="text" name="hero_primary_btn_text" class="form-control" value="<?= e($hero['hero_primary_btn_text']) ?>">
        <div class="form-text">Links to the Get Free Quote page.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Secondary Button Text</label>
        <input type="text" name="hero_secondary_btn_text" class="form-control" value="<?= e($hero['hero_secondary_btn_text']) ?>">
        <div class="form-text">Links to the Treatments page.</div>
      </div>
    </div>
    <button type="submit" class="btn btn-primary rounded-pill px-4 mt-3">Save Hero Content</button>
  </form>
</div>

<div class="stat-card bg-white p-4 mb-4">
  <h6 class="fw-heading mb-1">Hero Background Slider</h6>
  <p class="text-muted small">Upload one or more images to rotate behind the hero text. If no images are uploaded, a plain color background is used instead.</p>

  <form method="post" enctype="multipart/form-data" class="d-flex align-items-end gap-3 flex-wrap mb-4 border-bottom pb-4">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="add_slide">
    <div>
      <label class="form-label small fw-semibold">Add a Slide Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" required>
    </div>
    <button type="submit" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-plus-lg me-1"></i>Add Slide</button>
  </form>

  <?php if ($slides): ?>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="update_slides">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr><th>Image</th><th style="width:120px;">Order</th><th style="width:160px;">Status</th><th class="text-end">Remove</th></tr>
        </thead>
        <tbody>
          <?php foreach ($slides as $slide): ?>
          <tr>
            <td><img src="<?= e(img_url('hero-slides', $slide['image'])) ?>" class="thumb-sm" style="width:96px;height:56px;"></td>
            <td><input type="number" name="order[<?= (int)$slide['id'] ?>]" value="<?= (int)$slide['sort_order'] ?>" class="form-control form-control-sm"></td>
            <td>
              <select name="status[<?= (int)$slide['id'] ?>]" class="form-select form-select-sm">
                <option value="published" <?= $slide['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $slide['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
              </select>
            </td>
            <td class="text-end">
              <button type="submit" form="delete-slide-<?= (int)$slide['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this slide?');"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Order &amp; Status</button>
  </form>

  <?php foreach ($slides as $slide): ?>
  <form id="delete-slide-<?= (int)$slide['id'] ?>" method="post" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="delete_slide">
    <input type="hidden" name="slide_id" value="<?= (int)$slide['id'] ?>">
  </form>
  <?php endforeach; ?>
  <?php else: ?>
    <p class="text-muted small mb-0">No slides uploaded yet.</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
