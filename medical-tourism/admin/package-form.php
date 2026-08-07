<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$package = ['treatment_id' => '', 'hospital_id' => '', 'title' => '', 'summary' => '', 'description' => '', 'includes' => '', 'excludes' => '', 'image' => '', 'price' => '', 'duration' => '', 'featured' => 0, 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Package not found.');
        redirect(BASE_URL . '/admin/packages.php');
    }
    $package = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $treatmentId = (int)($_POST['treatment_id'] ?? 0);
    $hospitalId = (int)($_POST['hospital_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $includesText = trim($_POST['includes'] ?? '');
    $excludesText = trim($_POST['excludes'] ?? '');
    $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
    $duration = trim($_POST['duration'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($title === '' || !$treatmentId || !$hospitalId) {
        flash_set('danger', 'Title, treatment and hospital are required.');
    } else {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/packages', $package['image']);
        $slug = unique_slug($pdo, 'packages', $title, $id ?: null);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE packages SET treatment_id=?, hospital_id=?, title=?, slug=?, summary=?, description=?, includes=?, excludes=?, image=?, price=?, duration=?, featured=?, status=? WHERE id=?");
            $stmt->execute([$treatmentId, $hospitalId, $title, $slug, $summary, $description, $includesText, $excludesText, $image, $price, $duration, $featured, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO packages (treatment_id, hospital_id, title, slug, summary, description, includes, excludes, image, price, duration, featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$treatmentId, $hospitalId, $title, $slug, $summary, $description, $includesText, $excludesText, $image, $price, $duration, $featured, $status]);
        }
        flash_set('success', 'Package saved successfully.');
        redirect(BASE_URL . '/admin/packages.php');
    }
}

$treatments = $pdo->query("SELECT * FROM treatments ORDER BY name")->fetchAll();
$hospitals = $pdo->query("SELECT * FROM hospitals ORDER BY name")->fetchAll();

$pageTitle = $id ? 'Edit Package' : 'Add Package';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8">
      <label class="form-label small fw-semibold">Package Title</label>
      <input type="text" name="title" class="form-control" value="<?= e($package['title']) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Price (USD)</label>
      <input type="number" step="0.01" name="price" class="form-control" value="<?= e($package['price']) ?>" required>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Treatment</label>
      <select name="treatment_id" class="form-select" required>
        <option value="">Select...</option>
        <?php foreach ($treatments as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= (int)$package['treatment_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Hospital</label>
      <select name="hospital_id" class="form-select" required>
        <option value="">Select...</option>
        <?php foreach ($hospitals as $h): ?>
          <option value="<?= (int)$h['id'] ?>" <?= (int)$package['hospital_id'] === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Duration</label>
      <input type="text" name="duration" class="form-control" value="<?= e($package['duration']) ?>" placeholder="e.g. 5 days / 4 nights">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Short Summary</label>
      <input type="text" name="summary" class="form-control" value="<?= e($package['summary']) ?>" maxlength="255">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Full Description</label>
      <textarea name="description" rows="4" class="form-control"><?= e($package['description']) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">What's Included (one item per line)</label>
      <textarea name="includes" rows="5" class="form-control" placeholder="Hotel (4 nights)&#10;Airport transfers&#10;Surgery + follow-up"><?= e($package['includes']) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">What's Excluded (one item per line)</label>
      <textarea name="excludes" rows="5" class="form-control" placeholder="International flights&#10;Travel insurance&#10;Meals outside of hotel breakfast"><?= e($package['excludes']) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $package['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $package['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-md-6 d-flex align-items-center">
      <div class="form-check mt-4">
        <input type="checkbox" name="featured" class="form-check-input" id="featured" <?= $package['featured'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="featured">Show as featured package on homepage</label>
      </div>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('packages', $package['image'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Package</button>
  <a href="<?= e(BASE_URL) ?>/admin/packages.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
