<?php
require 'includes/db.php';
require 'includes/auth.php';

$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
if (empty($tag)) {
    header("Location: index.php");
    exit();
}

// Pagination setup
$videos_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $videos_per_page;

// Get videos with this hashtag
$stmt = $pdo->prepare("
    SELECT v.*, u.username, 
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as like_count,
           (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = v.user_id) as is_following,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) as is_liked
    FROM videos v
    JOIN users u ON v.user_id = u.id
    JOIN video_hashtags vh ON v.id = vh.video_id
    WHERE vh.tag = ?
    ORDER BY v.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([
    $_SESSION['user_id'] ?? 0, 
    $_SESSION['user_id'] ?? 0, 
    $tag, 
    $videos_per_page, 
    $offset
]);
$videos = $stmt->fetchAll();

// Get total count for pagination
$total_videos = $pdo->prepare("
    SELECT COUNT(*) 
    FROM video_hashtags 
    WHERE tag = ?
")->execute([$tag])->fetchColumn();

// Get trending hashtags for sidebar
$trending_hashtags = $pdo->query("
    SELECT tag, COUNT(*) as count 
    FROM video_hashtags 
    WHERE created_at >= NOW() - INTERVAL 7 DAY
    GROUP BY tag 
    ORDER BY count DESC 
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>#<?= htmlspecialchars($tag) ?> | Business Video Platform</title>
    <style>
.hashtag-page {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
    padding: 2rem;
}

.trending-tag {
    display: block;
    padding: 8px 12px;
    margin-bottom: 8px;
    background: #f5f5f5;
    border-radius: 4px;
}

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="hashtag-page">
        <div class="content">
            <div class="hashtag-header">
                <h1>#<?= htmlspecialchars($tag) ?></h1>
                <p class="video-count"><?= $total_videos ?> videos</p>
            </div>
            
            <?php if (empty($videos)): ?>
                <div class="empty-state">
                    <p>No videos found with this hashtag.</p>
                    <a href="upload.php" class="btn">Be the first to upload</a>
                </div>
            <?php else: ?>
                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                        <?php renderVideoCard($video, $_SESSION['user_id'] ?? null); ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?tag=<?= urlencode($tag) ?>&page=<?= $current_page - 1 ?>" class="page-link">
                            &laquo; Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="current-page">Page <?= $current_page ?> of <?= ceil($total_videos / $videos_per_page) ?></span>
                    
                    <?php if ($current_page * $videos_per_page < $total_videos): ?>
                        <a href="?tag=<?= urlencode($tag) ?>&page=<?= $current_page + 1 ?>" class="page-link">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="trending-section">
                <h3>Trending Hashtags</h3>
                <div class="trending-tags">
                    <?php foreach ($trending_hashtags as $trending): ?>
                        <a href="hashtag.php?tag=<?= urlencode($trending['tag']) ?>" class="trending-tag">
                            #<?= htmlspecialchars($trending['tag']) ?>
                            <span class="tag-count"><?= $trending['count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>