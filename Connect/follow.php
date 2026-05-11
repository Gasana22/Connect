<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$target_user_id = $_POST['user_id'] ?? 0;
$action = $_POST['action'] ?? '';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit();
}

if (!$target_user_id || !in_array($action, ['follow', 'unfollow'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    if ($action === 'follow') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $target_user_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$user_id, $target_user_id]);
    }
    
    // Return updated follow count and status
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $countStmt->execute([$user_id]);
    $followingCount = $countStmt->fetchColumn();

    echo json_encode(['success' => true, 'followingCount' => $followingCount]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
