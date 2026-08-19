<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiError('Method not allowed.', 405);
}

$me = requireApiAuth();

// Most recent message per counterpart, newest conversation first.
$stmt = getPDO()->prepare(
    "SELECT m.*, u.username AS other_username, u.profile_pic AS other_pic
     FROM messages m
     JOIN users u ON u.id = IF(m.sender_id = :me1, m.recipient_id, m.sender_id)
     WHERE m.id IN (
         SELECT MAX(id) FROM messages
         WHERE sender_id = :me2 OR recipient_id = :me3
         GROUP BY IF(sender_id = :me4, recipient_id, sender_id)
     )
     ORDER BY m.created_at DESC"
);
$stmt->execute([':me1' => $me['id'], ':me2' => $me['id'], ':me3' => $me['id'], ':me4' => $me['id']]);

$conversations = array_map(function ($m) use ($me) {
    $otherId = (int) ($m['sender_id'] == $me['id'] ? $m['recipient_id'] : $m['sender_id']);
    return [
        'user_id' => $otherId,
        'username' => $m['other_username'],
        'profile_pic' => profilePicUrl($m['other_pic']),
        'last_message' => $m['content'],
        'last_message_at' => $m['created_at'],
        'unread' => (int) $m['recipient_id'] === $me['id'] && !(bool) $m['is_read'],
    ];
}, $stmt->fetchAll());

apiRespond(['success' => true, 'conversations' => $conversations]);
