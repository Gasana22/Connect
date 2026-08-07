<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("SELECT logo FROM partners WHERE id = ?");
    $stmt->execute([$id]);
    $partner = $stmt->fetch();
    $del = $pdo->prepare("DELETE FROM partners WHERE id = ?");
    $del->execute([$id]);
    if ($partner && $partner['logo']) {
        $path = __DIR__ . '/../uploads/partners/' . $partner['logo'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    flash_set('success', 'Partner deleted.');
    redirect(BASE_URL . '/admin/partners.php');
}

$partners = $pdo->query("SELECT * FROM partners ORDER BY sort_order ASC, name ASC")->fetchAll();

$pageTitle = 'Partners';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($partners) ?> partner(s). Shown as a logo strip on the About page.</p>
  <a href="<?= e(BASE_URL) ?>/admin/partner-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Partner</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Logo</th><th>Name</th><th>Website</th><th>Order</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($partners as $p): ?>
        <tr>
          <td><img src="<?= e(img_url('partners', $p['logo'])) ?>" class="thumb-sm" style="object-fit:contain;background:#f5f4fb;"></td>
          <td><?= e($p['name']) ?></td>
          <td><?= $p['website_url'] ? '<a href="' . e($p['website_url']) . '" target="_blank">' . e($p['website_url']) . '</a>' : '<span class="text-muted">&mdash;</span>' ?></td>
          <td><?= (int)$p['sort_order'] ?></td>
          <td><span class="badge bg-<?= $p['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($p['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/admin/partner-form.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this partner?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$partners): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No partners yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
