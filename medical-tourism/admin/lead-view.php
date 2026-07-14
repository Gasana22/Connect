<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT l.*, t.name AS treatment_name, p.title AS package_title
    FROM leads l
    LEFT JOIN treatments t ON t.id = l.treatment_id
    LEFT JOIN packages p ON p.id = l.package_id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$lead = $stmt->fetch();

if (!$lead) {
    flash_set('danger', 'Lead not found.');
    redirect(BASE_URL . '/admin/leads.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $status = $_POST['status'] ?? 'new';
    if (in_array($status, ['new', 'contacted', 'converted', 'closed'], true)) {
        $upd = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
        $upd->execute([$status, $id]);
        flash_set('success', 'Lead status updated.');
        redirect(BASE_URL . '/admin/lead-view.php?id=' . $id);
    }
}

$pageTitle = 'Lead: ' . $lead['full_name'];
require_once __DIR__ . '/includes/admin_header.php';
?>

<a href="<?= e(BASE_URL) ?>/admin/leads.php" class="d-inline-block mb-3 small"><i class="bi bi-arrow-left"></i> Back to all leads</a>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="stat-card bg-white p-4">
      <h5 class="fw-heading"><?= e($lead['full_name']) ?></h5>
      <p class="text-muted small mb-4">Submitted <?= e(date('F j, Y \a\t g:i A', strtotime($lead['created_at']))) ?> via <?= e(str_replace('_', ' ', $lead['source'])) ?></p>
      <dl class="row">
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= e($lead['email']) ?></dd>
        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9"><?= e($lead['phone'] ?: '-') ?></dd>
        <dt class="col-sm-3">Country</dt><dd class="col-sm-9"><?= e($lead['country'] ?: '-') ?></dd>
        <dt class="col-sm-3">Treatment</dt><dd class="col-sm-9"><?= e($lead['treatment_name'] ?? '-') ?></dd>
        <dt class="col-sm-3">Package</dt><dd class="col-sm-9"><?= e($lead['package_title'] ?? '-') ?></dd>
      </dl>
      <h6 class="fw-semibold mt-3">Message</h6>
      <p class="border rounded-3 p-3 bg-brand-light"><?= nl2br(e($lead['message'])) ?></p>
      <a href="mailto:<?= e($lead['email']) ?>" class="btn btn-primary rounded-pill px-4"><i class="bi bi-envelope me-1"></i>Reply by Email</a>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="stat-card bg-white p-4">
      <h6 class="fw-heading">Update Status</h6>
      <form method="post">
        <?= csrf_field() ?>
        <select name="status" class="form-select mb-3">
          <?php foreach (['new', 'contacted', 'converted', 'closed'] as $s): ?>
            <option value="<?= $s ?>" <?= $lead['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline-primary w-100 rounded-pill">Update Status</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
