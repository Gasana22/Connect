<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$destination = ['name' => '', 'summary' => '', 'description' => '', 'image' => '', 'avg_savings' => '', 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Destination not found.');
        redirect(BASE_URL . '/admin/destinations.php');
    }
    $destination = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
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
        redirect(BASE_URL . '/admin/destinations.php');
    }
}

$pageTitle = $id ? 'Edit Destination' : 'Add Destination';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
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
      <textarea name="description" rows="5" class="form-control"><?= e($destination['description']) ?></textarea>
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
  <a href="<?= e(BASE_URL) ?>/admin/destinations.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
