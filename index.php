<?php
require_once __DIR__ . '/includes/auth.php';

$userId = currentUserId();
$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$tab = ($_GET['tab'] ?? 'for-you') === 'following' ? 'following' : 'for-you';

$pdo = getPDO();

// Active ads eligible for injection into the feed.
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare(
    "SELECT * FROM ads WHERE is_active = 1 AND start_date <= ? AND end_date >= ?
     AND (max_impressions = 0 OR max_impressions > (SELECT COUNT(*) FROM ad_impressions WHERE ad_id = ads.id))
     ORDER BY RAND() LIMIT 3"
);
$stmt->execute([$now, $now]);
$ads = $stmt->fetchAll();

if ($tab === 'following' && $userId) {
    $stmt = $pdo->prepare(
        "SELECT v.*, u.username, u.profile_pic, u.account_type, u.is_verified,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
            1 AS is_following,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = :uid1) AS is_liked
         FROM videos v JOIN users u ON v.user_id = u.id JOIN follows f ON v.user_id = f.following_id
         WHERE f.follower_id = :uid2 ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT v.*, u.username, u.profile_pic, u.account_type, u.is_verified,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
            (SELECT COUNT(*) FROM follows WHERE follower_id = :uid1 AND following_id = v.user_id) AS is_following,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = :uid2) AS is_liked
         FROM videos v JOIN users u ON v.user_id = u.id
         ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$videos = $stmt->fetchAll();

$pageTitle = 'Feed';
$activeNav = 'home';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="feed-tabs">
  <a href="?tab=for-you" class="tab-btn <?= $tab === 'for-you' ? 'active' : '' ?>"><i class="fas fa-compass"></i> For You</a>
  <?php if ($userId): ?><a href="?tab=following" class="tab-btn <?= $tab === 'following' ? 'active' : '' ?>"><i class="fas fa-user-friends"></i> Following</a><?php endif; ?>
</div>

<?php if (!$videos): ?>
  <div class="empty-state">
    <i class="fas fa-video"></i>
    <h3><?= $tab === 'following' ? 'Nothing from people you follow yet' : 'No videos yet' ?></h3>
    <p><?= $tab === 'following' ? 'Follow some creators and businesses to see their posts here.' : 'Be the first to post something.' ?></p>
    <a class="btn btn-primary" href="<?= $tab === 'following' ? 'explore.php' : ($userId ? 'upload.php' : 'signup.php') ?>"><?= $tab === 'following' ? 'Explore' : ($userId ? 'Upload' : 'Join Connect') ?></a>
  </div>
<?php else: ?>
  <div class="video-feed">
    <?php
    $items = $videos;
    if ($ads) {
        // Weave one ad in every ~4 videos.
        $withAds = [];
        $adIdx = 0;
        foreach ($items as $i => $v) {
            $withAds[] = ['type' => 'video', 'data' => $v];
            if (($i + 1) % 4 === 0 && $adIdx < count($ads)) {
                $withAds[] = ['type' => 'ad', 'data' => $ads[$adIdx++]];
            }
        }
        $items = $withAds;
    } else {
        $items = array_map(fn($v) => ['type' => 'video', 'data' => $v], $items);
    }
    foreach ($items as $item):
        if ($item['type'] === 'ad'): $ad = $item['data'];
            $pdo->prepare('INSERT INTO ad_impressions (ad_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)')
                ->execute([$ad['id'], $userId, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    ?>
      <div class="video-card ad-card">
        <div class="video-header">
          <span class="username"><?= e($ad['sponsor']) ?></span>
          <span class="badge badge-sponsored">Sponsored</span>
        </div>
        <a href="<?= e($ad['target_url']) ?>" target="_blank" rel="noopener sponsored">
          <img src="<?= e($ad['image_url']) ?>" class="ad-image" alt="<?= e($ad['title']) ?>" style="max-height:400px; width:100%; object-fit:cover;">
        </a>
        <div class="video-actions">
          <p class="video-caption" style="font-weight:700;"><?= e($ad['title']) ?></p>
          <p class="video-caption text-muted"><?= e($ad['description']) ?></p>
          <a href="<?= e($ad['target_url']) ?>" target="_blank" rel="noopener sponsored" class="btn btn-accent btn-block mt-2">Learn More</a>
        </div>
      </div>
    <?php else: $video = $item['data']; ?>
      <div class="video-card">
        <div class="video-header">
          <a class="user-info-row" href="profile.php?id=<?= (int) $video['user_id'] ?>">
            <img src="<?= e(profilePicUrl($video['profile_pic'])) ?>" class="user-avatar" alt="">
            <span class="username"><?= e($video['username']) ?><?php if ($video['account_type'] === 'business'): ?> <span class="badge badge-business">Business</span><?php endif; ?></span>
          </a>
          <?php if ($userId && $userId !== (int) $video['user_id']): ?>
            <button class="follow-btn <?= $video['is_following'] ? 'following' : '' ?>" data-user-id="<?= (int) $video['user_id'] ?>" data-following="<?= $video['is_following'] ? '1' : '0' ?>"><?= $video['is_following'] ? 'Following' : 'Follow' ?></button>
          <?php endif; ?>
        </div>

        <a href="watch.php?id=<?= (int) $video['id'] ?>">
          <div class="video-player"><video src="<?= e($video['video_path']) ?>" muted preload="metadata"></video></div>
        </a>

        <div class="video-actions">
          <div class="action-buttons">
            <button class="action-btn like-btn <?= $video['is_liked'] ? 'liked' : '' ?>" data-video-id="<?= (int) $video['id'] ?>">
              <i class="fas fa-heart"></i> <span class="like-count"><?= (int) $video['like_count'] ?></span>
            </button>
            <button class="action-btn comment-toggle" data-video-id="<?= (int) $video['id'] ?>"><i class="fas fa-comment"></i> <?= (int) $video['comment_count'] ?></button>
          </div>
          <?php if ($video['category']): ?><span class="category-badge"><i class="fas fa-tag"></i> <?= e(BUSINESS_CATEGORIES[$video['category']] ?? $video['category']) ?></span><?php endif; ?>
          <p class="video-caption mt-2"><?= link_hashtags(e($video['caption'])) ?></p>
        </div>

        <div class="comments-section" id="comments-<?= (int) $video['id'] ?>">
          <div class="comments-list"></div>
          <?php if ($userId): ?>
            <form class="comment-form" data-video-id="<?= (int) $video['id'] ?>">
              <input type="text" name="text" placeholder="Add a comment..." maxlength="1000">
              <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; endforeach; ?>
  </div>

  <div class="pagination">
    <?php if ($page > 1): ?><a class="page-link" href="?tab=<?= $tab ?>&page=<?= $page - 1 ?>"><i class="fas fa-arrow-left"></i> Prev</a><?php endif; ?>
    <span class="page-link active"><?= $page ?></span>
    <?php if (count($videos) === $perPage): ?><a class="page-link" href="?tab=<?= $tab ?>&page=<?= $page + 1 ?>">Next <i class="fas fa-arrow-right"></i></a><?php endif; ?>
  </div>
<?php endif; ?>
<?php if ($userId): ?><a href="upload.php" class="fab"><i class="fas fa-plus"></i></a><?php endif; ?>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
