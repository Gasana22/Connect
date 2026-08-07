<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$treatment = ['name' => '', 'category' => '', 'summary' => '', 'description' => '', 'image' => '', 'min_price' => '', 'max_price' => '', 'avg_duration' => '', 'featured' => 0, 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM treatments WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Treatment not found.');
        redirect(BASE_URL . '/admin/treatments.php');
    }
    $treatment = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $minPrice = $_POST['min_price'] !== '' ? (float)$_POST['min_price'] : null;
    $maxPrice = $_POST['max_price'] !== '' ? (float)$_POST['max_price'] : null;
    $avgDuration = trim($_POST['avg_duration'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($name === '' || $category === '') {
        flash_set('danger', 'Name and category are required.');
    } else {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/treatments', $treatment['image']);
        $slug = unique_slug($pdo, 'treatments', $name, $id ?: null);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE treatments SET name=?, slug=?, category=?, summary=?, description=?, image=?, min_price=?, max_price=?, avg_duration=?, featured=?, status=? WHERE id=?");
            $stmt->execute([$name, $slug, $category, $summary, $description, $image, $minPrice, $maxPrice, $avgDuration, $featured, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO treatments (name, slug, category, summary, description, image, min_price, max_price, avg_duration, featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $slug, $category, $summary, $description, $image, $minPrice, $maxPrice, $avgDuration, $featured, $status]);
        }
        flash_set('success', 'Treatment saved successfully.');
        redirect(BASE_URL . '/admin/treatments.php');
    }
}

$categories = $pdo->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
if ($treatment['category'] !== '' && !in_array($treatment['category'], $categories, true)) {
    $categories[] = $treatment['category'];
}

$pageTitle = $id ? 'Edit Treatment' : 'Add Treatment';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Treatment Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($treatment['name']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Category</label>
      <select name="category" class="form-select" required>
        <option value="">Select a category...</option>
        <?php foreach ($categories as $catName): ?>
          <option value="<?= e($catName) ?>" <?= $treatment['category'] === $catName ? 'selected' : '' ?>><?= e($catName) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Need a new one? <a href="<?= e(BASE_URL) ?>/admin/categories.php" target="_blank">Manage categories</a>.</div>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Short Summary</label>
      <input type="text" name="summary" class="form-control" value="<?= e($treatment['summary']) ?>" maxlength="255">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Full Description</label>
      <textarea name="description" rows="5" class="form-control"><?= e($treatment['description']) ?></textarea>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Min Price (USD)</label>
      <input type="number" step="0.01" name="min_price" class="form-control" value="<?= e($treatment['min_price']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Max Price (USD)</label>
      <input type="number" step="0.01" name="max_price" class="form-control" value="<?= e($treatment['max_price']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Average Duration</label>
      <input type="text" name="avg_duration" class="form-control" value="<?= e($treatment['avg_duration']) ?>" placeholder="e.g. 5-7 days">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $treatment['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $treatment['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <div class="form-check">
        <input type="checkbox" name="featured" class="form-check-input" id="featured" <?= $treatment['featured'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="featured">Show as featured treatment on homepage</label>
      </div>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('treatments', $treatment['image'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Treatment</button>
  <a href="<?= e(BASE_URL) ?>/admin/treatments.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
