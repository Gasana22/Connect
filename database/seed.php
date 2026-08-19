<?php
/**
 * Local/dev seed data. Run with: php database/seed.php
 * Requires the schema to already be loaded (database/schema.sql) and the DB
 * env vars in config/config.php to point at a reachable database.
 */
require_once __DIR__ . '/../includes/db.php';

$pdo = getPDO();
$hash = password_hash('password123', PASSWORD_DEFAULT);

$pdo->prepare('INSERT INTO users (username, email, password, account_type, bio) VALUES (?, ?, ?, ?, ?)')
    ->execute(['alice', 'alice@example.com', $hash, 'personal', 'Just here to share my day.']);
$aliceId = $pdo->lastInsertId();

$pdo->prepare(
    'INSERT INTO users (username, email, password, account_type, business_name, business_category, business_website, is_verified)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
)->execute(['brewhouse', 'brew@example.com', $hash, 'business', 'Brewhouse Coffee Co.', 'Restaurant', 'https://brewhouse.example.com', 1]);
$bizId = $pdo->lastInsertId();

$pdo->prepare('INSERT INTO admin (email, password, username) VALUES (?, ?, ?)')
    ->execute(['admin@example.com', password_hash('admin12345', PASSWORD_DEFAULT), 'siteadmin']);

$pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute(['Food & Drink', 'food-drink']);
$pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute(['Tech Talk', 'tech-talk']);

$pdo->prepare('INSERT INTO videos (user_id, caption, video_path, tags, category) VALUES (?, ?, ?, ?, ?)')
    ->execute([$bizId, 'New seasonal latte is here #coffee #newmenu', 'uploads/videos/sample.mp4', 'coffee,newmenu', 'Restaurant']);

$pdo->prepare('INSERT INTO videos (user_id, caption, video_path, tags, category) VALUES (?, ?, ?, ?, ?)')
    ->execute([$aliceId, 'Morning walk #life', 'uploads/videos/sample2.mp4', 'life', null]);

$pdo->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)')->execute([$aliceId, $bizId]);

$pdo->prepare(
    'INSERT INTO ads (title, description, image_url, target_url, sponsor, start_date, end_date, is_active)
     VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 30 DAY, 1)'
)->execute(['20% off your first order', 'Try our new app and save.', 'https://via.placeholder.com/600x400', 'https://example.com/promo', 'ExamplePay']);

echo "Seeded.\n";
echo "  personal login: alice@example.com / password123\n";
echo "  business login: brew@example.com / password123\n";
echo "  admin login:    admin@example.com / admin12345\n";
