<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getPDO();
$error = flash('settings_error');
$message = flash('settings_message');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateAdminCsrf();

    if (isset($_POST['add_category'])) {
        $name = sanitize_input($_POST['name'] ?? '');
        if ($name === '') {
            flash('settings_error', 'Category name is required.');
        } else {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
            $stmt->execute([$name, $slug]);
            flash('settings_message', 'Category added.');
        }
    } elseif (isset($_POST['delete_category'])) {
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int) $_POST['category_id']]);
        flash('settings_message', 'Category deleted.');
    }
    header('Location: settings.php');
    exit;
}

$categories = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM live_streams WHERE category_id = c.id) AS usage_count FROM categories c ORDER BY c.name')->fetchAll();

$pageTitle = 'Settings';
$activeAdminNav = 'settings';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="admin-header"><h1>Settings</h1></div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

<div class="card card-pad" style="max-width: 640px; margin-bottom: 2rem;">
  <h2 style="margin-bottom: 0.5rem;">Live stream categories</h2>
  <p class="text-muted mt-1" style="margin-bottom:1rem;">These power the category filter on the live-streaming browse page.</p>
  <form method="POST" class="flex gap-2" style="margin-bottom: 1.5rem;">
    <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
    <input class="form-control" type="text" name="name" placeholder="New category name" required>
    <button type="submit" name="add_category" value="1" class="btn btn-primary">Add</button>
  </form>

  <table class="data-table">
    <thead><tr><th>Name</th><th>Slug</th><th>In use</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= e($c['name']) ?></td>
          <td><?= e($c['slug']) ?></td>
          <td><?= (int) $c['usage_count'] ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Delete this category?');">
              <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
              <input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
              <button type="submit" name="delete_category" value="1" class="btn btn-danger" style="padding:3px 10px;"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card card-pad" style="max-width: 640px;">
  <h2 style="margin-bottom: 0.5rem;">Business categories</h2>
  <p class="text-muted">Fixed list used for video uploads and business signup — defined in <code>config/config.php</code> (<code>BUSINESS_CATEGORIES</code>).</p>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
