<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member = ['name' => '', 'role_title' => '', 'bio' => '', 'photo' => '', 'sort_order' => 0, 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Team member not found.');
        redirect(BASE_URL . '/marinka/team.php');
    }
    $member = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $roleTitle = trim($_POST['role_title'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($name === '') {
        flash_set('danger', 'Name is required.');
    } else {
        $photo = handle_image_upload('photo', __DIR__ . '/../uploads/team', $member['photo']);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE team_members SET name=?, role_title=?, bio=?, photo=?, sort_order=?, status=? WHERE id=?");
            $stmt->execute([$name, $roleTitle, $bio, $photo, $sortOrder, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO team_members (name, role_title, bio, photo, sort_order, status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $roleTitle, $bio, $photo, $sortOrder, $status]);
        }
        flash_set('success', 'Team member saved successfully.');
        redirect(BASE_URL . '/marinka/team.php');
    }
}

$pageTitle = $id ? 'Edit Team Member' : 'Add Team Member';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Full Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($member['name']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Role / Title</label>
      <input type="text" name="role_title" class="form-control" value="<?= e($member['role_title']) ?>" placeholder="e.g. Patient Coordinator">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Short Bio <span class="text-muted fw-normal">(optional)</span></label>
      <textarea name="bio" rows="3" class="form-control" maxlength="500"><?= e($member['bio']) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Display Order</label>
      <input type="number" name="sort_order" class="form-control" value="<?= (int)$member['sort_order'] ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $member['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $member['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Photo</label>
      <input type="file" name="photo" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('team', $member['photo'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;object-fit:cover;border-radius:50%;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Team Member</button>
  <a href="<?= e(BASE_URL) ?>/marinka/team.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
