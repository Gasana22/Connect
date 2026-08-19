<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}

$me = requireApiAuth();
$body = apiInput();
$targetUserId = (int) ($body['user_id'] ?? 0);
$action = $body['action'] ?? 'follow';

if (!$targetUserId || $targetUserId === $me['id']) {
    apiError('Invalid user.');
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$targetUserId]);
if (!$stmt->fetch()) {
    apiError('User not found.', 404);
}

if ($action === 'unfollow') {
    $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')->execute([$me['id'], $targetUserId]);
    $following = false;
} else {
    $pdo->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)')->execute([$me['id'], $targetUserId]);
    createNotification($pdo, $targetUserId, $me['id'], 'follow');
    $following = true;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
$stmt->execute([$targetUserId]);

apiRespond(['success' => true, 'following' => $following, 'follower_count' => (int) $stmt->fetchColumn()]);
