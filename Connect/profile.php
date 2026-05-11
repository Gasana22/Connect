<?php
require 'db.php';
session_start();

$viewer_id = $_SESSION['user_id'] ?? null;
$user_id = (int)($_GET['id'] ?? 0);

if (!$user_id) die("User not found");

// Get user data and stats
$stmt = $pdo->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers,
    (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following
    FROM users u WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) die("User not found");

// Get videos with stats
$stmt = $pdo->prepare("SELECT v.*, 
    (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as likes,
    (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comments,
    (SELECT COUNT(*) FROM views WHERE video_id = v.id) as views
    FROM videos v WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$videos = $stmt->fetchAll();

// Get liked videos with stats (using video's created_at since likes table doesn't have timestamp)
$stmt = $pdo->prepare("SELECT v.*, 
    (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as likes,
    (SELECT COUNT(*) FROM comments WHERE video_id = v.id) as comments,
    (SELECT COUNT(*) FROM views WHERE video_id = v.id) as views
    FROM videos v 
    JOIN likes l ON v.id = l.video_id 
    WHERE l.user_id = ? 
    ORDER BY v.created_at DESC");
$stmt->execute([$user_id]);
$liked_videos = $stmt->fetchAll();

// Check if viewer is following
$is_following = $viewer_id && $viewer_id != $user_id ? 
    $pdo->query("SELECT 1 FROM follows WHERE follower_id = $viewer_id AND following_id = $user_id")->fetch() : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($user['username']) ?> | Profile</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #1e90ff;
      --secondary: #25f4ee; 
      --dark: #121212;
      --light: #ffffff;
      --gray: #a8a8a8;
      --light-gray: #f1f1f2;
      --border: #e6e6e6;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    
    body {
      background-color: var(--light);
      color: var(--dark);
      line-height: 1.4;
    }
    
    .container {
      max-width: 100%;
      margin: 0 auto;
      padding: 0;
    }
    
    /* Header Section */
    .profile-header {
      position: relative;
      padding: 20px 16px 0;
      background: var(--light);
      border-bottom: 1px solid var(--border);
    }
    
    .profile-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--light);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .profile-stats {
      display: flex;
      justify-content: space-around;
      text-align: center;
      margin: 20px 0;
    }
    
    .profile-stat {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    .profile-stat-count {
      font-size: 18px;
      font-weight: 700;
      color: var(--primary); /* Changed to blue */
    }
    
    .profile-stat-label {
      font-size: 14px;
      color: var(--gray);
    }
    
    .profile-info {
      padding: 0 16px 16px;
    }
    
    .profile-name {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 5px;
      color: var(--primary); /* Changed to blue */
    }
    
    .profile-username {
      font-size: 16px;
      color: var(--gray);
      margin-bottom: 10px;
    }
    
    .profile-bio {
      font-size: 14px;
      margin-bottom: 12px;
      line-height: 1.5;
      color: var(--primary); /* Changed to blue */
    }
    
    .profile-link {
      color: var(--primary);
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
      margin-bottom: 16px;
    }
    
    .profile-actions {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
    }
    
    .btn {
      padding: 8px 16px;
      border-radius: 4px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      border: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      flex: 1;
    }
    
    .btn-primary {
      background: var(--primary);
      color: var(--light);
    }
    
    .btn-outline {
      background: transparent;
      color: var(--dark);
      border: 1px solid var(--border);
    }
    
    .btn-secondary {
      background: var(--light-gray);
      color: var(--dark);
    }
    
    .btn-danger {
      background: var(--light-gray);
      color: var(--dark);
    }
    
    /* Tabs */
    .profile-tabs {
      display: flex;
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      background: var(--light);
      z-index: 10;
    }
    
    .profile-tab {
      flex: 1;
      text-align: center;
      padding: 16px 0;
      font-size: 14px;
      font-weight: 600;
      color: var(--gray);
      position: relative;
    }
    
    .profile-tab.active {
      color: var(--primary);
    }
    
    .profile-tab.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: var(--primary);
    }
    
    .profile-tab i {
      transition: color 0.2s ease;
    }
    .profile-tab.active i {
      color: var(--primary);
    }

    /* Video Grid */
    .video-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2px;
    }
    
    .video-item {
      position: relative;
      aspect-ratio: 9/16;
      background: var(--dark);
    }
    
    .video-item video {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .video-stats {
      position: absolute;
      bottom: 8px;
      left: 8px;
      color: var(--light);
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .video-stat {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .video-stat i {
      color: var(--primary);
    }
    
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
      text-align: center;
    }
    
    .empty-icon {
      font-size: 48px;
      color: var(--primary);
      margin-bottom: 16px;
    }
    
    .empty-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary); /* Changed to blue */
      margin-bottom: 8px;
    }
    
    .empty-text {
      font-size: 14px;
      color: var(--gray);
    }
    
    /* Floating Action Button */
    .fab {
      position: fixed;
      bottom: 24px;
      right: 16px;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--primary);
      color: var(--light);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      z-index: 100;
    }
    
    /* Tab Content */
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }

    .delete-video-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0,0,0,0.5);
  border: none;
  color: white;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 2;
}

