<?php
session_start();
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['stream_key'])) {
    die('Stream key not provided');
}

$streamKey = $_GET['stream_key'];

$stmt = $pdo->prepare("SELECT cm.*, u.username 
                      FROM chat_messages cm
                      JOIN users u ON cm.user_id = u.id
                      WHERE cm.stream_key = ?
                      ORDER BY cm.sent_at DESC
                      LIMIT 50");
$stmt->execute([$streamKey]);
$messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

foreach ($messages as $message) {
    echo '<div class="chat-message">';
    echo '<span class="chat-username">' . htmlspecialchars($message['username']) . ':</span>';
    echo '<span>' . htmlspecialchars($message['message']) . '</span>';
    echo '</div>';
}
?>