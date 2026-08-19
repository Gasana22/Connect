<?php
require_once __DIR__ . '/includes/auth.php';

$tag = strtolower(trim($_GET['tag'] ?? ''));
if ($tag === '') {
    header('Location: explore.php');
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    "SELECT v.*, u.username, u.profile_pic,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count
     FROM videos v
     JOIN video_hashtags h ON h.video_id = v.id
     JOIN users u ON v.user_id = u.id
     WHERE h.tag = ?
     ORDER BY v.created_at DESC"
);
$stmt->execute([$tag]);
$videos = $stmt->fetchAll();

$pageTitle = '#' . $tag;
include __DIR__ . '/includes/layout_header.php';
?>
<h1 style="margin-bottom: 1.5rem;">#<?= e($tag) ?></h1>

<?php if (!$videos): ?>
  <div class="empty-state">
    <i class="fas fa-hashtag"></i>
    <h3>Nothing here yet</h3>
    <p>No videos have used #<?= e($tag) ?> yet.</p>
  </div>
<?php else: ?>
  <div class="video-grid">
    <?php foreach ($videos as $video): ?>
      <a class="video-card" href="watch.php?id=<?= (int) $video['id'] ?>" style="text-decoration:none;">
        <div class="video-player"><video src="<?= e($video['video_path']) ?>" muted preload="metadata"></video></div>
        <div class="video-header">
          <span class="username" style="color:var(--ink);"><?= e($video['username']) ?></span>
          <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-heart"></i> <?= formatNumber((int) $video['like_count']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
