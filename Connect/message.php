<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Database functions (PDO)
function getMessages($userId, $pdo) {
    $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_pic FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.recipient_id = ? ORDER BY m.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getMessageById($messageId, $userId, $pdo) {
    $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_pic FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ? AND m.recipient_id = ?");
    $stmt->execute([$messageId, $userId]);
    return $stmt->fetch();
}

function markAsRead($messageId, $userId, $pdo) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?");
    return $stmt->execute([$messageId, $userId]);
}

function deleteMessage($messageId, $userId, $pdo) {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND recipient_id = ?");
    return $stmt->execute([$messageId, $userId]);
}

function clearAllMessages($userId, $pdo) {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE recipient_id = ?");
    return $stmt->execute([$userId]);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_as_read'], $_POST['message_id'])) {
        markAsRead($_POST['message_id'], $userId, $pdo);
    } elseif (isset($_POST['delete_message'], $_POST['message_id'])) {
        deleteMessage($_POST['message_id'], $userId, $pdo);
    } elseif (isset($_POST['clear_all'])) {
        clearAllMessages($userId, $pdo);
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } else {
        header("Location: messages.php");
        exit();
    }
}

// Get current message if ID is provided
$currentMessage = null;
if (isset($_GET['id'])) {
    $currentMessage = getMessageById($_GET['id'], $userId, $pdo);
    if ($currentMessage && !$currentMessage['is_read']) {
        markAsRead($currentMessage['id'], $userId, $pdo);
    }
}

// Get all messages
$messages = getMessages($userId, $pdo);
$unreadCount = array_reduce($messages, function($count, $message) {
    return $count + ($message['is_read'] ? 0 : 1);
}, 0);

