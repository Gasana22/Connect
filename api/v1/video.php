<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$videoId = (int) ($_GET['id'] ?? 0);
if (!$videoId) {
    apiError('id is required.');
}

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $me = optionalApiAuth();
    $userId = $me['id'] ?? null;

    $stmt = $pdo->prepare(
        "SELECT v.*, u.username, u.profile_pic, u.account_type, u.is_verified,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
            (SELECT COUNT(*) FROM views WHERE video_id = v.id) AS view_count,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) AS is_liked,
            (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = v.user_id) AS is_following
         FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?"
    );
    $stmt->execute([$userId, $userId, $videoId]);
    $v = $stmt->fetch();
    if (!$v) {
        apiError('Video not found.', 404);
    }

    $pdo->prepare('INSERT INTO views (user_id, video_id) VALUES (?, ?)')->execute([$userId, $videoId]);

    apiRespond(['success' => true, 'video' => [
        'id' => (int) $v['id'],
        'user_id' => (int) $v['user_id'],
        'username' => $v['username'],
        'profile_pic' => profilePicUrl($v['profile_pic']),
        'account_type' => $v['account_type'],
        'is_verified' => (bool) $v['is_verified'],
        'caption' => $v['caption'],
        'video_url' => $v['video_path'],
        'category' => $v['category'],
        'like_count' => (int) $v['like_count'],
        'comment_count' => (int) $v['comment_count'],
        'view_count' => (int) $v['view_count'],
        'is_liked' => (bool) $v['is_liked'],
        'is_following' => (bool) $v['is_following'],
        'created_at' => $v['created_at'],
    ]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $me = requireApiAuth();
    $stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ? AND user_id = ?');
    $stmt->execute([$videoId, $me['id']]);
    $video = $stmt->fetch();
    if (!$video) {
        apiError('Video not found or not yours to delete.', 404);
    }
    $pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$videoId]);
    @unlink(__DIR__ . '/../../' . $video['video_path']);
    apiRespond(['success' => true]);
}

apiError('Method not allowed.', 405);
