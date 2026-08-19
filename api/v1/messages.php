<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$me = requireApiAuth();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $otherId = (int) ($_GET['user_id'] ?? 0);
    if (!$otherId) {
        apiError('user_id is required.');
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
         ORDER BY created_at ASC'
    );
    $stmt->execute([$me['id'], $otherId, $otherId, $me['id']]);
    $thread = array_map(function ($m) use ($me) {
        return [
            'id' => (int) $m['id'],
            'sender_id' => (int) $m['sender_id'],
            'content' => $m['content'],
            'mine' => (int) $m['sender_id'] === $me['id'],
            'created_at' => $m['created_at'],
        ];
    }, $stmt->fetchAll());

    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ?')->execute([$otherId, $me['id']]);

    apiRespond(['success' => true, 'messages' => $thread]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = apiInput();
    $otherId = (int) ($body['user_id'] ?? 0);
    $content = sanitize_input((string) ($body['content'] ?? ''));

    if (!$otherId || $content === '') {
        apiError('user_id and content are required.');
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$otherId]);
    if (!$stmt->fetch()) {
        apiError('User not found.', 404);
    }

    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, sender_name, recipient_id, content) VALUES (?, ?, ?, ?)');
    $stmt->execute([$me['id'], $me['username'], $otherId, $content]);
    $messageId = (int) $pdo->lastInsertId();
    createNotification($pdo, $otherId, $me['id'], 'message');

    apiRespond(['success' => true, 'message_id' => $messageId], 201);
}

apiError('Method not allowed.', 405);
