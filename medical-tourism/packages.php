<?php
require_once __DIR__ . '/includes/init.php';

$treatmentSlug = trim($_GET['treatment'] ?? '');
$destinationSlug = trim($_GET['destination'] ?? '');
[$page, $perPage, $offset] = paginate_params(9);

$where = ["p.status = 'published'"];
$params = [];
if ($treatmentSlug !== '') {
    $where[] = 't.slug = ?';
    $params[] = $treatmentSlug;
}
if ($destinationSlug !== '') {
    $where[] = 'd.slug = ?';
    $params[] = $destinationSlug;
}
$whereSql = implode(' AND ', $where);

$baseQuery = "
    FROM packages p
    JOIN treatments t ON t.id = p.treatment_id
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE $whereSql
";

$total = $pdo->prepare("SELECT COUNT(*) c $baseQuery");
$total->execute($params);
$totalCount = (int)$total->fetch()['c'];

$stmt = $pdo->prepare("
    SELECT p.*, t.name AS treatment_name, h.name AS hospital_name, d.name AS destination_name
    $baseQuery ORDER BY p.featured DESC, p.created_at DESC LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$packages = $stmt->fetchAll();

$treatments = $pdo->query("SELECT * FROM treatments WHERE status='published' ORDER BY name")->fetchAll();
$destinations = $pdo->query("SELECT * FROM destinations WHERE status='published' ORDER BY name")->fetchAll();

$pageTitle = 'Treatment Packages';
$pageDescription = 'All-inclusive treatment packages with transparent, upfront pricing.';
$ph = page_header_banner($pdo, 'packages_banner');
require_once __DIR__ . '/includes/header.php';
?>

<div class="<?= e($ph['class']) ?>" style="<?= e($ph['style']) ?>">
  <div class="container">
    <h1>Treatment Packages</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Packages</span></nav>
  </div>
</div>

<div class="container py-5">
  <form method="get" class="row g-3 mb-4 bg-brand-light p-4 rounded-4">
    <div class="col-md-5">
      <label class="form-label small fw-semibold">Treatment</label>
      <select name="treatment" class="form-select">
        <option value="">All Treatments</option>
        <?php foreach ($treatments as $t): ?>
          <option value="<?= e($t['slug']) ?>" <?= $treatmentSlug === $t['slug'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label small fw-semibold">Destination</label>
      <select name="destination" class="form-select">
        <option value="">All Destinations</option>
        <?php foreach ($destinations as $d): ?>
          <option value="<?= e($d['slug']) ?>" <?= $destinationSlug === $d['slug'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-primary w-100" type="submit">Filter</button>
    </div>
  </form>

  <p class="text-muted"><?= $totalCount ?> package<?= $totalCount === 1 ? '' : 's' ?> found</p>
  <div class="row g-4">
    <?php if (!$packages): ?>
      <div class="col-12"><div class="alert alert-info">No packages matched your filters. Try broadening your search.</div></div>
    <?php endif; ?>
    <?php foreach ($packages as $p): ?>
      <div class="col-lg-4 col-md-6">
        <div class="card shadow-card package-card">
          <div class="card-img-wrap">
            <?php if ($p['featured']): ?><span class="badge-featured">Featured</span><?php endif; ?>
            <img src="<?= e(img_url('packages', $p['image'])) ?>" alt="<?= e($p['title']) ?>">
          </div>
          <div class="card-body">
            <span class="badge-category"><?= e($p['treatment_name']) ?></span>
            <h5 class="mt-3"><a href="<?= e(BASE_URL) ?>/package-detail.php?slug=<?= e($p['slug']) ?>" class="text-dark stretched-link"><?= e($p['title']) ?></a></h5>
            <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= e($p['location'] ?: ($p['hospital_name'] . ', ' . $p['destination_name'])) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <div class="price-tag"><?= price_or_contact($p['show_price'], format_price($p['price'])) ?></div>
              <span class="text-muted small"><?= e($p['duration']) ?></span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= render_pagination($page, $totalCount, $perPage, BASE_URL . '/packages.php?' . http_build_query(['treatment' => $treatmentSlug, 'destination' => $destinationSlug])) ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
