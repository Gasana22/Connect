<?php
/**
 * Receives one MediaRecorder chunk (raw bytes in the request body) from the
 * broadcaster and appends it to that stream's growing file. Chunks arrive in
 * order from a single continuous `mediaRecorder.start(timeslice)` session, so
 * appending them in arrival order reconstructs one valid, playable WebM file —
 * this is the whole trick behind the chunked-upload live approach (see the
 * project plan): no RTMP/HLS media server needed, just an append-only file
 * that viewers poll and re-buffer from (assets/js/live-viewer.js).
 */
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

validateCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['key'] ?? '');
if ($streamKey === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing stream key.']);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM live_streams WHERE stream_key = ? AND user_id = ? AND is_live = 1');
$stmt->execute([$streamKey, currentUserId()]);
$stream = $stmt->fetch();

if (!$stream) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Stream not found or not yours to broadcast to.']);
    exit;
}

$chunk = file_get_contents('php://input');
if ($chunk === '' || strlen($chunk) > 5 * 1024 * 1024) { // 5MB per chunk is generous for a few seconds of video
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid chunk.']);
    exit;
}

$path = UPLOAD_DIR_LIVE . $streamKey . '.webm';
file_put_contents($path, $chunk, FILE_APPEND | LOCK_EX);

echo json_encode(['success' => true, 'size' => filesize($path)]);
