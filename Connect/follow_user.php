<?php
// follow_user.php
require_once 'db.php';
require_once 'helpers.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Assign target user ID for frontend rendering (optional for demo/testing)
$target_user_id = intval($_GET['target_user_id'] ?? 2); // Replace 2 with a valid user ID

// Handle AJAX POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to follow users']);
        exit();
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $current_user_id = $_SESSION['user_id'];
    $target_user_id = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($target_user_id <= 0 || !in_array($action, ['follow', 'unfollow'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }

    if ($current_user_id == $target_user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot follow yourself']);
        exit();
    }

    try {
        if (!userExists($target_user_id)) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }

        if ($action === 'follow') {
            $success = followUser($current_user_id, $target_user_id);
            $message = $success ? 'Followed successfully' : 'Already following';
        } else {
            $success = unfollowUser($current_user_id, $target_user_id);
            $message = $success ? 'Unfollowed successfully' : 'Not following';
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'follower_count' => getFollowerCount($target_user_id),
            'is_following' => isFollowing($current_user_id, $target_user_id),
            'action' => $action
        ]);
    } catch (PDOException $e) {
        error_log("Follow action failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit();
}

// Helper functions
function userExists($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetch();
}

function followUser($followerId, $followingId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        return $stmt->execute([$followerId, $followingId]);
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) return false; // Already following
        throw $e;
    }
}

function unfollowUser($followerId, $followingId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$followerId, $followingId]);
    return $stmt->rowCount() > 0;
}

function getFollowerCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function isFollowing($followerId, $followingId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$followerId, $followingId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Follow User</title>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token']; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    .follow-btn {
        padding: 6px 15px;
        border-radius: 20px;
        border: none;
        background-color: #3897f0;
        color: white;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        font-size: 14px;
    }
    .follow-btn:hover {
        background-color: #2680d9;
        transform: scale(1.03);
    }
    .follow-btn.following {
        background-color: #f0f0f0;
        color: #333;
        border: 1px solid #ddd;
    }
    .follow-btn.following:hover {
        background-color: #e0e0e0;
        color: #ff0000;
        border-color: #ff0000;
    }
    .follow-btn.following:hover span {
        display: none;
    }
    .follow-btn.following:hover::after {
        content: 'Unfollow';
        color: #ff0000;
    }
    .toast-notification {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 24px;
        border-radius: 4px;
        color: white;
        z-index: 1000;
        animation: slideIn 0.3s, fadeOut 0.5s 2.5s forwards;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .toast-notification.success { background-color: #4CAF50; }
    .toast-notification.error { background-color: #F44336; }
    @keyframes slideIn {
        from { bottom: 0; opacity: 0; }
        to { bottom: 20px; opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    </style>
</head>
<body>

<?php
$is_following = isset($_SESSION['user_id']) ? isFollowing($_SESSION['user_id'], $target_user_id) : false;
?>
<button class="follow-btn <?= $is_following ? 'following' : ''; ?>" 
        data-user-id="<?= htmlspecialchars($target_user_id); ?>">
    <i class="fas <?= $is_following ? 'fa-check' : 'fa-plus'; ?>"></i>
    <span><?= $is_following ? 'Following' : 'Follow'; ?></span>
    <span class="follower-count"><?= htmlspecialchars(getFollowerCount($target_user_id)); ?></span>
</button>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.querySelector('.follow-btn');
    const userId = btn.dataset.userId;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    btn.addEventListener('click', () => {
        const action = btn.classList.contains('following') ? 'unfollow' : 'follow';
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch('follow_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `user_id=${userId}&action=${action}&csrf_token=${csrfToken}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                btn.classList.toggle('following', data.is_following);
                btn.innerHTML = `
                    <i class="fas ${data.is_following ? 'fa-check' : 'fa-plus'}"></i>
                    <span>${data.is_following ? 'Following' : 'Follow'}</span>
                    <span class="follower-count">${data.follower_count}</span>`;
                showToast(data.message, 'success');
            } else {
                btn.innerHTML = originalHTML;
                showToast(data.message, 'error');
            }
        })
        .catch(() => {
            btn.innerHTML = originalHTML;
            showToast('An error occurred', 'error');
        })
        .finally(() => btn.disabled = false);
    });

    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
});
</script>

</body>
</html>
