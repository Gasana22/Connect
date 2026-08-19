<?php
// Non-incrementing status poll — see live_stream.php for the initial-load
// endpoint that bumps viewer_count once. Clients should poll THIS one
// repeatedly (mirrors the web viewer's live_status.php) so viewer_count
// doesn't inflate on every poll tick.
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['key'] ?? '');
if ($streamKey === '') {
    apiError('key is required.');
}

$stmt = getPDO()->prepare('SELECT is_live, viewer_count FROM live_streams WHERE stream_key = ?');
$stmt->execute([$streamKey]);
$stream = $stmt->fetch();

if (!$stream) {
    apiRespond(['success' => true, 'is_live' => false, 'size' => 0, 'viewer_count' => 0]);
}

$path = UPLOAD_DIR_LIVE . $streamKey . '.webm';
$size = file_exists($path) ? filesize($path) : 0;

apiRespond([
    'success' => true,
    'is_live' => (bool) $stream['is_live'],
    'size' => $size,
    'viewer_count' => (int) $stream['viewer_count'],
]);
