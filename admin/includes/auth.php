<?php
/**
 * Admin-only session/auth layer. Deliberately separate from includes/auth.php:
 * its own session keys (admin_id / admin_username), its own CSRF token, its own
 * login surface. An admin and a regular user can be logged in at once in the
 * same browser session without colliding.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ADMIN_SESSION_TIMEOUT', 1800); // 30 minutes

if (!function_exists('isAdminLoggedIn')) {
    function isAdminLoggedIn(): bool {
        return !empty($_SESSION['admin_id']);
    }
}

if (!function_exists('requireAdminAuth')) {
    function requireAdminAuth(): void {
        if (!isAdminLoggedIn()) {
            header('Location: login.php');
            exit;
        }
        if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > ADMIN_SESSION_TIMEOUT)) {
            adminLogout();
            header('Location: login.php?timeout=1');
            exit;
        }
        $_SESSION['admin_last_activity'] = time();
    }
}

if (!function_exists('attemptAdminLogin')) {
    function attemptAdminLogin(string $email, string $password): bool {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM admin WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_last_activity'] = time();
            return true;
        }
        return false;
    }
}

if (!function_exists('adminLogout')) {
    function adminLogout(): void {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_last_activity'], $_SESSION['admin_csrf_token']);
    }
}

if (!function_exists('adminCsrfToken')) {
    function adminCsrfToken(): string {
        if (empty($_SESSION['admin_csrf_token'])) {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf_token'];
    }
}

if (!function_exists('validateAdminCsrf')) {
    function validateAdminCsrf(?string $token = null): void {
        $token = $token ?? ($_POST['csrf_token'] ?? null);
        if (empty($_SESSION['admin_csrf_token']) || !$token || !hash_equals($_SESSION['admin_csrf_token'], (string) $token)) {
            http_response_code(403);
            die('CSRF token validation failed.');
        }
    }
}
