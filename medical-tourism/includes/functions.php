<?php
// -----------------------------------------------------------------------
// Shared helper functions for the public site and admin panel
// -----------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function slugify($text) {
    $text = trim($text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . time();
}

function unique_slug(PDO $pdo, $table, $baseSlug, $ignoreId = null) {
    $slug = slugify($baseSlug);
    $original = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT id FROM $table WHERE slug = ?" . ($ignoreId ? " AND id != ?" : "");
        $stmt = $pdo->prepare($sql);
        $params = [$slug];
        if ($ignoreId) {
            $params[] = $ignoreId;
        }
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $original . '-' . $i;
        $i++;
    }
}

function format_price($amount) {
    if ($amount === null || $amount === '') {
        return 'Contact for price';
    }
    return '$' . number_format((float)$amount, 0);
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function flash_set($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}

// ---------------------------------------------------------------------
// Image upload helper. Returns the stored filename on success, or null.
// ---------------------------------------------------------------------
function handle_image_upload($fieldName, $destinationDir, $currentFile = null) {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentFile;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash_set('danger', 'There was a problem uploading the image.');
        return $currentFile;
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($file['tmp_name']);

    if (!isset($allowed[$ext]) || $mime !== $allowed[$ext]) {
        flash_set('danger', 'Please upload a valid image file (jpg, png or webp).');
        return $currentFile;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        flash_set('danger', 'Image files must be smaller than 5MB.');
        return $currentFile;
    }

    $newName = bin2hex(random_bytes(12)) . '.' . $ext;
    $targetPath = rtrim($destinationDir, '/') . '/' . $newName;
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $newName;
    }
    flash_set('danger', 'The image could not be saved on the server.');
    return $currentFile;
}

// ---------------------------------------------------------------------
// Site settings
// ---------------------------------------------------------------------
function get_settings(PDO $pdo) {
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function setting($pdo, $key, $default = '') {
    $settings = get_settings($pdo);
    return $settings[$key] ?? $default;
}

// ---------------------------------------------------------------------
// Simple pagination helper
// ---------------------------------------------------------------------
function paginate_params($perPage = 9) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $perPage;
    return [$page, $perPage, $offset];
}

function render_pagination($page, $totalItems, $perPage, $baseUrl) {
    $totalPages = (int)ceil($totalItems / $perPage);
    if ($totalPages <= 1) {
        return '';
    }
    $sep = (strpos($baseUrl, '?') === false) ? '?' : '&';
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mt-4">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

// ---------------------------------------------------------------------
// Resolve an uploaded image to its public URL, falling back to a
// placeholder graphic when no image has been set/uploaded yet.
// ---------------------------------------------------------------------
function img_url($folder, $filename) {
    if ($filename) {
        $diskPath = __DIR__ . '/../uploads/' . $folder . '/' . $filename;
        if (is_file($diskPath)) {
            return BASE_URL . '/uploads/' . $folder . '/' . rawurlencode($filename);
        }
    }
    return BASE_URL . '/assets/img/placeholder.svg';
}

// ---------------------------------------------------------------------
// Resolve the site logo: the admin-uploaded logo if one exists, else the
// default brand mark shipped with the site.
// ---------------------------------------------------------------------
function logo_url($pdo) {
    $logo = setting($pdo, 'site_logo');
    if ($logo) {
        $diskPath = __DIR__ . '/../uploads/settings/' . $logo;
        if (is_file($diskPath)) {
            return BASE_URL . '/uploads/settings/' . rawurlencode($logo);
        }
    }
    return BASE_URL . '/assets/img/logo.svg';
}

// ---------------------------------------------------------------------
// Build the class + inline background-image style for a page-header band
// that has an admin-uploaded banner photo. Returns a plain gradient band
// (no photo) when the given setting has no image on disk.
// ---------------------------------------------------------------------
function page_header_banner(PDO $pdo, $settingKey) {
    $banner = setting($pdo, $settingKey);
    if ($banner) {
        $diskPath = __DIR__ . '/../uploads/banners/' . $banner;
        if (is_file($diskPath)) {
            $url = BASE_URL . '/uploads/banners/' . rawurlencode($banner);
            $style = "background-image: linear-gradient(90deg, rgba(15,8,32,.62) 0%, rgba(15,8,32,.4) 45%, rgba(15,8,32,.15) 75%, rgba(15,8,32,.05) 100%), url('" . $url . "');";
            return ['class' => 'page-header has-banner', 'style' => $style];
        }
    }
    return ['class' => 'page-header', 'style' => ''];
}

function star_rating($rating) {
    $rating = max(0, min(5, (int)$rating));
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
    }
    return $html;
}