// Pagination
$perPage = 10;
$totalMessages = count($messages);
$totalPages = ceil($totalMessages / $perPage);
$page = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$offset = ($page - 1) * $perPage;
$paginatedMessages = array_slice($messages, $offset, $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages | Connect</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #6366f1;
      --primary-hover: #4f46e5;
      --primary-light: #e0e7ff;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --bg: #f9fafb;
      --card-bg: #ffffff;
      --text: #111827;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --border-dark: #d1d5db;
      --shadow: 0 1px 3px rgba(0,0,0,0.1);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
      --radius: 0.5rem;
      --radius-lg: 0.75rem;
      --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    .messages-container {
      flex: 1;
      padding: 2rem;
      margin-left: 240px; /* Adjust based on your sidebar width */
    }

    .messages-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .messages-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .messages-title i {
      color: var(--primary);
    }

    .unread-badge {
      background-color: var(--primary);
      color: white;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      margin-left: 0.5rem;
    }

    .messages-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: var(--radius);
      font-weight: 500;
      cursor: pointer;
      transition: var(--transition);
      border: none;
      font-size: 0.875rem;
    }

    .btn-primary {
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
    }

    .btn-outline {
      background-color: transparent;
      border: 1px solid var(--border-dark);
      color: var(--text);
    }

    .btn-outline:hover {
      background-color: var(--bg);
      border-color: var(--text-light);
    }

    .btn-danger {
      background-color: var(--danger);
      color: white;
    }

    .btn-danger:hover {
      background-color: var(--danger-hover);
      transform: translateY(-1px);
    }

    .messages-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .message-card {
      background-color: var(--card-bg);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 1.25rem;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }

    .message-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
    }

    .message-card.unread {
      border-left: 4px solid var(--primary);
      background-color: var(--primary-light);
    }

    .message-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 0.75rem;
    }

    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }

    .message-sender {
      font-weight: 600;
      color: var(--text);
      text-decoration: none;
    }

    .message-sender:hover {
      color: var(--primary);
    }

    .message-time {
      font-size: 0.75rem;
      color: var(--text-light);
      margin-left: auto;
    }

    .message-content {
      padding-left: 56px; /* avatar width + gap */
      margin-bottom: 1rem;
      white-space: pre-wrap;
    }

    .message-actions {
      display: flex;
      gap: 0.75rem;
      padding-left: 56px;
    }

    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      background: none;
      border: none;
      color: var(--text-light);
      font-size: 0.875rem;
      cursor: pointer;
      transition: var(--transition);
      padding: 0.25rem 0.5rem;
      border-radius: var(--radius);
    }

    .action-btn:hover {
      color: var(--primary);
      background-color: rgba(99, 102, 241, 0.1);
    }

    .action-btn.delete:hover {
      color: var(--danger);
      background-color: rgba(239, 68, 68, 0.1);
    }

    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 4rem 2rem;
      border-radius: var(--radius);
      background-color: var(--card-bg);
      box-shadow: var(--shadow);
    }

    .empty-icon {
      font-size: 3rem;
      color: var(--border-dark);
      margin-bottom: 1rem;
    }

    .empty-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .empty-description {
      color: var(--text-light);
      max-width: 400px;
      margin-bottom: 1.5rem;
    }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 2rem;
    }

    .page-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 2.5rem;
      height: 2.5rem;
      border-radius: var(--radius);
      background-color: var(--card-bg);
      color: var(--text);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
      border: 1px solid var(--border);
    }

    .page-link:hover {
      background-color: var(--bg);
    }

    .page-link.active {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    /* Animation for message deletion */
    @keyframes fadeOut {
      from { opacity: 1; transform: translateY(0); }
      to { opacity: 0; transform: translateY(-20px); }
    }

    .message-fade-out {
      animation: fadeOut 0.3s forwards;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
      .messages-container {
        margin-left: 0;
        padding: 1rem;
      }

      .messages-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }

      .message-header {
        flex-wrap: wrap;
      }

      .message-time {
        margin-left: 0;
        width: 100%;
      }

      .message-content, .message-actions {
        padding-left: 0;
      }
    }

    /* Floating action button */
    .fab {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background-color: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: var(--shadow-lg);
      cursor: pointer;
      transition: var(--transition);
      border: none;
      z-index: 10;
    }

    .fab:hover {
      background-color: var(--primary-hover);
      transform: translateY(-2px) scale(1.05);
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0,0,0,0.5);
      z-index: 100;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background-color: var(--card-bg);
      border-radius: var(--radius-lg);
      width: 100%;
      max-width: 500px;
      padding: 2rem;
      box-shadow: var(--shadow-lg);
      transform: translateY(20px);
      opacity: 0;
      transition: var(--transition);
    }

    .modal.show {
      display: flex;
    }

    .modal.show .modal-content {
      transform: translateY(0);
      opacity: 1;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .modal-title {
      font-size: 1.25rem;
      font-weight: 600;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-light);
    }

    .modal-body {
      margin-bottom: 2rem;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
    }
  </style>
