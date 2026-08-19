<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$videoId = (int) ($_GET['video_id'] ?? 0);
if (!$videoId) {
    echo json_encode(['comments' => []]);
    exit;
}

$stmt = getPDO()->prepare(
    'SELECT c.id, c.user_id, c.text, c.created_at, u.username, u.profile_pic
     FROM comments c JOIN users u ON c.user_id = u.id
     WHERE c.video_id = ? ORDER BY c.created_at ASC LIMIT 200'
);
$stmt->execute([$videoId]);

$comments = array_map(function ($row) {
    return [
        'user_id'  => (int) $row['user_id'],
        'username' => e($row['username']),
        'avatar'   => e(profilePicUrl($row['profile_pic'])),
        'text'     => e($row['text']),
    ];
}, $stmt->fetchAll());

echo json_encode(['comments' => $comments]);
