<?php
require_once __DIR__ . '/includes/auth.php';

$viewerId = currentUserId();
$profileId = (int) ($_GET['id'] ?? 0);
if (!$profileId) {
    http_response_code(404);
    die('User not found.');
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    "SELECT u.*,
        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) AS follower_count,
        (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) AS following_count
     FROM users u WHERE u.id = ?"
);
$stmt->execute([$profileId]);
$profile = $stmt->fetch();
if (!$profile) {
    http_response_code(404);
    die('User not found.');
}

$stmt = $pdo->prepare(
    "SELECT v.*,
        (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
        (SELECT COUNT(*) FROM views WHERE video_id = v.id) AS view_count
     FROM videos v WHERE v.user_id = ? ORDER BY v.created_at DESC"
);
$stmt->execute([$profileId]);
$videos = $stmt->fetchAll();

$isFollowing = false;
if ($viewerId && $viewerId !== $profileId) {
    $stmt = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->execute([$viewerId, $profileId]);
    $isFollowing = (bool) $stmt->fetch();
}

$pageTitle = $profile['username'];
include __DIR__ . '/includes/layout_header.php';
?>
<div class="card card-pad" style="max-width: 720px; margin-bottom: 1.5rem;">
  <div class="flex items-center" style="justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div class="flex items-center gap-2">
      <img src="<?= e(profilePicUrl($profile['profile_pic'])) ?>" class="user-avatar" style="width:80px;height:80px;" alt="">
      <div>
        <h2 class="flex items-center gap-2">
          <?= e($profile['username']) ?>
          <?php if ($profile['is_verified']): ?><span class="badge badge-verified"><i class="fas fa-check"></i> Verified</span><?php endif; ?>
          <?php if ($profile['account_type'] === 'business'): ?><span class="badge badge-business"><i class="fas fa-briefcase"></i> Business</span><?php endif; ?>
        </h2>
        <?php if ($profile['account_type'] === 'business' && $profile['business_name']): ?>
          <div class="text-muted"><?= e($profile['business_name']) ?><?= $profile['business_category'] ? ' · ' . e(BUSINESS_CATEGORIES[$profile['business_category']] ?? $profile['business_category']) : '' ?></div>
        <?php endif; ?>
        <?php if ($profile['bio']): ?><p class="mt-1"><?= nl2br(e($profile['bio'])) ?></p><?php endif; ?>
        <?php if ($profile['business_website']): ?><a href="<?= e($profile['business_website']) ?>" target="_blank" rel="noopener"><i class="fas fa-link"></i> <?= e($profile['business_website']) ?></a><?php endif; ?>
      </div>
    </div>

    <div>
      <?php if ($viewerId === $profileId): ?>
        <a href="edit_profile.php" class="btn btn-outline"><i class="fas fa-pen"></i> Edit Profile</a>
      <?php elseif ($viewerId): ?>
        <button class="follow-btn <?= $isFollowing ? 'following' : '' ?>" data-user-id="<?= $profileId ?>" data-following="<?= $isFollowing ? '1' : '0' ?>"><?= $isFollowing ? 'Following' : 'Follow' ?></button>
        <a href="chat.php?user=<?= $profileId ?>" class="btn btn-outline"><i class="fas fa-envelope"></i></a>
      <?php else: ?>
        <a href="login.php" class="btn btn-primary">Follow</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="flex gap-2 mt-3" style="gap: 2rem;">
    <a href="followers.php?id=<?= $profileId ?>" style="color:var(--ink);"><strong><?= formatNumber((int) $profile['follower_count']) ?></strong> <span class="text-muted">Followers</span></a>
    <a href="following.php?id=<?= $profileId ?>" style="color:var(--ink);"><strong><?= formatNumber((int) $profile['following_count']) ?></strong> <span class="text-muted">Following</span></a>
    <span><strong><?= count($videos) ?></strong> <span class="text-muted">Videos</span></span>
  </div>
</div>

<?php if (!$videos): ?>
  <div class="empty-state">
    <i class="fas fa-video-slash"></i>
    <h3>No videos yet</h3>
    <p><?= $viewerId === $profileId ? "You haven't posted anything yet." : 'This user has not posted any videos yet.' ?></p>
    <?php if ($viewerId === $profileId): ?><a class="btn btn-primary mt-2" href="upload.php">Upload your first video</a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="video-grid">
    <?php foreach ($videos as $video): ?>
      <div class="video-card" style="position:relative;">
        <a href="watch.php?id=<?= (int) $video['id'] ?>" style="text-decoration:none;">
          <div class="video-player"><video src="<?= e($video['video_path']) ?>" muted preload="metadata"></video></div>
          <div class="video-actions">
            <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-play"></i> <?= formatNumber((int) $video['view_count']) ?> · <i class="fas fa-heart"></i> <?= formatNumber((int) $video['like_count']) ?></span>
          </div>
        </a>
        <?php if ($viewerId === $profileId): ?>
          <button class="btn btn-danger delete-video-btn" style="position:absolute; top:8px; right:8px; padding:4px 8px;" data-video-id="<?= (int) $video['id'] ?>"><i class="fas fa-trash"></i></button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($viewerId === $profileId): ?><a href="upload.php" class="fab"><i class="fas fa-plus"></i></a><?php endif; ?>

<script>
document.querySelectorAll('.delete-video-btn').forEach((btn) => {
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    if (!confirm('Delete this video? This cannot be undone.')) return;
    const result = await postJSON('delete_video.php', { video_id: btn.dataset.videoId });
    if (result.success) {
      btn.closest('.video-card').remove();
      toast('Video deleted');
    } else {
      toast(result.message || 'Could not delete video.', 'error');
    }
  });
});
</script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
