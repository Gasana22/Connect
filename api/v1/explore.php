<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'trending';

$pdo = getPDO();
$sql = "SELECT v.*, u.username, u.profile_pic, u.account_type,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM views WHERE video_id = v.id) AS view_count
        FROM videos v JOIN users u ON v.user_id = u.id";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE v.caption LIKE :search OR u.username LIKE :search OR v.tags LIKE :search';
    $params[':search'] = '%' . $search . '%';
}
$sql .= match ($sort) {
    'liked' => ' ORDER BY like_count DESC',
    'viewed' => ' ORDER BY view_count DESC',
    default => ' ORDER BY v.created_at DESC',
};
$sql .= ' LIMIT 60';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$videos = array_map(function ($v) {
    return [
        'id' => (int) $v['id'],
        'user_id' => (int) $v['user_id'],
        'username' => $v['username'],
        'profile_pic' => profilePicUrl($v['profile_pic']),
        'account_type' => $v['account_type'],
        'video_url' => $v['video_path'],
        'like_count' => (int) $v['like_count'],
        'view_count' => (int) $v['view_count'],
    ];
}, $stmt->fetchAll());

$tags = $pdo->query('SELECT tag, COUNT(*) AS uses FROM video_hashtags GROUP BY tag ORDER BY uses DESC LIMIT 12')->fetchAll();

apiRespond(['success' => true, 'videos' => $videos, 'popular_tags' => array_column($tags, 'tag')]);