</head>
<body>
  <?php include 'sidebar.php'; ?>

  <div class="container">
    <div class="messages-container">
      <div class="messages-header">
        <h1 class="messages-title">
          <i class="fas fa-envelope"></i>
          Messages
          <?php if ($unreadCount > 0): ?>
            <span class="unread-badge"><?= $unreadCount ?> unread</span>
          <?php endif; ?>
        </h1>
        
        <div class="messages-actions">
          <button class="btn btn-outline" id="markAllRead">
            <i class="fas fa-check-double"></i> Mark all as read
          </button>
          <button class="btn btn-danger" id="clearAllMessages">
            <i class="fas fa-trash-alt"></i> Clear all
          </button>
        </div>
      </div>

      <?php if (count($paginatedMessages) === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <i class="far fa-envelope-open"></i>
          </div>
          <h3 class="empty-title">Your inbox is empty</h3>
          <p class="empty-description">When you receive messages, they'll appear here. Start a conversation with someone!</p>
          <button class="btn btn-primary" id="newMessageBtn">
            <i class="fas fa-plus"></i> New Message
          </button>
        </div>
      <?php else: ?>
        <div class="messages-list">
          <?php foreach ($paginatedMessages as $message): ?>
            <div class="message-card <?= $message['is_read'] ? '' : 'unread' ?>" id="message-<?= $message['id'] ?>">
              <div class="message-header">
                <img src="<?= htmlspecialchars($message['profile_pic'] ?? 'default-avatar.jpg') ?>" 
                     alt="<?= htmlspecialchars($message['username']) ?>" class="avatar">
                <a href="profile.php?id=<?= $message['sender_id'] ?>" class="message-sender">
                  <?= htmlspecialchars($message['username']) ?>
                </a>
                <span class="message-time">
                  <?= date("M j, Y g:i a", strtotime($message['created_at'])) ?>
                </span>
              </div>
              
              <div class="message-content">
                <?= nl2br(htmlspecialchars($message['content'])) ?>
              </div>
              
              <div class="message-actions">
                <button class="action-btn reply-btn" data-id="<?= $message['id'] ?>">
                  <i class="fas fa-reply"></i> Reply
                </button>
                <button class="action-btn mark-read-btn" data-id="<?= $message['id'] ?>" <?= $message['is_read'] ? 'disabled' : '' ?>>
                  <i class="fas fa-check"></i> <?= $message['is_read'] ? 'Read' : 'Mark as read' ?>
                </button>
                <button class="action-btn delete delete-btn" data-id="<?= $message['id'] ?>">
                  <i class="fas fa-trash-alt"></i> Delete
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?page=<?= $page - 1 ?>" class="page-link">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?= $page + 1 ?>" class="page-link">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Floating action button -->
  <button class="fab" id="fabNewMessage">
    <i class="fas fa-plus"></i>
  </button>

  <!-- New Message Modal -->
  <div class="modal" id="newMessageModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">New Message</h3>
        <button class="modal-close" id="closeModal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="newMessageForm">
          <div style="margin-bottom: 1rem;">
            <label for="recipient" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">To:</label>
            <select id="recipient" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: var(--radius);">
              <option value="">Select a user...</option>
              <!-- Populate with users from your database -->
            </select>
          </div>
          <div style="margin-bottom: 1rem;">
            <label for="messageContent" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Message:</label>
            <textarea id="messageContent" rows="5" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: var(--radius);"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="cancelMessage">Cancel</button>
        <button class="btn btn-primary" id="sendMessage">Send Message</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // DOM elements
    const fabNewMessage = document.getElementById('fabNewMessage');
    const newMessageBtn = document.getElementById('newMessageBtn');
    const newMessageModal = document.getElementById('newMessageModal');
    const closeModal = document.getElementById('closeModal');
    const cancelMessage = document.getElementById('cancelMessage');
    const markAllReadBtn = document.getElementById('markAllRead');
    const clearAllMessagesBtn = document.getElementById('clearAllMessages');

    // Show modal
    const showModal = () => {
      newMessageModal.classList.add('show');
    };

    // Hide modal
    const hideModal = () => {
      newMessageModal.classList.remove('show');
    };

    // Event listeners
    if (fabNewMessage) fabNewMessage.addEventListener('click', showModal);
    if (newMessageBtn) newMessageBtn.addEventListener('click', showModal);
    if (closeModal) closeModal.addEventListener('click', hideModal);
    if (cancelMessage) cancelMessage.addEventListener('click', hideModal);

    // Mark all as read
    if (markAllReadBtn) {
      markAllReadBtn.addEventListener('click', () => {
        fetch('messages.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'mark_all_read=1'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.querySelectorAll('.message-card.unread').forEach(card => {
              card.classList.remove('unread');
            });
            document.querySelectorAll('.mark-read-btn').forEach(btn => {
              btn.disabled = true;
              btn.innerHTML = '<i class="fas fa-check"></i> Read';
            });
            Swal.fire({
              icon: 'success',
              title: 'All messages marked as read',
              showConfirmButton: false,
              timer: 1500
            });
          }
        });
      });
    }

    // Clear all messages
    if (clearAllMessagesBtn) {
      clearAllMessagesBtn.addEventListener('click', () => {
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to recover these messages!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete all!'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('messages.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'clear_all=1'
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                // Animate out all messages
                document.querySelectorAll('.message-card').forEach(card => {
                  card.style.animation = 'fadeOut 0.3s forwards';
                  setTimeout(() => card.remove(), 300);
                });
                
                // Show empty state
                setTimeout(() => {
                  const emptyState = document.createElement('div');
                  emptyState.className = 'empty-state';
                  emptyState.innerHTML = `
                    <div class="empty-icon">
                      <i class="far fa-envelope-open"></i>
                    </div>
                    <h3 class="empty-title">Your inbox is empty</h3>
                    <p class="empty-description">When you receive messages, they'll appear here. Start a conversation with someone!</p>
                    <button class="btn btn-primary" id="newMessageBtn">
                      <i class="fas fa-plus"></i> New Message
                    </button>
                  `;
                  document.querySelector('.messages-list').replaceWith(emptyState);
                  
                  // Add event listener to the new button
                  document.getElementById('newMessageBtn').addEventListener('click', showModal);
                }, 300);
                
                Swal.fire(
                  'Deleted!',
                  'All messages have been deleted.',
                  'success'
                );
              }
            });
          }
        });
      });
    }

    // Mark single message as read
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const messageId = this.getAttribute('data-id');
        const messageCard = document.getElementById(`message-${messageId}`);
        
        fetch('messages.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `mark_as_read=1&message_id=${messageId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            messageCard.classList.remove('unread');
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-check"></i> Read';
            
            // Update unread count in badge
            const unreadBadge = document.querySelector('.unread-badge');
            if (unreadBadge) {
              const currentCount = parseInt(unreadBadge.textContent);
              if (currentCount > 1) {
                unreadBadge.textContent = `${currentCount - 1} unread`;
              } else {
                unreadBadge.remove();
              }
            }
          }
        });
      });
    });

    // Delete single message
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const messageId = this.getAttribute('data-id');
        const messageCard = document.getElementById(`message-${messageId}`);
        
        Swal.fire({
          title: 'Delete this message?',
          text: "You won't be able to undo this action!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('messages.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: `delete_message=1&message_id=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                messageCard.style.animation = 'fadeOut 0.3s forwards';
                setTimeout(() => messageCard.remove(), 300);
                
                Swal.fire(
                  'Deleted!',
                  'Your message has been deleted.',
                  'success'
                );
                
                // If this was the last message, show empty state
                setTimeout(() => {
                  if (document.querySelectorAll('.message-card').length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                      <div class="empty-icon">
                        <i class="far fa-envelope-open"></i>
                      </div>
                      <h3 class="empty-title">Your inbox is empty</h3>
                      <p class="empty-description">When you receive messages, they'll appear here. Start a conversation with someone!</p>
                      <button class="btn btn-primary" id="newMessageBtn">
                        <i class="fas fa-plus"></i> New Message
                      </button>
                    `;
                    document.querySelector('.messages-list').replaceWith(emptyState);
                    
                    // Add event listener to the new button
                    document.getElementById('newMessageBtn').addEventListener('click', showModal);
                  }
                }, 300);
              }
            });
          }
        });
      });
    });

    // Reply to message
    document.querySelectorAll('.reply-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const messageId = this.getAttribute('data-id');
        // In a real app, this would open a chat with the sender
        Swal.fire({
          icon: 'info',
          title: 'Reply functionality',
          text: 'This would open a chat with the message sender in a complete implementation.',
          confirmButtonText: 'OK'
        });
      });
    });

    // Send new message (placeholder functionality)
    if (document.getElementById('sendMessage')) {
      document.getElementById('sendMessage').addEventListener('click', () => {
        const recipient = document.getElementById('recipient').value;
        const content = document.getElementById('messageContent').value;
        
        if (!recipient || !content) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please select a recipient and enter a message!',
          });
          return;
        }
        
        // In a real app, this would send the message to the server
        Swal.fire({
          icon: 'success',
          title: 'Message sent!',
          text: `Your message to user ${recipient} would be sent in a complete implementation.`,
          showConfirmButton: false,
          timer: 1500
        });
        
        hideModal();
        document.getElementById('newMessageForm').reset();
      });
    }

    // Close modal when clicking outside
    newMessageModal.addEventListener('click', (e) => {
      if (e.target === newMessageModal) {
        hideModal();
      }
    });
  </script>
</body>
</html>