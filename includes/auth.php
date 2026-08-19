<?php
/**
 * User-facing session/auth layer. Kept intentionally separate from admin/includes/auth.php
 * (different session keys, different login surface) even though both share one PHP session.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth(): void {
        if (!isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('currentUserId')) {
    function currentUserId(): ?int {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }
}

if (!function_exists('currentUser')) {
    function currentUser(): ?array {
        $id = currentUserId();
        if (!$id) {
            return null;
        }
        static $cached = null;
        if ($cached === null || $cached['id'] !== $id) {
            $stmt = getPDO()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $cached = $stmt->fetch() ?: null;
        }
        return $cached;
    }
}

if (!function_exists('loginUser')) {
    function loginUser(int $userId, string $username): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
    }
}

if (!function_exists('logoutUser')) {
    function logoutUser(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
    }
}

/**
 * Validates a CSRF token from (in order) an explicit argument, the POST body,
 * the X-CSRF-Token header, or a JSON request body. Halts the request on failure:
 * JSON 403 for AJAX/fetch calls, plain 403 for form posts.
 */
if (!function_exists('validateCsrf')) {
    function validateCsrf(?string $token = null): void {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if ($token === null) {
                $raw = file_get_contents('php://input');
                if ($raw) {
                    $json = json_decode($raw, true);
                    if (is_array($json) && isset($json['csrf_token'])) {
                        $token = $json['csrf_token'];
                    }
                }
            }
        }

        if (empty($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], (string) $token)) {
            http_response_code(403);
            $wantsJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
                || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
            if ($wantsJson) {
                header('Content-Type: application/json');
                die(json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh and try again.']));
            }
            die('Security token validation failed. Please go back and try again.');
        }
    }
}
