<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Lead deleted.');
    redirect(BASE_URL . '/admin/leads.php');
}

$statusFilter = trim($_GET['status'] ?? '');
$where = '1=1';
$params = [];
if ($statusFilter !== '') {
    $where = 'l.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT l.*, t.name AS treatment_name FROM leads l
    LEFT JOIN treatments t ON t.id = l.treatment_id
    WHERE $where ORDER BY l.created_at DESC
");
$stmt->execute($params);
$leads = $stmt->fetchAll();

$pageTitle = 'Leads / Enquiries';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($leads) ?> lead(s)</p>
  <div class="btn-group">
    <a href="<?= e(BASE_URL) ?>/admin/leads.php" class="btn btn-sm btn-outline-secondary <?= $statusFilter === '' ? 'active' : '' ?>">All</a>
    <a href="<?= e(BASE_URL) ?>/admin/leads.php?status=new" class="btn btn-sm btn-outline-secondary <?= $statusFilter === 'new' ? 'active' : '' ?>">New</a>
    <a href="<?= e(BASE_URL) ?>/admin/leads.php?status=contacted" class="btn btn-sm btn-outline-secondary <?= $statusFilter === 'contacted' ? 'active' : '' ?>">Contacted</a>
    <a href="<?= e(BASE_URL) ?>/admin/leads.php?status=converted" class="btn btn-sm btn-outline-secondary <?= $statusFilter === 'converted' ? 'active' : '' ?>">Converted</a>
    <a href="<?= e(BASE_URL) ?>/admin/leads.php?status=closed" class="btn btn-sm btn-outline-secondary <?= $statusFilter === 'closed' ? 'active' : '' ?>">Closed</a>
  </div>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Name</th><th>Contact</th><th>Treatment</th><th>Source</th><th>Status</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($leads as $lead): ?>
        <tr>
          <td><a href="<?= e(BASE_URL) ?>/admin/lead-view.php?id=<?= (int)$lead['id'] ?>"><?= e($lead['full_name']) ?></a></td>
          <td><?= e($lead['email']) ?><br><span class="text-muted small"><?= e($lead['phone']) ?></span></td>
          <td><?= e($lead['treatment_name'] ?? '-') ?></td>
          <td><span class="badge bg-light text-dark"><?= e(str_replace('_', ' ', $lead['source'])) ?></span></td>
          <td><span class="badge bg-<?= $lead['status'] === 'new' ? 'warning' : ($lead['status'] === 'converted' ? 'success' : ($lead['status'] === 'closed' ? 'dark' : 'secondary')) ?>"><?= e(ucfirst($lead['status'])) ?></span></td>
          <td class="text-muted small"><?= e(date('M j, Y', strtotime($lead['created_at']))) ?></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/admin/lead-view.php?id=<?= (int)$lead['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this lead?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$lead['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$leads): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No leads found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
