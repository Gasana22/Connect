<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

$data = json_decode(file_get_contents('php://input'), true);
$video_id = $data['video_id'] ?? null;

if (!$video_id) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['success' => false, 'message' => 'Video ID required']));
}

try {
    // First get video info to verify ownership and get file path
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
    $stmt->execute([$video_id, $_SESSION['user_id']]);
    $video = $stmt->fetch();
    
    if (!$video) {
        header('HTTP/1.1 404 Not Found');
        die(json_encode(['success' => false, 'message' => 'Video not found or not owned by user']));
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete likes
    $pdo->prepare("DELETE FROM likes WHERE video_id = ?")->execute([$video_id]);
    
    // Delete comments
    $pdo->prepare("DELETE FROM comments WHERE video_id = ?")->execute([$video_id]);
    
    // Delete views
    $pdo->prepare("DELETE FROM views WHERE video_id = ?")->execute([$video_id]);
    
    // Delete video record
    $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$video_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Delete the actual file
    if (file_exists($video['video_path'])) {
        unlink($video['video_path']);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    header('HTTP/1.1 500 Internal Server Error');
    die(json_encode(['success' => false, 'message' => 'Database error']));
}