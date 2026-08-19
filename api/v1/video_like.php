<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}

$me = requireApiAuth();
$body = apiInput();
$videoId = (int) ($body['video_id'] ?? 0);

if (!$videoId) {
    apiError('video_id is required.');
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT user_id FROM videos WHERE id = ?');
$stmt->execute([$videoId]);
$video = $stmt->fetch();
if (!$video) {
    apiError('Video not found.', 404);
}

$stmt = $pdo->prepare('SELECT 1 FROM likes WHERE user_id = ? AND video_id = ?');
$stmt->execute([$me['id'], $videoId]);

if ($stmt->fetch()) {
    $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND video_id = ?')->execute([$me['id'], $videoId]);
    $liked = false;
} else {
    $pdo->prepare('INSERT INTO likes (user_id, video_id) VALUES (?, ?)')->execute([$me['id'], $videoId]);
    createNotification($pdo, (int) $video['user_id'], $me['id'], 'like', $videoId);
    $liked = true;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE video_id = ?');
$stmt->execute([$videoId]);

apiRespond(['success' => true, 'liked' => $liked, 'like_count' => (int) $stmt->fetchColumn()]);
