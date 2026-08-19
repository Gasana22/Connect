/**
 * Shared client-side behavior: sidebar toggle, toasts, notification-badge polling,
 * and delegated like/follow/comment handlers reused across the feed, profile, and
 * explore pages. Page-specific scripts should read window.CSRF_TOKEN rather than
 * re-declaring their own token handling.
 */

window.CSRF_TOKEN = document.body.getAttribute('data-csrf') || '';

function toast(message, type) {
  type = type || 'success';
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const el = document.createElement('div');
  el.className = 'toast toast-' + type;
  const icon = type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check';
  el.innerHTML = '<i class="fas ' + icon + '"></i><span></span>';
  el.querySelector('span').textContent = message;
  container.appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 250);
  }, 3000);
}

async function postJSON(url, body) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN },
    body: JSON.stringify(Object.assign({ csrf_token: window.CSRF_TOKEN }, body)),
  });
  return res.json();
}

document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  // Notification badge polling (only on pages with a sidebar/logged-in user).
  const notifNav = document.getElementById('notifNavItem');
  if (notifNav) {
    const pollNotifications = () => {
      fetch('api/unread_count.php')
        .then((r) => r.json())
        .then((data) => {
          let badge = document.getElementById('notifBadge');
          if (data.unread > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.id = 'notifBadge';
              badge.className = 'notification-badge pulse';
              notifNav.appendChild(badge);
            }
            badge.textContent = data.unread;
          } else if (badge) {
            badge.remove();
          }
        })
        .catch(() => {});
    };
    pollNotifications();
    setInterval(pollNotifications, 30000);
  }

  // Delegated like button: <button class="action-btn like-btn" data-video-id="123">
  document.body.addEventListener('click', async (e) => {
    const likeBtn = e.target.closest('.like-btn');
    if (likeBtn) {
      const videoId = likeBtn.dataset.videoId;
      likeBtn.disabled = true;
      try {
        const result = await postJSON('like.php', { video_id: videoId });
        if (result.success) {
          likeBtn.classList.toggle('liked', result.liked);
          const countEl = likeBtn.querySelector('.like-count');
          if (countEl) countEl.textContent = result.like_count;
        } else {
          toast(result.message || 'Could not update like.', 'error');
        }
      } catch (err) {
        toast('Network error. Please try again.', 'error');
      }
      likeBtn.disabled = false;
      return;
    }

    // Delegated follow button: <button class="follow-btn" data-user-id="5" data-following="0|1">
    const followBtn = e.target.closest('.follow-btn');
    if (followBtn) {
      const userId = followBtn.dataset.userId;
      const isFollowing = followBtn.dataset.following === '1';
      followBtn.disabled = true;
      try {
        const result = await postJSON('follow.php', { user_id: userId, action: isFollowing ? 'unfollow' : 'follow' });
        if (result.success) {
          const nowFollowing = !isFollowing;
          followBtn.dataset.following = nowFollowing ? '1' : '0';
          followBtn.classList.toggle('following', nowFollowing);
          followBtn.textContent = nowFollowing ? 'Following' : 'Follow';
          toast(nowFollowing ? 'Followed' : 'Unfollowed');
        } else {
          toast(result.message || 'Could not update follow state.', 'error');
        }
      } catch (err) {
        toast('Network error. Please try again.', 'error');
      }
      followBtn.disabled = false;
      return;
    }

    // Toggle a video's comment panel: <button class="comment-toggle" data-video-id="123">
    const commentToggle = e.target.closest('.comment-toggle');
    if (commentToggle) {
      const panel = document.getElementById('comments-' + commentToggle.dataset.videoId);
      if (panel) {
        panel.classList.toggle('expanded');
        if (panel.classList.contains('expanded') && !panel.dataset.loaded) {
          loadComments(commentToggle.dataset.videoId, panel);
        }
      }
    }
  });

  document.body.addEventListener('submit', async (e) => {
    const form = e.target.closest('.comment-form');
    if (!form) return;
    e.preventDefault();
    const input = form.querySelector('input[name="text"]');
    const videoId = form.dataset.videoId;
    const text = input.value.trim();
    if (!text) return;
    try {
      const result = await postJSON('comment.php', { video_id: videoId, text });
      if (result.success) {
        input.value = '';
        const panel = document.getElementById('comments-' + videoId);
        if (panel) loadComments(videoId, panel);
      } else {
        toast(result.message || 'Could not post comment.', 'error');
      }
    } catch (err) {
      toast('Network error. Please try again.', 'error');
    }
  });
});

function loadComments(videoId, panel) {
  fetch('fetch_comments.php?video_id=' + encodeURIComponent(videoId))
    .then((r) => r.json())
    .then((data) => {
      const list = panel.querySelector('.comments-list');
      if (!list) return;
      panel.dataset.loaded = '1';
      if (!data.comments || !data.comments.length) {
        list.innerHTML = '<p class="text-muted" style="font-size:0.85rem;">No comments yet. Be the first to say something.</p>';
        return;
      }
      list.innerHTML = data.comments
        .map(
          (c) =>
            '<div class="comment"><img class="comment-avatar" src="' + c.avatar + '" alt="">' +
            '<div><a class="comment-user" href="profile.php?id=' + c.user_id + '">' + c.username + '</a>' +
            '<div class="comment-text">' + c.text + '</div></div></div>'
        )
        .join('');
    })
    .catch(() => {});
}
