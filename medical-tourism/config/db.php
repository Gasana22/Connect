<?php
// -----------------------------------------------------------------------
// Database + site configuration
// Edit these values to match your XAMPP / MySQL setup.
// -----------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'medical_tourism');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL of the site as seen in the browser, e.g. http://localhost/medical-tourism
define('BASE_URL', 'http://localhost/medical-tourism');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please make sure MySQL is running in XAMPP and that the "medical_tourism" database has been imported.');
}
