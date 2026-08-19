<?php
/**
 * Shared page shell. Include after includes/auth.php (needs currentUser()/e()).
 * Set $pageTitle, $activeNav, $layout ('app'|'auth', default 'app') before including.
 */

$pageTitle = $pageTitle ?? SITE_NAME;
$activeNav = $activeNav ?? '';
$layout = $layout ?? 'app';

$user = function_exists('currentUser') ? currentUser() : null;
$unreadCount = 0;
if ($user) {
    $stmt = getPDO()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id']]);
    $unreadCount = (int) $stmt->fetchColumn();
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body data-csrf="<?= e(csrfToken()) ?>">
<div class="toast-container" id="toastContainer"></div>
<?php if ($layout === 'app'): ?>
<button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
<?php include __DIR__ . '/sidebar_nav.php'; ?>
<div class="main-content">
<?php elseif ($layout === 'admin'): ?>
<div class="admin-shell">
<?php else: ?>
<div class="auth-shell">
<?php endif; ?>
