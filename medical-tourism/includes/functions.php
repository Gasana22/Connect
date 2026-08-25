<?php
// -----------------------------------------------------------------------
// Shared helper functions for the public site and admin panel
// -----------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
// Defense-in-depth: only load scripts/styles/fonts/frames from this site
// (plus the Google Maps embed on the Contact page), and block outbound
// fetch/XHR to any third-party host so a future stored-XSS bug can't
// exfiltrate data. 'unsafe-inline' stays on script/style because the site
// uses inline onclick/onsubmit confirms and inline style attributes
// throughout — removing it would require a larger template refactor.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-src https://www.google.com; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self';");

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

// Admins can hide a treatment/package's price; when hidden, show a
// neutral prompt instead of the formatted amount.
function price_or_contact($show, $formattedText) {
    return $show ? $formattedText : 'Contact for price';
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

    // Re-encode through GD so only genuine decoded pixel data is ever
    // written to disk. This strips EXIF/metadata and defeats polyglot
    // files crafted to pass MIME sniffing while smuggling other content.
    $image = null;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $image = @imagecreatefromjpeg($file['tmp_name']);
    } elseif ($ext === 'png') {
        $image = @imagecreatefrompng($file['tmp_name']);
    } elseif ($ext === 'webp') {
        $image = @imagecreatefromwebp($file['tmp_name']);
    }
    if (!$image) {
        flash_set('danger', 'The uploaded file is not a valid image.');
        return $currentFile;
    }

    $saved = false;
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $saved = imagejpeg($image, $targetPath, 90);
    } elseif ($ext === 'png') {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $saved = imagepng($image, $targetPath, 6);
    } elseif ($ext === 'webp') {
        $saved = imagewebp($image, $targetPath, 90);
    }
    imagedestroy($image);

    if ($saved) {
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

// -----------------------------------------------------------------------
// Analytics + security tracking.
//
// Records page views (IP, country, referrer, page) and security events
// (failed/successful admin logins, obviously malicious-looking requests)
// so the admin panel can show visitor stats and flag suspicious IPs.
// Every step here is wrapped defensively — if the DB write or the geo
// lookup fails for any reason, the page must still render normally.
// -----------------------------------------------------------------------

// The visitor's real IP. Deliberately uses REMOTE_ADDR only (not
// X-Forwarded-For), because that header is trivial for a client to spoof —
// trusting it here would let an attacker plant a fake IP in the very log
// meant to record their real one.
function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_private_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

// Looks up (and caches) the country for an IP using the free, keyless
// ip-api.com API. Requires the server this site runs on to have outbound
// internet access; fails silently (returns null) if it doesn't, is
// offline, or the lookup times out.
function geo_lookup_country(PDO $pdo, $ip) {
    if ($ip === '0.0.0.0' || is_private_ip($ip)) {
        return 'Local/Private';
    }
    try {
        $stmt = $pdo->prepare("SELECT country, looked_up_at FROM ip_geo_cache WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $cached = $stmt->fetch();
        if ($cached && strtotime($cached['looked_up_at']) > strtotime('-30 days')) {
            return $cached['country'];
        }
    } catch (PDOException $e) {
        return null;
    }

    $country = null;
    try {
        $context = stream_context_create(['http' => ['timeout' => 1.5, 'ignore_errors' => true]]);
        $response = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country', false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (($data['status'] ?? '') === 'success' && !empty($data['country'])) {
                $country = $data['country'];
            }
        }
    } catch (Throwable $e) {
        $country = null;
    }

    try {
        $upsert = $pdo->prepare("INSERT INTO ip_geo_cache (ip_address, country) VALUES (?, ?) ON DUPLICATE KEY UPDATE country = VALUES(country), looked_up_at = NOW()");
        $upsert->execute([$ip, $country]);
    } catch (PDOException $e) {
        // Non-fatal — just means we retry the lookup next visit.
    }

    return $country;
}

// Records one page view. Skips the admin panel itself so staff usage
// doesn't pollute visitor analytics.
function track_pageview(PDO $pdo) {
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/marinka/') !== false) {
        return;
    }
    try {
        $ip = client_ip();
        $country = geo_lookup_country($pdo, $ip);
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $url = substr($_SERVER['REQUEST_URI'] ?? '', 0, 500);
        $referrer = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);
        $visitorHash = hash('sha256', $ip . '|' . $ua . '|' . date('Y-m-d'));

        $stmt = $pdo->prepare("INSERT INTO page_views (url, referrer, ip_address, country, user_agent, visitor_hash) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$url, $referrer ?: null, $ip, $country, $ua ?: null, $visitorHash]);
    } catch (PDOException $e) {
        // Never let analytics break the page.
    }
}

function security_log_event(PDO $pdo, $eventType, $detail = null) {
    try {
        $ip = client_ip();
        $country = geo_lookup_country($pdo, $ip);
        $stmt = $pdo->prepare("INSERT INTO security_log (event_type, ip_address, country, detail) VALUES (?, ?, ?, ?)");
        $stmt->execute([$eventType, $ip, $country, $detail !== null ? substr($detail, 0, 500) : null]);
    } catch (PDOException $e) {
        // Never let logging break the request.
    }
}

// Lightweight signature check for common attack patterns (SQL injection,
// XSS, path traversal, PHP object injection) in the URL path and query
// string. This only logs matches for admin visibility — it never blocks
// the request, so it can't accidentally lock out a legitimate visitor
// whose input happens to look unusual.
function detect_suspicious_request(PDO $pdo) {
    $target = ($_SERVER['REQUEST_URI'] ?? '') . ' ' . implode(' ', array_map('strval', $_GET));
    $patterns = [
        '/union\s+select/i',
        '/select\s+.*\s+from\s+information_schema/i',
        '/\bor\s+1\s*=\s*1\b/i',
        '/<script[\s>]/i',
        '/javascript\s*:/i',
        '/on(error|load)\s*=/i',
        '/\.\.\/\.\.\//',
        '/\betc\/passwd\b/i',
        '/base64_decode\s*\(/i',
        '/\bunion\b.{0,20}\bselect\b/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $target)) {
            security_log_event($pdo, 'suspicious_request', substr($target, 0, 300));
            return;
        }
    }
}
