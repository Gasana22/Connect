<?php
session_start();
require_once 'db.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Handle starting/stopping a stream
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLoggedIn) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    if (isset($_POST['start_stream'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $categoryId = $_POST['category_id'];
        $streamType = $_POST['stream_type'];
        $streamKey = uniqid('stream_', true);
        
        $stmt = $pdo->prepare("INSERT INTO live_streams (user_id, stream_key, title, description, category_id, stream_type, is_live, started_at) 
                               VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$userId, $streamKey, $title, $description, $categoryId, $streamType]);
        
        $_SESSION['current_stream_key'] = $streamKey;
        $_SESSION['notification'] = "Stream started successfully!";
        header("Location: live.php?stream=" . $streamKey);
        exit();
    } elseif (isset($_POST['stop_stream'])) {
        $streamKey = $_POST['stream_key'];
        
        $stmt = $pdo->prepare("UPDATE live_streams SET is_live = 0, ended_at = NOW() WHERE stream_key = ? AND user_id = ?");
        $stmt->execute([$streamKey, $userId]);
        
        unset($_SESSION['current_stream_key']);
        $_SESSION['notification'] = "Stream ended successfully!";
        header("Location: live.php");
        exit();
    } elseif (isset($_POST['chat_message'])) {
        $message = htmlspecialchars($_POST['chat_message']);
        $streamKey = $_POST['stream_key'];
        
        $stmt = $pdo->prepare("INSERT INTO chat_messages (stream_key, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$streamKey, $userId, $message]);
    }
}

// Get current stream if viewing
$currentStream = null;
$isWatching = false;
if (isset($_GET['stream'])) {
    $streamKey = $_GET['stream'];
    
    $stmt = $pdo->prepare("SELECT ls.*, u.username, u.avatar, c.name as category_name 
                          FROM live_streams ls
                          JOIN users u ON ls.user_id = u.id
                          LEFT JOIN categories c ON ls.category_id = c.id
                          WHERE ls.stream_key = ? AND ls.is_live = 1");
    $stmt->execute([$streamKey]);
    $currentStream = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentStream) {
        $isWatching = true;
        
        // Increment viewer count
        $pdo->prepare("UPDATE live_streams SET viewer_count = viewer_count + 1 WHERE stream_key = ?")
            ->execute([$streamKey]);
    }
}

// Get active streams for browsing
$stmt = $pdo->query("SELECT ls.*, u.username, u.avatar, c.name as category_name 
                    FROM live_streams ls
                    JOIN users u ON ls.user_id = u.id
                    LEFT JOIN categories c ON ls.category_id = c.id
                    WHERE ls.is_live = 1
                    ORDER BY ls.viewer_count DESC");
$activeStreams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get chat messages for current stream
$chatMessages = [];
if ($isWatching) {
    $stmt = $pdo->prepare("SELECT cm.*, u.username, u.avatar 
                          FROM chat_messages cm
                          JOIN users u ON cm.user_id = u.id
                          WHERE cm.stream_key = ?
                          ORDER BY cm.sent_at DESC
                          LIMIT 50");
    $stmt->execute([$currentStream['stream_key']]);
    $chatMessages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

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
    <title><?= $isWatching ? htmlspecialchars($currentStream['title']) : 'Live Streaming Platform' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if ($isWatching): ?>
    <meta property="og:title" content="<?= htmlspecialchars($currentStream['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($currentStream['description']) ?>">
    <meta property="og:image" content="https://via.placeholder.com/1200x630?text=<?= urlencode($currentStream['title']) ?>">
    <meta property="og:url" content="<?= "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="og:type" content="video.other">
    <meta name="twitter:card" content="player">
    <?php else: ?>
    <meta name="description" content="Watch and stream live content on our platform">
    <?php endif; ?>
    <style>
        :root {
            --primary:rgb(15, 90, 175);
            --primary-dark:rgb(6, 77, 148);
            --dark: #0e0e10;
            --dark-gray: #1f1f23;
            --medium-gray: #26262c;
            --light-gray: #3a3a3d;
            --text: #efeff1;
            --text-muted: #adadb8;
            --success: #00b874;
            --danger: #ff5555;
            --warning: #e8b339;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
        
        /* Header Styles */
        header {
            background-color: var(--dark-gray);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }
        
        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--light-gray);
            color: var(--text);
        }
        
        .btn-outline:hover {
            background-color: var(--medium-gray);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #e04a4a;
        }
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Notification */
        .notification {
            padding: 12px 20px;
            background-color: var(--success);
            color: white;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .notification-close {
            cursor: pointer;
            margin-left: 15px;
        }
        
        /* Stream Container */
        .stream-container {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }
        
        .video-container {
            flex: 3;
            background-color: var(--dark-gray);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .video-player {
            width: 100%;
            background-color: black;
            aspect-ratio: 16/9;
        }
        
        .stream-info {
            padding: 20px;
        }
        
        .stream-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .stream-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
            color: var(--text-muted);
        }
        
        .streamer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .streamer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .stream-description {
            margin-top: 15px;
            line-height: 1.7;
        }
        
        .stream-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Chat Container */
        .chat-container {
            flex: 1;
            background-color: var(--dark-gray);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            max-height: 80vh;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--medium-gray);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px 20px;
        }
        
        .chat-message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        
        .chat-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .chat-content {
            flex: 1;
        }
        
        .chat-username {
            font-weight: 600;
            color: var(--primary);
            margin-right: 8px;
        }
        
        .chat-timestamp {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .chat-text {
            margin-top: 2px;
            word-break: break-word;
        }
        
        .chat-input-container {
            padding: 15px 20px;
            border-top: 1px solid var(--medium-gray);
        }
        
        .chat-input {
            display: flex;
            gap: 10px;
        }
        
        .chat-input input {
            flex: 1;
            padding: 10px 15px;
            background-color: var(--medium-gray);
            border: none;
            border-radius: 4px;
            color: var(--text);
            font-family: inherit;
        }
        
        .chat-input button {
            padding: 0 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .chat-input button:hover {
            background-color: var(--primary-dark);
        }
        
        /* Stream List */
        .stream-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 40px 0 20px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .category-filter {
            display: flex;
            gap: 10px;
        }
        
        .category-btn {
            padding: 6px 12px;
            background-color: var(--medium-gray);
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .category-btn:hover, .category-btn.active {
            background-color: var(--primary);
            color: white;
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
            position: relative;
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
        
        .stream-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .live-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--danger);
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
        
        /* Stream Form */
        .stream-form-container {
            max-width: 700px;
            margin: 30px auto;
            background-color: var(--dark-gray);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: var(--medium-gray);
            border: 1px solid var(--light-gray);
            border-radius: 4px;
            color: var(--text);
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        /* Streaming Info */
        .streaming-info {
            background-color: var(--dark-gray);
            border-radius: 8px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .streaming-info-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
        }
        
        .info-value {
            background-color: var(--medium-gray);
            padding: 10px 15px;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
        }
        
        .streaming-instructions {
            margin-top: 30px;
            padding: 20px;
            background-color: rgba(145, 71, 255, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .streaming-instructions h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .streaming-instructions ol, .streaming-instructions ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .streaming-instructions li {
            margin-bottom: 8px;
        }
        
        /* Admin Controls */
        .admin-controls {
            background-color: rgba(255, 85, 85, 0.1);
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            border-left: 4px solid var(--danger);
        }
        
        .admin-controls h3 {
            margin-bottom: 15px;
            color: var(--danger);
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .stream-container {
                flex-direction: column;
            }
            
            .chat-container {
                max-height: 400px;
                margin-top: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .stream-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
            
            .category-filter {
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 576px) {
            .stream-grid {
                grid-template-columns: 1fr;
            }
            
            .stream-form-container {
                padding: 20px;
            }
        }
        
        /* Animations */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .live-pulse {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--danger);
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse 1.5s infinite;
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="live.php" class="logo">
                <i class="fas fa-broadcast-tower"></i>
                ConnectLive
            </a>
            
            <div class="nav-links">
                
                <a href="browse.php" class="btn btn-outline">
                    <i class="fas fa-compass"></i> Browse
                </a>
                
            </div>
        </div>
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
        
        <?php if ($isWatching): ?>
            <!-- Watching a stream -->
            <div class="stream-container">
                <div class="video-container">
                    <?php if ($currentStream['stream_type'] === 'rtmp'): ?>
                        <!-- RTMP stream -->
                        <video class="video-player" controls autoplay>
                            <source src="rtmp://your-stream-server.com/live/<?= $currentStream['stream_key'] ?>" type="rtmp/mp4">
                            Your browser does not support RTMP streaming.
                        </video>
                    <?php else: ?>
                        <!-- HLS stream -->
                        <video class="video-player" controls autoplay>
                            <source src="http://your-stream-server.com/live/<?= $currentStream['stream_key'] ?>/index.m3u8" type="application/x-mpegURL">
                            Your browser does not support HLS streaming.
                        </video>
                    <?php endif; ?>
                    
                
                <div class="chat-container">
                    <div class="chat-header">
                        <span><i class="fas fa-comments"></i> Live Chat</span>
                        <span><?= number_format($currentStream['viewer_count']) ?> viewers</span>
                    </div>
                    <div class="chat-messages" id="chat-messages">
                        <?php foreach ($chatMessages as $message): ?>
                            <div class="chat-message">
                                <img src="<?= htmlspecialchars($message['avatar'] ?? 'https://via.placeholder.com/32') ?>" alt="User Avatar" class="chat-avatar">
                                <div class="chat-content">
                                    <div>
                                        <span class="chat-username"><?= htmlspecialchars($message['username']) ?></span>
                                        <span class="chat-timestamp"><?= date('H:i', strtotime($message['sent_at'])) ?></span>
                                    </div>
                                    <div class="chat-text"><?= htmlspecialchars($message['message']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($isLoggedIn): ?>
                        <form method="post" class="chat-input-container">
                            <input type="hidden" name="stream_key" value="<?= $currentStream['stream_key'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="chat-input">
                                <input type="text" name="chat_message" placeholder="Send a message..." required>
                                <button type="submit"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="chat-input-container" style="text-align: center; padding: 20px;">
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login to Chat
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($isLoggedIn && $_SESSION['is_admin']): ?>
            <div class="admin-controls">
                <h3><i class="fas fa-shield-alt"></i> Admin Controls</h3>
                <div class="admin-actions">
                    <form method="post">
                        <input type="hidden" name="stream_key" value="<?= $currentStream['stream_key'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="ban_stream" class="btn btn-danger">
                            <i class="fas fa-ban"></i> Ban Stream
                        </button>
                    </form>
                    <a href="admin.php" class="btn btn-outline">
                        <i class="fas fa-cog"></i> Admin Dashboard
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <script>
                // Auto-scroll chat to bottom
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Refresh chat every 3 seconds
                setInterval(() => {
                    fetch('get_chat.php?stream_key=<?= $currentStream['stream_key'] ?>')
                        .then(response => response.text())
                        .then(html => {
                            chatMessages.innerHTML = html;
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        });
                }, 3000);

                // Check stream status periodically
                function checkStreamStatus() {
                    fetch('check_stream.php?stream_key=<?= $currentStream['stream_key'] ?>')
                        .then(response => response.json())
                        .then(data => {
                            if (!data.is_live) {
                                alert('The stream has ended');
                                window.location.href = 'live.php';
                            }
                        });
                }

                // Auto-reconnect player
                const videoPlayer = document.querySelector('.video-player');
                videoPlayer.addEventListener('error', function() {
                    setTimeout(() => {
                        videoPlayer.load();
                        videoPlayer.play().catch(e => console.log('Autoplay prevented'));
                    }, 3000);
                });

                // Only run if watching a stream
                setInterval(checkStreamStatus, 10000);
            </script>
            
        <?php elseif ($isLoggedIn && isset($_SESSION['current_stream_key'])): ?>
            <!-- User is streaming -->
            <?php 
                $stmt = $pdo->prepare("SELECT ls.*, u.username, u.avatar, c.name as category_name 
                                      FROM live_streams ls
                                      JOIN users u ON ls.user_id = u.id
                                      LEFT JOIN categories c ON ls.category_id = c.id
                                      WHERE ls.stream_key = ?");
                $stmt->execute([$_SESSION['current_stream_key']]);
                $myStream = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div class="streaming-info">
                <h2 class="streaming-info-title"><i class="fas fa-broadcast-tower"></i> You're Live Now!</h2>
                
                <div class="info-item">
                    <div class="info-label">Stream Title</div>
                    <div class="info-value"><?= htmlspecialchars($myStream['title']) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Stream Key (Keep this private)</div>
                    <div class="info-value"><?= $myStream['stream_key'] ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">RTMP Server URL</div>
                    <div class="info-value">rtmp://your-stream-server.com/live</div>
                </div>
                
                <div class="stream-actions" style="margin-top: 30px;">
                    <form method="post">
                        <input type="hidden" name="stream_key" value="<?= $myStream['stream_key'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="stop_stream" class="btn btn-danger">
                            <i class="fas fa-stop"></i> End Stream
                        </button>
                        <a href="live.php?stream=<?= $myStream['stream_key'] ?>" class="btn btn-primary">
                            <i class="fas fa-tv"></i> View Stream
                        </a>
                    </form>
                </div>
                
                <div class="streaming-instructions" style="margin-top: 30px;">
                    <h3><i class="fas fa-info-circle"></i> Streaming Instructions</h3>
                    <ol>
                        <li>Download <a href="https://obsproject.com/" target="_blank">OBS Studio</a> or similar streaming software</li>
                        <li>Configure with these settings:
                            <ul>
                                <li><strong>Server:</strong> rtmp://your-stream-server.com/live</li>
                                <li><strong>Stream Key:</strong> <?= $myStream['stream_key'] ?></li>
                            </ul>
                        </li>
                        <li>Click "Start Streaming" in your software</li>
                    </ol>
                    <p><strong>Recommended Settings:</strong><br>
                    Video: 720p @ 30fps, 2500-4000 kbps<br>
                    Audio: 160 kbps, 44.1kHz<br>
                    Keyframe Interval: 2 seconds</p>
                </div>
            </div>
            
            <div class="stream-list-header">
                <h2 class="section-title">Other Live Streams</h2>
                <div class="category-filter">
                    <div class="category-btn active">All</div>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-btn"><?= htmlspecialchars($category['name']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="stream-grid">
    <?php foreach ($activeStreams as $stream): ?>
        <?php if ($stream['stream_key'] !== $myStream['stream_key']): ?>
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
        <?php endif; ?>
    <?php endforeach; ?>
</div>
        <?php endif; ?>
        
        <?php if ($isLoggedIn && !isset($_SESSION['current_stream_key'])): ?>
    <div style="text-align: center; margin: 40px 0;">
        <a href="live.php?go_live" class="btn btn-primary" style="padding: 12px 24px; font-size: 18px;">
            <i class="fas fa-video"></i> Start Your Own Stream
        </a>
    </div>
<?php endif; ?>

<?php if ($isLoggedIn && !isset($_SESSION['current_stream_key'])): ?>
    <?php if (isset($_GET['go_live'])): ?>
        <div class="stream-form-container">
            <h2 class="form-title"><i class="fas fa-video"></i> Start Your Stream</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="title" class="form-label">Stream Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="What are you streaming today?" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Tell viewers about your stream"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category_id" class="form-label">Category</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Stream Type</label>
                    <div>
                        <label style="display: inline-flex; align-items: center; margin-right: 20px;">
                            <input type="radio" name="stream_type" value="rtmp" checked style="margin-right: 8px;">
                            RTMP (Recommended)
                        </label>
                        <label style="display: inline-flex; align-items: center;">
                            <input type="radio" name="stream_type" value="hls" style="margin-right: 8px;">
                            HLS
                        </label>
                    </div>
                </div>
                
                <div class="form-footer">
                    <a href="live.php" class="btn btn-outline" style="margin-right: 10px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="start_stream" class="btn btn-primary">
                        <i class="fas fa-broadcast-tower"></i> Go Live
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        
    <?php endif; ?>
<?php elseif (!$isLoggedIn): ?>
    <div style="text-align: center; margin: 40px 0;">
        <a href="login.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 18px;">
            <i class="fas fa-sign-in-alt"></i> Login to Start Streaming
        </a>
    </div>
<?php endif; ?>

</div>

<script>
    // Handle category filtering
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelector('.category-btn.active').classList.remove('active');
            this.classList.add('active');
            
            // In a real implementation, you would filter streams here
            // This is just a UI demonstration
            console.log('Filter by:', this.textContent);
        });
    });
    
    // Handle notifications
    if (document.querySelector('.notification')) {
        setTimeout(() => {
            document.querySelector('.notification').style.opacity = '0';
            setTimeout(() => {
                document.querySelector('.notification').style.display = 'none';
            }, 300);
        }, 5000);
    }
</script>
</body>
</html>