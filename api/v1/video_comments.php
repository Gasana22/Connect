<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $videoId = (int) ($_GET['video_id'] ?? 0);
    if (!$videoId) {
        apiError('video_id is required.');
    }
    $stmt = $pdo->prepare(
        'SELECT c.id, c.user_id, c.text, c.created_at, u.username, u.profile_pic
         FROM comments c JOIN users u ON c.user_id = u.id
         WHERE c.video_id = ? ORDER BY c.created_at ASC LIMIT 200'
    );
    $stmt->execute([$videoId]);
    $comments = array_map(function ($c) {
        return [
            'id' => (int) $c['id'],
            'user_id' => (int) $c['user_id'],
            'username' => $c['username'],
            'profile_pic' => profilePicUrl($c['profile_pic']),
            'text' => $c['text'],
            'created_at' => $c['created_at'],
        ];
    }, $stmt->fetchAll());
    apiRespond(['success' => true, 'comments' => $comments]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = requireApiAuth();
    $body = apiInput();
    $videoId = (int) ($body['video_id'] ?? 0);
    $text = sanitize_input((string) ($body['text'] ?? ''));

    if (!$videoId || $text === '') {
        apiError('video_id and text are required.');
    }
    if (strlen($text) > 1000) {
        apiError('Comment is too long.');
    }

    $stmt = $pdo->prepare('SELECT user_id FROM videos WHERE id = ?');
    $stmt->execute([$videoId]);
    $video = $stmt->fetch();
    if (!$video) {
        apiError('Video not found.', 404);
    }

    $stmt = $pdo->prepare('INSERT INTO comments (user_id, video_id, text) VALUES (?, ?, ?)');
    $stmt->execute([$me['id'], $videoId, $text]);
    $commentId = (int) $pdo->lastInsertId();
    createNotification($pdo, (int) $video['user_id'], $me['id'], 'comment', $videoId);

    apiRespond(['success' => true, 'comment_id' => $commentId], 201);
}

apiError('Method not allowed.', 405);
