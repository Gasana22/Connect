<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['key'] ?? '');
if ($streamKey === '') {
    apiError('key is required.');
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $me = optionalApiAuth();
    $stmt = $pdo->prepare('SELECT ls.*, u.username, u.profile_pic FROM live_streams ls JOIN users u ON ls.user_id = u.id WHERE ls.stream_key = ?');
    $stmt->execute([$streamKey]);
    $stream = $stmt->fetch();
    if (!$stream) {
        apiError('Stream not found.', 404);
    }

    if ($stream['is_live'] && (!$me || $me['id'] !== (int) $stream['user_id'])) {
        $pdo->prepare('UPDATE live_streams SET viewer_count = viewer_count + 1 WHERE id = ?')->execute([$stream['id']]);
    }

    apiRespond(['success' => true, 'stream' => [
        'stream_key' => $stream['stream_key'],
        'user_id' => (int) $stream['user_id'],
        'username' => $stream['username'],
        'profile_pic' => profilePicUrl($stream['profile_pic']),
        'title' => $stream['title'],
        'is_live' => (bool) $stream['is_live'],
        'viewer_count' => (int) $stream['viewer_count'],
        'stream_url' => UPLOAD_URL_LIVE . $streamKey . '.webm',
        'is_mine' => $me ? $me['id'] === (int) $stream['user_id'] : false,
    ]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = requireApiAuth();
    $body = apiInput();
    if (($body['action'] ?? '') !== 'stop') {
        apiError('Unknown action.');
    }

    $stmt = $pdo->prepare('SELECT * FROM live_streams WHERE stream_key = ? AND user_id = ?');
    $stmt->execute([$streamKey, $me['id']]);
    if (!$stmt->fetch()) {
        apiError('Stream not found or not yours.', 404);
    }

    $pdo->prepare('UPDATE live_streams SET is_live = 0, ended_at = NOW() WHERE stream_key = ?')->execute([$streamKey]);
    apiRespond(['success' => true]);
}

apiError('Method not allowed.', 405);
