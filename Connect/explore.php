<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Search functionality
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'recent';

// Base query
$sql = "
    SELECT v.*, u.username, u.profile_pic,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.visibility = 'public'
";

if (!empty($search_query)) {
    $sql .= " AND (v.caption LIKE :search OR u.username LIKE :search OR v.tags LIKE :search)";
}

// Add ordering based on filter
switch ($filter) {
    case 'trending':
        $sql .= " ORDER BY like_count DESC, comment_count DESC";
        break;
    case 'most_liked':
        $sql .= " ORDER BY like_count DESC";
        break;
    case 'most_commented':
        $sql .= " ORDER BY comment_count DESC";
        break;
    case 'recent':
    default:
        $sql .= " ORDER BY v.created_at DESC";
        break;
}

$sql .= " LIMIT 30";

$stmt = $pdo->prepare($sql);

if (!empty($search_query)) {
    $stmt->bindValue(':search', "%$search_query%", PDO::PARAM_STR);
}

$stmt->execute();
$explore_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get suggested tags/categories
$tags_stmt = $pdo->query("SELECT DISTINCT tags FROM videos WHERE tags IS NOT NULL AND tags != '' LIMIT 10");
$all_tags = $tags_stmt->fetchAll(PDO::FETCH_ASSOC);
$popular_tags = [];

foreach ($all_tags as $tag) {
    $tag_list = explode(',', $tag['tags']);
    foreach ($tag_list as $t) {
        $t = trim($t);
        if (!empty($t)) {
            if (isset($popular_tags[$t])) {
                $popular_tags[$t]++;
            } else {
                $popular_tags[$t] = 1;
            }
        }
    }
}

arsort($popular_tags);
$popular_tags = array_slice(array_keys($popular_tags), 0, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Explore</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary: #6366f1;
      --primary-light: #a5b4fc;
      --text: #111827;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --bg: #f9fafb;
      --card-bg: #fff;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg);
      margin: 0;
      padding: 0;
    }

    .explore-container {
      margin-left: 240px;
      padding: 30px;
      max-width: 1200px;
    }

    h2 {
      font-size: 28px;
      color: var(--primary);
      margin-bottom: 20px;
      display: flex;
    }

    .search-container {
      display: flex;
      margin-bottom: 20px;
      gap: 10px;
    }

    .search-input {
      flex: 1;
      padding: 12px 20px;
      border: 1px solid var(--border);
      border-radius: 30px;
      font-size: 16px;
      outline: none;
      transition: border-color 0.3s;
    }

    .search-input:focus {
      border-color: var(--primary);
    }

    .search-button {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 30px;
      padding: 0 25px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.3s;
    }

    .search-button:hover {
      background: #4f46e5;
    }

    .filter-container {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      overflow-x: auto;
      padding-bottom: 10px;
    }

    .filter-button {
      background: white;
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 8px 16px;
      cursor: pointer;
      font-size: 14px;
      white-space: nowrap;
      transition: all 0.3s;
    }

    .filter-button:hover, .filter-button.active {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .tags-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
    }

    .tag {
      background: var(--primary-light);
      color: var(--primary);
      border-radius: 20px;
      padding: 6px 12px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .tag:hover {
      background: var(--primary);
      color: white;
    }

    .explore-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
    }

    .explore-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
      transition: transform 0.2s ease;
    }

    .explore-card:hover {
      transform: scale(1.02);
    }

    .explore-thumbnail {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .explore-info {
      padding: 10px 15px;
    }

    .explore-user {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
    }

    .explore-user img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }

    .explore-caption {
      font-size: 16px;
      margin-top: 5px;
      color: var(--text);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .explore-stats {
      display: flex;
      gap: 15px;
      margin-top: 10px;
      color: var(--text-light);
      font-size: 14px;
    }

    .explore-stat {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .no-results {
      text-align: center;
      padding: 50px;
      color: var(--text-light);
      font-size: 18px;
      grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
      .explore-container {
        margin-left: 0;
        padding: 20px;
      }
      
      .explore-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      }
      
      .explore-thumbnail {
        height: 150px;
      }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="explore-container">
  <h2>Explore</h2>
  
  <!-- Search Bar -->
  <form method="GET" action="explore.php" class="search-container">
    <input type="text" name="q" placeholder="Search videos, users, or tags..." class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
    <button type="submit" class="search-button">
      <i class="fas fa-search"></i> Search
    </button>
  </form>
  
  <!-- Filter Options -->
  <div class="filter-container">
    <a href="?q=<?php echo urlencode($search_query); ?>&filter=recent" class="filter-button <?php echo $filter === 'recent' ? 'active' : ''; ?>">
      <i class="fas fa-clock"></i> Recent
    </a>
    <a href="?q=<?php echo urlencode($search_query); ?>&filter=trending" class="filter-button <?php echo $filter === 'trending' ? 'active' : ''; ?>">
      <i class="fas fa-fire"></i> Trending
    </a>
    <a href="?q=<?php echo urlencode($search_query); ?>&filter=most_liked" class="filter-button <?php echo $filter === 'most_liked' ? 'active' : ''; ?>">
      <i class="fas fa-thumbs-up"></i> Most Liked
    </a>
    <a href="?q=<?php echo urlencode($search_query); ?>&filter=most_commented" class="filter-button <?php echo $filter === 'most_commented' ? 'active' : ''; ?>">
      <i class="fas fa-comment"></i> Most Comments
    </a>
  </div>
  
  <!-- Popular Tags -->
  <?php if (!empty($popular_tags)): ?>
  <div class="tags-container">
    <span>Popular Tags:</span>
    <?php foreach ($popular_tags as $tag): ?>
      <a href="?q=<?php echo urlencode($tag); ?>" class="tag">#<?php echo htmlspecialchars($tag); ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  
  <!-- Results Grid -->
  <div class="explore-grid">
    <?php if (empty($explore_items)): ?>
      <div class="no-results">
        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px;"></i>
        <h3>No videos found</h3>
        <p>Try different search terms or filters</p>
      </div>
    <?php else: ?>
      <?php foreach ($explore_items as $item): ?>
        <div class="explore-card">
          <a href="view_video.php?id=<?php echo $item['id']; ?>">
                       <img src="<?php echo htmlspecialchars($item['thumbnail']); ?>" class="explore-thumbnail" alt="Video thumbnail">
          </a>
          <div class="explore-info">
            <div class="explore-caption"><?php echo htmlspecialchars($item['caption']); ?></div>
            <div class="explore-user">
              <img src="<?php echo htmlspecialchars($item['profile_pic']); ?>" alt="User Profile">
              <span><?php echo htmlspecialchars($item['username']); ?></span>
            </div>
            <div class="explore-stats">
              <div class="explore-stat">
                <i class="fas fa-thumbs-up"></i> <?php echo $item['like_count']; ?>
              </div>
              <div class="explore-stat">
                <i class="fas fa-comment"></i> <?php echo $item['comment_count']; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
