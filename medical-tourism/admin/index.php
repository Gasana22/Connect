<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$totalLeads = (int)$pdo->query("SELECT COUNT(*) c FROM leads")->fetch()['c'];
$newLeads = (int)$pdo->query("SELECT COUNT(*) c FROM leads WHERE status = 'new'")->fetch()['c'];
$weekLeads = (int)$pdo->query("SELECT COUNT(*) c FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['c'];
$totalTreatments = (int)$pdo->query("SELECT COUNT(*) c FROM treatments")->fetch()['c'];
$totalHospitals = (int)$pdo->query("SELECT COUNT(*) c FROM hospitals")->fetch()['c'];
$totalPackages = (int)$pdo->query("SELECT COUNT(*) c FROM packages")->fetch()['c'];

$recentLeads = $pdo->query("
    SELECT l.*, t.name AS treatment_name
    FROM leads l LEFT JOIN treatments t ON t.id = l.treatment_id
    ORDER BY l.created_at DESC LIMIT 8
")->fetchAll();

$leadsByDay = $pdo->query("
    SELECT DATE(created_at) d, COUNT(*) c FROM leads
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) ORDER BY d ASC
")->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-4 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-4">
      <div class="icon-box mb-3"><i class="bi bi-inbox-fill"></i></div>
      <div class="fs-3 fw-bold"><?= $totalLeads ?></div>
      <div class="text-muted small">Total Leads</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-4">
      <div class="icon-box mb-3"><i class="bi bi-envelope-exclamation-fill"></i></div>
      <div class="fs-3 fw-bold"><?= $newLeads ?></div>
      <div class="text-muted small">New Leads</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-4">
      <div class="icon-box mb-3"><i class="bi bi-calendar-week-fill"></i></div>
      <div class="fs-3 fw-bold"><?= $weekLeads ?></div>
      <div class="text-muted small">Leads This Week</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-4">
      <div class="icon-box mb-3"><i class="bi bi-box-seam-fill"></i></div>
      <div class="fs-3 fw-bold"><?= $totalPackages ?></div>
      <div class="text-muted small">Active Packages</div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="stat-card bg-white p-4 text-center">
      <div class="fs-4 fw-bold text-primary-brand"><?= $totalTreatments ?></div>
      <div class="text-muted small">Treatments</div>
      <a href="<?= e(BASE_URL) ?>/admin/treatments.php" class="small">Manage &rarr;</a>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card bg-white p-4 text-center">
      <div class="fs-4 fw-bold text-primary-brand"><?= $totalHospitals ?></div>
      <div class="text-muted small">Hospitals</div>
      <a href="<?= e(BASE_URL) ?>/admin/hospitals.php" class="small">Manage &rarr;</a>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card bg-white p-4 text-center">
      <div class="fs-4 fw-bold text-primary-brand"><?= $totalPackages ?></div>
      <div class="text-muted small">Packages</div>
      <a href="<?= e(BASE_URL) ?>/admin/packages.php" class="small">Manage &rarr;</a>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="stat-card bg-white p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-heading mb-0">Leads &mdash; Last 14 Days</h6>
      </div>
      <?php if ($leadsByDay): $max = max(array_column($leadsByDay, 'c')) ?: 1; ?>
        <div class="d-flex align-items-end gap-2" style="height:160px;">
          <?php foreach ($leadsByDay as $row): ?>
            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-end h-100">
              <div class="w-100 rounded-top" style="background:var(--brand-primary);height:<?= max(6, (int)($row['c'] / $max * 130)) ?>px;" title="<?= (int)$row['c'] ?> leads"></div>
              <div class="small text-muted mt-1"><?= e(date('M j', strtotime($row['d']))) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted small mb-0">No lead activity in the last 14 days yet.</p>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="stat-card bg-white p-4 h-100">
      <h6 class="fw-heading mb-3">Quick Actions</h6>
      <div class="d-grid gap-2">
        <a href="<?= e(BASE_URL) ?>/admin/treatment-form.php" class="btn btn-outline-primary text-start"><i class="bi bi-plus-circle me-2"></i>Add Treatment</a>
        <a href="<?= e(BASE_URL) ?>/admin/package-form.php" class="btn btn-outline-primary text-start"><i class="bi bi-plus-circle me-2"></i>Add Package</a>
        <a href="<?= e(BASE_URL) ?>/admin/blog-form.php" class="btn btn-outline-primary text-start"><i class="bi bi-plus-circle me-2"></i>Write Blog Post</a>
        <a href="<?= e(BASE_URL) ?>/admin/leads.php" class="btn btn-outline-primary text-start"><i class="bi bi-inbox me-2"></i>Review New Leads</a>
      </div>
    </div>
  </div>
</div>

<div class="stat-card bg-white p-4 mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-heading mb-0">Recent Leads</h6>
    <a href="<?= e(BASE_URL) ?>/admin/leads.php" class="small">View all &rarr;</a>
  </div>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Name</th><th>Email</th><th>Treatment</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentLeads as $lead): ?>
        <tr>
          <td><a href="<?= e(BASE_URL) ?>/admin/lead-view.php?id=<?= (int)$lead['id'] ?>"><?= e($lead['full_name']) ?></a></td>
          <td><?= e($lead['email']) ?></td>
          <td><?= e($lead['treatment_name'] ?? '-') ?></td>
          <td><span class="badge bg-<?= $lead['status'] === 'new' ? 'warning' : ($lead['status'] === 'converted' ? 'success' : 'secondary') ?>"><?= e(ucfirst($lead['status'])) ?></span></td>
          <td class="text-muted small"><?= e(date('M j, Y', strtotime($lead['created_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recentLeads): ?>
          <tr><td colspan="5" class="text-center text-muted">No leads yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
