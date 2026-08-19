# Connect API v1

Base URL: `https://your-domain/api/v1/`

All responses are JSON: `{"success": true, ...}` or `{"success": false, "message": "..."}` with a
non-2xx HTTP status. All endpoints are single PHP files (no router) — call them directly by path.

## Auth

Token-based (opaque bearer token), separate from the website's cookie-session login. A token is
returned once at register/login; store it (e.g. `flutter_secure_storage`) and send it on every
authenticated request:

```
Authorization: Bearer <token>
```

There's no expiry/refresh flow yet — a token is valid until the user logs out (revokes it) or an
admin deletes its `api_tokens` row. Add expiry if you need it before shipping.

| Endpoint | Method | Auth | Body | Notes |
|---|---|---|---|---|
| `auth_register.php` | POST | — | `username, email, password, account_type (personal\|business), business_name?, business_category?, business_website?, device_name?` | Returns `{token, user}` |
| `auth_login.php` | POST | — | `email, password, device_name?` | Returns `{token, user}` |
| `auth_logout.php` | POST | required | — | Revokes the token in the `Authorization` header |
| `me.php` | GET | required | — | Current user |
| `me.php` | PUT | required | `bio?, business_name?, business_category?, business_website?` | Partial update |
| `me_avatar.php` | POST | required | multipart `avatar` file | Content-verified image upload |

## Feed / videos

| Endpoint | Method | Auth | Notes |
|---|---|---|---|
| `feed.php?tab=for-you\|following&page=&per_page=` | GET | optional | `items[]` is `{type: "video"\|"ad", video/ad: {...}}` — ads are pre-woven in, render them inline |
| `videos.php?user_id=` | GET | optional | A user's videos (for a profile screen) |
| `videos.php` | POST | required | multipart: `category, caption, video` file. Returns `{video_id, video_url}` |
| `video.php?id=` | GET | optional | Single video detail; also logs a view |
| `video.php?id=` | DELETE | required (owner) | Deletes the video + its file |
| `video_like.php` | POST | required | `{video_id}` — toggles, returns `{liked, like_count}` |
| `video_comments.php?video_id=` | GET | — | List comments |
| `video_comments.php` | POST | required | `{video_id, text}` |

## Users / social graph

| Endpoint | Method | Auth | Notes |
|---|---|---|---|
| `users.php?id=` | GET | optional | Profile + counts + `is_following`/`is_me` |
| `follow.php` | POST | required | `{user_id, action: "follow"\|"unfollow"}` |
| `followers.php?user_id=` | GET | optional | |
| `following.php?user_id=` | GET | optional | |

## Notifications & messages

| Endpoint | Method | Auth | Notes |
|---|---|---|---|
| `notifications.php?page=&per_page=` | GET | required | |
| `notifications.php` | POST | required | `{action: "mark_all_read"\|"clear_all"}` |
| `notifications.php?id=` | DELETE | required | Delete one |
| `conversations.php` | GET | required | One row per counterpart, latest message + unread flag |
| `messages.php?user_id=` | GET | required | Full thread with that user; marks it read |
| `messages.php` | POST | required | `{user_id, content}` |

## Discovery

| Endpoint | Method | Auth | Notes |
|---|---|---|---|
| `explore.php?q=&sort=trending\|liked\|viewed` | GET | — | |
| `hashtag.php?tag=` | GET | — | |
| `categories.php` | GET | — | `{live_categories[], business_categories[]}` |

## Live streaming

Chunked-upload approach — no RTMP/HLS server. See the web app's `live_chunk.php`/`live-viewer.js`
for the same idea; these are the token-authed twins for the Flutter client.

| Endpoint | Method | Auth | Notes |
|---|---|---|---|
| `live.php` | GET | — | Currently-live streams |
| `live.php` | POST | required | `{title, category_id?}` → `{stream_key}`. 409 if you're already live |
| `live_stream.php?key=` | GET | optional | Stream detail incl. `stream_url` (the growing `.webm` file); bumps `viewer_count` |
| `live_stream.php?key=` | POST | required (owner) | `{action: "stop"}` |
| `live_chunk.php?key=` | POST | required (owner) | Raw bytes body — one `MediaRecorder` chunk, ≤5MB |
| `live_chat.php?key=&after_id=` | GET | — | Poll for new chat messages |
| `live_chat.php?key=` | POST | required | `{message}` |

**Broadcasting from Flutter**: capture camera/mic, record continuously in short segments (e.g. with
`camera` + a segment-friendly encoder, or `flutter_webrtc`'s local recording), POST each segment's
raw bytes to `live_chunk.php` in order. **Viewing**: poll `live_stream.php` for `viewer_count`, and
either poll-reload `stream_url` into a video player (matching the web viewer) or, if you want
smoother playback, adapt to true segmented HLS later — this endpoint shape doesn't need to change
for that, only what's behind `stream_url`.

## Errors

| Status | Meaning |
|---|---|
| 400 | Bad/missing input |
| 401 | Missing, malformed, or invalid/revoked token |
| 403 | Authenticated but not allowed (e.g. not your stream) |
| 404 | Not found |
| 405 | Wrong HTTP method for that endpoint |
| 409 | Conflict (duplicate email/username, already live) |
