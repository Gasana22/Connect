<?php
session_start();
require 'db.php';
require 'helpers.php';
require 'sidebar.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Pagination setup
$videos_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $videos_per_page;

$business_categories = [
    'Entrepreneurship', 'Startups', 'Finance', 'Marketing', 'Leadership',
    'Management', 'Sales', 'Investing', 'E-commerce', 'Business Strategy',
    'Productivity', 'Career Development', 'Networking', 'Innovation', 'Consulting'
];

// Get active ads from database
$currentDate = date('Y-m-d H:i:s');
$ads = $pdo->query("
    SELECT * FROM ads 
    WHERE is_active = 1 
    AND start_date <= '$currentDate' 
    AND end_date >= '$currentDate'
    AND (max_impressions = 0 OR max_impressions > (
        SELECT COUNT(*) FROM ad_impressions WHERE ad_id = ads.id
    ))
")->fetchAll();

// Cast to int just to be safe
$videos_per_page = (int)$videos_per_page;
$offset = (int)$offset;

// Fetch "For You" Videos with engagement data
$sql = "
    SELECT v.*, u.username, u.profile_pic,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
           (SELECT COUNT(*) FROM follows WHERE follower_id = ? AND following_id = v.user_id) AS is_following,
           (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) AS is_liked,
           v.category AS video_category  -- Make sure your videos table has a category column
    FROM videos v
    JOIN users u ON v.user_id = u.id
    ORDER BY v.created_at DESC
    LIMIT $videos_per_page OFFSET $offset
";
$forYouStmt = $pdo->prepare($sql);
$forYouStmt->execute([$user_id, $user_id]);
$forYouVideos = $forYouStmt->fetchAll();

// Fetch "Following" Videos
$followingVideos = [];
if ($user_id) {
    $sql = "
        SELECT v.*, u.username, u.profile_pic,
               (SELECT COUNT(*) FROM likes WHERE video_id = v.id) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE video_id = v.id) AS comment_count,
               1 AS is_following,
               (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) AS is_liked
        FROM videos v
        JOIN users u ON v.user_id = u.id
        JOIN follows f ON v.user_id = f.following_id
        WHERE f.follower_id = ?
        ORDER BY v.created_at DESC
        LIMIT $videos_per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $followingVideos = $stmt->fetchAll();
}

// Total counts for pagination
$total_for_you = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();

if ($user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM videos v JOIN follows f ON v.user_id = f.following_id WHERE f.follower_id = ?");
    $stmt->execute([$user_id]);
    $total_following = $stmt->fetchColumn();
} else {
    $total_following = 0;
}