.delete-video-btn:hover {
  background: rgba(255,0,0,0.7);
}

    
    /* Responsive */
    @media (min-width: 768px) {
      .container {
        max-width: 600px;
      }
      
      .profile-header {
        padding: 24px 0 0;
      }
      
      .profile-info {
        padding: 0 24px 24px;
      }
      
      .profile-avatar {
        width: 100px;
        height: 100px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="profile-header">
      <div class="profile-top">
        <div style="flex: 1;">
          <h1 class="profile-name"><?= htmlspecialchars($user['username']) ?></h1>
          <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>
        </div>
        <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'default.jpg') ?>" class="profile-avatar" onerror="this.src='default.jpg'">
      </div>
      
      <div class="profile-stats">
        <div class="profile-stat" onclick="location.href='followers.php?id=<?= $user['id'] ?>'">
          <span class="profile-stat-count"><?= number_format($user['followers']) ?></span>
          <span class="profile-stat-label">Followers</span>
        </div>
        <div class="profile-stat" onclick="location.href='following.php?id=<?= $user['id'] ?>'">
          <span class="profile-stat-count"><?= number_format($user['following']) ?></span>
          <span class="profile-stat-label">Following</span>
        </div>
        <div class="profile-stat">
          <span class="profile-stat-count"><?= count($videos) ?></span>
          <span class="profile-stat-label">Videos</span>
        </div>
      </div>
      
      <div class="profile-info">
        <?php if (!empty($user['bio'])): ?>
          <p class="profile-bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($user['website'])): ?>
          <a href="<?= htmlspecialchars($user['website']) ?>" class="profile-link" target="_blank">
            <i class="fas fa-link"></i> <?= htmlspecialchars($user['website']) ?>
          </a>
        <?php endif; ?>
        
        <div class="profile-actions">
          <?php if ($viewer_id === $user['id']): ?>
            <a href="edit_profile.php" class="btn btn-outline"><i class="fas fa-edit"></i> Edit</a>
          <?php elseif ($viewer_id): ?>
            <button id="followBtn" class="btn <?= $is_following ? 'btn-danger' : 'btn-primary' ?>" 
              data-following="<?= $is_following ? '1' : '0' ?>" data-user-id="<?= $user['id'] ?>">
              <i class="fas fa-<?= $is_following ? 'user-minus' : 'user-plus' ?>"></i> <?= $is_following ? 'Following' : 'Follow' ?>
            </button>
            <a href="chat.php?user=<?= $user['id'] ?>" class="btn btn-outline"><i class="fas fa-envelope"></i></a>
          <?php else: ?>
            <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Follow</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="profile-tabs">
      <div class="profile-tab active" data-tab="videos">
        <i class="fas fa-video"></i>
      </div>
      <div class="profile-tab" data-tab="liked">
        <i class="fas fa-heart"></i>
      </div>
    </div>
    
    <div id="videos-tab" class="tab-content active">
      <?php if (empty($videos)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-video-slash"></i></div>
          <h3 class="empty-title">No Videos Yet</h3>
          <p class="empty-text">This user hasn't uploaded any videos.</p>
        </div>
      <?php else: ?>
        <div class="video-grid">
          <?php foreach ($videos as $video): ?>
            // In the video grid section, modify the video-item div to include a delete button
<div class="video-item" onclick="location.href='watch.php?id=<?= $video['id'] ?>'">
  <video src="<?= htmlspecialchars($video['video_path']) ?>" muted loop></video>
  <div class="video-stats">
    <div class="video-stat">
      <i class="fas fa-play"></i> <?= number_format($video['views']) ?>
    </div>
    <div class="video-stat">
      <i class="fas fa-heart"></i> <?= number_format($video['likes']) ?>
    </div>
  </div>
  <?php if ($viewer_id === $user['id']): ?>
    <button class="delete-video-btn" data-video-id="<?= $video['id'] ?>" onclick="event.stopPropagation();">
      <i class="fas fa-trash"></i>
    </button>
  <?php endif; ?>
</div>
            <div class="video-stats">
                <div class="video-stat">
                  <i class="fas fa-play"></i> <?= number_format($video['views']) ?>
                </div>
                <div class="video-stat">
                  <i class="fas fa-heart"></i> <?= number_format($video['likes']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    
    <div id="liked-tab" class="tab-content">
      <?php if (empty($liked_videos)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-heart"></i></div>
          <h3 class="empty-title">No Liked Videos Yet</h3>
          <p class="empty-text">Videos this user has liked will appear here</p>
        </div>
      <?php else: ?>
        <div class="video-grid">
          <?php foreach ($liked_videos as $video): ?>
            <div class="video-item" onclick="location.href='watch.php?id=<?= $video['id'] ?>'">
              <video src="<?= htmlspecialchars($video['video_path']) ?>" muted loop></video>
              <div class="video-stats">
                <div class="video-stat">
                  <i class="fas fa-play"></i> <?= number_format($video['views']) ?>
                </div>
                <div class="video-stat">
                  <i class="fas fa-heart"></i> <?= number_format($video['likes']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <?php if ($viewer_id === $user['id']): ?>
    <a href="upload.php" class="fab">
      <i class="fas fa-plus"></i>
    </a>
  <?php endif; ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Tab switching - improved version
      document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.addEventListener('click', () => {
          // Remove active class from all tabs and content
          document.querySelectorAll('.profile-tab, .tab-content').forEach(el => {
            el.classList.remove('active');
          });
          
          // Add active class to clicked tab and corresponding content
          tab.classList.add('active');
          const tabContent = document.getElementById(`${tab.dataset.tab}-tab`);
          if (tabContent) {
            tabContent.classList.add('active');
          }
        });
      });
      
      // Follow button
      const followBtn = document.getElementById('followBtn');
      if (followBtn) followBtn.addEventListener('click', async e => {
        e.stopPropagation();
        const isFollowing = followBtn.dataset.following === '1';
        const userId = followBtn.dataset.userId;
        const originalText = followBtn.innerHTML;
        
        followBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        followBtn.disabled = true;
        
        try {
          const res = await fetch('follow_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, action: isFollowing ? 'unfollow' : 'follow' })
          });
          
          const result = await res.json();
          if (result.success) {
            const newState = !isFollowing;
            followBtn.dataset.following = newState ? '1' : '0';
            followBtn.className = `btn ${newState ? 'btn-danger' : 'btn-primary'}`;
            followBtn.innerHTML = `<i class="fas fa-${newState ? 'user-minus' : 'user-plus'}"></i> ${newState ? 'Following' : 'Follow'}`;
            
            // Update followers count
            const followersCount = document.querySelector('.profile-stat:nth-child(1) .profile-stat-count');
            followersCount.textContent = 
              (parseInt(followersCount.textContent.replace(/,/g, '')) + 
              (newState ? 1 : -1)
            ).toLocaleString();
            
            // Show notification
            showNotification(newState ? 'Followed' : 'Unfollowed', newState ? 'success' : 'info');
          } else {
            showNotification(result.message || 'Error', 'error');
            followBtn.innerHTML = originalText;
          }
        } catch(e) {
          showNotification('Error', 'error');
          followBtn.innerHTML = originalText;
        }
        followBtn.disabled = false;
      });

      // Video hover effect
      document.querySelectorAll('.video-item').forEach(item => {
        const video = item.querySelector('video');
        item.addEventListener('mouseenter', () => video && video.play());
        item.addEventListener('mouseleave', () => video && (video.pause(), video.currentTime = 0));
      });

      // Simple notification function
      function showNotification(message, type) {
        const notif = document.createElement('div');
        notif.className = `notification ${type}`;
        notif.textContent = message;
        document.body.appendChild(notif);
        
        setTimeout(() => {
          notif.classList.add('show');
          setTimeout(() => {
            notif.classList.remove('show');
            setTimeout(() => notif.remove(), 300);
          }, 3000);
        }, 10);
      }
    });

