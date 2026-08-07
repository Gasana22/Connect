<?php
// Expects $pdo to already be available and functions.php to be loaded.
// Optional per-page variables: $pageTitle, $pageDescription
$siteName = setting($pdo, 'site_name', "Let's Go Medical");
$pageTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | ' . $siteName : $siteName . ' - Trusted Medical Tourism';
$pageDescription = $pageDescription ?? setting($pdo, 'site_tagline', 'Compare hospitals, doctors and treatment packages abroad and get a free quote today.');
$currentPage = basename($_SERVER['PHP_SELF']);
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><text y=%2224%22 font-size=%2224%22>%E2%9A%95%EF%B8%8F</text></svg>">
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="top-strip">
  <div class="container d-flex flex-wrap justify-content-between align-items-center py-1 small">
    <div class="d-none d-md-flex gap-3">
      <span><i class="bi bi-telephone-fill me-1"></i><?= e(setting($pdo, 'site_phone')) ?></span>
      <span><i class="bi bi-envelope-fill me-1"></i><?= e(setting($pdo, 'site_email')) ?></span>
    </div>
    <div class="d-flex gap-3 ms-auto">
      <a href="<?= e(setting($pdo, 'facebook_url', '#')) ?>"><i class="bi bi-facebook"></i></a>
      <a href="<?= e(setting($pdo, 'instagram_url', '#')) ?>"><i class="bi bi-instagram"></i></a>
      <a href="<?= e(setting($pdo, 'youtube_url', '#')) ?>"><i class="bi bi-youtube"></i></a>
    </div>
  </div>
</div>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top main-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= e(BASE_URL) ?>/index.php">
      <img src="<?= e(logo_url($pdo)) ?>" alt="<?= e($siteName) ?>" class="brand-logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= in_array($currentPage, ['treatments.php','treatment-detail.php']) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/treatments.php">Treatments</a></li>
        <li class="nav-item"><a class="nav-link <?= in_array($currentPage, ['destinations.php','destination-detail.php']) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/destinations.php">Destinations</a></li>
        <li class="nav-item"><a class="nav-link <?= in_array($currentPage, ['packages.php','package-detail.php']) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/packages.php">Packages</a></li>
        <li class="nav-item"><a class="nav-link <?= in_array($currentPage, ['blog.php','blog-post.php']) ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/blog.php">Blog</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/about.php">About</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= e(BASE_URL) ?>/contact.php">Contact</a></li>
      </ul>
      <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-primary rounded-pill px-4 fw-semibold"><?= e(setting($pdo, 'nav_cta_text', 'Get Free Quote')) ?></a>
    </div>
  </div>
</nav>

<?php if ($flash): ?>
<div class="container mt-3">
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
    <?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>
