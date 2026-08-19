<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // List a user's videos: GET videos.php?user_id=5
    $userId = (int) ($_GET['user_id'] ?? 0);
    if (!$userId) {
        apiError('user_id is required.');
    }
    $stmt = $pdo->prepare(
        "SELECT v.*,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM views WHERE video_id = v.id) AS view_count
         FROM videos v WHERE v.user_id = ? ORDER BY v.created_at DESC"
    );
    $stmt->execute([$userId]);
    $videos = array_map(function ($v) {
        return [
            'id' => (int) $v['id'],
            'caption' => $v['caption'],
            'video_url' => $v['video_path'],
            'category' => $v['category'],
            'like_count' => (int) $v['like_count'],
            'view_count' => (int) $v['view_count'],
            'created_at' => $v['created_at'],
        ];
    }, $stmt->fetchAll());
    apiRespond(['success' => true, 'videos' => $videos]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $me = requireApiAuth();
    $caption = sanitize_input($_POST['caption'] ?? '');
    $category = sanitize_input($_POST['category'] ?? '');

    if ($category === '') {
        apiError('category is required.');
    }
    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        apiError('A video file is required.');
    }

    $result = validateVideoUpload($_FILES['video']);
    if (!$result['ok']) {
        apiError($result['error']);
    }

    $filename = randomFilename($result['ext']);
    if (!move_uploaded_file($_FILES['video']['tmp_name'], UPLOAD_DIR_VIDEOS . $filename)) {
        apiError('Failed to save video.', 500);
    }
    $videoPath = UPLOAD_URL_VIDEOS . $filename;

    $pdo->beginTransaction();
    try {
        $hashtags = extractHashtags($caption);
        $stmt = $pdo->prepare('INSERT INTO videos (user_id, caption, video_path, tags, category) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$me['id'], $caption, $videoPath, implode(',', $hashtags), $category]);
        $videoId = (int) $pdo->lastInsertId();

        if ($hashtags) {
            $tagStmt = $pdo->prepare('INSERT INTO video_hashtags (video_id, tag) VALUES (?, ?)');
            foreach ($hashtags as $tag) {
                $tagStmt->execute([$videoId, $tag]);
            }
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('API video upload failed: ' . $e->getMessage());
        @unlink(UPLOAD_DIR_VIDEOS . $filename);
        apiError('Upload failed. Please try again.', 500);
    }

    apiRespond(['success' => true, 'video_id' => $videoId, 'video_url' => $videoPath], 201);
}

apiError('Method not allowed.', 405);
