<?php
require_once 'db.php'; // Your DB config
require_once 'functions.php'; // Helper functions

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header("Location: index.php");
    exit;
}

// Get profile info
$stmt = $pdo->prepare("SELECT id, username, display_name, bio, avatar, is_verified FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    echo "User not found.";
    exit;
}

// Get user's videos
$stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$userVideos = $stmt->fetchAll();

// Check if current user is following
$isFollowing = false;
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$_SESSION['user_id'], $user_id]);
    $isFollowing = $stmt->fetch() ? true : false;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($profile['display_name'] ?? $profile['username']) ?>'s Profile</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="profile-header">
    <img src="<?= $profile['avatar'] ?>" alt="Avatar" class="profile-avatar">
    <div class="profile-details">
        <h2>
            <?= htmlspecialchars($profile['display_name'] ?? $profile['username']) ?>
            <?php if ($profile['is_verified']): ?>
                <i class="fas fa-check-circle verified-icon"></i>
            <?php endif; ?>
        </h2>
        <p class="profile-username">@<?= htmlspecialchars($profile['username']) ?></p>
        <p class="profile-bio"><?= nl2br(htmlspecialchars($profile['bio'] ?? '')) ?></p>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id): ?>
            <button class="follow-btn <?= $isFollowing ? 'following' : '' ?>" data-user-id="<?= $user_id ?>">
                <i class="fas fa-<?= $isFollowing ? 'check' : 'plus' ?>"></i>
                <?= $isFollowing ? 'Following' : 'Follow' ?>
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="user-video-feed">
    <h3><?= htmlspecialchars($profile['display_name'] ?? $profile['username']) ?>'s Videos</h3>
    
    <?php if (empty($userVideos)): ?>
        <p>No videos uploaded yet.</p>
    <?php else: ?>
        <div class="video-grid">
            <?php foreach ($userVideos as $video): ?>
                <div class="video-card">
                    <video class="video-player" src="<?= $video['video_url'] ?>" poster="<?= $video['thumbnail'] ?>" muted loop></video>
                    <p class="video-desc"><?= htmlspecialchars($video['description']) ?></p>
                    <div class="video-stats">
                        <span><i class="fas fa-heart"></i> <?= $video['likes'] ?></span>
                        <span><i class="fas fa-comment"></i> <?= $video['comments_count'] ?></span>
                        <span><i class="fas fa-eye"></i> <?= $video['views'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/follow.js"></script>
</body>
</html>
