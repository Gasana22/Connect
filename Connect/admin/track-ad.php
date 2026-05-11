<?php
require 'db.php';
require 'auth.php';

// Rate limiting function
function rateLimit($key, $limit = 10, $interval = 60) {
    $redis = new Redis();
    try {
        $redis->connect('127.0.0.1', 6379);
        $current = $redis->get($key);
        
        if ($current !== false && $current >= $limit) {
            return false;
        }
        
        $redis->multi();
        $redis->incr($key);
        $redis->expire($key, $interval);
        $redis->exec();
        
        return true;
    } catch (Exception $e) {
        error_log("Redis error: " . $e->getMessage());
        return true; // Fail open if Redis fails
    }
}

// Get ad ID from request with validation
$ad_id = filter_input(INPUT_GET, 'ad_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$ad_id) {
    header("HTTP/1.1 400 Bad Request");
    exit;
}

// Verify ad exists and is active
try {
    $stmt = $pdo->prepare("SELECT id FROM ads WHERE id = ? AND is_active = 1 
                          AND start_date <= NOW() AND end_date >= NOW()
                          AND (max_impressions = 0 OR max_impressions > (
                              SELECT COUNT(*) FROM ad_impressions WHERE ad_id = ads.id
                          ))");
    $stmt->execute([$ad_id]);
    
    if (!$stmt->fetch()) {
        header("HTTP/1.1 404 Not Found");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}

// Rate limit by IP and ad combination
$ip_address = $_SERVER['REMOTE_ADDR'];
$rateLimitKey = "ad_{$ad_id}_ip_{$ip_address}";

if (!rateLimit($rateLimitKey, 5, 60)) { // Max 5 requests per minute per IP per ad
    header("HTTP/1.1 429 Too Many Requests");
    exit;
}

// Get user info with validation
$user_id = isset($_SESSION['user_id']) ? filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) : 0;
$ip_address = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
$user_agent = substr(filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING), 0, 255);

// Record the impression with prepared statement
try {
    $stmt = $pdo->prepare("INSERT INTO ad_impressions (ad_id, user_id, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([$ad_id, $user_id, $ip_address, $user_agent]);
} catch (PDOException $e) {
    error_log("Failed to record impression: " . $e->getMessage());
}

// Return 1x1 transparent pixel
header("Content-Type: image/gif");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
echo base64_decode("R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==");