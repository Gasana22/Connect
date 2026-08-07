<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash_set('danger', 'Please enter a category name.');
        } else {
            $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $check->execute([$name]);
            if ($check->fetch()) {
                flash_set('danger', 'That category already exists.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                flash_set('success', 'Category added.');
            }
        }
        redirect(BASE_URL . '/admin/categories.php');
    }

    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();
        if ($category) {
            $inUse = $pdo->prepare("SELECT COUNT(*) c FROM treatments WHERE category = ?");
            $inUse->execute([$category['name']]);
            if ((int)$inUse->fetch()['c'] > 0) {
                flash_set('danger', 'This category is used by one or more treatments and cannot be deleted. Reassign those treatments first.');
            } else {
                $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $del->execute([$id]);
                flash_set('success', 'Category deleted.');
            }
        }
        redirect(BASE_URL . '/admin/categories.php');
    }
}

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM treatments t WHERE t.category = c.name) AS treatment_count
    FROM categories c ORDER BY c.name ASC
")->fetchAll();

$pageTitle = 'Categories';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="stat-card bg-white p-4 mb-4">
  <h6 class="fw-heading mb-3">Add a New Category</h6>
  <form method="post" class="d-flex gap-3 flex-wrap align-items-end">
    <?= csrf_field() ?>
    <div class="flex-grow-1" style="max-width:320px;">
      <label class="form-label small fw-semibold">Category Name</label>
      <input type="text" name="name" class="form-control" placeholder="e.g. Dermatology" required>
    </div>
    <button type="submit" name="add_category" value="1" class="btn btn-primary rounded-pill px-4">Add Category</button>
  </form>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Name</th><th>Treatments Using It</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= e($c['name']) ?></td>
          <td><span class="badge bg-light text-dark"><?= (int)$c['treatment_count'] ?></span></td>
          <td class="text-end">
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$categories): ?>
        <tr><td colspan="3" class="text-center text-muted py-4">No categories yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