// Add this to your existing JavaScript
document.querySelectorAll('.delete-video-btn').forEach(btn => {
  btn.addEventListener('click', async (e) => {
    e.stopPropagation();
    const videoId = btn.dataset.videoId;
    const videoItem = btn.closest('.video-item');
    
    if (!confirm('Are you sure you want to delete this video?')) return;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    try {
      const res = await fetch('delete_video.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: videoId })
      });
      
      const result = await res.json();
      if (result.success) {
        videoItem.remove();
        showNotification('Video deleted', 'success');
        
        // Update video count
        const videoCount = document.querySelector('.profile-stat:nth-child(3) .profile-stat-count');
        videoCount.textContent = (parseInt(videoCount.textContent.replace(/,/g, ''))) - 1;
      } else {
        showNotification(result.message || 'Error deleting video', 'error');
        btn.innerHTML = '<i class="fas fa-trash"></i>';
        btn.disabled = false;
      }
    } catch(e) {
      showNotification('Error deleting video', 'error');
      btn.innerHTML = '<i class="fas fa-trash"></i>';
      btn.disabled = false;
    }
  });
});

  </script>
  
  <style>
    .notification {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: var(--dark);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 1000;
    }
    
    .notification.show {
      opacity: 1;
    }
    
    .notification.success {
      background: var(--primary);
    }
    
    .notification.error {
      background: #ff4757;
    }
    
    .notification.info {
      background: #576574;
    }
  </style>
</body>
</html>