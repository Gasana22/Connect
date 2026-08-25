<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$destination = ['name' => '', 'summary' => '', 'description' => '', 'image' => '', 'avg_savings' => '', 'status' => 'published'];
$galleryImages = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Destination not found.');
        redirect(BASE_URL . '/marinka/destinations.php');
    }
    $destination = $found;

    $galStmt = $pdo->prepare("SELECT * FROM destination_gallery WHERE destination_id = ? ORDER BY sort_order ASC, id ASC");
    $galStmt->execute([$id]);
    $galleryImages = $galStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'save_destination';

    if ($formAction === 'add_gallery_image' && $id) {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/destination-gallery', null);
        if (!$image) {
            flash_set('danger', 'Please choose an image to upload.');
        } else {
            $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) m FROM destination_gallery WHERE destination_id = ?");
            $maxOrder->execute([$id]);
            $nextOrder = (int)$maxOrder->fetch()['m'] + 1;
            $ins = $pdo->prepare("INSERT INTO destination_gallery (destination_id, image, sort_order) VALUES (?, ?, ?)");
            $ins->execute([$id, $image, $nextOrder]);
            flash_set('success', 'Photo added to the gallery.');
        }
        redirect(BASE_URL . '/marinka/destination-form.php?id=' . $id);
    }

    if ($formAction === 'delete_gallery_image' && $id) {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM destination_gallery WHERE id = ? AND destination_id = ?");
        $stmt->execute([$imageId, $id]);
        $img = $stmt->fetch();
        if ($img) {
            $del = $pdo->prepare("DELETE FROM destination_gallery WHERE id = ?");
            $del->execute([$imageId]);
            $path = __DIR__ . '/../uploads/destination-gallery/' . $img['image'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
        redirect(BASE_URL . '/marinka/destination-form.php?id=' . $id);
    }

    if ($formAction === 'save_destination') {
        $name = trim($_POST['name'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $avgSavings = trim($_POST['avg_savings'] ?? '');
        $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

        if ($name === '') {
            flash_set('danger', 'Destination name is required.');
        } else {
            $image = handle_image_upload('image', __DIR__ . '/../uploads/destinations', $destination['image']);
            $slug = unique_slug($pdo, 'destinations', $name, $id ?: null);

            if ($id) {
                $stmt = $pdo->prepare("UPDATE destinations SET name=?, slug=?, summary=?, description=?, image=?, avg_savings=?, status=? WHERE id=?");
                $stmt->execute([$name, $slug, $summary, $description, $image, $avgSavings, $status, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO destinations (name, slug, summary, description, image, avg_savings, status) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$name, $slug, $summary, $description, $image, $avgSavings, $status]);
            }
            flash_set('success', 'Destination saved successfully.');
            redirect(BASE_URL . '/marinka/destinations.php');
        }
    }
}

$pageTitle = $id ? 'Edit Destination' : 'Add Destination';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="save_destination">
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label small fw-semibold">Country / Destination Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($destination['name']) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Average Savings</label>
      <input type="text" name="avg_savings" class="form-control" value="<?= e($destination['avg_savings']) ?>" placeholder="e.g. Up to 70%">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Short Summary</label>
      <input type="text" name="summary" class="form-control" value="<?= e($destination['summary']) ?>" maxlength="255">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Full Description</label>
      <textarea name="description" rows="8" class="form-control" data-rich-editor><?= e($destination['description']) ?></textarea>
      <div class="form-text">Use the toolbar for bold, italics, headings and lists. Shown as the main "About this destination" text on the public page.</div>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $destination['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $destination['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('destinations', $destination['image'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Destination</button>
  <a href="<?= e(BASE_URL) ?>/marinka/destinations.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php if ($id): ?>
<div class="stat-card bg-white p-4 mt-4">
  <h6 class="fw-heading mb-1">Photo Gallery</h6>
  <p class="text-muted small">Extra photos shown in a gallery on the right-hand side of the destination page.</p>

  <form method="post" enctype="multipart/form-data" class="d-flex align-items-end gap-3 mb-4">
    <?= csrf_field() ?>
    <input type="hidden" name="form_action" value="add_gallery_image">
    <div>
      <label class="form-label small fw-semibold">Add a Photo</label>
      <input type="file" name="image" class="form-control" accept="image/*" required>
    </div>
    <button type="submit" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-plus-lg me-1"></i>Add Photo</button>
  </form>

  <?php if ($galleryImages): ?>
    <div class="row g-3">
      <?php foreach ($galleryImages as $img): ?>
        <div class="col-6 col-md-3 col-lg-2">
          <div class="position-relative">
            <img src="<?= e(img_url('destination-gallery', $img['image'])) ?>" class="w-100" style="height:100px;object-fit:cover;">
            <form method="post" onsubmit="return confirm('Remove this photo?');" class="position-absolute top-0 end-0">
              <?= csrf_field() ?>
              <input type="hidden" name="form_action" value="delete_gallery_image">
              <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x"></i></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-muted small mb-0">No gallery photos yet.</p>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="stat-card bg-white p-4 mt-4">
  <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Save this destination first, then come back to add photo gallery images.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
