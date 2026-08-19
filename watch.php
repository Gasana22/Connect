<?php
require_once __DIR__ . '/includes/auth.php';

$videoId = (int) ($_GET['id'] ?? 0);
$viewerId = currentUserId();

$pdo = getPDO();
$stmt = $pdo->prepare(
    "SELECT v.*, u.username, u.profile_pic, u.account_type, u.is_verified,
        (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
        (SELECT COUNT(*) FROM views WHERE video_id = v.id) AS view_count,
        (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) AS is_liked,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = v.user_id) AS is_following
     FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?"
);
$stmt->execute([$viewerId, $viewerId, $videoId]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    include __DIR__ . '/includes/layout_header.php';
    echo '<div class="empty-state"><i class="fas fa-video-slash"></i><h3>Video not found</h3></div>';
    include __DIR__ . '/includes/layout_footer.php';
    exit;
}

$pdo->prepare('INSERT INTO views (user_id, video_id) VALUES (?, ?)')->execute([$viewerId, $videoId]);

$pageTitle = $video['username'] . "'s video";
include __DIR__ . '/includes/layout_header.php';
?>
<div class="video-feed">
  <div class="video-card">
    <div class="video-header">
      <a class="user-info-row" href="profile.php?id=<?= (int) $video['user_id'] ?>">
        <img src="<?= e(profilePicUrl($video['profile_pic'])) ?>" class="user-avatar" alt="">
        <span class="username"><?= e($video['username']) ?><?php if ($video['account_type'] === 'business'): ?> <span class="badge badge-business">Business</span><?php endif; ?></span>
      </a>
      <?php if ($viewerId && $viewerId !== (int) $video['user_id']): ?>
        <button class="follow-btn <?= $video['is_following'] ? 'following' : '' ?>" data-user-id="<?= (int) $video['user_id'] ?>" data-following="<?= $video['is_following'] ? '1' : '0' ?>"><?= $video['is_following'] ? 'Following' : 'Follow' ?></button>
      <?php endif; ?>
    </div>

    <div class="video-player"><video src="<?= e($video['video_path']) ?>" controls autoplay></video></div>

    <div class="video-actions">
      <div class="action-buttons">
        <button class="action-btn like-btn <?= $video['is_liked'] ? 'liked' : '' ?>" data-video-id="<?= (int) $video['id'] ?>">
          <i class="fas fa-heart"></i> <span class="like-count"><?= (int) $video['like_count'] ?></span>
        </button>
        <button class="action-btn comment-toggle" data-video-id="<?= (int) $video['id'] ?>"><i class="fas fa-comment"></i> <?= (int) $video['comment_count'] ?></button>
        <span class="action-btn" style="cursor:default;"><i class="fas fa-eye"></i> <?= formatNumber((int) $video['view_count']) ?></span>
      </div>
      <?php if ($video['category']): ?><span class="category-badge"><i class="fas fa-tag"></i> <?= e(BUSINESS_CATEGORIES[$video['category']] ?? $video['category']) ?></span><?php endif; ?>
      <p class="video-caption mt-2"><?= link_hashtags(e($video['caption'])) ?></p>
    </div>

    <div class="comments-section" id="comments-<?= (int) $video['id'] ?>">
      <div class="comments-list"></div>
      <?php if ($viewerId): ?>
        <form class="comment-form" data-video-id="<?= (int) $video['id'] ?>">
          <input type="text" name="text" placeholder="Add a comment..." maxlength="1000">
          <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.getElementById('comments-<?= (int) $video['id'] ?>').classList.add('expanded');
loadComments(<?= (int) $video['id'] ?>, document.getElementById('comments-<?= (int) $video['id'] ?>'));
</script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
