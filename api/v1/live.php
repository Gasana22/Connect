<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $streams = $pdo->query(
        'SELECT ls.*, u.username, u.profile_pic FROM live_streams ls JOIN users u ON ls.user_id = u.id
         WHERE ls.is_live = 1 ORDER BY ls.viewer_count DESC, ls.started_at DESC LIMIT 30'
    )->fetchAll();

    $rows = array_map(function ($s) {
        return [
            'stream_key' => $s['stream_key'],
            'user_id' => (int) $s['user_id'],
            'username' => $s['username'],
            'profile_pic' => profilePicUrl($s['profile_pic']),
            'title' => $s['title'],
            'viewer_count' => (int) $s['viewer_count'],
            'started_at' => $s['started_at'],
        ];
    }, $streams);

    apiRespond(['success' => true, 'streams' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = requireApiAuth();
    $body = apiInput();
    $title = sanitize_input($body['title'] ?? '');
    $categoryId = !empty($body['category_id']) ? (int) $body['category_id'] : null;

    if ($title === '') {
        apiError('title is required.');
    }

    $stmt = $pdo->prepare('SELECT id FROM live_streams WHERE user_id = ? AND is_live = 1');
    $stmt->execute([$me['id']]);
    if ($stmt->fetch()) {
        apiError('You already have an active stream.', 409);
    }

    $key = bin2hex(random_bytes(12));
    $stmt = $pdo->prepare('INSERT INTO live_streams (user_id, stream_key, title, category_id, is_live, started_at) VALUES (?, ?, ?, ?, 1, NOW())');
    $stmt->execute([$me['id'], $key, $title, $categoryId]);

    apiRespond(['success' => true, 'stream_key' => $key], 201);
}

apiError('Method not allowed.', 405);
