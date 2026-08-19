<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}

$body = apiInput();
$email = trim($body['email'] ?? '');
$password = (string) ($body['password'] ?? '');
$deviceName = sanitize_input($body['device_name'] ?? '');

$stmt = getPDO()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    apiError('Incorrect email or password.', 401);
}

$token = issueApiToken((int) $user['id'], $deviceName ?: null);

apiRespond(['success' => true, 'token' => $token, 'user' => apiUser($user)]);
