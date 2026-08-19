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

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ? AND user_id = ?');
$stmt->execute([$videoId, $userId]);
$video = $stmt->fetch();

if (!$video) {
    echo json_encode(['success' => false, 'message' => 'Video not found or not yours to delete.']);
    exit;
}

$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$videoId]);
    $pdo->commit();
    $path = __DIR__ . '/' . $video['video_path'];
    if (file_exists($path)) {
        unlink($path);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Delete video failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not delete video.']);
}
