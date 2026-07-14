<?php
// Include after init.php on every protected admin page.
if (empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/admin/login.php');
}

function admin_require_role($role) {
    if (($_SESSION['admin_role'] ?? '') !== $role) {
        http_response_code(403);
        die('You do not have permission to access this page.');
    }
}
