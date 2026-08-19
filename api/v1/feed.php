<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$me = optionalApiAuth();
$userId = $me['id'] ?? null;
$tab = ($_GET['tab'] ?? 'for-you') === 'following' ? 'following' : 'for-you';
$perPage = min(30, max(1, (int) ($_GET['per_page'] ?? 10)));
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$pdo = getPDO();

if ($tab === 'following' && $userId) {
    $stmt = $pdo->prepare(
        "SELECT v.*, u.username, u.profile_pic, u.account_type, u.is_verified,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
            1 AS is_following,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = :uid1) AS is_liked
         FROM videos v JOIN users u ON v.user_id = u.id JOIN follows f ON v.user_id = f.following_id
         WHERE f.follower_id = :uid2 ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
} else {
    $stmt = $pdo->prepare(
        "SELECT v.*, u.username, u.profile_pic, u.account_type, u.is_verified,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :uid1 AND following_id = v.user_id) AS is_following,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = :uid2) AS is_liked
         FROM videos v JOIN users u ON v.user_id = u.id
         ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$videos = $stmt->fetchAll();

$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare(
    "SELECT * FROM ads WHERE is_active = 1 AND start_date <= ? AND end_date >= ?
     AND (max_impressions = 0 OR max_impressions > (SELECT COUNT(*) FROM ad_impressions WHERE ad_id = ads.id))
     ORDER BY RAND() LIMIT 2"
);
$stmt->execute([$now, $now]);
$ads = $stmt->fetchAll();

$items = array_map(function ($v) {
    return [
        'type' => 'video',
        'video' => [
            'id'            => (int) $v['id'],
            'user_id'       => (int) $v['user_id'],
            'username'      => $v['username'],
            'profile_pic'   => profilePicUrl($v['profile_pic']),
            'account_type'  => $v['account_type'],
            'is_verified'   => (bool) $v['is_verified'],
            'caption'       => $v['caption'],
            'video_url'     => $v['video_path'],
            'category'      => $v['category'],
            'like_count'    => (int) $v['like_count'],
            'comment_count' => (int) $v['comment_count'],
            'is_liked'      => (bool) $v['is_liked'],
            'is_following'  => (bool) $v['is_following'],
            'created_at'    => $v['created_at'],
        ],
    ];
}, $videos);

foreach ($ads as $i => $ad) {
    $pos = min(count($items), ($i + 1) * 4);
    array_splice($items, $pos, 0, [[
        'type' => 'ad',
        'ad' => [
            'id' => (int) $ad['id'],
            'title' => $ad['title'],
            'description' => $ad['description'],
            'image_url' => $ad['image_url'],
            'target_url' => $ad['target_url'],
            'sponsor' => $ad['sponsor'],
        ],
    ]]);
    $pdo->prepare('INSERT INTO ad_impressions (ad_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)')
        ->execute([$ad['id'], $userId, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
}

apiRespond(['success' => true, 'items' => $items, 'page' => $page, 'has_more' => count($videos) === $perPage]);
