<?php
// Token-authed twin of the web app's /live_chunk.php — see that file for how the
// chunked-upload live approach works. Same append-only file, different auth.
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}

$me = requireApiAuth();
$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['key'] ?? '');
if ($streamKey === '') {
    apiError('key is required.');
}

$stmt = getPDO()->prepare('SELECT id FROM live_streams WHERE stream_key = ? AND user_id = ? AND is_live = 1');
$stmt->execute([$streamKey, $me['id']]);
if (!$stmt->fetch()) {
    apiError('Stream not found or not yours to broadcast to.', 403);
}

$chunk = file_get_contents('php://input');
if ($chunk === '' || strlen($chunk) > 5 * 1024 * 1024) {
    apiError('Invalid chunk.');
}

$path = UPLOAD_DIR_LIVE . $streamKey . '.webm';
file_put_contents($path, $chunk, FILE_APPEND | LOCK_EX);

apiRespond(['success' => true, 'size' => filesize($path)]);
