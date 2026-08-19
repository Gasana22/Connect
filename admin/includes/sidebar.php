<?php
/** Expects $activeAdminNav in scope. */
?>
<aside class="admin-sidebar">
  <div class="admin-sidebar-header">
    <i class="fas fa-shield-halved" style="font-size:1.4rem; color: var(--brand);"></i>
    <div>
      <div style="font-weight:800; font-family: var(--font-display);">Admin Panel</div>
      <div class="text-muted" style="font-size:0.78rem;"><?= e($_SESSION['admin_username'] ?? '') ?></div>
    </div>
  </div>
  <nav>
    <a href="dashboard.php" class="admin-menu-item <?= $activeAdminNav === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-gauge-high"></i> Dashboard</a>
    <a href="ads.php" class="admin-menu-item <?= $activeAdminNav === 'ads' ? 'active' : '' ?>"><i class="fas fa-rectangle-ad"></i> Ad Management</a>
    <a href="users.php" class="admin-menu-item <?= $activeAdminNav === 'users' ? 'active' : '' ?>"><i class="fas fa-users"></i> Users</a>
    <a href="analytics.php" class="admin-menu-item <?= $activeAdminNav === 'analytics' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Analytics</a>
    <a href="settings.php" class="admin-menu-item <?= $activeAdminNav === 'settings' ? 'active' : '' ?>"><i class="fas fa-gear"></i> Settings</a>
    <a href="logout.php" class="admin-menu-item" style="color: var(--danger); margin-top: 1rem;"><i class="fas fa-arrow-right-from-bracket"></i> Log out</a>
  </nav>
</aside>
