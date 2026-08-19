<?php
// Single follow/unfollow endpoint (replaces the old duplicate follow.php/follow_user.php pair).
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

validateCsrf($body['csrf_token'] ?? null);

$targetUserId = (int) ($body['user_id'] ?? 0);
$action = $body['action'] ?? 'follow';
$viewerId = currentUserId();

if (!$targetUserId || $targetUserId === $viewerId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user.']);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
$stmt->execute([$targetUserId]);
$target = $stmt->fetch();
if (!$target) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if ($action === 'unfollow') {
    $stmt = $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$viewerId, $targetUserId]);
    $nowFollowing = false;
} else {
    $stmt = $pdo->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
    $stmt->execute([$viewerId, $targetUserId]);
    createNotification($pdo, $targetUserId, $viewerId, 'follow');
    $nowFollowing = true;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
$stmt->execute([$targetUserId]);
$followerCount = (int) $stmt->fetchColumn();

echo json_encode(['success' => true, 'following' => $nowFollowing, 'follower_count' => $followerCount]);
