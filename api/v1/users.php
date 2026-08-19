<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$userId = (int) ($_GET['id'] ?? 0);
if (!$userId) {
    apiError('id is required.');
}

$me = optionalApiAuth();
$pdo = getPDO();

$stmt = $pdo->prepare(
    "SELECT u.*,
        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) AS follower_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) AS following_count,
        (SELECT COUNT(*) FROM videos WHERE user_id = u.id) AS video_count
     FROM users u WHERE u.id = ?"
);
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    apiError('User not found.', 404);
}

$isFollowing = false;
if ($me && $me['id'] !== $userId) {
    $stmt = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$me['id'], $userId]);
    $isFollowing = (bool) $stmt->fetch();
}

$profile = apiUser($user);
$profile['follower_count'] = (int) $user['follower_count'];
$profile['following_count'] = (int) $user['following_count'];
$profile['video_count'] = (int) $user['video_count'];
$profile['is_following'] = $isFollowing;
$profile['is_me'] = $me ? $me['id'] === $userId : false;

apiRespond(['success' => true, 'user' => $profile]);