// Check for new notifications
if ($user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_notifications = $stmt->fetchColumn();
} else {
    $unread_notifications = 0;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect | Video Feed</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #a5b4fc;
            --text: #1e293b;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --rounded-sm: 0.25rem;
            --rounded: 0.5rem;
            --rounded-lg: 1rem;
            --rounded-xl: 1.5rem;
            --rounded-full: 9999px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Modern scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--border-light);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Main layout */
        .container {
      display: flex;
      min-height: 100vh;
    }

        .feed-container {
             flex:1;
     padding:2rem;
     margin-left: 240px
        }

        /* Floating action button */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: var(--rounded-full);
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            z-index: 50;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .fab:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .fab i {
            font-size: 1.5rem;
        }

        /* Tabs - Modern pill style */
        .feed-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            background-color: var(--border-light);
            padding: 0.25rem;
            border-radius: var(--rounded-full);
        }

        .feed-tabs .tab-btn {
            background: none;
            border: none;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            border-radius: var(--rounded-full);
            transition: all 0.2s ease;
            flex: 1;
            text-align: center;
        }

        .feed-tabs .tab-btn.active {
            background-color: var(--card-bg);
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .feed-tabs .tab-btn:hover:not(.active) {
            background-color: rgba(99, 102, 241, 0.1);
        }

        /* Video Cards - Modern glassmorphism style */
        .video-feed {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .video-card {
            background-color: var(--card-bg);
            border-radius: var(--rounded-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            border: 1px solid var(--border);
        }

        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .video-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .video-card:hover::before {
            opacity: 1;
        }

        .video-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .user-info:hover {
            opacity: 0.9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--rounded-full);
            object-fit: cover;
            border: 2px solid var(--primary);
            transition: transform 0.2s ease;
        }

        .user-info:hover .user-avatar {
            transform: scale(1.05);
        }

        .username {
            font-weight: 600;
            color: var(--text);
            font-size: 0.9375rem;
        }

        .follow-btn {
            padding: 0.375rem 0.875rem;
            border-radius: var(--rounded-full);
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .follow-btn.following {
            background-color: var(--primary);
            color: white;
        }

        .follow-btn i {
            font-size: 0.75rem;
        }

        .video-player {
            width: 100%;
            background-color: #000;
            position: relative;
        }

        .video-player video {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            display: block;
        }

        .video-actions {
            padding: 0.75rem 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.2s ease;
            padding: 0.375rem 0.5rem;
            border-radius: var(--rounded-sm);
        }

        .action-btn:hover {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .action-btn.liked {
            color: var(--danger);
        }

        .action-btn.liked:hover {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .action-btn i {
            font-size: 1.125rem;
        }

        .video-caption {
            margin-top: 0.5rem;
            line-height: 1.5;
            font-size: 0.9375rem;
            padding: 0 0.25rem;
        }

        .hashtag {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .hashtag:hover {
            text-decoration: underline;
            opacity: 0.9;
        }

        /* Comments Section - Modern slide-up panel */
        .comments-section {
            padding: 0 1rem 1rem;
            border-top: 1px solid var(--border-light);
            margin-top: 0.5rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
        }

        .comments-section.expanded {
            max-height: 500px;
            padding: 1rem 1rem;
        }

        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1rem;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .comment {
            display: flex;
            gap: 0.75rem;
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--rounded-full);
            object-fit: cover;
            flex-shrink: 0;
        }

        .comment-content {
            flex-grow: 1;
        }

        .comment-user {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text);
            text-decoration: none;
            display: block;
            margin-bottom: 0.125rem;
        }

        .comment-user:hover {
            color: var(--primary);
        }

        .comment-text {
            font-size: 0.875rem;
            color: var(--text);
        }

        .comment-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .comment-form input {
            flex-grow: 1;
            padding: 0.625rem 0.875rem;
            border-radius: var(--rounded-full);
            border: 1px solid var(--border);
            font-size: 0.875rem;
            background-color: var(--bg);
            transition: all 0.2s ease;
        }

        .comment-form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .comment-form button {
            padding: 0 1rem;
            border-radius: var(--rounded-full);
            background-color: var(--primary);
            color: white;
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .comment-form button:hover {
            background-color: var(--primary-hover);
        }

        .comment-form button i {
            font-size: 1rem;
        }

        /* Pagination - Modern minimal style */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 0.875rem;
            border-radius: var(--rounded);
            text-decoration: none;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .page-link:hover {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .page-link.active {
            background-color: var(--primary);
            color: white;
        }

        .page-link i {
            font-size: 0.75rem;
        }

        /* Empty State - Modern illustration style */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
            background-color: var(--card-bg);
            border-radius: var(--rounded-lg);
            box-shadow: var(--shadow-sm);
            border: 1px dashed var(--border);
            margin-top: 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--border);
            opacity: 0.8;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .empty-state p {
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .empty-state .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background-color: var(--primary);
            color: white;
            border-radius: var(--rounded-full);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .empty-state .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .empty-state .btn i {
            font-size: 0.875rem;
        }

        .empty-state .btn.secondary {
            background-color: var(--bg);
            color: var(--text);
            margin-left: 0.75rem;
        }

        .empty-state .btn.secondary:hover {
            background-color: var(--border);
        }

        /* Floating video controls */
        .video-controls {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 10;
        }

        .video-control-btn {
            width: 36px;
            height: 36px;
            border-radius: var(--rounded-full);
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .video-control-btn:hover {
            background-color: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }

        .video-control-btn i {
            font-size: 1rem;
        }

        /* Loading skeleton */
        .skeleton {
            background-color: var(--border-light);
            border-radius: var(--rounded);
            position: relative;
            overflow: hidden;
        }

        .skeleton::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Responsive Design */

        @media (max-width: 768px) {
        

            .feed-tabs {
                border-radius: var(--rounded);
                padding: 0.25rem;
            }

            .feed-tabs .tab-btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.8125rem;
            }

            .video-player video {
                max-height: 400px;
            }

            .fab {
                bottom: 1.5rem;
                right: 1.5rem;
                width: 50px;
                height: 50px;
            }
        }

        @media (max-width: 480px) {
            .video-header {
                padding: 0.75rem;
            }

            .video-actions {
                padding: 0.5rem;
            }

            .action-buttons {
                gap: 0.75rem;
            }

            .user-avatar {
                width: 36px;
                height: 36px;
            }

            .video-player video {
                max-height: 60vh;
            }

            .empty-state {
                padding: 2rem 1rem;
            }

            .empty-state .btn {
                padding: 0.5rem 1rem;
                font-size: 0.8125rem;
            }

            .empty-state .btn.secondary {
                margin-left: 0.5rem;
            }
        }
/* Enhanced Feed Page Styles - Complete CSS */

.filter-toggle {
    background: #f3f4f6;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.filter-toggle:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
}

.filter-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

/* Filters Panel */
.filters-panel {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    z-index: 200;
    animation: filtersPanelSlide 0.3s ease;
}

@keyframes filtersPanelSlide {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filter-section {
    margin-bottom: 20px;
}

.filter-section h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.filter-chip {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 6px 12px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-chip:hover {
    background: #e5e7eb;
}

.filter-chip.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.sort-select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: white;
}

.filter-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #2563eb;
}

/* Enhanced Feed Tabs */
.feed-tabs {
    display: flex;
    gap: 8px;
    background: white;
    border-radius: 12px;
    padding: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.feed-tabs::-webkit-scrollbar {
    display: none;
}

.tab-btn {
    background: transparent;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    position: relative;
}

.tab-btn:hover {
    background: #f3f4f6;
}

.tab-btn.active {
    background: #3b82f6;
    color: white;
}

.tab-badge {
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
}

.tab-btn.active .tab-badge {
    background: rgba(255,255,255,0.3);
}

.live-indicator {
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    display: inline-block;
}

.live-indicator.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Loading Indicator */
.loading-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    gap: 16px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Feed Container */
.feed-container {
    position: relative;
}

/* Video Grid */
.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

/* Enhanced Video Card */
.video-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
}

.video-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.video-thumbnail-container {
    position: relative;
    aspect-ratio: 9/16;
    background: #000;
    overflow: hidden;
}

.video-player {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0;
}

.video-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.3) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.video-card:hover .video-overlay {
    opacity: 1;
}

.play-btn {
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.play-btn:hover {
    background: white;
    transform: scale(1.1);
}

.play-btn i {
    font-size: 24px;
    color: #374151;
    margin-left: 4px;
}

.quality-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.video-info-overlay {
    position: absolute;
    bottom: 12px;
    left: 12px;
    right: 12px;
    display: flex;
    justify-content: space-between;
    align-items: end;
}

.video-duration,
.video-views {
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.live-badge {
    background: #ef4444;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Video Controls */
.video-controls {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    padding: 20px 16px 16px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.video-card:hover .video-controls {
    opacity: 1;
}

.video-control-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.video-control-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

.video-control-btn i {
    color: white;
    font-size: 14px;
}

/* Video Content */
.video-content {
    padding: 16px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.user-avatar-link {
    position: relative;
    flex-shrink: 0;
    display: inline-block;
}

.user-avatar-link:hover {
    transform: scale(1.05);
}

.avatar-container {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.verified-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
}

.online-indicator {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
    width: 12px;
    height: 12px;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.username {
    font-weight: 600;
    color: #111827;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.username:hover {
    color: #3b82f6;
}

.verified-icon {
    color: #3b82f6;
    font-size: 14px;
}

.user-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.location {
    display: flex;
    align-items: center;
    gap: 4px;
}

.follow-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.follow-btn:hover {
    background: #2563eb;
    transform: scale(1.05);
}

.follow-btn.following {
    background: #f3f4f6;
    color: #374151;
}

.follow-btn.following:hover {
    background: #ef4444;
    color: white;
}

/* Video Description */
.video-description {
    margin-bottom: 16px;
}

.description-text {
    color: #374151;
    line-height: 1.5;
    margin: 0 0 12px 0;
}

.hashtags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.hashtag {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.hashtag:hover {
    text-decoration: underline;
}

.category-badge {
    background: #e0e7ff;
    color: #4f46e5;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
}

.category-badge i {
    font-size: 10px;
}

.filter-chip.active[data-filter="category"] {
    background: #4f46e5;
    color: white;
}

/* Video Stats */
.video-stats {
    border-top: 1px solid #f3f4f6;
    padding-top: 16px;
}

.stats-row {
    display: flex;
    justify-content: space-around;
    margin-bottom: 12px;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border-radius: 8px;
}

.action-btn:hover {
    background: #f3f4f6;
    transform: scale(1.05);
}

.action-btn i {
    font-size: 18px;
    color: #6b7280;
    transition: all 0.3s ease;
}

.action-btn.liked i,
.action-btn.bookmarked i {
    color: #ef4444;
}

.action-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.like-count,
.comment-count {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

/* Comments Section */
.comments-section {
    border-top: 1px solid #f3f4f6;
    padding-top: 16px;
    transition: max-height 0.3s ease;
    max-height: 0;
    overflow: hidden;
}

.comments-section.expanded {
    max-height: 600px !important;
}

.comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.comments-header h4 {
    margin: 0;
    font-size: 16px;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}

.comment-sort {
    padding: 4px 8px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 12px;
    background: white;
}

.comment-form {
    margin-bottom: 20px;
}

.comment-input-container {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.comment-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.comment-user-info {
    display: inline-block;
    transition: transform 0.2s ease;
}

.comment-user-info:hover {
    transform: scale(1.05);
}

.comment-input-wrapper {
    flex: 1;
    position: relative;
}

.comment-input {
    width: 100%;
    padding: 8px 40px 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 14px;
    resize: none;
    transition: all 0.3s ease;
}

.comment-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.comment-actions {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 4px;
}

.emoji-btn,
.comment-submit {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.emoji-btn:hover,
.comment-submit:hover:not(:disabled) {
    background: #f3f4f6;
}

.comment-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Comments List */
.comments-list {
    max-height: 400px;
    overflow-y: auto;
}

.comment {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}

.comment-content {
    flex: 1;
    min-width: 0;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.comment-username {
    font-weight: 600;
    color: #111827;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s ease;
}

.comment-username:hover {
    color: #3b82f6;
}

.comment-time {
    font-size: 12px;
    color: #6b7280;
}

.comment-text {
    color: #374151;
    line-height: 1.4;
    margin: 0 0 8px 0;
    font-size: 14px;
}

/* Enhanced FAB */
.fab-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.fab-menu {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 12px;
}

.fab-menu:not(.open) {
    pointer-events: none;
}

.fab-item {
    background: white;
    border: none;
    border-radius: 50%;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    text-decoration: none;
    color: #374151;
    transform: scale(0) translateY(20px);
    opacity: 0;
}

.fab-item:hover {
    transform: scale(1.1) translateY(0);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.main-fab {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 50%;
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    transition: all 0.3s ease;
    font-size: 24px;
}

.main-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
}

/* Pagination */
.pagination-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    margin-top: 40px;
}

.pagination {
    display: flex;
    gap: 8px;
    align-items: center;
}

.page-link {
    background: white;
    border: 1px solid #e5e7eb;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.page-link:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.page-link.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 12px 0;
    color: #374151;
    font-size: 24px;
}

.empty-state p {
    margin: 0 0 24px 0;
    font-size: 16px;
    line-height: 1.5;
}

.empty-state-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn:hover {
    background: #2563eb;
    transform: scale(1.05);
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.toast {
    background: white;
    border-radius: 8px;
    padding: 12px 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast-success {
    border-left: 4px solid #10b981;
}

.toast-error {
    border-left: 4px solid #ef4444;
}

.toast i {
    font-size: 18px;
}

.toast-success i {
    color: #10b981;
}

.toast-error i {
    color: #ef4444;
}

/* Ad Card Styles */
.ad-card {
    border: 2px solid var(--primary);
    position: relative;
}

.ad-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

.ad-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.ad-overlay {
    position: absolute;
    bottom: 12px;
    left: 12px;
    right: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sponsored-badge {
    background: var(--primary);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.ad-duration {
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.ad-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
}

.ad-cta {
    margin-top: 12px;
    display: inline-block;
    width: 100%;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-filter-section {
        position: static;
        margin: 10px;
    }
    
    .search-container {
        flex-direction: column;
        gap: 12px;
    }
    
    .filters-panel {
        width: 100%;
        position: relative;
        top: 0;
        right: auto;
        box-shadow: none;
        border: 1px solid #e5e7eb;
    }
    
    .video-grid {
        grid-template-columns: 1fr;
        gap: 16px;
        padding: 0 10px;
    }
    
    .feed-tabs {
        margin: 10px;
        padding: 6px;
    }
    
    .tab-btn {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .video-card {
        border-radius: 12px;
    }
    
    .video-content {
        padding: 12px;
    }
    
    .fab-container {
        bottom: 10px;
        right: 10px;
    }
}

@media (max-width: 480px) {
    .search-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .stats-row {
        justify-content: space-between;
        padding: 0 8px;
    }
    
    .action-btn {
        padding: 6px 8px;
    }
    
    .action-btn i {
        font-size: 16px;
    }
    
    .action-label {
        font-size: 11px;
    }
    
    .comments-section {
        padding-top: 12px;
    }
    
    .comment-input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

/* Animations */
@keyframes floatUp {
    0% { transform: translateY(0) scale(1); opacity: 1; }
    100% { transform: translateY(-100px) scale(0.5); opacity: 0; }
}

.video-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* Focus visible for accessibility */
.tab-btn:focus-visible,
.action-btn:focus-visible,
.video-control-btn:focus-visible,
.follow-btn:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Selection styling */
::selection {
    background: #3b82f6;
    color: white;
}

::-moz-selection {
    background: #3b82f6;
    color: white;
}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
        
        <!-- Advanced Filters Panel -->
        <div class="filters-panel" id="filtersPanel" style="display: none;">
            <div class="filter-section">
    <h4><i class="fas fa-briefcase"></i> Business Categories</h4>
    <div class="filter-chips">
        <?php foreach ($business_categories as $category): ?>
            <button class="filter-chip" data-filter="category" data-value="<?= strtolower(str_replace(' ', '-', $category)) ?>">
                <?= $category ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>
            
            <div class="filter-section">
                <h4><i class="fas fa-clock"></i> Duration</h4>
                <div class="filter-chips">
                    <button class="filter-chip" data-filter="duration" data-value="short">Under 1 min</button>
                    <button class="filter-chip" data-filter="duration" data-value="medium">1-5 mins</button>
                    <button class="filter-chip" data-filter="duration" data-value="long">5+ mins</button>
                </div>
            </div>
            
            <div class="filter-section">
                <h4><i class="fas fa-calendar"></i> Upload Date</h4>
                <div class="filter-chips">
                    <button class="filter-chip" data-filter="date" data-value="today">Today</button>
                    <button class="filter-chip" data-filter="date" data-value="week">This Week</button>
                    <button class="filter-chip" data-filter="date" data-value="month">This Month</button>
                    <button class="filter-chip" data-filter="date" data-value="year">This Year</button>
                </div>
            </div>
            
            <div class="filter-section">
                <h4><i class="fas fa-sort"></i> Sort By</h4>
                <select class="sort-select" id="sortBy">
                    <option value="latest">Latest</option>
                    <option value="popular">Most Popular</option>
                    <option value="views">Most Viewed</option>
                    <option value="likes">Most Liked</option>
                    <option value="comments">Most Commented</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn-secondary" id="clearFilters">
                    <i class="fas fa-undo"></i> Clear All
                </button>
                <button class="btn-primary" id="applyFilters">
                    <i class="fas fa-check"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>

    <div class="feed-container">
        <!-- Enhanced pill-style tabs with badges -->
        <div class="feed-tabs">
            <button class="tab-btn active" onclick="showTab('forYou')">
                <i class="fas fa-compass"></i> 
                <span>For You</span>
                <span class="tab-badge" id="forYouBadge"></span>
            </button>
            <button class="tab-btn" onclick="showTab('following')">
                <i class="fas fa-user-friends"></i> 
                <span>Following</span>
                <span class="tab-badge" id="followingBadge"></span>
            </button>
            <button class="tab-btn" onclick="showTab('trending')">
                <i class="fas fa-fire"></i> 
                <span>Trending</span>
                <span class="tab-badge" id="trendingBadge"></span>
            </button>
            <button class="tab-btn" onclick="showTab('live')">
                <i class="fas fa-video"></i> 
                <span>Live</span>
                <span class="live-indicator pulse"></span>
            </button>
        </div>

        <!-- Loading Indicator -->
        <div class="loading-indicator" id="loadingIndicator" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Loading awesome videos...</p>
        </div>

       <!-- For You Tab -->
<div id="forYou" class="video-feed">
    <?php if (empty($forYouVideos)): ?>
        <div class="empty-state">
            <i class="fas fa-video-slash"></i>
            <h3>No videos found</h3>
            <p>Discover amazing content or share your own videos with the community</p>
            <?php if ($user_id): ?>
                <div class="empty-state-actions">
                </div>
            <?php else: ?>
                <a href="login.php" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login to View
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="video-grid" id="videoGrid">
            <?php 
            $video_count = 0;
            foreach ($forYouVideos as $video): 
                $video_count++;
            ?>
                <!-- Enhanced Video Card -->
                <div class="video-card" data-video-id="<?= $video['id'] ?>" 
                     data-category="<?= isset($video['video_category']) ? strtolower(str_replace(' ', '-', $video['video_category'])) : '' ?>" 
                     data-duration="<?= $video['duration'] ?? 0 ?>">
                    <div class="video-thumbnail-container">
                        <!-- Video Player with Enhanced Controls -->
                        <video class="video-player" 
                               poster="<?= $video['thumbnail'] ?>" 
                               preload="metadata"
                               data-video-id="<?= $video['id'] ?>"
                               loop
                               muted>
                            <source src="<?= $video['video_url'] ?>" type="video/mp4">
                        </video>
                        
                        <!-- Video Overlay with Play Button -->
                        <div class="video-overlay">
                            <button class="play-btn" aria-label="Play video">
                                <i class="fas fa-play"></i>
                            </button>
                            
                            <!-- Video Quality Badge -->
                            <?php if (isset($video['quality']) && $video['quality'] === '4K'): ?>
                                <span class="quality-badge">4K</span>
                            <?php elseif (isset($video['quality']) && $video['quality'] === 'HD'): ?>
                                <span class="quality-badge">HD</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Video Info Overlay -->
                        <div class="video-info-overlay">
                            <span class="video-duration"><?= isset($video['duration']) ? formatDuration($video['duration']) : '0:00' ?></span>
                            <span class="video-views">
                                <i class="fas fa-eye"></i> <?= formatNumber($video['views'] ?? 0) ?>
                            </span>
                            <?php if (isset($video['is_live']) && $video['is_live']): ?>
                                <span class="live-badge">
                                    <span class="live-indicator"></span> LIVE
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Enhanced Video Controls -->
                        <div class="video-controls">
                            <div class="quick-actions">
                                <button class="video-control-btn like-btn <?= isset($video['is_liked']) && $video['is_liked'] ? 'liked' : '' ?>" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="Like video">
                                    <i class="fas fa-heart"></i>
                                    <span class="quick-count"><?= formatNumber($video['likes'] ?? 0) ?></span>
                                </button>
                                
                                <button class="video-control-btn comment-btn" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="View comments">
                                    <i class="fas fa-comment"></i>
                                    <span class="quick-count"><?= formatNumber($video['comments_count'] ?? 0) ?></span>
                                </button>
                                
                                <button class="video-control-btn share-btn" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="Share video">
                                    <i class="fas fa-share"></i>
                                </button>
                                
                                <button class="video-control-btn bookmark-btn <?= isset($video['is_bookmarked']) && $video['is_bookmarked'] ? 'bookmarked' : '' ?>" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="Bookmark video">
                                    <i class="fas fa-bookmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Video Content -->
                    <div class="video-content">
                        <!-- User Info Section - Modified for better profile linking -->
                        <div class="user-info">
                            <a href="profile.php?id=<?= $video['user_id'] ?>" class="user-avatar-link" title="View <?= htmlspecialchars($video['username']) ?>'s profile">
                                <div class="avatar-container">
                                    <img src="<?= $video['user_avatar'] ?>" 
                                         class="user-avatar" 
                                         alt="<?= $video['username'] ?>"
                                         loading="lazy">
                                    <?php if (isset($video['is_verified']) && $video['is_verified']): ?>
                                        <span class="verified-badge">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (isset($video['user_is_online']) && $video['user_is_online']): ?>
                                        <span class="online-indicator"></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            
                            <div class="user-details">
                                <a href="profile.php?id=<?= $video['user_id'] ?>" class="username" title="View <?= htmlspecialchars($video['username']) ?>'s profile">
                                    <?= htmlspecialchars($video['display_name'] ?? $video['username']) ?>
                                    <?php if (isset($video['is_verified']) && $video['is_verified']): ?>
                                        <i class="fas fa-check-circle verified-icon"></i>
                                    <?php endif; ?>
                                </a>
                                <div class="user-meta">
                                    <span class="upload-time" title="<?= date('F j, Y g:i A', strtotime($video['created_at'])) ?>">
                                        <?= timeAgo($video['created_at']) ?>
                                    </span>
                                    <?php if (isset($video['location']) && $video['location']): ?>
                                        <span class="location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?= htmlspecialchars($video['location']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Follow Button -->
                            <?php if (isset($user_id) && $user_id && $user_id !== $video['user_id']): ?>
                                <button class="follow-btn <?= isset($video['is_following']) && $video['is_following'] ? 'following' : '' ?>" 
                                        data-user-id="<?= $video['user_id'] ?>"
                                        aria-label="<?= isset($video['is_following']) && $video['is_following'] ? 'Unfollow' : 'Follow' ?> <?= $video['username'] ?>">
                                    <i class="fas fa-<?= isset($video['is_following']) && $video['is_following'] ? 'check' : 'plus' ?>"></i>
                                    <span><?= isset($video['is_following']) && $video['is_following'] ? 'Following' : 'Follow' ?></span>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Video Description -->
                        <div class="video-description">
                            <p class="description-text">
                                <?= processDescription($video['description'] ?? '') ?>
                            </p>
                            
                            <!-- Hashtags -->
                            <?php if (!empty($video['hashtags'])): ?>
                                <div class="hashtags">
                                    <?php foreach ($video['hashtags'] as $hashtag): ?>
                                        <a href="/hashtag/<?= urlencode($hashtag) ?>" class="hashtag">
                                            #<?= htmlspecialchars($hashtag) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Category Badge -->
                            <?php if (!empty($video['video_category'])): ?>
                                <span class="category-badge">
                                    <i class="fas fa-tag"></i>
                                    <?= htmlspecialchars($video['video_category']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Enhanced Video Stats -->
                        <div class="video-stats">
                            <div class="stats-row">
                                <button class="action-btn like-btn <?= isset($video['is_liked']) && $video['is_liked'] ? 'liked' : '' ?>" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="Like video">
                                    <i class="fas fa-heart"></i>
                                    <span class="like-count"><?= formatNumber($video['likes'] ?? 0) ?></span>
                                    <span class="action-label">Like</span>
                                </button>
                                
                                <button class="action-btn comment-toggle" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="View comments">
                                    <i class="fas fa-comment"></i>
                                    <span class="comment-count"><?= formatNumber($video['comments_count'] ?? 0) ?></span>
                                    <span class="action-label">Comment</span>
                                </button>
                                
                                <button class="action-btn share-btn" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="Share video">
                                    <i class="fas fa-share"></i>
                                    <span class="action-label">Share</span>
                                </button>
                                
                                <button class="action-btn bookmark-btn <?= isset($video['is_bookmarked']) && $video['is_bookmarked'] ? 'bookmarked' : '' ?>" 
                                        data-video-id="<?= $video['id'] ?>"
                                        aria-label="Bookmark video">
                                    <i class="fas fa-bookmark"></i>
                                    <span class="action-label">Save</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Comments Section -->
                        <div class="comments-section" id="comments-<?= $video['id'] ?>" style="max-height: 0; overflow: hidden;">
                            <div class="comments-header">
                                <h4>
                                    <i class="fas fa-comments"></i>
                                    Comments (<?= formatNumber($video['comments_count'] ?? 0) ?>)
                                </h4>
                                <div class="comments-controls">
                                    <select class="comment-sort">
                                        <option value="newest">Newest</option>
                                        <option value="oldest">Oldest</option>
                                        <option value="popular">Most Popular</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Comment Form -->
                            <?php if (isset($user_id) && $user_id): ?>
                                <form class="comment-form" data-video-id="<?= $video['id'] ?>">
                                    <div class="comment-input-container">
                                        <img src="<?= $current_user['avatar'] ?? '/default-avatar.jpg' ?>" 
                                             class="comment-user-avatar" 
                                             alt="Your avatar">
                                        <div class="comment-input-wrapper">
                                            <input type="text" 
                                                   placeholder="Add a comment..." 
                                                   class="comment-input"
                                                   maxlength="500">
                                            <div class="comment-actions">
                                                <button type="button" class="emoji-btn">
                                                    <i class="fas fa-smile"></i>
                                                </button>
                                                <button type="submit" class="comment-submit" disabled>
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="comment-login-prompt">
                                    <p>
                                        <a href="login.php">Login</a> to leave a comment
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Comments List -->
                            <div class="comments-list">
                                <?php if (!empty($video['comments'])): ?>
                                    <?php foreach (array_slice($video['comments'], 0, 3) as $comment): ?>
                                        <div class="comment" data-comment-id="<?= $comment['id'] ?>">
                                            <a href="profile.php?id=<?= $comment['user_id'] ?>" class="comment-user-info" title="View <?= htmlspecialchars($comment['username']) ?>'s profile">
                                                <img src="<?= $comment['user_avatar'] ?>" 
                                                     class="comment-user-avatar" 
                                                     alt="<?= $comment['username'] ?>">
                                            </a>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <a href="profile.php?id=<?= $comment['user_id'] ?>" class="comment-username" title="View <?= htmlspecialchars($comment['username']) ?>'s profile">
                                                        <?= htmlspecialchars($comment['display_name']) ?>
                                                    </a>
                                                    <span class="comment-time">
                                                        <?= timeAgo($comment['created_at']) ?>
                                                    </span>
                                                </div>
                                                <p class="comment-text">
                                                    <?= processDescription($comment['text']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-comments">
                                        <i class="fas fa-comment-slash"></i>
                                        <p>No comments yet</p>
                                        <small>Be the first to comment!</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php 
                // Check if it's time to show an ad
                if ($video_count % $ad_frequency === 0 && !empty($ads)) {
                    $random_ad = $ads[array_rand($ads)];
                    ?>
                    <!-- Ad Card -->
                    <div class="video-card ad-card" data-ad-id="<?= $random_ad['id'] ?>">
                        <div class="video-thumbnail-container">
                            <img src="<?= $random_ad['image_url'] ?>" class="ad-image" alt="<?= htmlspecialchars($random_ad['title']) ?>">
                            <div class="ad-overlay">
                                <span class="sponsored-badge">Sponsored</span>
                                <span class="ad-duration"><?= formatDuration($random_ad['duration']) ?></span>
                            </div>
                        </div>
                        
                        <div class="video-content">
                            <div class="user-info">
                                <div class="avatar-container">
                                    <i class="fas fa-ad ad-icon"></i>
                                </div>
                                <div class="user-details">
                                    <span class="username"><?= htmlspecialchars($random_ad['sponsor']) ?></span>
                                    <div class="user-meta">
                                        <span>Promoted</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="video-description">
                                <h4><?= htmlspecialchars($random_ad['title']) ?></h4>
                                <p class="description-text"><?= htmlspecialchars($random_ad['description']) ?></p>
                                <a href="<?= $random_ad['target_url'] ?>" target="_blank" class="btn btn-primary ad-cta">
                                    Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            endforeach; 
            ?>
        </div>

        <!-- Enhanced Pagination -->
        <div class="pagination-container">
            <div class="pagination">
                <?php if (isset($current_page) && $current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Prev
                    </a>
                <?php endif; ?>

                <?php
                if (isset($total_for_you) && isset($videos_per_page) && isset($current_page)) {
                    $total_pages = ceil($total_for_you / $videos_per_page);
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i == $current_page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor;
                } ?>

                <?php if (isset($current_page) && isset($videos_per_page) && isset($total_for_you) && ($current_page * $videos_per_page < $total_for_you)): ?>
                    <a href="?page=<?= $current_page + 1 ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
        <!-- Following Tab -->
        <div id="following" class="video-feed" style="display:none;">
            <?php if (empty($followingVideos)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>
                        <?= isset($user_id) && $user_id 
                            ? "You're not following anyone yet" 
                            : "Login to see following" ?>
                    </h3>
                    <p>
                        <?= isset($user_id) && $user_id 
                            ? "Discover and follow creators to see their videos here" 
                            : "Sign in to view content from creators you follow" ?>
                    </p>
                    <?php if (isset($user_id) && $user_id): ?>
                        <div class="empty-state-actions">
                            <a href="explore.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Explore Creators
                            </a>
                            <a href="suggested-users.php" class="btn btn-secondary">
                                <i class="fas fa-users"></i> Suggested Users
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn">
                            <i class="fas fa-sign-in-alt"></i> Login Now
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="video-grid">
                    <?php foreach ($followingVideos as $video): ?>
                        <!-- Same enhanced video card structure as above -->
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Trending Tab -->
        <div id="trending" class="video-feed" style="display:none;">
            <?php if (empty($trendingVideos)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>Trending Coming Soon!</h3>
                    <p>We're working on bringing you the hottest content based on engagement and popularity</p>
                    <div class="empty-state-actions">
                        <button class="btn btn-primary" id="notifyTrending">
                            <i class="fas fa-bell"></i> Notify Me
                        </button>
                        <a href="explore.php" class="btn btn-secondary">
                            <i class="fas fa-compass"></i> Explore Instead
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="video-grid">
                    <?php foreach ($trendingVideos as $video): ?>
                        <!-- Same enhanced video card structure as above -->
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Live Tab -->
        <div id="live" class="video-feed" style="display:none;">
            <?php if (empty($liveStreams)): ?>
                <div class="empty-state">
                    <i class="fas fa-video"></i>
                    <h3>No Live Streams Right Now</h3>
                    <p>Check back later for live content from your favorite creators</p>
                    <div class="empty-state-actions">
                        <?php if (isset($user_id) && $user_id): ?>
                            <a href="live.php" class="btn btn-primary">
                                <i class="fas fa-broadcast-tower"></i> Go Live
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn">
                                <i class="fas fa-sign-in-alt"></i> Login to Go Live
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="video-grid">
                    <?php foreach ($liveStreams as $stream): ?>
                        <!-- Live stream cards would go here -->
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Enhanced Floating Action Menu -->
    <?php if (isset($user_id) && $user_id): ?>
        <div class="fab-container">
            <div class="fab-menu" id="fabMenu">
                <a href="upload.php" class="fab-item" data-tooltip="Upload Video">
                    <i class="fas fa-upload"></i>
                </a>
                
            </div>
            <button class="main-fab" id="mainFab">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toastContainer"></div>
</div>

<script>
// Enhanced JavaScript with all functionality
document.addEventListener('DOMContentLoaded', () => {
    // Initialize all managers
    window.feedManager = new FeedManager();
    window.interactionManager = new InteractionManager();
    window.fabManager = new FABManager();
});

// Feed Manager Class
class FeedManager {
    constructor() {
        this.currentTab = 'forYou';
        this.isLoading = false;
        this.filters = {
            category: [],
            duration: null,
            date: null,
            sort: 'latest'
        };
        this.searchQuery = '';
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupSearchFunctionality();
        this.setupFilterFunctionality();
        this.loadInitialContent();
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const onclick = e.currentTarget.getAttribute('onclick');
                if (onclick) {
                    const tabId = onclick.match(/'([^']+)'/)[1];
                    this.switchTab(tabId);
                }
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
    }

    setupSearchFunctionality() {
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.getElementById('searchClear');

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                
                // Show/hide clear button
                if (searchClear) {
                    searchClear.style.display = query ? 'block' : 'none';
                }
                
                // Debounced search
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, 300);
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchClear.style.display = 'none';
                this.performSearch('');
            });
        }
    }

    setupFilterFunctionality() {
        const filterToggle = document.getElementById('filterToggle');
        const filtersPanel = document.getElementById('filtersPanel');
        const applyFilters = document.getElementById('applyFilters');
        const clearFilters = document.getElementById('clearFilters');

        if (filterToggle && filtersPanel) {
            filterToggle.addEventListener('click', () => {
                filtersPanel.style.display = filtersPanel.style.display === 'none' ? 'block' : 'none';
            });
        }

        // Filter chips
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                const filterType = e.currentTarget.dataset.filter;
                const filterValue = e.currentTarget.dataset.value;
                this.toggleFilter(filterType, filterValue, e.currentTarget);
            });
        });

        // Sort dropdown
        const sortSelect = document.getElementById('sortBy');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.filters.sort = e.target.value;
                this.updateFilterBadge();
            });
        }

        if (applyFilters) {
            applyFilters.addEventListener('click', () => {
                this.applyFilters();
                if (filtersPanel) {
                    filtersPanel.style.display = 'none';
                }
            });
        }

        if (clearFilters) {
            clearFilters.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }
    }

    switchTab(tabId) {
        // Update active tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`.tab-btn[onclick*="${tabId}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
        
        // Show selected tab content
        document.querySelectorAll('.video-feed').forEach(feed => {
            feed.style.display = 'none';
        });
        
        const targetTab = document.getElementById(tabId);
        if (targetTab) {
            targetTab.style.display = 'flex';
        }
        
        this.currentTab = tabId;
        
        // Update URL
        history.pushState(null, null, `?tab=${tabId}`);
    }

    performSearch(query) {
        this.searchQuery = query;
        console.log('Searching for:', query);
        // Implement search functionality here
    }

    toggleFilter(filterType, filterValue, element) {
        element.classList.toggle('active');
        
        if (filterType === 'category') {
            const index = this.filters.category.indexOf(filterValue);
            if (index > -1) {
                this.filters.category.splice(index, 1);
            } else {
                this.filters.category.push(filterValue);
            }
        } else {
            if (this.filters[filterType] === filterValue) {
                this.filters[filterType] = null;
            } else {
                this.filters[filterType] = filterValue;
                
                // Remove active class from other filters in same type
                document.querySelectorAll(`[data-filter="${filterType}"]`).forEach(chip => {
                    if (chip !== element) {
                        chip.classList.remove('active');
                    }
                });
            }
        }
        
        this.updateFilterBadge();
    }

    updateFilterBadge() {
        const badge = document.getElementById('filterBadge');
        const activeFilters = this.getActiveFiltersCount();
        
        if (badge) {
            if (activeFilters > 0) {
                badge.textContent = activeFilters;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    getActiveFiltersCount() {
        let count = 0;
        count += this.filters.category.length;
        count += this.filters.duration ? 1 : 0;
        count += this.filters.date ? 1 : 0;
        count += this.filters.sort !== 'latest' ? 1 : 0;
        return count;
    }

    clearAllFilters() {
        this.filters = {
            category: [],
            duration: null,
            date: null,
            sort: 'latest'
        };
        
        // Reset UI
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.classList.remove('active');
        });
        
        const sortSelect = document.getElementById('sortBy');
        if (sortSelect) {
            sortSelect.value = 'latest';
        }
        this.updateFilterBadge();
    }

    // In the FeedManager class
applyFilters() {
    console.log('Applying business filters:', this.filters);
    
    // Hide all videos first
    document.querySelectorAll('.video-card').forEach(card => {
        card.style.display = 'none';
    });
    
    // Filter logic
    const filteredCards = Array.from(document.querySelectorAll('.video-card')).filter(card => {
        const category = card.dataset.category;
        const duration = parseInt(card.dataset.duration) || 0;
        
        // Category filter
        if (this.filters.category.length > 0 && !this.filters.category.includes(category.toLowerCase())) {
            return false;
        }
        
        // Duration filter
        if (this.filters.duration === 'short' && duration > 60) {
            return false;
        }
        if (this.filters.duration === 'medium' && (duration <= 60 || duration > 300)) {
            return false;
        }
        if (this.filters.duration === 'long' && duration <= 300) {
            return false;
        }
        
        return true;
    });
    
    // Show filtered videos
    filteredCards.forEach(card => {
        card.style.display = 'block';
    });
    
    this.showToast('Business filters applied');
}

    handleKeyboardShortcuts(e) {
        // Only handle shortcuts when not typing in inputs
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        switch (e.key) {
            case '1':
                this.switchTab('forYou');
                break;
            case '2':
                this.switchTab('following');
                break;
            case '3':
                this.switchTab('trending');
                break;
            case '5':
                this.switchTab('live');
                break;
            case 'f':
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                }
                break;
            case 'Escape':
                const activeElement = document.activeElement;
                if (activeElement) {
                    activeElement.blur();
                }
                break;
        }
    }

    loadInitialContent() {
        // Load correct tab from URL
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'forYou';
        this.switchTab(activeTab);
    }

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}"></i>
            <span>${message}</span>
        `;
        
        const container = document.getElementById('toastContainer');
        if (container) {
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    }
}

// Interaction Manager Class
class InteractionManager {
    constructor() {
        this.setupLikeHandling();
        this.setupFollowHandling();
        this.setupCommentHandling();
        this.setupShareHandling();
        this.setupBookmarkHandling();
        this.setupVideoControls();
        this.setupAdTracking();
    }

    setupLikeHandling() {
        document.addEventListener('click', async (e) => {
            const likeBtn = e.target.closest('.like-btn');
            if (!likeBtn) return;
            
            const videoId = likeBtn.dataset.videoId;
            const isLiked = likeBtn.classList.contains('liked');
            
            // Optimistic UI update
            likeBtn.classList.toggle('liked');
            const likeCount = likeBtn.querySelector('.like-count');
            if (likeCount) {
                const currentCount = parseInt(likeCount.textContent.replace(/[KM]/g, '')) || 0;
                likeCount.textContent = this.formatNumber(currentCount + (isLiked ? -1 : 1));
            }
            
            // Animate
            this.animateLike(likeBtn, !isLiked);
            
            try {
                // Here you would make the actual API call
                console.log(`${isLiked ? 'Unliked' : 'Liked'} video ${videoId}`);
            } catch (error) {
                console.error('Like error:', error);
                // Revert on error
                likeBtn.classList.toggle('liked');
                if (likeCount) {
                    const currentCount = parseInt(likeCount.textContent.replace(/[KM]/g, '')) || 0;
                    likeCount.textContent = this.formatNumber(currentCount + (isLiked ? 1 : -1));
                }
            }
        });
    }

    setupFollowHandling() {
        document.addEventListener('click', async (e) => {
            const followBtn = e.target.closest('.follow-btn');
            if (!followBtn) return;
            
            const userId = followBtn.dataset.userId;
            const isFollowing = followBtn.classList.contains('following');
            
            // Optimistic UI update
            followBtn.innerHTML = isFollowing 
                ? '<i class="fas fa-plus"></i><span>Follow</span>' 
                : '<i class="fas fa-check"></i><span>Following</span>';
            followBtn.classList.toggle('following');
            
            try {
                // Here you would make the actual API call
                console.log(`${isFollowing ? 'Unfollowed' : 'Followed'} user ${userId}`);
            } catch (error) {
                console.error('Follow error:', error);
                // Revert on error
                followBtn.innerHTML = isFollowing 
                    ? '<i class="fas fa-check"></i><span>Following</span>' 
                    : '<i class="fas fa-plus"></i><span>Follow</span>';
                followBtn.classList.toggle('following');
            }
        });
    }

    setupCommentHandling() {
        document.addEventListener('click', (e) => {
            const commentToggle = e.target.closest('.comment-toggle');
            if (!commentToggle) return;
            
            const videoId = commentToggle.dataset.videoId;
            const commentsSection = document.getElementById(`comments-${videoId}`);
            
            if (commentsSection) {
                const isExpanded = commentsSection.classList.contains('expanded');
                
                if (isExpanded) {
                    commentsSection.style.maxHeight = '0';
                    commentsSection.classList.remove('expanded');
                } else {
                    commentsSection.classList.add('expanded');
                    commentsSection.style.maxHeight = '600px';
                }
            }
        });
    }

    setupShareHandling() {
        document.addEventListener('click', async (e) => {
            const shareBtn = e.target.closest('.share-btn');
            if (!shareBtn) return;
            
            const videoId = shareBtn.dataset.videoId;
            const videoUrl = `${window.location.origin}/video/${videoId}`;
            
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Check out this video!',
                        url: videoUrl
                    });
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        this.copyToClipboard(videoUrl);
                    }
                }
            } else {
                this.copyToClipboard(videoUrl);
            }
        });
    }

    setupBookmarkHandling() {
        document.addEventListener('click', (e) => {
            const bookmarkBtn = e.target.closest('.bookmark-btn');
            if (!bookmarkBtn) return;
            
            const videoId = bookmarkBtn.dataset.videoId;
            const isBookmarked = bookmarkBtn.classList.contains('bookmarked');
            
            // Toggle bookmark state
            bookmarkBtn.classList.toggle('bookmarked');
            
            console.log(`${isBookmarked ? 'Removed bookmark from' : 'Bookmarked'} video ${videoId}`);
            
            // Show feedback
            if (window.feedManager) {
                window.feedManager.showToast(
                    isBookmarked ? 'Removed from saved videos' : 'Video saved!'
                );
            }
        });
    }

    setupVideoControls() {
        document.addEventListener('click', (e) => {
            const videoPlayer = e.target.closest('.video-player');
            if (!videoPlayer) return;
            
            if (videoPlayer.paused) {
                videoPlayer.play();
            } else {
                videoPlayer.pause();
            }
        });
    }

    // In the InteractionManager class
setupAdTracking() {
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            this.trackVisibleAds();
        }
    });
    
    document.addEventListener('scroll', () => {
        this.trackVisibleAds();
    });
    
    // Initial check
    this.trackVisibleAds();
}

trackVisibleAds() {
    const adCards = document.querySelectorAll('.ad-card');
    
    adCards.forEach(card => {
        const rect = card.getBoundingClientRect();
        const isVisible = (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
        
        if (isVisible && !card.dataset.viewTracked) {
            const adId = card.dataset.adId;
            console.log(`Ad ${adId} is visible - tracking view`);
            
            // Here you would send the view to your analytics/backend
            // fetch(`/track-ad-view.php?ad_id=${adId}`);
            
            // Mark as tracked to avoid duplicate counts
            card.dataset.viewTracked = 'true';
        }
    });
}

    animateLike(button, isLiked) {
        const icon = button.querySelector('.fa-heart');
        
        if (isLiked && icon) {
            // Heart animation
            icon.style.transform = 'scale(1.5)';
            icon.style.color = '#ef4444';
            
            // Create floating hearts
            this.createFloatingHearts(button);
        } else if (icon) {
            icon.style.transform = 'scale(0.8)';
            icon.style.color = '';
        }
        
        setTimeout(() => {
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        }, 300);
    }

    createFloatingHearts(button) {
        const rect = button.getBoundingClientRect();
        
        for (let i = 0; i < 3; i++) {
            const heart = document.createElement('div');
            heart.innerHTML = '❤️';
            heart.style.position = 'fixed';
            heart.style.left = `${rect.left + rect.width / 2}px`;
            heart.style.top = `${rect.top}px`;
            heart.style.pointerEvents = 'none';
            heart.style.zIndex = '9999';
            heart.style.fontSize = '1.5rem';
            heart.style.animation = `floatUp 2s ease-out forwards`;
            heart.style.animationDelay = `${i * 0.2}s`;
            
            document.body.appendChild(heart);
            
            setTimeout(() => heart.remove(), 2200);
        }
    }

    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            if (window.feedManager) {
                window.feedManager.showToast('Link copied to clipboard!');
            }
        } catch (error) {
            console.error('Copy failed:', error);
            if (window.feedManager) {
                window.feedManager.showToast('Failed to copy link', 'error');
            }
        }
    }

    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }
}

// FAB Manager Class
class FABManager {
    constructor() {
        this.isOpen = false;
        this.setup();
    }

    setup() {
        const mainFab = document.getElementById('mainFab');
        const fabMenu = document.getElementById('fabMenu');
        
        if (!mainFab) return;
        
        mainFab.addEventListener('click', () => {
            this.toggle();
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.fab-container') && this.isOpen) {
                this.close();
            }
        });
        
        // Keyboard shortcut
        document.addEventListener('keydown', (e) => {
            if (e.key === 'c' && e.ctrlKey) {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const fabMenu = document.getElementById('fabMenu');
        const mainFab = document.getElementById('mainFab');
        
        if (fabMenu && mainFab) {
            fabMenu.classList.add('open');
            mainFab.style.transform = 'rotate(45deg)';
            this.isOpen = true;
            
            // Animate items
            const items = fabMenu.querySelectorAll('.fab-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.transform = 'scale(1) translateY(0)';
                    item.style.opacity = '1';
                }, index * 50);
            });
        }
    }

    close() {
        const fabMenu = document.getElementById('fabMenu');
        const mainFab = document.getElementById('mainFab');
        
        if (fabMenu && mainFab) {
            fabMenu.classList.remove('open');
            mainFab.style.transform = 'rotate(0deg)';
            this.isOpen = false;
            
            // Reset items
            const items = fabMenu.querySelectorAll('.fab-item');
            items.forEach(item => {
                item.style.transform = 'scale(0) translateY(20px)';
                item.style.opacity = '0';
            });
        }
    }
}

// Global Functions
function showTab(tabId) {
    if (window.feedManager) {
        window.feedManager.switchTab(tabId);
    }
}

function openQuickCapture() {
    console.log('Opening quick capture...');
    // Implement quick capture functionality
}

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function timeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (let [unit, seconds] of Object.entries(intervals)) {
        const interval = Math.floor(diffInSeconds / seconds);
        if (interval >= 1) {
            return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
        }
    }
    
    return 'Just now';
}

function processDescription(description) {
    if (!description) return '';
    
    // Process hashtags, mentions, and links
    return description
        .replace(/#(\w+)/g, '<a href="/hashtag/$1" class="hashtag">#$1</a>')
        .replace(/@(\w+)/g, '<a href="/profile/$1" class="mention">@$1</a>')
        .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
}
</script>

</body>
</html>

<?php
// Helper functions (add these to your functions file or include them)
if (!function_exists('formatNumber')) {
    function formatNumber($num) {
        if ($num >= 1000000) {
            return number_format($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return number_format($num / 1000, 1) . 'K';
        }
        return number_format($num);
    }
}

if (!function_exists('formatDuration')) {
    function formatDuration($seconds) {
        $mins = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $mins, $secs);
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Just now';
        if ($time < 3600) return floor($time/60) . ' min' . (floor($time/60) > 1 ? 's' : '') . ' ago';
        if ($time < 86400) return floor($time/3600) . ' hour' . (floor($time/3600) > 1 ? 's' : '') . ' ago';
        if ($time < 2592000) return floor($time/86400) . ' day' . (floor($time/86400) > 1 ? 's' : '') . ' ago';
        if ($time < 31536000) return floor($time/2592000) . ' month' . (floor($time/2592000) > 1 ? 's' : '') . ' ago';
        
        return floor($time/31536000) . ' year' . (floor($time/31536000) > 1 ? 's' : '') . ' ago';
    }
}

if (!function_exists('processDescription')) {
    function processDescription($description) {
        if (empty($description)) return '';
        
        // Process hashtags, mentions, and links
        $description = preg_replace('/#(\w+)/', '<a href="/hashtag/$1" class="hashtag">#$1</a>', $description);
        $description = preg_replace('/@(\w+)/', '<a href="/profile/$1" class="mention">@$1</a>', $description);
        $description = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $description);
        
        return htmlspecialchars_decode($description);
    }
}
?>