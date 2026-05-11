<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Notification ID missing']);
    exit();
}

$notification_id = (int)$_POST['id'];
$user_id = $_SESSION['user_id'];

// Verify the notification belongs to the user before deleting
$stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
$success = $stmt->execute([$notification_id, $user_id]);

echo json_encode(['success' => $success, 'deleted_id' => $notification_id]);
?>