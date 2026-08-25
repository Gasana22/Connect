<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("SELECT photo FROM team_members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch();
    $del = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
    $del->execute([$id]);
    if ($member && $member['photo']) {
        $path = __DIR__ . '/../uploads/team/' . $member['photo'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    flash_set('success', 'Team member deleted.');
    redirect(BASE_URL . '/marinka/team.php');
}

$team = $pdo->query("SELECT * FROM team_members ORDER BY sort_order ASC, name ASC")->fetchAll();

$pageTitle = 'Team';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($team) ?> team member(s). Shown on the About page only once at least one is added.</p>
  <a href="<?= e(BASE_URL) ?>/marinka/team-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Add Team Member</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Photo</th><th>Name</th><th>Role</th><th>Order</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($team as $m): ?>
        <tr>
          <td><img src="<?= e(img_url('team', $m['photo'])) ?>" class="thumb-sm" style="object-fit:cover;border-radius:50%;"></td>
          <td><?= e($m['name']) ?></td>
          <td><?= e($m['role_title']) ?></td>
          <td><?= (int)$m['sort_order'] ?></td>
          <td><span class="badge bg-<?= $m['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($m['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/marinka/team-form.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this team member?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$m['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$team): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No team members yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
