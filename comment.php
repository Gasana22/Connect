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
$text = sanitize_input((string) ($body['text'] ?? ''));
$parentId = !empty($body['parent_id']) ? (int) $body['parent_id'] : null;
$userId = currentUserId();

if (!$videoId || $text === '') {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
    exit;
}
if (strlen($text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comment is too long.']);
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

$stmt = $pdo->prepare('INSERT INTO comments (user_id, video_id, parent_id, text) VALUES (?, ?, ?, ?)');
$stmt->execute([$userId, $videoId, $parentId, $text]);
$commentId = (int) $pdo->lastInsertId();
createNotification($pdo, (int) $video['user_id'], $userId, 'comment', $videoId);

echo json_encode(['success' => true, 'comment_id' => $commentId]);
