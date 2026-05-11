<?php
// Database connection settings
$host = 'localhost';
$dbname = 'connect';
$user = 'root';
$pass = 'root';

try {
    // Create a new PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Create a notification (used for likes, comments, follows, etc.)
if (!function_exists('createNotification')) {
    function createNotification($pdo, $user_id, $sender_id, $type, $video_id = null) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, sender_id, type, video_id, created_at, is_read)
            VALUES (?, ?, ?, ?, NOW(), 0)
        ");
        return $stmt->execute([$user_id, $sender_id, $type, $video_id]);
    }
}
?>
