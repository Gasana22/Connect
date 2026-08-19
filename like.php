<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

validateCsrf($body['csrf_token'] ?? null);

$videoId = (int) ($body['video_id'] ?? 0);
$userId = currentUserId();

if (!$videoId) {
    echo json_encode(['success' => false, 'message' => 'Invalid video.']);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT user_id FROM videos WHERE id = ?');
$stmt->execute([$videoId]);
$video = $stmt->fetch();
if (!$video) {
    echo json_encode(['success' => false, 'message' => 'Video not found.']);
    exit;
}

$stmt = $pdo->prepare('SELECT 1 FROM likes WHERE user_id = ? AND video_id = ?');
$stmt->execute([$userId, $videoId]);

if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND video_id = ?')->execute([$userId, $videoId]);
    $liked = false;
} else {
    $pdo->prepare('INSERT INTO likes (user_id, video_id) VALUES (?, ?)')->execute([$userId, $videoId]);
    createNotification($pdo, (int) $video['user_id'], $userId, 'like', $videoId);
    $liked = true;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE video_id = ?');
$stmt->execute([$videoId]);
$likeCount = (int) $stmt->fetchColumn();

echo json_encode(['success' => true, 'liked' => $liked, 'like_count' => $likeCount]);
