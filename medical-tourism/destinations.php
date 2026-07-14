<?php
require_once __DIR__ . '/includes/init.php';

$destinations = $pdo->query("SELECT * FROM destinations WHERE status = 'published' ORDER BY name ASC")->fetchAll();

$pageTitle = 'Destinations';
$pageDescription = 'Explore the top medical tourism destinations around the world.';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Destinations</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Destinations</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <?php foreach ($destinations as $d): ?>
      <div class="col-lg-4 col-md-6">
        <div class="card shadow-card destination-card">
          <div class="card-img-wrap">
            <img src="<?= e(img_url('destinations', $d['image'])) ?>" alt="<?= e($d['name']) ?>">
          </div>
          <div class="card-body">
            <h5><a href="<?= e(BASE_URL) ?>/destination-detail.php?slug=<?= e($d['slug']) ?>" class="text-dark stretched-link"><?= e($d['name']) ?></a></h5>
            <p class="text-muted small mb-2"><?= e($d['summary']) ?></p>
            <span class="badge-category">Save <?= e($d['avg_savings']) ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
