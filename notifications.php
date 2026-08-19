<?php
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$userId = currentUserId();
$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$stmt->execute([$userId]);
$total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT n.*, u.username AS sender_username, u.profile_pic AS sender_pic, v.video_path
     FROM notifications n
     JOIN users u ON n.sender_id = u.id
     LEFT JOIN videos v ON n.video_id = v.id
     WHERE n.user_id = :uid
     ORDER BY n.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Mark everything on this page as read.
$pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);

$icons = ['like' => 'fa-heart', 'follow' => 'fa-user-plus', 'comment' => 'fa-comment', 'message' => 'fa-envelope'];
$verbs = ['like' => 'liked your video', 'follow' => 'started following you', 'comment' => 'commented on your video', 'message' => 'sent you a message'];

$pageTitle = 'Notifications';
$activeNav = 'notifications';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="flex items-center" style="justify-content: space-between; margin-bottom: 1.5rem;">
  <h1>Notifications</h1>
  <?php if ($notifications): ?>
    <button class="btn btn-secondary" id="clearAllBtn"><i class="fas fa-trash"></i> Clear all</button>
  <?php endif; ?>
</div>

<?php if (!$notifications): ?>
  <div class="empty-state">
    <i class="fas fa-bell-slash"></i>
    <h3>No notifications yet</h3>
    <p>Likes, comments, and new followers will show up here.</p>
  </div>
<?php else: ?>
  <div class="card" style="max-width: 640px;">
    <?php foreach ($notifications as $n): ?>
      <div class="flex items-center gap-2" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-soft);" data-notification-id="<?= (int) $n['id'] ?>">
        <img src="<?= e(profilePicUrl($n['sender_pic'])) ?>" class="user-avatar" alt="">
        <div style="flex:1;">
          <a href="profile.php?id=<?= (int) $n['sender_id'] ?>" style="color: var(--ink); font-weight: 700;"><?= e($n['sender_username']) ?></a>
          <span class="text-muted"> <?= e($verbs[$n['type']] ?? 'interacted with your content') ?></span>
          <div class="text-muted" style="font-size: 0.78rem;"><?= e(date('M j, Y g:ia', strtotime($n['created_at']))) ?></div>
        </div>
        <?php if ($n['video_path']): ?>
          <a href="watch.php?id=<?= (int) $n['video_id'] ?>"><i class="fas <?= e($icons[$n['type']] ?? 'fa-bell') ?>" style="color: var(--brand);"></i></a>
        <?php else: ?>
          <i class="fas <?= e($icons[$n['type']] ?? 'fa-bell') ?>" style="color: var(--brand);"></i>
        <?php endif; ?>
        <button class="btn btn-secondary delete-notif-btn" style="padding: 4px 10px;" data-id="<?= (int) $n['id'] ?>"><i class="fas fa-xmark"></i></button>
      </div>
    <?php endforeach; ?>
  </div>

  <?php $totalPages = (int) ceil($total / $perPage); if ($totalPages > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a class="page-link <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
document.getElementById('clearAllBtn')?.addEventListener('click', async () => {
  if (!confirm('Clear all notifications?')) return;
  const result = await postJSON('clear_notifications.php', {});
  if (result.success) location.reload();
});

document.querySelectorAll('.delete-notif-btn').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const result = await postJSON('delete_notification.php', { notification_id: btn.dataset.id });
    if (result.success) {
      btn.closest('[data-notification-id]').remove();
    }
  });
});
</script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
