<?php
require_once __DIR__ . '/includes/auth.php';

$userId = currentUserId();
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'trending';

$pdo = getPDO();

$sql = "SELECT v.*, u.username, u.profile_pic, u.account_type,
            (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
            (SELECT COUNT(*) FROM views WHERE video_id = v.id) AS view_count
        FROM videos v JOIN users u ON v.user_id = u.id";
$params = [];
if ($search !== '') {
    $sql .= " WHERE v.caption LIKE :search OR u.username LIKE :search OR v.tags LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
$sql .= match ($sort) {
    'liked' => ' ORDER BY like_count DESC',
    'commented' => ' ORDER BY comment_count DESC',
    'viewed' => ' ORDER BY view_count DESC',
    default => ' ORDER BY v.created_at DESC',
};
$sql .= ' LIMIT 60';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$videos = $stmt->fetchAll();

// Popular hashtags for discovery chips.
$popularTags = $pdo->query(
    'SELECT tag, COUNT(*) AS uses FROM video_hashtags GROUP BY tag ORDER BY uses DESC LIMIT 12'
)->fetchAll();

$pageTitle = 'Explore';
$activeNav = 'explore';
include __DIR__ . '/includes/layout_header.php';
?>
<h1 style="margin-bottom: 1rem;">Explore</h1>

<form class="flex gap-2 mt-1" style="margin-bottom: 1rem;">
  <input class="form-control" type="text" name="q" placeholder="Search videos, creators, businesses..." value="<?= e($search) ?>">
  <select class="form-control" name="sort" style="max-width: 200px;" onchange="this.form.submit()">
    <option value="trending" <?= $sort === 'trending' ? 'selected' : '' ?>>Latest</option>
    <option value="liked" <?= $sort === 'liked' ? 'selected' : '' ?>>Most Liked</option>
    <option value="commented" <?= $sort === 'commented' ? 'selected' : '' ?>>Most Commented</option>
    <option value="viewed" <?= $sort === 'viewed' ? 'selected' : '' ?>>Most Viewed</option>
  </select>
  <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
</form>

<?php if ($popularTags): ?>
  <div class="flex gap-2" style="flex-wrap: wrap; margin-bottom: 1.5rem;">
    <?php foreach ($popularTags as $t): ?>
      <a class="badge badge-category" href="hashtag.php?tag=<?= e($t['tag']) ?>">#<?= e($t['tag']) ?></a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$videos): ?>
  <div class="empty-state"><i class="fas fa-magnifying-glass"></i><h3>No results</h3><p>Try a different search.</p></div>
<?php else: ?>
  <div class="video-grid">
    <?php foreach ($videos as $video): ?>
      <a class="video-card" href="watch.php?id=<?= (int) $video['id'] ?>" style="text-decoration:none;">
        <div class="video-player"><video src="<?= e($video['video_path']) ?>" muted preload="metadata"></video></div>
        <div class="video-header">
          <span class="username" style="color:var(--ink);"><?= e($video['username']) ?><?php if ($video['account_type'] === 'business'): ?> <span class="badge badge-business">Business</span><?php endif; ?></span>
        </div>
        <div class="video-actions" style="padding-top:0;">
          <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-heart"></i> <?= formatNumber((int) $video['like_count']) ?> · <i class="fas fa-eye"></i> <?= formatNumber((int) $video['view_count']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
