<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read when page loads
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

// Fetch all notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fixed SQL query - parameters need to be integers for LIMIT/OFFSET
$stmt = $pdo->prepare("
    SELECT n.*, u.username, u.profile_pic, v.caption, v.thumbnail 
    FROM notifications n
    LEFT JOIN users u ON n.sender_id = u.id
    LEFT JOIN videos v ON n.video_id = v.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT :limit OFFSET :offset
");

// Bind parameters explicitly with type
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);


$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$total_stmt->execute([$user_id]);
$total_notifications = $total_stmt->fetchColumn();
$total_pages = ceil($total_notifications / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
  <style>
    :root {
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --primary-light: #e0e7ff;
      --primary-light-hover: #c7d2fe;
      --bg: #f9fafb;
      --card-bg: #ffffff;
      --text: #111827;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --shadow: 0 1px 3px rgba(0,0,0,0.1);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
      --radius: 12px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    [data-theme="dark"] {
      --primary: #818cf8;
      --primary-hover: #6366f1;
      --primary-light: #3730a3;
      --primary-light-hover: #312e81;
      --bg: #1a1a1a;
      --card-bg: #2d2d2d;
      --text: #f3f4f6;
      --text-light: #9ca3af;
      --border: #4b5563;
      --shadow: 0 1px 3px rgba(0,0,0,0.3);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.3), 0 2px 4px -1px rgba(0,0,0,0.2);
    }
    
    body {
      background-color: var(--bg);
      color: var(--text);
      transition: var(--transition);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      line-height: 1.5;
      margin: 0;
      padding: 0;
      display: flex;
      min-height: 100vh;
    }
    
    
    
    /* Main content styles */
    .main-content {
     
      display: flex;
      min-height: 100vh;
    }
    
    
    .notification-container {
     flex:1;
     padding:2rem;
     margin-left: 240px
    }
    
    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border);
    }
    
    .notification-header h2 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--primary);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .header-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }
    
    .theme-toggle {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 0.5rem;
      cursor: pointer;
      color: var(--text-light);
      transition: var(--transition);
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .theme-toggle:hover {
      color: var(--primary);
      border-color: var(--primary);
    }
    
    .clear-all {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 0.5rem 1rem;
      color: var(--text);
      cursor: pointer;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: var(--transition);
    }
    
    .clear-all:hover {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .notification-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    
    .notification {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
      display: flex;
      gap: 1rem;
      align-items: flex-start;
      transition: var(--transition);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
      cursor: pointer;
    }
    
    .notification::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: transparent;
      transition: var(--transition);
    }
    
    .notification:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
      background: var(--primary-light-hover);
    }
    
    .notification:hover::before {
      background: var(--primary);
    }
    
    .notification.unread {
      background: var(--primary-light);
    }
    
    .notification.unread::before {
      background: var(--primary);
    }
    
    .notification-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid var(--primary);
      transition: var(--transition);
      background: var(--bg);
    }
    
    .notification:hover .notification-avatar {
      transform: scale(1.05);
      box-shadow: 0 0 0 3px var(--primary-hover);
    }
    
    .notification-content {
      flex: 1;
      min-width: 0;
    }
    
    .notification .username {
      font-weight: 600;
      color: var(--primary);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .notification .username:hover {
      text-decoration: underline;
    }
    
    .notification .video-link {
      color: var(--primary);
      font-weight: 500;
      text-decoration: none;
      transition: var(--transition);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      display: inline-block;
      max-width: 200px;
    }
    
    .notification .video-link:hover {
      text-decoration: underline;
    }
    
    .notification .time {
      font-size: 0.85em;
      color: var(--text-light);
      margin-top: 0.5rem;
      display: block;
    }
    
    .notification-text {
      margin: 0.5rem 0;
      line-height: 1.5;
      word-break: break-word;
    }
    
    .notification-actions {
      display: flex;
      gap: 0.75rem;
      margin-top: 0.75rem;
      flex-wrap: wrap;
    }
    
    .notification-action {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 0.35rem 0.75rem;
      color: var(--text-light);
      cursor: pointer;
      font-size: 0.85em;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      transition: var(--transition);
    }
    
    .notification-action:hover {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .notification-action i {
      font-size: 0.9em;
    }
    
    .pagination {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 2rem;
      flex-wrap: wrap;
    }
    
    .pagination a, .pagination span {
      padding: 0.5rem 0.9rem;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      text-decoration: none;
      color: var(--text);
      transition: var(--transition);
      font-size: 0.9rem;
    }
    
    .pagination a:hover {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .pagination .current {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }
    
    .empty-state {
      text-align: center;
      padding: 3rem 0;
      color: var(--text-light);
    }
    
    .empty-state i {
      font-size: 3.5rem;
      margin-bottom: 1.5rem;
      color: var(--border);
      opacity: 0.7;
    }
    
    .empty-state h3 {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
      color: var(--text);
    }
    
    .empty-state p {
      max-width: 400px;
      margin: 0 auto;
      line-height: 1.6;
    }
    
    .notification-type-icon {
      color: var(--primary);
      margin-right: 0.5rem;
    }
    
    .notification-thumbnail {
      width: 80px;
      height: 45px;
      border-radius: 4px;
      object-fit: cover;
      margin-left: auto;
      transition: var(--transition);
      cursor: pointer;
      flex-shrink: 0;
      border: 1px solid var(--border);
    }
    
    .notification-thumbnail:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .unread-badge {
      background: var(--primary);
      color: white;
      border-radius: 50%;
      width: 8px;
      height: 8px;
      display: inline-block;
      margin-left: 5px;
      vertical-align: middle;
    }
    
    @media (max-width: 768px) {
      body {
        flex-direction: column;
      }
      
      
      .main-content {
        padding: 1rem;
      }
      
      .notification-container {
        padding: 0;
      }
      
      .notification {
        flex-direction: column;
        gap: 0.75rem;
      }
      
      .notification-avatar {
        width: 40px;
        height: 40px;
      }
      
      .notification-thumbnail {
        margin-left: 0;
        margin-top: 0.5rem;
        width: 100%;
        height: auto;
        max-height: 150px;
      }
      
      .header-actions {
        gap: 0.5rem;
      }
      
      .notification-header h2 {
        font-size: 1.5rem;
      }
    }
    
    /* Animation for new notifications */
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .notification.new {
      animation: slideIn 0.4s ease-out forwards;
    }
    
    /* Floating action button */
    .fab {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: none;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-md);
      cursor: pointer;
      transition: var(--transition);
      z-index: 100;
      border: none;
    }
    
    .fab:hover {
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
      background: var(--primary-hover);
    }
    
    .fab i {
      font-size: 1.5rem;
    }
    
    /* Loading spinner */
    .loading-spinner {
      display: none;
      text-align: center;
      padding: 1rem;
    }
    
    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid rgba(0, 0, 0, 0.1);
      border-radius: 50%;
      border-top-color: var(--primary);
      animation: spin 1s ease-in-out infinite;
      margin: 0 auto;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Toast notification */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--primary);
      color: white;
      padding: 12px 20px;
      border-radius: var(--radius);
      box-shadow: var(--shadow-md);
      z-index: 1000;
      transform: translateY(-30px);
      opacity: 0;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }

    .toast i {
      font-size: 1.2rem;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">
  <div class="notification-container">
    <div class="notification-header">
      <h2><i class="fas fa-bell"></i> Notifications</h2>
      <div class="header-actions">
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
          <i class="fas fa-moon"></i>
        </button>
        <button class="clear-all" id="clearAll">
          <i class="fas fa-trash-alt"></i> Clear All
        </button>
      </div>
    </div>

    <?php if (count($notifications) === 0): ?>
      <div class="empty-state">
        <i class="far fa-bell-slash"></i>
        <h3>No notifications yet</h3>
        <p>When you receive notifications, they'll appear here. Stay tuned for updates!</p>
      </div>
    <?php else: ?>
      <div class="notification-list">
        <?php foreach ($notifications as $note): ?>
          <div class="notification <?= $note['is_read'] ? '' : 'unread' ?>">
            <img src="<?= htmlspecialchars($note['profile_pic'] ?? 'default-avatar.jpg') ?>" 
                 alt="<?= htmlspecialchars($note['username'] ?? 'User') ?>'s profile picture" 
                 class="notification-avatar"
                 onerror="this.src='default-avatar.jpg'">
            
            <div class="notification-content">
              <?php if ($note['type'] === 'like'): ?>
                <p class="notification-text">
                  <i class="fas fa-heart notification-type-icon"></i>
                  <a href="profile.php?id=<?= $note['sender_id'] ?>" class="username"><?= htmlspecialchars($note['username']) ?></a> 
                  liked your video: 
                  <a href="video.php?id=<?= $note['video_id'] ?>" class="video-link" title="<?= htmlspecialchars($note['caption'] ?? 'Untitled') ?>">
                    <?= htmlspecialchars($note['caption'] ?? 'Untitled') ?>
                  </a>
                  <?= $note['is_read'] ? '' : '<span class="unread-badge"></span>' ?>
                </p>
              <?php elseif ($note['type'] === 'comment'): ?>
                <p class="notification-text">
                  <i class="fas fa-comment notification-type-icon"></i>
                  <a href="profile.php?id=<?= $note['sender_id'] ?>" class="username"><?= htmlspecialchars($note['username']) ?></a> 
                  commented on your video: 
                  <a href="video.php?id=<?= $note['video_id'] ?>" class="video-link" title="<?= htmlspecialchars($note['caption'] ?? 'Untitled') ?>">
                    <?= htmlspecialchars($note['caption'] ?? 'Untitled') ?>
                  </a>
                  <?= $note['is_read'] ? '' : '<span class="unread-badge"></span>' ?>
                </p>
              <?php elseif ($note['type'] === 'follow'): ?>
                <p class="notification-text">
                  <i class="fas fa-user-plus notification-type-icon"></i>
                  <a href="profile.php?id=<?= $note['sender_id'] ?>" class="username"><?= htmlspecialchars($note['username']) ?></a> 
                  started following you
                  <?= $note['is_read'] ? '' : '<span class="unread-badge"></span>' ?>
                </p>
              <?php else: ?>
                <p class="notification-text">
                  <i class="fas fa-bell notification-type-icon"></i>
                  You have a new notification
                  <?= $note['is_read'] ? '' : '<span class="unread-badge"></span>' ?>
                </p>
              <?php endif; ?>
              
              <span class="time"><?= date("F j, Y, g:i a", strtotime($note['created_at'])) ?></span>
              
              <div class="notification-actions">
                <button class="notification-action" aria-label="Like this notification">
                  <i class="far fa-thumbs-up"></i> Like
                </button>
                <button class="notification-action" aria-label="Reply to this notification">
                  <i class="far fa-comment-dots"></i> Reply
                </button>
                <button class="notification-action delete-notification" data-id="<?= $note['id'] ?>" aria-label="Delete this notification">
                  <i class="far fa-trash-alt"></i> Delete
                </button>
              </div>
            </div>
            
            <?php if (!empty($note['thumbnail'])): ?>
              <img src="<?= htmlspecialchars($note['thumbnail']) ?>" 
                   alt="Video thumbnail for <?= htmlspecialchars($note['caption'] ?? 'video') ?>" 
                   class="notification-thumbnail"
                   onclick="window.location.href='video.php?id=<?= $note['video_id'] ?>'"
                   onerror="this.style.display='none'">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      
      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i> Previous</a>
          <?php endif; ?>
          
          <?php 
          // Show limited pagination links
          $start = max(1, $page - 2);
          $end = min($total_pages, $page + 2);
          
          if ($start > 1): ?>
            <span>...</span>
          <?php endif; ?>
          
          <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>
          
          <?php if ($end < $total_pages): ?>
            <span>...</span>
          <?php endif; ?>
          
          <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Next <i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <div class="loading-spinner" id="loadingSpinner">
      <div class="spinner"></div>
      <p>Loading more notifications...</p>
    </div>
  </div>
</main>

<button class="fab" id="scrollToTop" aria-label="Scroll to top">
  <i class="fas fa-arrow-up"></i>
</button>

<div class="toast" id="toastNotification">
  <i class="fas fa-bell"></i>
  <span id="toastMessage">New notification received</span>
</div>

<script>
  // Theme toggle functionality
  const themeToggle = document.getElementById('themeToggle');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  let currentTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
  
  function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    if (theme === 'dark') {
      themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
      themeToggle.setAttribute('aria-label', 'Switch to light mode');
    } else {
      themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
      themeToggle.setAttribute('aria-label', 'Switch to dark mode');
    }
  }
  
  setTheme(currentTheme);
  
  themeToggle.addEventListener('click', () => {
    currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(currentTheme);
  });
  
  // Scroll to top button
  const scrollToTopBtn = document.getElementById('scrollToTop');
  
  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
      scrollToTopBtn.style.display = 'flex';
    } else {
      scrollToTopBtn.style.display = 'none';
    }
  });
  
  scrollToTopBtn.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
  
  // AJAX for deleting notifications
  document.querySelectorAll('.delete-notification').forEach(button => {
    button.addEventListener('click', function(e) {
      e.stopPropagation();
      const notificationId = this.getAttribute('data-id');
      const notificationElement = this.closest('.notification');
      
      if (confirm('Are you sure you want to delete this notification?')) {
        fetch('delete_notification.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            notificationElement.style.transform = 'scale(0.9)';
            notificationElement.style.opacity = '0';
            setTimeout(() => {
              notificationElement.remove();
              checkEmptyState();
              showToast('Notification deleted');
            }, 300);
          } else {
            showToast('Failed to delete notification', 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('An error occurred', 'error');
        });
      }
    });
  });
  
  // Clear all notifications
  document.getElementById('clearAll').addEventListener('click', function() {
    if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
      const loadingSpinner = document.getElementById('loadingSpinner');
      loadingSpinner.style.display = 'block';
      
      fetch('clear_notifications.php', {
        method: 'POST'
      })
      .then(response => response.json())
      .then(data => {
        loadingSpinner.style.display = 'none';
        if (data.success) {
          document.querySelectorAll('.notification').forEach(el => {
            el.style.transform = 'scale(0.9)';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
          });
          
          setTimeout(() => {
            checkEmptyState();
            showToast('All notifications cleared');
          }, 350);
        } else {
          showToast('Failed to clear notifications', 'error');
        }
      })
      .catch(error => {
        loadingSpinner.style.display = 'none';
        console.error('Error:', error);
        showToast('An error occurred', 'error');
      });
    }
  });
  
  function checkEmptyState() {
    if (document.querySelectorAll('.notification').length === 0) {
      const emptyState = document.createElement('div');
      emptyState.className = 'empty-state';
      emptyState.innerHTML = `
        <i class="far fa-bell-slash"></i>
        <h3>No notifications yet</h3>
        <p>When you receive notifications, they'll appear here. Stay tuned for updates!</p>
      `;
      
      const container = document.querySelector('.notification-container');
      const list = document.querySelector('.notification-list') || container;
      list.replaceWith(emptyState);
    }
  }
  
  // Make notifications clickable
  document.querySelectorAll('.notification').forEach(notification => {
    notification.addEventListener('click', function(e) {
      // Don't trigger if clicking on a link or button
      if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a, button')) {
        return;
      }
      
      // Find the first link in the notification (either profile or video link)
      const link = this.querySelector('a');
      if (link) {
        window.location.href = link.href;
      }
    });
  });
  
  // Infinite scroll (optional)
  window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
      loadMoreNotifications();
    }
  });
  
  let isLoading = false;
  let currentPage = <?= $page ?>;
  
  function loadMoreNotifications() {
    if (isLoading || currentPage >= <?= $total_pages ?>) return;
    
    isLoading = true;
    const loadingSpinner = document.getElementById('loadingSpinner');
    loadingSpinner.style.display = 'block';
    
    currentPage++;
    
    fetch(`notifications.php?page=${currentPage}`)
      .then(response => response.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newNotifications = doc.querySelector('.notification-list')?.innerHTML;
        
        if (newNotifications) {
          document.querySelector('.notification-list').insertAdjacentHTML('beforeend', newNotifications);
          // Reattach event listeners to new elements
          attachEventListenersToNewNotifications();
          showToast(`Loaded page ${currentPage}`);
        }
        
        // Update pagination
        const newPagination = doc.querySelector('.pagination')?.outerHTML;
        if (newPagination) {
          document.querySelector('.pagination').outerHTML = newPagination;
        }
      })
      .catch(error => {
        console.error('Error loading more notifications:', error);
        currentPage--; // Revert page on error
        showToast('Error loading more notifications', 'error');
      })
      .finally(() => {
        isLoading = false;
        loadingSpinner.style.display = 'none';
      });
  }
  
  function attachEventListenersToNewNotifications() {
    // Reattach all the event listeners to newly loaded notifications
    document.querySelectorAll('.delete-notification').forEach(button => {
      button.addEventListener('click', function(e) {
        e.stopPropagation();
        const notificationId = this.getAttribute('data-id');
        const notificationElement = this.closest('.notification');
        
        if (confirm('Are you sure you want to delete this notification?')) {
          fetch('delete_notification.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${notificationId}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              notificationElement.style.transform = 'scale(0.9)';
              notificationElement.style.opacity = '0';
              setTimeout(() => {
                notificationElement.remove();
                checkEmptyState();
                showToast('Notification deleted');
              }, 300);
            } else {
              showToast('Failed to delete notification', 'error');
            }
          });
        }
      });
    });
    
    document.querySelectorAll('.notification').forEach(notification => {
      notification.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a, button')) {
          return;
        }
        
        const link = this.querySelector('a');
        if (link) {
          window.location.href = link.href;
        }
      });
    });
  }
  
  // Toast notification function
  function showToast(message, type = 'success') {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    
    // Set different colors based on type
    if (type === 'error') {
      toast.style.backgroundColor = '#ef4444';
    } else if (type === 'success') {
      toast.style.backgroundColor = 'var(--primary)';
    } else {
      toast.style.backgroundColor = 'var(--primary)';
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }
  
  // Real-time updates with EventSource
  if (typeof(EventSource) !== "undefined") {
    const eventSource = new EventSource("notification_stream.php");
    
    eventSource.onmessage = function(event) {
      const data = JSON.parse(event.data);
      
      // If we're on the empty state, remove it
      const emptyState = document.querySelector('.empty-state');
      if (emptyState) emptyState.remove();
      
      // Create notification list if it doesn't exist
      let list = document.querySelector('.notification-list');
      if (!list) {
        list = document.createElement('div');
        list.className = 'notification-list';
        document.querySelector('.notification-container').appendChild(list);
      }
      
      // Create and prepend new notification
      const newNotification = document.createElement('div');
      newNotification.className = 'notification unread new';
      
      let thumbnailHtml = '';
      if (data.thumbnail) {
        thumbnailHtml = `<img src="${escapeHtml(data.thumbnail)}" alt="Video thumbnail" class="notification-thumbnail" onclick="window.location.href='video.php?id=${data.video_id}'">`;
      }
      
      newNotification.innerHTML = `
        <img src="${escapeHtml(data.profile_pic || 'default-avatar.jpg')}" alt="Profile" class="notification-avatar" onerror="this.src='default-avatar.jpg'">
        <div class="notification-content">
          <p class="notification-text">
            <i class="fas fa-${getNotificationIcon(data.type)} notification-type-icon"></i>
            <a href="profile.php?id=${data.sender_id}" class="username">${escapeHtml(data.username)}</a> 
            ${getNotificationText(data)}
            <span class="unread-badge"></span>
          </p>
          <span class="time">Just now</span>
          <div class="notification-actions">
            <button class="notification-action">
              <i class="far fa-thumbs-up"></i> Like
            </button>
            <button class="notification-action">
              <i class="far fa-comment-dots"></i> Reply
            </button>
            <button class="notification-action delete-notification" data-id="${data.id}">
              <i class="far fa-trash-alt"></i> Delete
            </button>
          </div>
        </div>
        ${thumbnailHtml}
      `;
      
      // Add event listener to the new delete button
      newNotification.querySelector('.delete-notification').addEventListener('click', function(e) {
        e.stopPropagation();
        const notificationId = this.getAttribute('data-id');
        const notificationElement = this.closest('.notification');
        
        if (confirm('Are you sure you want to delete this notification?')) {
          fetch('delete_notification.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${notificationId}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              notificationElement.style.transform = 'scale(0.9)';
              notificationElement.style.opacity = '0';
              setTimeout(() => {
                notificationElement.remove();
                checkEmptyState();
                showToast('Notification deleted');
              }, 300);
            } else {
              showToast('Failed to delete notification', 'error');
            }
          });
        }
      });
      
      // Make the notification clickable
      newNotification.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('a, button')) {
          return;
        }
        const link = this.querySelector('a');
        if (link) {
          window.location.href = link.href;
        }
      });
      
      // Insert at the top
      list.insertBefore(newNotification, list.firstChild);
      
      // Show toast notification
      showToast('New notification received');
    };
    
    function getNotificationIcon(type) {
      switch(type) {
        case 'like': return 'heart';
        case 'comment': return 'comment';
        case 'follow': return 'user-plus';
        default: return 'bell';
      }
    }
    
    function getNotificationText(data) {
      switch(data.type) {
        case 'like': 
          return `liked your video: <a href="video.php?id=${data.video_id}" class="video-link">${escapeHtml(data.caption || 'Untitled')}</a>`;
        case 'comment': 
          return `commented on your video: <a href="video.php?id=${data.video_id}" class="video-link">${escapeHtml(data.caption || 'Untitled')}</a>`;
        case 'follow': 
          return 'started following you';
        default: 
          return 'sent you a notification';
      }
    }
    
    function escapeHtml(unsafe) {
      if (!unsafe) return '';
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }
  }
</script>

</body>
</html>

