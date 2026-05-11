<?php
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get unread notification count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unreadCount = $stmt->fetchColumn();

// Get profile picture
if (!function_exists('getProfilePicture')) {
    function getProfilePicture($user_id) {
        $path = "assets/profile_pics/" . $user_id . ".jpg";
        return file_exists($path) ? $path : "assets/profile_pics/default.png";
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Toggle Button (for Mobile) -->
<button id="sidebarToggle" class="sidebar-toggle">☰</button>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <svg class="logo-icon" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M19 7H5C3.9 7 3 7.9 3 9V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V9C21 7.9 20.1 7 19 7Z" stroke="currentColor" stroke-width="2"/>
                <path d="M16 3V7" stroke="currentColor" stroke-width="2"/>
                <path d="M8 3V7" stroke="currentColor" stroke-width="2"/>
                <path d="M3 11H21" stroke="currentColor" stroke-width="2"/>
            </svg>
            <h2 class="logo-text">Connect</h2>
        </div>

        <a href="profile.php?id=<?= $user_id ?>" class="user-profile-link">
            <div class="user-profile">
                <img src="<?= getProfilePicture($user_id) ?>" alt="Profile" class="profile-pic" onerror="this.onerror=null; this.src='assets/profile_pics/default.png';">
                <div class="user-info">
                    <span class="username"><?= htmlspecialchars($username) ?></span>
                    <span class="user-handle">@<?= htmlspecialchars($username) ?></span>
                </div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>" data-tooltip="Home">🏠 <span class="nav-text">Home</span></a>
        <a href="explore.php" class="nav-item <?= $currentPage === 'explore.php' ? 'active' : '' ?>" data-tooltip="Explore">🔍 <span class="nav-text">Explore</span></a>
        <a href="message.php" class="nav-item <?= $currentPage === 'messages.php' ? 'active' : '' ?>" data-tooltip="Messages">💬 <span class="nav-text">Messages</span></a>
        <a href="notifications.php" class="nav-item <?= $currentPage === 'notifications.php' ? 'active' : '' ?>" data-tooltip="Alerts">
            🔔 <span class="nav-text">Alerts</span>
            <?php if ($unreadCount > 0): ?>
                <span class="notification-badge pulse"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn" data-tooltip="Log Out">🚪 <span>Log Out</span></a>
    </div>
</aside>

<!-- Include your existing <style> and <script> here (unchanged) -->

<style>
.sidebar-toggle {
    display: none;
    background: #007BFF;
    color: #fff;
    border: none;
    padding: 10px;
    font-size: 20px;
    cursor: pointer;
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 200;
}

/* Sidebar container */
.sidebar {
    width: 240px;
    background-color: #fff;
    border-right: 1px solid #ddd;
    height: 100vh;
    padding: 15px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 150;
    transition: transform 0.3s ease;
}

/* Collapsible support */
@media (max-width: 768px) {
    .sidebar-toggle {
        display: block;
    }

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
    }
}

.sidebar-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 8px;
}

.logo-icon {
    color: #333;
}

.logo-text {
    font-size: 1.2em;
    font-weight: bold;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-pic {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.username {
    font-weight: bold;
    font-size: 14px;
}

.user-handle {
    font-size: 12px;
    color: #777;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    text-decoration: none;
    color: #333;
    border-radius: 6px;
    transition: background 0.2s;
    font-size: 14px;
    position: relative;
}

.nav-item:hover,
.nav-item.active {
    background-color: #f0f0f0;
    font-weight: bold;
    border-left: 4px solid #007BFF;
}

.nav-item::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 110%;
    top: 50%;
    transform: translateY(-50%);
    background: #000;
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    display: none;
    white-space: nowrap;
}

.nav-item:hover::after {
    display: block;
}

.notification-badge {
    background-color: red;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 999px;
    margin-left: auto;
    font-weight: bold;
}

.pulse {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.sidebar-footer {
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    color: #d00;
    text-decoration: none;
    font-size: 14px;
}
</style>

<script>
function pollNotifications() {
    fetch('api/get_notifications.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.unread > 0) {
                if (!badge) {
                    const notifLink = document.querySelector('a[href="notifications.php"]');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge pulse';
                    newBadge.textContent = data.unread;
                    notifLink.appendChild(newBadge);
                } else {
                    badge.textContent = data.unread;
                }
            } else if (badge) {
                badge.remove();
            }
        })
        .catch(console.error);
}
setInterval(pollNotifications, 30000);
document.addEventListener('DOMContentLoaded', () => {
    pollNotifications();

    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
});
</script>
