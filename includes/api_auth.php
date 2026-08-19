<?php
/**
 * Token-based auth for the JSON API (api/v1/*), used by the Flutter client.
 * Deliberately separate from includes/auth.php's session/CSRF model — bearer
 * tokens in an Authorization header aren't sent automatically by a browser the
 * way cookies are, so CSRF protection doesn't apply here; the token itself is
 * the credential. Opaque random tokens (not JWT) so a lost/stolen device's
 * token can be revoked server-side by deleting its api_tokens row.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

function apiRespond($data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function apiError(string $message, int $status = 400): never {
    apiRespond(['success' => false, 'message' => $message], $status);
}

/** Reads and JSON-decodes the request body; falls back to $_POST for form/multipart requests. */
function apiInput(): array {
    $raw = file_get_contents('php://input');
    if ($raw !== '' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $_POST;
}

function issueApiToken(int $userId, ?string $deviceName = null): string {
    $token = bin2hex(random_bytes(32)); // returned to the client once; only the hash is stored
    $hash = hash('sha256', $token);
    $stmt = getPDO()->prepare('INSERT INTO api_tokens (user_id, token_hash, device_name, last_used_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$userId, $hash, $deviceName]);
    return $token;
}

/** Returns the authenticated user row, or halts the request with 401. */
function requireApiAuth(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('apache_request_headers')) {
        $header = apache_request_headers()['Authorization'] ?? '';
    }
    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        apiError('Missing or malformed Authorization header. Expected: Bearer <token>', 401);
    }
    $hash = hash('sha256', $m[1]);

    $stmt = getPDO()->prepare(
        'SELECT u.* FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = ?'
    );
    $stmt->execute([$hash]);
    $user = $stmt->fetch();

    if (!$user) {
        apiError('Invalid or expired token.', 401);
    }

    getPDO()->prepare('UPDATE api_tokens SET last_used_at = NOW() WHERE token_hash = ?')->execute([$hash]);

    return $user;
}

/** Same as requireApiAuth() but returns null instead of erroring when no/invalid token is given. */
function optionalApiAuth(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
        return null;
    }
    $hash = hash('sha256', $m[1]);
    $stmt = getPDO()->prepare('SELECT u.* FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = ?');
    $stmt->execute([$hash]);
    return $stmt->fetch() ?: null;
}

/** Shapes a users row into the public JSON representation used across the API. */
function apiUser(array $user): array {
    return [
        'id'                => (int) $user['id'],
        'username'          => $user['username'],
        'bio'               => $user['bio'],
        'profile_pic'       => profilePicUrl($user['profile_pic'] ?? null),
        'account_type'      => $user['account_type'],
        'business_name'     => $user['business_name'],
        'business_category' => $user['business_category'],
        'business_website'  => $user['business_website'],
        'is_verified'       => (bool) $user['is_verified'],
        'created_at'        => $user['created_at'],
    ];
}
