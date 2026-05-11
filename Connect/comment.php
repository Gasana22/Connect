<?php
session_start();
require 'db.php';
require 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $video_id = intval($_POST['video_id']);
    $comment = sanitize_input($_POST['comment']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if (empty($comment)) {
        echo json_encode(['error' => 'Comment cannot be empty']);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO comments (user_id, video_id, comment, parent_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $video_id, $comment, $parent_id]);

    echo json_encode(['success' => true]);
}
?>
