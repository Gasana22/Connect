<?php
require_once __DIR__ . '/includes/auth.php';

$profileId = (int) ($_GET['id'] ?? 0);
$viewerId = currentUserId();

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$stmt->execute([$profileId]);
$profile = $stmt->fetch();
if (!$profile) {
    http_response_code(404);
    die('User not found.');
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.username, u.profile_pic, u.account_type, u.is_verified,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = u.id) AS is_following
     FROM follows f JOIN users u ON f.follower_id = u.id
     WHERE f.following_id = ? ORDER BY f.created_at DESC"
);
$stmt->execute([$viewerId, $profileId]);
$rows = $stmt->fetchAll();

$pageTitle = e($profile['username']) . "'s followers";
include __DIR__ . '/includes/layout_header.php';
?>
<h1 style="margin-bottom: 1.5rem;"><?= e($profile['username']) ?>'s followers</h1>
<?php if (!$rows): ?>
  <div class="empty-state"><i class="fas fa-user-slash"></i><h3>No followers yet</h3></div>
<?php else: ?>
  <div class="card" style="max-width: 480px;">
    <?php foreach ($rows as $row): ?>
      <div class="flex items-center gap-2" style="padding: 0.9rem 1.25rem; border-bottom: 1px solid var(--border-soft);">
        <a class="user-info-row" href="profile.php?id=<?= (int) $row['id'] ?>" style="flex:1;">
          <img src="<?= e(profilePicUrl($row['profile_pic'])) ?>" class="user-avatar" alt="">
          <span class="username"><?= e($row['username']) ?><?php if ($row['account_type'] === 'business'): ?> <span class="badge badge-business">Business</span><?php endif; ?></span>
        </a>
        <?php if ($viewerId && $viewerId !== (int) $row['id']): ?>
          <button class="follow-btn <?= $row['is_following'] ? 'following' : '' ?>" data-user-id="<?= (int) $row['id'] ?>" data-following="<?= $row['is_following'] ? '1' : '0' ?>"><?= $row['is_following'] ? 'Following' : 'Follow' ?></button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
