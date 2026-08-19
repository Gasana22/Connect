<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$me = requireApiAuth();
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    apiRespond(['success' => true, 'user' => apiUser($me)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $body = apiInput();
    $bio = array_key_exists('bio', $body) ? sanitize_input($body['bio']) : $me['bio'];
    $businessName = array_key_exists('business_name', $body) ? sanitize_input($body['business_name']) : $me['business_name'];
    $businessCategory = array_key_exists('business_category', $body) ? sanitize_input($body['business_category']) : $me['business_category'];
    $businessWebsite = array_key_exists('business_website', $body) ? sanitize_input($body['business_website']) : $me['business_website'];

    $stmt = $pdo->prepare('UPDATE users SET bio = ?, business_name = ?, business_category = ?, business_website = ? WHERE id = ?');
    $stmt->execute([$bio, $businessName, $businessCategory, $businessWebsite, $me['id']]);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$me['id']]);
    apiRespond(['success' => true, 'user' => apiUser($stmt->fetch())]);
}

apiError('Method not allowed.', 405);
