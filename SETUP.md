# Connect — setup

## Requirements

- PHP 8.1+ with `pdo_mysql`, `fileinfo`, `gd` extensions
- MySQL 8 or MariaDB 10.6+

## Configure

Set these environment variables (or edit the defaults in `config/config.php` for local dev):

| Variable | Default | Purpose |
|---|---|---|
| `CONNECT_DB_HOST` | `localhost` | Database host |
| `CONNECT_DB_NAME` | `connect` | Database name |
| `CONNECT_DB_USER` | `connect` | Database user |
| `CONNECT_DB_PASS` | `connect_dev_pw` | Database password |
| `CONNECT_SITE_URL` | `http://localhost:8000` | Used to build absolute links (e.g. password reset) |
| `CONNECT_DEBUG` | `0` | Set to `1` to show PHP errors and the dev password-reset link fallback |

## Database

```
mysql -u root -e "CREATE DATABASE connect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER 'connect'@'localhost' IDENTIFIED BY 'connect_dev_pw'; GRANT ALL PRIVILEGES ON connect.* TO 'connect'@'localhost';"
mysql -u root connect < database/schema.sql
php database/seed.php   # optional demo data
```

## Run locally

```
php -S localhost:8000
```

Visit `http://localhost:8000`.

## Notes

- Password reset does not send real email yet — with `CONNECT_DEBUG=1` the reset
  link is shown directly on the "forgot password" page instead. Wire up SMTP in
  `forgot-password.php` when ready.
- Live streaming uses a chunked-upload approach (`live_chunk.php`, `live_status.php`,
  `assets/js/live-broadcast.js`/`live-viewer.js`) rather than RTMP/HLS — no separate
  media server is required, at the cost of a few seconds of latency.
- Admin panel lives under `/admin` with its own login, separate from user accounts.
