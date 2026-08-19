<?php
/**
 * Single database connection source. Every page in the app requires this file
 * (directly or via includes/auth.php) instead of opening its own PDO handle.
 */

require_once __DIR__ . '/../config/config.php';

if (!function_exists('getPDO')) {
    function getPDO(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            try {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES    => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Database connection error.');
            }
        }
        return $pdo;
    }
}

// Back-compat: most pages were written against a global $pdo.
$pdo = getPDO();
