<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html');

$streamKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['stream_key'] ?? '');
$afterId = (int) ($_GET['after_id'] ?? 0);

$stmt = getPDO()->prepare(
    'SELECT cm.*, u.username, u.profile_pic FROM chat_messages cm
     JOIN users u ON cm.user_id = u.id
     WHERE cm.stream_key = ? AND cm.id > ?
     ORDER BY cm.id ASC LIMIT 50'
);
$stmt->execute([$streamKey, $afterId]);
$messages = $stmt->fetchAll();

foreach ($messages as $m) {
    echo '<div class="chat-message" data-id="' . (int) $m['id'] . '">';
    echo '<img class="chat-avatar" src="' . e(profilePicUrl($m['profile_pic'])) . '" alt="">';
    echo '<div><strong>' . e($m['username']) . '</strong><span>' . e($m['message']) . '</span></div>';
    echo '</div>';
}
