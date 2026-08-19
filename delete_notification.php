<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;
}

validateCsrf($body['csrf_token'] ?? null);

$id = (int) ($body['notification_id'] ?? 0);
$stmt = getPDO()->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
$stmt->execute([$id, currentUserId()]);

echo json_encode(['success' => true]);
