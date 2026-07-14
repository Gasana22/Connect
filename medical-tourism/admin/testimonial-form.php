<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$testimonial = ['patient_name' => '', 'patient_country' => '', 'treatment_id' => '', 'content' => '', 'rating' => 5, 'photo' => '', 'status' => 'published'];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Testimonial not found.');
        redirect(BASE_URL . '/admin/testimonials.php');
    }
    $testimonial = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $patientName = trim($_POST['patient_name'] ?? '');
    $patientCountry = trim($_POST['patient_country'] ?? '');
    $treatmentId = !empty($_POST['treatment_id']) ? (int)$_POST['treatment_id'] : null;
    $content = trim($_POST['content'] ?? '');
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';

    if ($patientName === '' || $content === '') {
        flash_set('danger', 'Patient name and testimonial content are required.');
    } else {
        $photo = handle_image_upload('photo', __DIR__ . '/../uploads/testimonials', $testimonial['photo']);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE testimonials SET patient_name=?, patient_country=?, treatment_id=?, content=?, rating=?, photo=?, status=? WHERE id=?");
            $stmt->execute([$patientName, $patientCountry, $treatmentId, $content, $rating, $photo, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO testimonials (patient_name, patient_country, treatment_id, content, rating, photo, status) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$patientName, $patientCountry, $treatmentId, $content, $rating, $photo, $status]);
        }
        flash_set('success', 'Testimonial saved successfully.');
        redirect(BASE_URL . '/admin/testimonials.php');
    }
}

$treatments = $pdo->query("SELECT * FROM treatments ORDER BY name")->fetchAll();

$pageTitle = $id ? 'Edit Testimonial' : 'Add Testimonial';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Patient Name</label>
      <input type="text" name="patient_name" class="form-control" value="<?= e($testimonial['patient_name']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Patient Country</label>
      <input type="text" name="patient_country" class="form-control" value="<?= e($testimonial['patient_country']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label small fw-semibold">Related Treatment</label>
      <select name="treatment_id" class="form-select">
        <option value="">None</option>
        <?php foreach ($treatments as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= (int)$testimonial['treatment_id'] === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Rating</label>
      <select name="rating" class="form-select">
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>" <?= (int)$testimonial['rating'] === $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $testimonial['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $testimonial['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Testimonial</label>
      <textarea name="content" rows="4" class="form-control" required><?= e($testimonial['content']) ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Photo</label>
      <input type="file" name="photo" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('testimonials', $testimonial['photo'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;border-radius:50%;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Testimonial</button>
  <a href="<?= e(BASE_URL) ?>/admin/testimonials.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
