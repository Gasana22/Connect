<?php
require_once __DIR__ . '/includes/init.php';

$q = trim($_GET['q'] ?? '');
$destinationSlug = trim($_GET['destination'] ?? '');
$category = trim($_GET['category'] ?? '');

$where = ["t.status = 'published'"];
$params = [];
if ($q !== '') {
    $where[] = '(t.name LIKE ? OR t.summary LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($category !== '') {
    $where[] = 't.category = ?';
    $params[] = $category;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT * FROM treatments t WHERE $whereSql ORDER BY t.featured DESC, t.name ASC");
$stmt->execute($params);
$treatments = $stmt->fetchAll();

$hospitals = [];
if ($destinationSlug !== '') {
    $hStmt = $pdo->prepare("
        SELECT h.*, d.name AS destination_name FROM hospitals h
        JOIN destinations d ON d.id = h.destination_id
        WHERE d.slug = ? AND h.status = 'published'
    ");
    $hStmt->execute([$destinationSlug]);
    $hospitals = $hStmt->fetchAll();
}

$pageTitle = 'Search Results';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Search Results</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Search</span></nav>
  </div>
</div>

<div class="container py-5">
  <?php if ($q === '' && $category === '' && $destinationSlug === ''): ?>
    <div class="alert alert-info">Please enter a search term on the homepage to get started.</div>
  <?php endif; ?>

  <h4 class="fw-heading mb-4">Matching Treatments (<?= count($treatments) ?>)</h4>
  <div class="row g-4 mb-5">
    <?php if (!$treatments): ?>
      <div class="col-12"><p class="text-muted">No treatments matched your search.</p></div>
    <?php endif; ?>
    <?php foreach ($treatments as $t): ?>
      <div class="col-lg-3 col-md-6">
        <div class="card shadow-card treatment-card">
          <div class="card-img-wrap">
            <img src="<?= e(img_url('treatments', $t['image'])) ?>" alt="<?= e($t['name']) ?>">
          </div>
          <div class="card-body">
            <span class="badge-category"><?= e($t['category']) ?></span>
            <h5 class="mt-3"><a href="<?= e(BASE_URL) ?>/treatment-detail.php?slug=<?= e($t['slug']) ?>" class="text-dark stretched-link"><?= e($t['name']) ?></a></h5>
            <div class="price-tag">From <?= format_price($t['min_price']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($destinationSlug !== ''): ?>
    <h4 class="fw-heading mb-4">Hospitals in Selected Destination (<?= count($hospitals) ?>)</h4>
    <div class="row g-4">
      <?php foreach ($hospitals as $h): ?>
        <div class="col-lg-4 col-md-6">
          <div class="card shadow-card hospital-card">
            <div class="card-img-wrap sm">
              <img src="<?= e(img_url('hospitals', $h['image'])) ?>" alt="<?= e($h['name']) ?>">
            </div>
            <div class="card-body">
              <h6><a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($h['slug']) ?>" class="text-dark stretched-link"><?= e($h['name']) ?></a></h6>
              <p class="text-muted small mb-0"><?= e($h['summary']) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
