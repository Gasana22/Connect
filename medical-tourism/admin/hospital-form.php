<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hospital = ['destination_id' => '', 'name' => '', 'city' => '', 'summary' => '', 'description' => '', 'image' => '', 'accreditations' => '', 'established_year' => '', 'bed_count' => '', 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Hospital not found.');
        redirect(BASE_URL . '/admin/hospitals.php');
    }
    $hospital = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $destinationId = (int)($_POST['destination_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $accreditations = trim($_POST['accreditations'] ?? '');
    $establishedYear = $_POST['established_year'] !== '' ? (int)$_POST['established_year'] : null;
    $bedCount = $_POST['bed_count'] !== '' ? (int)$_POST['bed_count'] : null;
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($name === '' || !$destinationId) {
        flash_set('danger', 'Hospital name and destination are required.');
    } else {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/hospitals', $hospital['image']);
        $slug = unique_slug($pdo, 'hospitals', $name, $id ?: null);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE hospitals SET destination_id=?, name=?, slug=?, city=?, summary=?, description=?, image=?, accreditations=?, established_year=?, bed_count=?, status=? WHERE id=?");
            $stmt->execute([$destinationId, $name, $slug, $city, $summary, $description, $image, $accreditations, $establishedYear, $bedCount, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO hospitals (destination_id, name, slug, city, summary, description, image, accreditations, established_year, bed_count, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$destinationId, $name, $slug, $city, $summary, $description, $image, $accreditations, $establishedYear, $bedCount, $status]);
        }
        flash_set('success', 'Hospital saved successfully.');
        redirect(BASE_URL . '/admin/hospitals.php');
    }
}

$destinations = $pdo->query("SELECT * FROM destinations ORDER BY name")->fetchAll();

$pageTitle = $id ? 'Edit Hospital' : 'Add Hospital';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Hospital Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($hospital['name']) ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Destination</label>
      <select name="destination_id" class="form-select" required>
        <option value="">Select...</option>
        <?php foreach ($destinations as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= (int)$hospital['destination_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">City</label>
      <input type="text" name="city" class="form-control" value="<?= e($hospital['city']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Short Summary</label>
      <input type="text" name="summary" class="form-control" value="<?= e($hospital['summary']) ?>" maxlength="255">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Full Description</label>
      <textarea name="description" rows="5" class="form-control"><?= e($hospital['description']) ?></textarea>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Accreditations</label>
      <input type="text" name="accreditations" class="form-control" value="<?= e($hospital['accreditations']) ?>" placeholder="e.g. JCI, ISO 9001">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Established Year</label>
      <input type="number" name="established_year" class="form-control" value="<?= e($hospital['established_year']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Bed Count</label>
      <input type="number" name="bed_count" class="form-control" value="<?= e($hospital['bed_count']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $hospital['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $hospital['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('hospitals', $hospital['image'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Hospital</button>
  <a href="<?= e(BASE_URL) ?>/admin/hospitals.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
