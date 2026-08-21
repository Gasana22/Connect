<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$package = ['treatment_id' => '', 'hospital_id' => '', 'title' => '', 'summary' => '', 'description' => '', 'includes' => '', 'excludes' => '', 'image' => '', 'price' => '', 'duration' => '', 'location' => '', 'days' => '', 'valid_from' => '', 'valid_until' => '', 'featured' => 0, 'show_price' => 1, 'status' => 'published'];
$itineraryDays = [];
$galleryImages = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Package not found.');
        redirect(BASE_URL . '/admin/packages.php');
    }
    $package = $found;

    $itStmt = $pdo->prepare("SELECT * FROM package_itinerary WHERE package_id = ? ORDER BY day_number ASC, id ASC");
    $itStmt->execute([$id]);
    $itineraryDays = $itStmt->fetchAll();

    $galStmt = $pdo->prepare("SELECT * FROM package_gallery WHERE package_id = ? ORDER BY sort_order ASC, id ASC");
    $galStmt->execute([$id]);
    $galleryImages = $galStmt->fetchAll();
} else {
    $package['includes'] = setting($pdo, 'package_includes_template');
    $package['excludes'] = setting($pdo, 'package_excludes_template');
}

function save_itinerary(PDO $pdo, $packageId, $titles, $descriptions) {
    $del = $pdo->prepare("DELETE FROM package_itinerary WHERE package_id = ?");
    $del->execute([$packageId]);

    $insert = $pdo->prepare("INSERT INTO package_itinerary (package_id, day_number, title, description) VALUES (?, ?, ?, ?)");
    $dayNumber = 0;
    foreach ($titles as $i => $title) {
        $title = trim($title);
        $description = trim($descriptions[$i] ?? '');
        if ($title === '' && $description === '') {
            continue;
        }
        $dayNumber++;
        $insert->execute([$packageId, $dayNumber, $title, $description]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? 'save_package';

    if ($formAction === 'add_gallery_image' && $id) {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/package-gallery', null);
        if (!$image) {
            flash_set('danger', 'Please choose an image to upload.');
        } else {
            $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) m FROM package_gallery WHERE package_id = ?");
            $maxOrder->execute([$id]);
            $nextOrder = (int)$maxOrder->fetch()['m'] + 1;
            $ins = $pdo->prepare("INSERT INTO package_gallery (package_id, image, sort_order) VALUES (?, ?, ?)");
            $ins->execute([$id, $image, $nextOrder]);
            flash_set('success', 'Photo added to the gallery.');
        }
        redirect(BASE_URL . '/admin/package-form.php?id=' . $id);
    }

    if ($formAction === 'delete_gallery_image' && $id) {
        $imageId = (int)($_POST['image_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT image FROM package_gallery WHERE id = ? AND package_id = ?");
        $stmt->execute([$imageId, $id]);
        $img = $stmt->fetch();
        if ($img) {
            $del = $pdo->prepare("DELETE FROM package_gallery WHERE id = ?");
            $del->execute([$imageId]);
            $path = __DIR__ . '/../uploads/package-gallery/' . $img['image'];
            if (is_file($path)) {
                @unlink($path);
            }
        }
        redirect(BASE_URL . '/admin/package-form.php?id=' . $id);
    }

    if ($formAction === 'save_package') {
        $treatmentId = (int)($_POST['treatment_id'] ?? 0);
        $hospitalId = (int)($_POST['hospital_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $includesText = trim($_POST['includes'] ?? '');
        $excludesText = trim($_POST['excludes'] ?? '');
        $price = $_POST['price'] !== '' ? (float)$_POST['price'] : 0;
        $duration = trim($_POST['duration'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $days = $_POST['days'] !== '' ? (int)$_POST['days'] : null;
        $validFrom = $_POST['valid_from'] !== '' ? $_POST['valid_from'] : null;
        $validUntil = $_POST['valid_until'] !== '' ? $_POST['valid_until'] : null;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $showPrice = isset($_POST['show_price']) ? 1 : 0;
        $status = $_POST['status'] === 'draft' ? 'draft' : 'published';
        $itineraryTitles = $_POST['itinerary_title'] ?? [];
        $itineraryDescriptions = $_POST['itinerary_description'] ?? [];

        if ($title === '' || !$treatmentId || !$hospitalId || !$location || !$days) {
            flash_set('danger', 'Title, treatment, hospital, location and number of days are required.');
        } elseif ($validFrom && $validUntil && $validFrom > $validUntil) {
            flash_set('danger', 'The "valid from" date must be before the "valid until" date.');
        } else {
            $image = handle_image_upload('image', __DIR__ . '/../uploads/packages', $package['image']);
            $slug = unique_slug($pdo, 'packages', $title, $id ?: null);

            if ($id) {
                $stmt = $pdo->prepare("UPDATE packages SET treatment_id=?, hospital_id=?, title=?, slug=?, summary=?, description=?, includes=?, excludes=?, image=?, price=?, duration=?, location=?, days=?, valid_from=?, valid_until=?, featured=?, show_price=?, status=? WHERE id=?");
                $stmt->execute([$treatmentId, $hospitalId, $title, $slug, $summary, $description, $includesText, $excludesText, $image, $price, $duration, $location, $days, $validFrom, $validUntil, $featured, $showPrice, $status, $id]);
                $packageId = $id;
            } else {
                $stmt = $pdo->prepare("INSERT INTO packages (treatment_id, hospital_id, title, slug, summary, description, includes, excludes, image, price, duration, location, days, valid_from, valid_until, featured, show_price, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$treatmentId, $hospitalId, $title, $slug, $summary, $description, $includesText, $excludesText, $image, $price, $duration, $location, $days, $validFrom, $validUntil, $featured, $showPrice, $status]);
                $packageId = (int)$pdo->lastInsertId();
            }

            save_itinerary($pdo, $packageId, $itineraryTitles, $itineraryDescriptions);

            flash_set('success', 'Package saved successfully.');
            redirect(BASE_URL . '/admin/packages.php');
        }
    }
}

$treatments = $pdo->query("SELECT * FROM treatments ORDER BY name")->fetchAll();
$hospitals = $pdo->query("SELECT * FROM hospitals ORDER BY name")->fetchAll();
$includesTemplate = setting($pdo, 'package_includes_template');
$excludesTemplate = setting($pdo, 'package_excludes_template');

$pageTitle = $id ? 'Edit Package' : 'Add Package';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <input type="hidden" name="form_action" value="save_package">
  <h6 class="fw-heading mb-3">Package Details</h6>
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
      <label class="form-label small fw-semibold">Location (Town/Country)</label>
      <input type="text" name="location" class="form-control" value="<?= e($package['location']) ?>" placeholder="e.g. Istanbul, Turkey" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Number of Days</label>
      <input type="number" min="1" name="days" class="form-control" value="<?= e($package['days']) ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Duration (display text)</label>
      <input type="text" name="duration" class="form-control" value="<?= e($package['duration']) ?>" placeholder="e.g. 5 days / 4 nights">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Valid From</label>
      <input type="date" name="valid_from" class="form-control" value="<?= e($package['valid_from']) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Valid Until</label>
      <input type="date" name="valid_until" class="form-control" value="<?= e($package['valid_until']) ?>">
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
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label small fw-semibold mb-0">What's Included (one item per line)</label>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('includes_ta').value = <?= json_encode($includesTemplate) ?>;">Reset to Template</button>
      </div>
      <textarea id="includes_ta" name="includes" rows="5" class="form-control mt-1" placeholder="Hotel (4 nights)&#10;Airport transfers&#10;Surgery + follow-up"><?= e($package['includes']) ?></textarea>
    </div>
    <div class="col-md-6">
      <div class="d-flex justify-content-between align-items-center">
        <label class="form-label small fw-semibold mb-0">What's Excluded (one item per line)</label>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('excludes_ta').value = <?= json_encode($excludesTemplate) ?>;">Reset to Template</button>
      </div>
      <textarea id="excludes_ta" name="excludes" rows="5" class="form-control mt-1" placeholder="International flights&#10;Travel insurance&#10;Meals outside of hotel breakfast"><?= e($package['excludes']) ?></textarea>
    </div>

    <div class="col-md-6">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $package['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $package['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-md-6 d-flex flex-column justify-content-center">
      <div class="form-check">
        <input type="checkbox" name="featured" class="form-check-input" id="featured" <?= $package['featured'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="featured">Show as featured package on homepage</label>
      </div>
      <div class="form-check">
        <input type="checkbox" name="show_price" class="form-check-input" id="show_price" <?= $package['show_price'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="show_price">Show price on the website</label>
      </div>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Main Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('packages', $package['image'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;">
    </div>
  </div>

  <hr class="my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-heading mb-0">Day-by-Day Itinerary</h6>
    <button type="button" id="add-day-btn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Add Day</button>
  </div>
  <div id="itinerary-rows">
    <?php foreach ($itineraryDays as $i => $day): ?>
      <div class="itinerary-row border p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Day <span class="day-number"><?= $i + 1 ?></span></strong>
          <button type="button" class="btn btn-sm btn-outline-danger remove-day">Remove</button>
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <input type="text" name="itinerary_title[]" class="form-control" placeholder="Day title" value="<?= e($day['title']) ?>">
          </div>
          <div class="col-md-8">
            <textarea name="itinerary_description[]" class="form-control" rows="2" placeholder="What happens this day"><?= e($day['description']) ?></textarea>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="text-muted small mb-0">Empty days are skipped automatically when you save.</p>

  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Package</button>
  <a href="<?= e(BASE_URL) ?>/admin/packages.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php if ($id): ?>
<div class="stat-card bg-white p-4 mt-4">
  <h6 class="fw-heading mb-1">Photo Gallery</h6>
  <p class="text-muted small">Extra photos shown in a gallery on the package page, beyond the main image above.</p>

  <form method="post" enctype="multipart/form-data" class="d-flex align-items-end gap-3 flex-wrap mb-4 border-bottom pb-4">
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
            <img src="<?= e(img_url('package-gallery', $img['image'])) ?>" class="w-100" style="height:100px;object-fit:cover;">
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
  <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>Save this package first, then come back to add photo gallery images.</p>
</div>
<?php endif; ?>

<script>
(function () {
  var container = document.getElementById('itinerary-rows');
  var addBtn = document.getElementById('add-day-btn');

  function renumber() {
    container.querySelectorAll('.itinerary-row .day-number').forEach(function (el, index) {
      el.textContent = index + 1;
    });
  }

  function bindRemove(row) {
    row.querySelector('.remove-day').addEventListener('click', function () {
      row.remove();
      renumber();
    });
  }

  container.querySelectorAll('.itinerary-row').forEach(bindRemove);

  addBtn.addEventListener('click', function () {
    var row = document.createElement('div');
    row.className = 'itinerary-row border p-3 mb-3';
    row.innerHTML =
      '<div class="d-flex justify-content-between align-items-center mb-2">' +
      '<strong>Day <span class="day-number"></span></strong>' +
      '<button type="button" class="btn btn-sm btn-outline-danger remove-day">Remove</button></div>' +
      '<div class="row g-2">' +
      '<div class="col-md-4"><input type="text" name="itinerary_title[]" class="form-control" placeholder="Day title"></div>' +
      '<div class="col-md-8"><textarea name="itinerary_description[]" class="form-control" rows="2" placeholder="What happens this day"></textarea></div>' +
      '</div>';
    container.appendChild(row);
    bindRemove(row);
    renumber();
  });
})();
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
