<?php
session_start();
require 'db.php';

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$video_id = $data['video_id'] ?? null;
$action = $data['action'] ?? null;

if (!$video_id || !in_array($action, ['like', 'unlike'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

try {
    if ($action === 'like') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO likes (user_id, video_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $video_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
    }

    // Return updated likes count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE video_id = ?");
    $countStmt->execute([$video_id]);
    $likesCount = $countStmt->fetchColumn();

    echo json_encode(['success' => true, 'likes' => $likesCount]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
