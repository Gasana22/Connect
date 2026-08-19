<?php
require_once __DIR__ . '/includes/auth.php';

$search = trim($_GET['q'] ?? '');
$categoryId = !empty($_GET['category']) ? (int) $_GET['category'] : null;

$pdo = getPDO();
$sql = "SELECT ls.*, u.username, u.profile_pic, c.name AS category_name
        FROM live_streams ls JOIN users u ON ls.user_id = u.id
        LEFT JOIN categories c ON ls.category_id = c.id
        WHERE ls.is_live = 1";
$params = [];
if ($search !== '') {
    $sql .= ' AND (ls.title LIKE :search OR u.username LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($categoryId) {
    $sql .= ' AND ls.category_id = :category';
    $params[':category'] = $categoryId;
}
$sql .= ' ORDER BY ls.viewer_count DESC, ls.started_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$streams = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Browse Live';
$activeNav = 'live';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="flex items-center" style="justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
  <h1>Live now</h1>
  <a href="live.php" class="btn btn-accent"><i class="fas fa-video"></i> Go live</a>
</div>

<form class="flex gap-2" style="margin-bottom: 1.5rem;">
  <input class="form-control" type="text" name="q" placeholder="Search streams or streamers..." value="<?= e($search) ?>">
  <select class="form-control" name="category" style="max-width: 220px;" onchange="this.form.submit()">
    <option value="">All categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
</form>

<?php if (!$streams): ?>
  <div class="empty-state"><i class="fas fa-signal"></i><h3>No live streams right now</h3><p>Be the first to go live.</p></div>
<?php else: ?>
  <div class="video-grid">
    <?php foreach ($streams as $s): ?>
      <a class="video-card" href="live.php?watch=<?= e($s['stream_key']) ?>" style="text-decoration:none;">
        <div class="video-header">
          <span class="flex items-center gap-2" style="color:var(--ink);">
            <img src="<?= e(profilePicUrl($s['profile_pic'])) ?>" class="user-avatar" style="width:28px;height:28px;" alt="">
            <?= e($s['username']) ?>
          </span>
          <span class="badge badge-live pulse">LIVE</span>
        </div>
        <div class="video-actions" style="padding-top:0;">
          <p style="font-weight:600;"><?= e($s['title']) ?></p>
          <?php if ($s['category_name']): ?><span class="badge badge-category mt-1"><?= e($s['category_name']) ?></span><?php endif; ?>
          <p class="text-muted mt-1" style="font-size:0.8rem;"><i class="fas fa-eye"></i> <?= (int) $s['viewer_count'] ?> watching</p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
