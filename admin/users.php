<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getPDO();
$search = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';

$sql = 'SELECT u.*, (SELECT COUNT(*) FROM videos WHERE user_id = u.id) AS video_count FROM users u WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (u.username LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if (in_array($typeFilter, ['personal', 'business'], true)) {
    $sql .= ' AND u.account_type = :type';
    $params[':type'] = $typeFilter;
}
$sql .= ' ORDER BY u.created_at DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateAdminCsrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    if (isset($_POST['toggle_verified'])) {
        $pdo->prepare('UPDATE users SET is_verified = NOT is_verified WHERE id = ?')->execute([$userId]);
    } elseif (isset($_POST['delete_user'])) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    }
    header('Location: users.php' . ($search ? '?q=' . urlencode($search) : ''));
    exit;
}

$pageTitle = 'Users';
$activeAdminNav = 'users';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="admin-header"><h1>Users</h1></div>

<form class="flex gap-2" style="margin-bottom: 1.5rem;">
  <input class="form-control" type="text" name="q" placeholder="Search username or email..." value="<?= e($search) ?>">
  <select class="form-control" name="type" style="max-width:180px;" onchange="this.form.submit()">
    <option value="">All types</option>
    <option value="personal" <?= $typeFilter === 'personal' ? 'selected' : '' ?>>Personal</option>
    <option value="business" <?= $typeFilter === 'business' ? 'selected' : '' ?>>Business</option>
  </select>
  <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
</form>

<table class="data-table">
  <thead><tr><th>Username</th><th>Email</th><th>Type</th><th>Videos</th><th>Verified</th><th>Joined</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['username']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= $u['account_type'] === 'business' ? e($u['business_name'] ?: 'Business') : 'Personal' ?></td>
        <td><?= (int) $u['video_count'] ?></td>
        <td>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button type="submit" name="toggle_verified" value="1" class="btn <?= $u['is_verified'] ? 'btn-primary' : 'btn-secondary' ?>" style="padding:3px 10px;">
              <?= $u['is_verified'] ? 'Verified' : 'Verify' ?>
            </button>
          </form>
        </td>
        <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
        <td>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user and all their content?');">
            <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button type="submit" name="delete_user" value="1" class="btn btn-danger" style="padding:3px 10px;"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
