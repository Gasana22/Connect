<?php
session_start();
require 'db.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (!isset($_SESSION['user_id'])) {
    die("retry: 1000\n\n");
}

$user_id = $_SESSION['user_id'];

// Get the last notification ID the client knows about
$lastId = isset($_SERVER['HTTP_LAST_EVENT_ID']) ? $_SERVER['HTTP_LAST_EVENT_ID'] : 0;

// Close session to allow other requests during long polling
session_write_close();

function sendNotification($data) {
    echo "id: " . $data['id'] . "\n";
    echo "event: notification\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Check for new notifications every 5 seconds (long polling)
while (true) {
    $stmt = $pdo->prepare("
        SELECT n.*, u.username, u.profile_pic, v.caption 
        FROM notifications n
        LEFT JOIN users u ON n.sender_id = u.id
        LEFT JOIN videos v ON n.video_id = v.id
        WHERE n.user_id = ? AND n.id > ? AND n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $lastId]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($notification) {
        sendNotification($notification);
        $lastId = $notification['id'];
        
        // Mark as read immediately after sending
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$notification['id']]);
    }

    // Sleep for 5 seconds before checking again
    sleep(5);
    
    // Add a keep-alive comment every 15 seconds
    if (time() % 15 == 0) {
        echo ": keep-alive\n\n";
        ob_flush();
        flush();
    }
}
?>