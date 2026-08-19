<?php
/**
 * Included by layout_header.php for layout='app'. Expects $user, $unreadCount, $activeNav in scope.
 */
?>
<aside class="sidebar" id="sidebar">
    <div>
        <div class="logo-container">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M19 7H5C3.9 7 3 7.9 3 9V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V9C21 7.9 20.1 7 19 7Z" stroke="currentColor" stroke-width="2"/>
                <path d="M16 3V7" stroke="currentColor" stroke-width="2"/>
                <path d="M8 3V7" stroke="currentColor" stroke-width="2"/>
                <path d="M3 11H21" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="logo-text">Connect</span>
        </div>

        <a href="profile.php?id=<?= (int) $user['id'] ?>" class="user-profile-link">
            <div class="user-profile">
                <img src="<?= e(profilePicUrl($user['profile_pic'] ?? null)) ?>" alt="" class="profile-pic">
                <div class="user-info">
                    <span class="username">
                        <?= e($user['username']) ?>
                        <?php if (!empty($user['is_verified'])): ?><i class="fas fa-badge-check" style="color:var(--brand)"></i><?php endif; ?>
                    </span>
                    <span class="user-handle">@<?= e($user['username']) ?><?= $user['account_type'] === 'business' ? ' · Business' : '' ?></span>
                </div>
            </div>
        </a>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $activeNav === 'home' ? 'active' : '' ?>"><i class="fas fa-house nav-icon"></i> Home</a>
            <a href="explore.php" class="nav-item <?= $activeNav === 'explore' ? 'active' : '' ?>"><i class="fas fa-compass nav-icon"></i> Explore</a>
            <a href="browse.php" class="nav-item <?= $activeNav === 'live' ? 'active' : '' ?>"><i class="fas fa-signal nav-icon"></i> Live</a>
            <a href="message.php" class="nav-item <?= $activeNav === 'messages' ? 'active' : '' ?>"><i class="fas fa-comment nav-icon"></i> Messages</a>
            <a href="notifications.php" class="nav-item <?= $activeNav === 'notifications' ? 'active' : '' ?>" id="notifNavItem">
                <i class="fas fa-bell nav-icon"></i> Alerts
                <?php if ($unreadCount > 0): ?><span class="notification-badge pulse" id="notifBadge"><?= (int) $unreadCount ?></span><?php endif; ?>
            </a>
            <a href="upload.php" class="nav-item <?= $activeNav === 'upload' ? 'active' : '' ?>"><i class="fas fa-circle-plus nav-icon"></i> Upload</a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn"><i class="fas fa-arrow-right-from-bracket"></i> Log Out</a>
    </div>
</aside>
