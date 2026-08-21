<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doctor = ['hospital_id' => '', 'name' => '', 'specialty' => '', 'bio' => '', 'photo' => '', 'experience_years' => '', 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Doctor not found.');
        redirect(BASE_URL . '/marinka/doctors.php');
    }
    $doctor = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $hospitalId = (int)($_POST['hospital_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $specialty = trim($_POST['specialty'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $experienceYears = $_POST['experience_years'] !== '' ? (int)$_POST['experience_years'] : null;
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($name === '' || !$hospitalId) {
        flash_set('danger', 'Doctor name and hospital are required.');
    } else {
        $photo = handle_image_upload('photo', __DIR__ . '/../uploads/doctors', $doctor['photo']);
        $slug = unique_slug($pdo, 'doctors', $name, $id ?: null);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE doctors SET hospital_id=?, name=?, slug=?, specialty=?, bio=?, photo=?, experience_years=?, status=? WHERE id=?");
            $stmt->execute([$hospitalId, $name, $slug, $specialty, $bio, $photo, $experienceYears, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO doctors (hospital_id, name, slug, specialty, bio, photo, experience_years, status) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$hospitalId, $name, $slug, $specialty, $bio, $photo, $experienceYears, $status]);
        }
        flash_set('success', 'Doctor saved successfully.');
        redirect(BASE_URL . '/marinka/doctors.php');
    }
}

$hospitals = $pdo->query("SELECT * FROM hospitals ORDER BY name")->fetchAll();

$pageTitle = $id ? 'Edit Doctor' : 'Add Doctor';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Doctor Name</label>
      <input type="text" name="name" class="form-control" value="<?= e($doctor['name']) ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Hospital</label>
      <select name="hospital_id" class="form-select" required>
        <option value="">Select...</option>
        <?php foreach ($hospitals as $h): ?>
          <option value="<?= (int)$h['id'] ?>" <?= (int)$doctor['hospital_id'] === (int)$h['id'] ? 'selected' : '' ?>><?= e($h['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Years of Experience</label>
      <input type="number" name="experience_years" class="form-control" value="<?= e($doctor['experience_years']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Specialty</label>
      <input type="text" name="specialty" class="form-control" value="<?= e($doctor['specialty']) ?>">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Biography</label>
      <textarea name="bio" rows="5" class="form-control"><?= e($doctor['bio']) ?></textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $doctor['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $doctor['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Photo</label>
      <input type="file" name="photo" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('doctors', $doctor['photo'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;border-radius:50%;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Doctor</button>
  <a href="<?= e(BASE_URL) ?>/marinka/doctors.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
