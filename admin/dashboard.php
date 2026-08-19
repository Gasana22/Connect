<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getPDO();
$stats = [
    'total_users'       => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'business_users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'business'")->fetchColumn(),
    'total_videos'      => $pdo->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'total_ads'         => $pdo->query('SELECT COUNT(*) FROM ads')->fetchColumn(),
    'active_ads'        => $pdo->query('SELECT COUNT(*) FROM ads WHERE is_active = 1')->fetchColumn(),
    'total_impressions' => $pdo->query('SELECT COUNT(*) FROM ad_impressions')->fetchColumn(),
    'today_impressions' => $pdo->query('SELECT COUNT(*) FROM ad_impressions WHERE DATE(view_time) = CURDATE()')->fetchColumn(),
    'live_now'          => $pdo->query('SELECT COUNT(*) FROM live_streams WHERE is_live = 1')->fetchColumn(),
];

$recentUsers = $pdo->query('SELECT username, account_type, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Dashboard';
$activeAdminNav = 'dashboard';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="admin-header"><h1>Dashboard</h1></div>

<div class="stats-grid">
  <div class="stat-card"><h3>Total Users</h3><div class="value"><?= number_format($stats['total_users']) ?></div></div>
  <div class="stat-card"><h3>Business Accounts</h3><div class="value"><?= number_format($stats['business_users']) ?></div></div>
  <div class="stat-card"><h3>Total Videos</h3><div class="value"><?= number_format($stats['total_videos']) ?></div></div>
  <div class="stat-card"><h3>Live Now</h3><div class="value"><?= number_format($stats['live_now']) ?></div></div>
  <div class="stat-card"><h3>Active Ads</h3><div class="value"><?= number_format($stats['active_ads']) ?> / <?= number_format($stats['total_ads']) ?></div></div>
  <div class="stat-card"><h3>Ad Impressions Today</h3><div class="value"><?= number_format($stats['today_impressions']) ?></div></div>
  <div class="stat-card"><h3>Total Ad Impressions</h3><div class="value"><?= number_format($stats['total_impressions']) ?></div></div>
</div>

<h2 style="margin-bottom:1rem;">Recently joined</h2>
<table class="data-table">
  <thead><tr><th>Username</th><th>Type</th><th>Joined</th></tr></thead>
  <tbody>
    <?php foreach ($recentUsers as $u): ?>
      <tr>
        <td><?= e($u['username']) ?></td>
        <td><?= $u['account_type'] === 'business' ? '<span class="badge badge-business">Business</span>' : 'Personal' ?></td>
        <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
