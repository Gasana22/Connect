<?php
session_start();
require_once 'db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get active streams with optional category filter
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;

$query = "SELECT ls.*, u.username, u.avatar, c.name as category_name 
          FROM live_streams ls
          JOIN users u ON ls.user_id = u.id
          LEFT JOIN categories c ON ls.category_id = c.id
          WHERE ls.is_live = 1";

$params = [];

if ($categoryFilter) {
    $query .= " AND ls.category_id = ?";
    $params[] = $categoryFilter;
}

if ($searchQuery) {
    $query .= " AND (ls.title LIKE ? OR ls.description LIKE ? OR u.username LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$query .= " ORDER BY ls.viewer_count DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activeStreams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular categories (categories with most active streams)
$popularCategories = $pdo->query(
    "SELECT c.id, c.name, COUNT(ls.id) as stream_count 
     FROM categories c
     LEFT JOIN live_streams ls ON c.id = ls.category_id AND ls.is_live = 1
     GROUP BY c.id
     ORDER BY stream_count DESC, c.name
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Handle notifications
$notification = '';
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']);
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Streams - StreamWave</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse styles from live.php or add specific ones */
        :root {
            --primary:rgb(15, 90, 175);
            --primary-dark:rgb(6, 77, 148);
            --dark: #0e0e10;
            --dark-gray: #1f1f23;
            --medium-gray: #26262c;
            --light-gray: #3a3a3d;
            --text: #efeff1;
            --text-muted: #adadb8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark);
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles - Same as live.php */
        
        /* Browse Page Specific Styles */
        .browse-header {
            margin: 40px 0 20px;
        }
        
        .search-container {
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 20px;
            background-color: var(--medium-gray);
            border: 1px solid var(--light-gray);
            border-radius: 4px;
            color: var(--text);
            font-family: inherit;
        }
        
        .search-button {
            padding: 12px 20px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .category-tab {
            padding: 8px 16px;
            background-color: var(--medium-gray);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .category-tab:hover, .category-tab.active {
            background-color: var(--primary);
            color: white;
        }
        
        .popular-categories {
            margin-bottom: 40px;
        }
        
        .popular-categories h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .popular-categories-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .stream-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stream-card {
            background-color: var(--dark-gray);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stream-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stream-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            background-color: black;
            position: relative;
        }
        
        .live-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #ff5555;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .viewer-count {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .stream-info-card {
            padding: 15px;
        }
        
        .stream-card-title {
            font-weight: 600;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .stream-card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .streamer-avatar-sm {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .stream-category {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        
        .no-streams {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .stream-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }
        
        @media (max-width: 576px) {
            .stream-grid {
                grid-template-columns: 1fr;
            }
            
            .search-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        
       
    </header>
    
    <div class="container">
        <?php if ($notification): ?>
        <div class="notification">
            <?= $notification ?>
            <span class="notification-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </span>
        </div>
        <?php endif; ?>
        
        <div class="browse-header">
            <h1>Browse Live Streams</h1>
            <p>Discover and watch streams from creators around the world</p>
        </div>
        
        <form method="get" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search streams..." 
                   value="<?= htmlspecialchars($searchQuery ?? '') ?>">
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
        
        <div class="popular-categories">
            <h3>Popular Categories</h3>
            <div class="popular-categories-list">
                <?php foreach ($popularCategories as $category): ?>
                    <a href="browse.php?category=<?= $category['id'] ?>" class="category-tab <?= $categoryFilter == $category['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($category['name']) ?>
                        <span>(<?= $category['stream_count'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="category-tabs">
            <a href="browse.php" class="category-tab <?= !$categoryFilter ? 'active' : '' ?>">All Streams</a>
            <?php foreach ($categories as $category): ?>
                <a href="browse.php?category=<?= $category['id'] ?>" class="category-tab <?= $categoryFilter == $category['id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($activeStreams)): ?>
            <div class="no-streams">
                <i class="fas fa-broadcast-tower" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h3>No live streams found</h3>
                <p><?= $searchQuery ? 'Try a different search term' : 'Check back later for live streams' ?></p>
                <?php if ($isLoggedIn && !isset($_SESSION['current_stream_key'])): ?>
                    <a href="live.php?go_live" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-video"></i> Start Your Own Stream
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="stream-grid">
                <?php foreach ($activeStreams as $stream): ?>
                    <a href="live.php?stream=<?= $stream['stream_key'] ?>" class="stream-card">
                        <div class="stream-thumbnail">
                            <img src="https://via.placeholder.com/500x280?text=<?= urlencode($stream['title']) ?>" alt="<?= htmlspecialchars($stream['title']) ?>">
                            <div class="live-badge">
                                <span class="live-pulse"></span>
                                LIVE
                            </div>
                            <div class="viewer-count">
                                <i class="fas fa-user"></i> <?= number_format($stream['viewer_count']) ?>
                            </div>
                        </div>
                        <div class="stream-info-card">
                            <h3 class="stream-card-title"><?= htmlspecialchars($stream['title']) ?></h3>
                            <div class="stream-category"><?= $stream['category_name'] ?? 'No Category' ?></div>
                            <div class="stream-card-meta">
                                <img src="<?= htmlspecialchars($stream['avatar'] ?? 'https://via.placeholder.com/30') ?>" alt="Streamer Avatar" class="streamer-avatar-sm">
                                <span><?= htmlspecialchars($stream['username']) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Handle category tab clicks
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                if (this.classList.contains('active')) {
                    e.preventDefault();
                }
            });
        });
        
        // Auto-focus search input when search parameter exists
        <?php if ($searchQuery): ?>
            document.querySelector('.search-input').focus();
        <?php endif; ?>
    </script>
</body>
</html>