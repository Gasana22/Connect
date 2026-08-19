<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['key'] ?? '');
if ($streamKey === '') {
    apiError('key is required.');
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $afterId = (int) ($_GET['after_id'] ?? 0);
    $stmt = $pdo->prepare(
        'SELECT cm.*, u.username, u.profile_pic FROM chat_messages cm JOIN users u ON cm.user_id = u.id
         WHERE cm.stream_key = ? AND cm.id > ? ORDER BY cm.id ASC LIMIT 50'
    );
    $stmt->execute([$streamKey, $afterId]);

    $messages = array_map(function ($m) {
        return [
            'id' => (int) $m['id'],
            'user_id' => (int) $m['user_id'],
            'username' => $m['username'],
            'profile_pic' => profilePicUrl($m['profile_pic']),
            'message' => $m['message'],
            'sent_at' => $m['sent_at'],
        ];
    }, $stmt->fetchAll());

    apiRespond(['success' => true, 'messages' => $messages]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = requireApiAuth();
    $body = apiInput();
    $message = sanitize_input((string) ($body['message'] ?? ''));
    if ($message === '') {
        apiError('message is required.');
    }

    $stmt = $pdo->prepare('INSERT INTO chat_messages (stream_key, user_id, message) VALUES (?, ?, ?)');
    $stmt->execute([$streamKey, $me['id'], $message]);

    apiRespond(['success' => true, 'id' => (int) $pdo->lastInsertId()], 201);
}

apiError('Method not allowed.', 405);
