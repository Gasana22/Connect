<?php
require_once __DIR__ . '/includes/init.php';

$destinationSlug = trim($_GET['destination'] ?? '');
[$page, $perPage, $offset] = paginate_params(9);

$where = ["h.status = 'published'"];
$params = [];
if ($destinationSlug !== '') {
    $where[] = 'd.slug = ?';
    $params[] = $destinationSlug;
}
$whereSql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) c FROM hospitals h JOIN destinations d ON d.id = h.destination_id WHERE $whereSql");
$total->execute($params);
$totalCount = (int)$total->fetch()['c'];

$stmt = $pdo->prepare("
    SELECT h.*, d.name AS destination_name, d.slug AS destination_slug
    FROM hospitals h JOIN destinations d ON d.id = h.destination_id
    WHERE $whereSql ORDER BY h.name ASC LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$hospitals = $stmt->fetchAll();

$destinations = $pdo->query("SELECT * FROM destinations WHERE status='published' ORDER BY name")->fetchAll();

$pageTitle = 'Hospitals & Clinics';
$pageDescription = 'Browse internationally accredited hospitals and clinics for your treatment abroad.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Hospitals &amp; Clinics</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Hospitals</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-3">
      <div class="filter-sidebar bg-brand-light p-4">
        <h6 class="fw-semibold mb-3">Destinations</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="<?= e(BASE_URL) ?>/hospitals.php" class="<?= $destinationSlug === '' ? 'fw-bold text-primary-brand' : 'text-dark' ?>">All Destinations</a></li>
          <?php foreach ($destinations as $d): ?>
            <li class="mb-2">
              <a href="<?= e(BASE_URL) ?>/hospitals.php?destination=<?= e($d['slug']) ?>" class="<?= $destinationSlug === $d['slug'] ? 'fw-bold text-primary-brand' : 'text-dark' ?>"><?= e($d['name']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="col-lg-9">
      <p class="text-muted"><?= $totalCount ?> hospital<?= $totalCount === 1 ? '' : 's' ?> found</p>
      <div class="row g-4">
        <?php if (!$hospitals): ?>
          <div class="col-12"><div class="alert alert-info">No hospitals found for this destination yet.</div></div>
        <?php endif; ?>
        <?php foreach ($hospitals as $h): ?>
          <div class="col-md-6">
            <div class="card shadow-card hospital-card">
              <div class="card-img-wrap">
                <img src="<?= e(img_url('hospitals', $h['image'])) ?>" alt="<?= e($h['name']) ?>">
              </div>
              <div class="card-body">
                <span class="badge-category"><?= e($h['destination_name']) ?></span>
                <h5 class="mt-3"><a href="<?= e(BASE_URL) ?>/hospital-detail.php?slug=<?= e($h['slug']) ?>" class="text-dark stretched-link"><?= e($h['name']) ?></a></h5>
                <p class="text-muted small mb-2"><?= e($h['summary']) ?></p>
                <p class="text-muted small mb-0"><i class="bi bi-patch-check-fill text-primary-brand"></i> <?= e($h['accreditations']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?= render_pagination($page, $totalCount, $perPage, BASE_URL . '/hospitals.php?' . http_build_query(['destination' => $destinationSlug])) ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
