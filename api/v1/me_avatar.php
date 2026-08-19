<?php
require_once __DIR__ . '/../../includes/api_auth.php';

$me = requireApiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed.', 405);
}
if (empty($_FILES['avatar']['name'])) {
    apiError('No avatar file provided.');
}

$result = validateImageUpload($_FILES['avatar']);
if (!$result['ok']) {
    apiError($result['error']);
}

$filename = randomFilename($result['ext']);
if (!move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_DIR_PROFILE_PICS . $filename)) {
    apiError('Failed to save avatar.', 500);
}

$oldPic = $me['profile_pic'];
$newPath = UPLOAD_URL_PROFILE_PICS . $filename;

getPDO()->prepare('UPDATE users SET profile_pic = ? WHERE id = ?')->execute([$newPath, $me['id']]);

if ($oldPic && file_exists(__DIR__ . '/../../' . $oldPic)) {
    unlink(__DIR__ . '/../../' . $oldPic);
}

apiRespond(['success' => true, 'profile_pic' => $newPath]);
