<?php
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$userId = currentUserId();
$otherId = (int) ($_GET['user'] ?? 0);
$pdo = getPDO();

$stmt = $pdo->prepare('SELECT id, username, profile_pic FROM users WHERE id = ?');
$stmt->execute([$otherId]);
$other = $stmt->fetch();
if (!$other) {
    http_response_code(404);
    die('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $content = sanitize_input($_POST['content'] ?? '');
    if ($content !== '') {
        $me = currentUser();
        $stmt = $pdo->prepare('INSERT INTO messages (sender_id, sender_name, recipient_id, content) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $me['username'], $otherId, $content]);
        createNotification($pdo, $otherId, $userId, 'message');
    }
    header('Location: chat.php?user=' . $otherId);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at ASC'
);
$stmt->execute([$userId, $otherId, $otherId, $userId]);
$thread = $stmt->fetchAll();

$pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ?')->execute([$otherId, $userId]);

$pageTitle = 'Chat with ' . $other['username'];
$activeNav = 'messages';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="card" style="max-width: 640px; display:flex; flex-direction:column; height: calc(100vh - 4rem);">
  <div class="flex items-center gap-2" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-soft);">
    <img src="<?= e(profilePicUrl($other['profile_pic'])) ?>" class="user-avatar" alt="">
    <a href="profile.php?id=<?= (int) $other['id'] ?>" style="color:var(--ink); font-weight:700;"><?= e($other['username']) ?></a>
  </div>

  <div style="flex:1; overflow-y:auto; padding: 1rem 1.25rem; display:flex; flex-direction:column; gap:0.6rem;">
    <?php if (!$thread): ?>
      <p class="text-muted">No messages yet. Say hello.</p>
    <?php endif; ?>
    <?php foreach ($thread as $m): $mine = (int) $m['sender_id'] === $userId; ?>
      <div style="max-width: 75%; align-self: <?= $mine ? 'flex-end' : 'flex-start' ?>; background: <?= $mine ? 'var(--brand)' : 'var(--border-soft)' ?>; color: <?= $mine ? '#fff' : 'var(--ink)' ?>; padding: 0.6rem 0.9rem; border-radius: var(--radius);">
        <?= nl2br(e($m['content'])) ?>
        <div style="font-size:0.68rem; opacity:0.75; margin-top:2px;"><?= e(date('g:ia', strtotime($m['created_at']))) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <form method="POST" class="flex gap-2" style="padding: 1rem 1.25rem; border-top: 1px solid var(--border-soft);">
    <?= csrfField() ?>
    <input class="form-control" type="text" name="content" placeholder="Message <?= e($other['username']) ?>..." required autofocus>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
  </form>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
