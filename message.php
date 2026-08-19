<?php
require_once __DIR__ . '/includes/auth.php';
requireAuth();

header_remove('X-Powered-By');
$userId = currentUserId();
$pdo = getPDO();

// AJAX actions (mark read / delete / clear) — JSON in, JSON out.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    validateCsrf($body['csrf_token'] ?? null);

    $action = $body['action'] ?? '';
    if ($action === 'mark_read') {
        $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?')->execute([(int) $body['id'], $userId]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM messages WHERE id = ? AND recipient_id = ?')->execute([(int) $body['id'], $userId]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'clear_all') {
        $pdo->prepare('DELETE FROM messages WHERE recipient_id = ?')->execute([$userId]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    exit;
}

$stmt = $pdo->prepare(
    'SELECT m.*, u.profile_pic AS sender_pic FROM messages m
     LEFT JOIN users u ON m.sender_id = u.id
     WHERE m.recipient_id = ? ORDER BY m.created_at DESC'
);
$stmt->execute([$userId]);
$messages = $stmt->fetchAll();

$pageTitle = 'Messages';
$activeNav = 'messages';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="flex items-center" style="justify-content: space-between; margin-bottom: 1.5rem;">
  <h1>Messages</h1>
  <?php if ($messages): ?><button class="btn btn-secondary" id="clearAllBtn"><i class="fas fa-trash"></i> Clear all</button><?php endif; ?>
</div>

<?php if (!$messages): ?>
  <div class="empty-state"><i class="fas fa-envelope-open"></i><h3>No messages</h3><p>Direct messages you receive will show up here.</p></div>
<?php else: ?>
  <div class="card" style="max-width: 640px;">
    <?php foreach ($messages as $m): ?>
      <div class="flex items-center gap-2" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-soft); <?= $m['is_read'] ? '' : 'background: var(--brand-light);' ?>" data-message-id="<?= (int) $m['id'] ?>">
        <img src="<?= e(profilePicUrl($m['sender_pic'])) ?>" class="user-avatar" alt="">
        <div style="flex:1; min-width:0;">
          <a href="chat.php?user=<?= (int) $m['sender_id'] ?>" style="color:var(--ink); font-weight:700;"><?= e($m['sender_name']) ?></a>
          <?php if ($m['subject']): ?><div style="font-weight:600; font-size:0.85rem;"><?= e($m['subject']) ?></div><?php endif; ?>
          <div class="text-muted" style="font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($m['content']) ?></div>
          <div class="text-muted" style="font-size:0.75rem;"><?= e(date('M j, Y g:ia', strtotime($m['created_at']))) ?></div>
        </div>
        <button class="btn btn-secondary delete-msg-btn" style="padding:4px 10px;" data-id="<?= (int) $m['id'] ?>"><i class="fas fa-xmark"></i></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
async function postAction(action, id) {
  const res = await fetch('message.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ csrf_token: window.CSRF_TOKEN, action, id }),
  });
  return res.json();
}
document.getElementById('clearAllBtn')?.addEventListener('click', async () => {
  if (!confirm('Delete all messages?')) return;
  const r = await postAction('clear_all');
  if (r.success) location.reload();
});
document.querySelectorAll('.delete-msg-btn').forEach((btn) => {
  btn.addEventListener('click', async () => {
    const r = await postAction('delete', btn.dataset.id);
    if (r.success) btn.closest('[data-message-id]').remove();
  });
});
</script>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
