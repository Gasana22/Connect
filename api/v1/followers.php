<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$profileId = (int) ($_GET['user_id'] ?? 0);
if (!$profileId) {
    apiError('user_id is required.');
}

$me = optionalApiAuth();
$viewerId = $me['id'] ?? null;

$stmt = getPDO()->prepare(
    "SELECT u.id, u.username, u.profile_pic, u.account_type, u.is_verified,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) AS is_following
     FROM follows f JOIN users u ON f.follower_id = u.id
     WHERE f.following_id = ? ORDER BY f.created_at DESC"
);
$stmt->execute([$viewerId, $profileId]);

$rows = array_map(function ($u) {
    return [
        'id' => (int) $u['id'],
        'username' => $u['username'],
        'profile_pic' => profilePicUrl($u['profile_pic']),
        'account_type' => $u['account_type'],
        'is_verified' => (bool) $u['is_verified'],
        'is_following' => (bool) $u['is_following'],
    ];
}, $stmt->fetchAll());

apiRespond(['success' => true, 'users' => $rows]);
