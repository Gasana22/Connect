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

$user_id = $_SESSION['user_id'];

// Delete all notifications for the user
$stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
$success = $stmt->execute([$user_id]);

echo json_encode(['success' => $success, 'count' => $stmt->rowCount()]);
?>