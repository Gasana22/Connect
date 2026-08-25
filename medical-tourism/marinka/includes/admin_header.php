<?php
// Expects $pdo, functions.php and auth.php already loaded.
// Optional: $pageTitle
$siteName = setting($pdo, 'site_name', "Let's Go Medical");
$adminPageTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' | Admin' : 'Admin Dashboard';
$currentPage = basename($_SERVER['PHP_SELF']);
$flash = flash_get();

function nav_active($pages, $current) {
    $pages = (array)$pages;
    return in_array($current, $pages, true) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($adminPageTitle) ?></title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 32 32%22><text y=%2224%22 font-size=%2224%22>%E2%9A%95%EF%B8%8F</text></svg>">
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-brand-light">
<div class="d-flex">
  <aside class="admin-sidebar p-3" style="width:260px;flex-shrink:0;">
    <a href="<?= e(BASE_URL) ?>/marinka/index.php" class="d-inline-block text-decoration-none mb-4 px-2">
      <img src="<?= e(logo_url($pdo)) ?>" alt="<?= e($siteName) ?>" class="brand-logo">
    </a>
    <nav class="nav flex-column gap-1">
      <a class="<?= nav_active('index.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/index.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <a class="<?= nav_active('leads.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/leads.php"><i class="bi bi-inbox-fill me-2"></i>Leads / Enquiries</a>
      <hr class="my-2">
      <a class="<?= nav_active('home-page.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/home-page.php"><i class="bi bi-house-heart-fill me-2"></i>Home Page</a>
      <a class="<?= nav_active('about-page.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/about-page.php"><i class="bi bi-info-circle-fill me-2"></i>About Page</a>
      <a class="<?= nav_active(['partners.php','partner-form.php'], $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/partners.php"><i class="bi bi-handshake me-2"></i>Partners</a>
      <a class="<?= nav_active(['team.php','team-form.php'], $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/team.php"><i class="bi bi-people-fill me-2"></i>Team</a>
      <a class="<?= nav_active('page-banners.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/page-banners.php"><i class="bi bi-image-fill me-2"></i>Page Banners</a>
      <hr class="my-2">
      <a class="<?= nav_active('treatments.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/treatments.php"><i class="bi bi-clipboard2-pulse me-2"></i>Treatments</a>
      <a class="<?= nav_active('categories.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/categories.php"><i class="bi bi-tags-fill me-2"></i>Categories</a>
      <a class="<?= nav_active('destinations.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/destinations.php"><i class="bi bi-globe-americas me-2"></i>Destinations</a>
      <a class="<?= nav_active('hospitals.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/hospitals.php"><i class="bi bi-hospital-fill me-2"></i>Hospitals</a>
      <a class="<?= nav_active('doctors.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/doctors.php"><i class="bi bi-person-badge-fill me-2"></i>Doctors</a>
      <a class="<?= nav_active('packages.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/packages.php"><i class="bi bi-box-seam-fill me-2"></i>Packages</a>
      <hr class="my-2">
      <a class="<?= nav_active('blog.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/blog.php"><i class="bi bi-newspaper me-2"></i>Blog Posts</a>
      <a class="<?= nav_active('testimonials.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/testimonials.php"><i class="bi bi-chat-quote-fill me-2"></i>Testimonials</a>
      <hr class="my-2">
      <a class="<?= nav_active('settings.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/settings.php"><i class="bi bi-gear-fill me-2"></i>Site Settings</a>
      <?php if (($_SESSION['admin_role'] ?? '') === 'super_admin'): ?>
      <a class="<?= nav_active('admin-users.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/admin-users.php"><i class="bi bi-people-fill me-2"></i>Admin Users</a>
      <?php endif; ?>
      <a class="<?= nav_active('profile.php', $currentPage) ?>" href="<?= e(BASE_URL) ?>/marinka/profile.php"><i class="bi bi-person-circle me-2"></i>My Profile</a>
      <a href="<?= e(BASE_URL) ?>/index.php" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View Site</a>
      <a href="<?= e(BASE_URL) ?>/marinka/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </aside>

  <main class="flex-grow-1" style="min-width:0;">
    <div class="admin-topbar d-flex justify-content-between align-items-center px-4 py-3">
      <h5 class="mb-0 fw-heading"><?= e($pageTitle ?? 'Dashboard') ?></h5>
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-person-circle fs-4 text-muted"></i>
        <span class="fw-semibold small"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
      </div>
    </div>
    <div class="p-4">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
