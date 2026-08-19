<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$me = requireApiAuth();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 20)));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare(
        "SELECT n.*, u.username AS sender_username, u.profile_pic AS sender_pic
         FROM notifications n JOIN users u ON n.sender_id = u.id
         WHERE n.user_id = :uid ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':uid', $me['id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = array_map(function ($n) {
        return [
            'id' => (int) $n['id'],
            'type' => $n['type'],
            'sender_id' => (int) $n['sender_id'],
            'sender_username' => $n['sender_username'],
            'sender_pic' => profilePicUrl($n['sender_pic']),
            'video_id' => $n['video_id'] ? (int) $n['video_id'] : null,
            'is_read' => (bool) $n['is_read'],
            'created_at' => $n['created_at'],
        ];
    }, $stmt->fetchAll());

    apiRespond(['success' => true, 'notifications' => $notifications]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = apiInput();
    $action = $body['action'] ?? '';

    if ($action === 'mark_all_read') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$me['id']]);
        apiRespond(['success' => true]);
    }
    if ($action === 'clear_all') {
        $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$me['id']]);
        apiRespond(['success' => true]);
    }
    apiError('Unknown action.');
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = (int) ($_GET['id'] ?? 0);
    $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?')->execute([$id, $me['id']]);
    apiRespond(['success' => true]);
}

apiError('Method not allowed.', 405);
