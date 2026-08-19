<?php
require_once __DIR__ . '/../../includes/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}

$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
    getPDO()->prepare('DELETE FROM api_tokens WHERE token_hash = ?')->execute([hash('sha256', $m[1])]);
}

apiRespond(['success' => true]);
