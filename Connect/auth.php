<?php
session_start();

// Database connection
function getPDO() {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=connect', 'username', 'password', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die('Database connection error');
        }
    }
    return $pdo;
}

// Authentication functions
function requireAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireAdminAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied. Admins only.');
    }
}

function attemptLogin($email, $password) {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['user_role'] = 'admin'; // Always 'admin' from your table structure
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    return false;
}

function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// CSRF protection
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header('HTTP/1.1 403 Forbidden');
        exit('CSRF token validation failed');
    }
}