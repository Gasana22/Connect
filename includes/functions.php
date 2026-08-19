<?php
/**
 * Shared helpers. Convention used throughout the app: sanitize_input() trims and
 * strips tags at write-time (so raw HTML never lands in the DB); e() escapes for
 * HTML output at render-time. Never rely on sanitize_input() alone to make a value
 * safe to print — always pass through e() (or htmlspecialchars) when echoing.
 */

require_once __DIR__ . '/db.php';

if (!function_exists('sanitize_input')) {
    function sanitize_input(string $data): string {
        return trim(strip_tags($data));
    }
}

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('link_hashtags')) {
    // Expects already-HTML-escaped $text (call e() first) so this only ever adds trusted markup.
    function link_hashtags(string $text): string {
        return preg_replace('/#(\w+)/', '<a href="hashtag.php?tag=$1" class="hashtag">#$1</a>', $text);
    }
}

if (!function_exists('createNotification')) {
    function createNotification(PDO $pdo, int $user_id, int $sender_id, string $type, ?int $video_id = null): bool {
        if ($user_id === $sender_id) {
            return false; // never notify users about their own actions
        }
        $stmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, sender_id, type, video_id, created_at, is_read)
             VALUES (?, ?, ?, ?, NOW(), 0)"
        );
        return $stmt->execute([$user_id, $sender_id, $type, $video_id]);
    }
}

if (!function_exists('flash')) {
    /**
     * flash('key', 'message') sets a one-time flash message.
     * flash('key') reads and clears it (null if none was set).
     */
    function flash(string $key, ?string $message = null): ?string {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }
        if (!empty($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('profilePicUrl')) {
    function profilePicUrl(?string $profilePic): string {
        if ($profilePic && file_exists(__DIR__ . '/../' . $profilePic)) {
            return $profilePic;
        }
        return 'assets/img/default-avatar.svg';
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber(int $n): string {
        if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
        if ($n >= 1000) return round($n / 1000, 1) . 'K';
        return (string) $n;
    }
}

if (!function_exists('extractHashtags')) {
    function extractHashtags(string $caption): array {
        if (preg_match_all('/#(\w+)/', $caption, $matches)) {
            return array_values(array_unique(array_map('strtolower', $matches[1])));
        }
        return [];
    }
}

/**
 * Validates an uploaded image by its real decoded content (getimagesize), not the
 * client-supplied filename/MIME string. Returns ['ok' => bool, 'error' => ?string, 'ext' => ?string].
 */
if (!function_exists('validateImageUpload')) {
    function validateImageUpload(array $file, int $maxBytes = UPLOAD_MAX_IMAGE_BYTES): array {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Image upload failed.', 'ext' => null];
        }
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'Image exceeds ' . round($maxBytes / 1024 / 1024) . 'MB limit.', 'ext' => null];
        }
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['ok' => false, 'error' => 'File is not a valid image.', 'ext' => null];
        }
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!isset($allowed[$info[2]])) {
            return ['ok' => false, 'error' => 'Only JPG, PNG, GIF, or WEBP images are allowed.', 'ext' => null];
        }
        return ['ok' => true, 'error' => null, 'ext' => $allowed[$info[2]]];
    }
}

/**
 * Validates an uploaded video by its actual sniffed MIME type (finfo on the file
 * content), not the client-supplied `type` field.
 */
if (!function_exists('validateVideoUpload')) {
    function validateVideoUpload(array $file, int $maxBytes = UPLOAD_MAX_VIDEO_BYTES): array {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Video upload failed.', 'ext' => null];
        }
        if ($file['size'] > $maxBytes) {
            return ['ok' => false, 'error' => 'Video exceeds ' . round($maxBytes / 1024 / 1024) . 'MB limit.', 'ext' => null];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'error' => 'Only MP4, WebM, or MOV formats are allowed.', 'ext' => null];
        }
        return ['ok' => true, 'error' => null, 'ext' => $allowed[$mime]];
    }
}

if (!function_exists('randomFilename')) {
    function randomFilename(string $extension): string {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }
}
