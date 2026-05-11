<?php
require 'db.php';

$video_id = intval($_GET['video_id']);

$stmt = $pdo->prepare("
  SELECT c.*, u.username FROM comments c
  JOIN users u ON c.user_id = u.id
  WHERE c.video_id = ?
  ORDER BY c.created_at ASC
");
$stmt->execute([$video_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($comments);
