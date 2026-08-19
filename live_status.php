<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['key'] ?? '');
$stmt = getPDO()->prepare('SELECT is_live, viewer_count FROM live_streams WHERE stream_key = ?');
$stmt->execute([$streamKey]);
$stream = $stmt->fetch();

if (!$stream) {
    echo json_encode(['is_live' => false, 'size' => 0, 'viewer_count' => 0]);
    exit;
}

$path = UPLOAD_DIR_LIVE . $streamKey . '.webm';
$size = file_exists($path) ? filesize($path) : 0;

echo json_encode([
    'is_live' => (bool) $stream['is_live'],
    'size' => $size,
    'viewer_count' => (int) $stream['viewer_count'],
]);
