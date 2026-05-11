<?php
require 'auth.php';
requireAdminAuth();

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    logout();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

// Rest of your existing dashboard code...

// Get admin stats
try {
    $pdo = require '../db.php';
    
    $stats = [
        'total_ads' => $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn(),
        'active_ads' => $pdo->query("SELECT COUNT(*) FROM ads WHERE is_active = TRUE")->fetchColumn(),
        'total_impressions' => $pdo->query("SELECT COUNT(*) FROM ad_impressions")->fetchColumn(),
        'today_impressions' => $pdo->query("SELECT COUNT(*) FROM ad_impressions WHERE DATE(view_time) = CURDATE()")->fetchColumn(),
    ];
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die('Error loading dashboard data');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item:hover, .menu-item.active {
            color: var(--primary);
            background: #f1f5ff;
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .stat-card .trend {
            display: flex;
            align-items: center;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .trend.up {
            color: var(--success);
        }
        
        .trend.down {
            color: var(--danger);
        }
        
        .recent-activity {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--text-light);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div style="display: flex; align-items: center;">
                    <img src="https://via.placeholder.com/40" alt="Logo">
                    <h2>Admin Panel</h2>
                </div>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="ads.php" class="menu-item">
                    <i class="fas fa-ad"></i>
                    Ad Management
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    User Management
                </a>
                <a href="analytics.php" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                
                <div class="user-menu">
                    <img src="https://via.placeholder.com/40" alt="User" class="user-avatar">
                    <div>
                        <div><?= htmlspecialchars($_SESSION['username']) ?></div>
                        <small style="color: var(--text-light);">Administrator</small>
                    </div>
                    <a href="../logout.php" style="margin-left: 20px; color: var(--danger);">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Ads</h3>
                    <div class="value"><?= number_format($stats['total_ads']) ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Active Ads</h3>
                    <div class="value"><?= number_format($stats['active_ads']) ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 5% from last week
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Impressions</h3>
                    <div class="value"><?= number_format($stats['total_impressions']) ?></div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up"></i> 24% from last month
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Today's Impressions</h3>
                    <div class="value"><?= number_format($stats['today_impressions']) ?></div>
                    <div class="trend down">
                        <i class="fas fa-arrow-down"></i> 3% from yesterday
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="recent-activity">
                <h2 style="margin-bottom: 20px;">Recent Activity</h2>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-ad"></i>
                    </div>
                    <div class="activity-content">
                        <strong>New ad campaign created</strong>
                        <p>Premium Business Tools campaign launched</p>
                        <div class="activity-time">2 hours ago</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="activity-content">
                        <strong>New user registered</strong>
                        <p>Marketer John joined as advertiser</p>
                        <div class="activity-time">5 hours ago</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="activity-content">
                        <strong>Performance milestone</strong>
                        <p>Reached 1M total ad impressions</p>
                        <div class="activity-time">1 day ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>