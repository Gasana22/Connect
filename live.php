<?php
require_once __DIR__ . '/includes/auth.php';

$userId = currentUserId();
$pdo = getPDO();
$watchKey = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['watch'] ?? '');
$error = '';

// My own currently-live stream, if any.
$myStream = null;
if ($userId) {
    $stmt = $pdo->prepare('SELECT * FROM live_streams WHERE user_id = ? AND is_live = 1');
    $stmt->execute([$userId]);
    $myStream = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'start_stream' && $userId && !$myStream) {
        $title = sanitize_input($_POST['title'] ?? '');
        $categoryId = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        if ($title === '') {
            $error = 'Please give your stream a title.';
        } else {
            $key = bin2hex(random_bytes(12));
            $stmt = $pdo->prepare('INSERT INTO live_streams (user_id, stream_key, title, category_id, is_live, started_at) VALUES (?, ?, ?, ?, 1, NOW())');
            $stmt->execute([$userId, $key, $title, $categoryId]);
            header('Location: live.php?watch=' . $key . '&broadcaster=1');
            exit;
        }
    } elseif ($action === 'stop_stream' && $userId && $myStream) {
        $pdo->prepare('UPDATE live_streams SET is_live = 0, ended_at = NOW() WHERE id = ?')->execute([$myStream['id']]);
        header('Location: live.php');
        exit;
    } elseif ($action === 'chat_message' && $userId && $watchKey) {
        $message = sanitize_input($_POST['message'] ?? '');
        if ($message !== '') {
            $pdo->prepare('INSERT INTO chat_messages (stream_key, user_id, message) VALUES (?, ?, ?)')->execute([$watchKey, $userId, $message]);
        }
        header('Location: live.php?watch=' . $watchKey);
        exit;
    }
}

$stream = null;
if ($watchKey) {
    $stmt = $pdo->prepare('SELECT ls.*, u.username, u.profile_pic FROM live_streams ls JOIN users u ON ls.user_id = u.id WHERE ls.stream_key = ?');
    $stmt->execute([$watchKey]);
    $stream = $stmt->fetch();
    if ($stream && $stream['is_live'] && (int) $stream['user_id'] !== $userId) {
        $pdo->prepare('UPDATE live_streams SET viewer_count = viewer_count + 1 WHERE id = ?')->execute([$stream['id']]);
    }
}

$liveStreams = $pdo->query(
    'SELECT ls.*, u.username, u.profile_pic FROM live_streams ls JOIN users u ON ls.user_id = u.id WHERE ls.is_live = 1 ORDER BY ls.started_at DESC LIMIT 20'
)->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Live';
$activeNav = 'live';
include __DIR__ . '/includes/layout_header.php';
?>

<?php if ($stream): ?>
  <div class="card" style="max-width: 900px;">
    <div class="video-header">
      <a class="user-info-row" href="profile.php?id=<?= (int) $stream['user_id'] ?>">
        <img src="<?= e(profilePicUrl($stream['profile_pic'])) ?>" class="user-avatar" alt="">
        <span class="username"><?= e($stream['username']) ?></span>
      </a>
      <span class="badge badge-live pulse"><i class="fas fa-circle"></i> <?= $stream['is_live'] ? 'LIVE' : 'ENDED' ?></span>
    </div>
    <div class="video-player" style="position:relative;">
      <video id="liveVideo" controls autoplay muted style="width:100%; max-height:520px;"></video>
    </div>
    <div class="video-actions">
      <h2 style="font-size:1.1rem;"><?= e($stream['title']) ?></h2>
      <p class="text-muted"><i class="fas fa-eye"></i> <span id="viewerCount"><?= (int) $stream['viewer_count'] ?></span> watching</p>
      <?php if ((int) $stream['user_id'] === $userId): ?>
        <form method="POST" class="mt-2"><?= csrfField() ?><input type="hidden" name="action" value="stop_stream"><button class="btn btn-danger" type="submit">End stream</button></form>
      <?php endif; ?>
    </div>

    <div style="border-top:1px solid var(--border-soft); padding: 1rem;">
      <div id="chatMessages" style="max-height: 260px; overflow-y:auto; display:flex; flex-direction:column; gap:8px; margin-bottom:0.75rem;"></div>
      <?php if ($userId): ?>
        <form method="POST" class="flex gap-2">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="chat_message">
          <input class="form-control" type="text" name="message" placeholder="Say something..." required>
          <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ((int) $stream['user_id'] === $userId && ($_GET['broadcaster'] ?? '') === '1'): ?>
    <script src="assets/js/live-broadcast.js"></script>
    <script>initBroadcast('<?= e($stream['stream_key']) ?>', document.getElementById('liveVideo'));</script>
  <?php else: ?>
    <script src="assets/js/live-viewer.js"></script>
    <script>initViewer('<?= e($stream['stream_key']) ?>', document.getElementById('liveVideo'));</script>
  <?php endif; ?>

  <script>
  (function pollChat() {
    let afterId = 0;
    setInterval(() => {
      fetch('get_chat.php?stream_key=<?= e($stream['stream_key']) ?>&after_id=' + afterId)
        .then(r => r.text()).then(html => {
          if (!html) return;
          const box = document.getElementById('chatMessages');
          box.insertAdjacentHTML('beforeend', html);
          box.scrollTop = box.scrollHeight;
          const ids = [...box.querySelectorAll('[data-id]')].map(el => parseInt(el.dataset.id, 10));
          if (ids.length) afterId = Math.max(...ids);
        }).catch(() => {});
    }, 3000);
  })();
  </script>

<?php else: ?>

  <?php if ($userId && !$myStream): ?>
    <div class="card card-pad mt-2" style="max-width: 480px; margin-bottom: 2rem;">
      <h2 style="margin-bottom: 1rem;">Go live</h2>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="start_stream">
        <div class="form-group">
          <label class="form-label" for="title">Title</label>
          <input class="form-control" id="title" name="title" type="text" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="category_id">Category</label>
          <select class="form-control" id="category_id" name="category_id">
            <option value="">General</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-accent btn-block"><i class="fas fa-video"></i> Start streaming</button>
      </form>
    </div>
  <?php elseif ($myStream): ?>
    <div class="alert alert-success mt-2">You're already live. <a href="live.php?watch=<?= e($myStream['stream_key']) ?>&broadcaster=1">Go to your stream</a>.</div>
  <?php endif; ?>

  <h2 class="mt-2" style="margin-bottom: 1rem;">Live now</h2>
  <?php if (!$liveStreams): ?>
    <div class="empty-state"><i class="fas fa-signal"></i><h3>No one's live right now</h3><p>Check back soon, or start your own stream.</p></div>
  <?php else: ?>
    <div class="video-grid">
      <?php foreach ($liveStreams as $ls): ?>
        <a class="video-card" href="live.php?watch=<?= e($ls['stream_key']) ?>" style="text-decoration:none;">
          <div class="video-header">
            <span class="username" style="color:var(--ink);"><?= e($ls['username']) ?></span>
            <span class="badge badge-live pulse">LIVE</span>
          </div>
          <div class="video-actions" style="padding-top:0;">
            <p><?= e($ls['title']) ?></p>
            <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-eye"></i> <?= (int) $ls['viewer_count'] ?> watching</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
