<?php
/** Set $pageTitle, $activeAdminNav before including. Requires admin/includes/auth.php already loaded. */
$pageTitle = $pageTitle ?? 'Admin';
$activeAdminNav = $activeAdminNav ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | Connect Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body data-csrf="<?= e(adminCsrfToken()) ?>">
<div class="toast-container" id="toastContainer"></div>
<div class="admin-shell">
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="admin-main">
