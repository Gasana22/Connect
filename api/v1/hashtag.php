<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$tag = strtolower(trim($_GET['tag'] ?? ''));
if ($tag === '') {
    apiError('tag is required.');
}

$stmt = getPDO()->prepare(
    "SELECT v.*, u.username, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count
     FROM videos v JOIN video_hashtags h ON h.video_id = v.id JOIN users u ON v.user_id = u.id
     WHERE h.tag = ? ORDER BY v.created_at DESC"
);
$stmt->execute([$tag]);

$videos = array_map(function ($v) {
    return [
        'id' => (int) $v['id'],
        'user_id' => (int) $v['user_id'],
        'username' => $v['username'],
        'profile_pic' => profilePicUrl($v['profile_pic']),
        'video_url' => $v['video_path'],
        'like_count' => (int) $v['like_count'],
    ];
}, $stmt->fetchAll());

apiRespond(['success' => true, 'tag' => $tag, 'videos' => $videos]);
