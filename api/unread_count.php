<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['unread' => 0]);
    exit;
}

$stmt = getPDO()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([currentUserId()]);
echo json_encode(['unread' => (int) $stmt->fetchColumn()]);
