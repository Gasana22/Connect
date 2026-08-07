<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$partner = ['name' => '', 'logo' => '', 'website_url' => '', 'sort_order' => 0, 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Partner not found.');
        redirect(BASE_URL . '/admin/partners.php');
    }
    $partner = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $websiteUrl = trim($_POST['website_url'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($name === '') {
        flash_set('danger', 'Partner name is required.');
    } elseif ($websiteUrl !== '' && !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
        flash_set('danger', 'Please enter a valid website URL (including https://) or leave it blank.');
    } else {
        $logo = handle_image_upload('logo', __DIR__ . '/../uploads/partners', $partner['logo']);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE partners SET name=?, logo=?, website_url=?, sort_order=?, status=? WHERE id=?");
            $stmt->execute([$name, $logo, $websiteUrl, $sortOrder, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO partners (name, logo, website_url, sort_order, status) VALUES (?,?,?,?,?)");
            $stmt->execute([$name, $logo, $websiteUrl, $sortOrder, $status]);
        }
        flash_set('success', 'Partner saved successfully.');
        redirect(BASE_URL . '/admin/partners.php');
    }
}

$pageTitle = $id ? 'Edit Partner' : 'Add Partner';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Partner Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($partner['name']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Website URL <span class="text-muted fw-normal">(optional)</span></label>
      <input type="url" name="website_url" class="form-control" value="<?= e($partner['website_url']) ?>" placeholder="https://example.com">
      <div class="form-text">If set, the logo links out to this site. Leave blank for a plain, non-clickable logo.</div>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Display Order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= (int)$partner['sort_order'] ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $partner['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $partner['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Logo</label>
      <input type="file" name="logo" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('partners', $partner['logo'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;object-fit:contain;background:#f5f4fb;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Partner</button>
  <a href="<?= e(BASE_URL) ?>/admin/partners.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
