<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}

$body = apiInput();
$username = sanitize_input($body['username'] ?? '');
$email = sanitize_input($body['email'] ?? '');
$password = (string) ($body['password'] ?? '');
$accountType = ($body['account_type'] ?? 'personal') === 'business' ? 'business' : 'personal';
$businessName = sanitize_input($body['business_name'] ?? '');
$businessCategory = sanitize_input($body['business_category'] ?? '');
$businessWebsite = sanitize_input($body['business_website'] ?? '');
$deviceName = sanitize_input($body['device_name'] ?? '');

if (strlen($username) < 3) {
    apiError('Username must be at least 3 characters.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiError('Please provide a valid email address.');
}
if (strlen($password) < 8) {
    apiError('Password must be at least 8 characters.');
}
if ($accountType === 'business' && $businessName === '') {
    apiError('Business name is required for a business account.');
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    apiError('That username or email is already registered.', 409);
}

$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password, account_type, business_name, business_category, business_website)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $username,
    $email,
    password_hash($password, PASSWORD_DEFAULT),
    $accountType,
    $accountType === 'business' ? $businessName : null,
    $accountType === 'business' ? $businessCategory : null,
    $accountType === 'business' ? $businessWebsite : null,
]);
$userId = (int) $pdo->lastInsertId();

$token = issueApiToken($userId, $deviceName ?: null);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

apiRespond(['success' => true, 'token' => $token, 'user' => apiUser($user)], 201);
