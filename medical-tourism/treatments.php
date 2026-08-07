<?php
require_once __DIR__ . '/includes/init.php';

$category = trim($_GET['category'] ?? '');
$q = trim($_GET['q'] ?? '');
[$page, $perPage, $offset] = paginate_params(9);

$where = ["status = 'published'"];
$params = [];
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR summary LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$whereSql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) c FROM treatments WHERE $whereSql");
$total->execute($params);
$totalCount = (int)$total->fetch()['c'];

$stmt = $pdo->prepare("SELECT * FROM treatments WHERE $whereSql ORDER BY featured DESC, name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$treatments = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM treatments WHERE status='published' ORDER BY category")->fetchAll();

$pageTitle = 'Treatments';
$pageDescription = 'Browse treatments and procedures available abroad with transparent pricing.';
$ph = page_header_banner($pdo, 'treatments_banner');
require_once __DIR__ . '/includes/header.php';
?>

<div class="<?= e($ph['class']) ?>" style="<?= e($ph['style']) ?>">
  <div class="container">
    <h1>Treatments &amp; Procedures</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Treatments</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <div class="col-lg-3">
      <div class="filter-sidebar bg-brand-light p-4">
        <form method="get" class="mb-4">
          <label class="form-label fw-semibold small">Search</label>
          <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Search treatments" value="<?= e($q) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
          </div>
        </form>
        <h6 class="fw-semibold mb-3">Categories</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="<?= e(BASE_URL) ?>/treatments.php" class="<?= $category === '' ? 'fw-bold text-primary-brand' : 'text-dark' ?>">All Categories</a></li>
          <?php foreach ($categories as $c): ?>
            <li class="mb-2">
              <a href="<?= e(BASE_URL) ?>/treatments.php?category=<?= e(urlencode($c['category'])) ?>" class="<?= $category === $c['category'] ? 'fw-bold text-primary-brand' : 'text-dark' ?>"><?= e($c['category']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="col-lg-9">
      <p class="text-muted"><?= $totalCount ?> treatment<?= $totalCount === 1 ? '' : 's' ?> found</p>
      <div class="row g-4">
        <?php if (!$treatments): ?>
          <div class="col-12"><div class="alert alert-info">No treatments matched your search. Try a different category or keyword.</div></div>
        <?php endif; ?>
        <?php foreach ($treatments as $t): ?>
          <div class="col-md-6 col-xl-4">
            <div class="card shadow-card treatment-card">
              <div class="card-img-wrap">
                <?php if ($t['featured']): ?><span class="badge-featured">Featured</span><?php endif; ?>
                <img src="<?= e(img_url('treatments', $t['image'])) ?>" alt="<?= e($t['name']) ?>">
              </div>
              <div class="card-body">
                <span class="badge-category"><?= e($t['category']) ?></span>
                <h5 class="mt-3"><a href="<?= e(BASE_URL) ?>/treatment-detail.php?slug=<?= e($t['slug']) ?>" class="text-dark stretched-link"><?= e($t['name']) ?></a></h5>
                <p class="text-muted small mb-2"><?= e($t['summary']) ?></p>
                <div class="d-flex justify-content-between align-items-center">
                  <div class="price-tag">From <?= format_price($t['min_price']) ?></div>
                  <span class="text-muted small"><?= e($t['avg_duration']) ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?= render_pagination($page, $totalCount, $perPage, BASE_URL . '/treatments.php?' . http_build_query(['category' => $category, 'q' => $q])) ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
