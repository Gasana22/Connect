<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

// IPs with repeated failed logins in the last 24 hours — likely brute-force.
$bruteForceIps = $pdo->query("
    SELECT ip_address, COALESCE(country, 'Unknown') country, COUNT(*) attempts, MAX(created_at) last_seen
    FROM security_log
    WHERE event_type = 'login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY ip_address, country
    HAVING attempts >= 3
    ORDER BY attempts DESC
")->fetchAll();

// IPs that triggered the suspicious-request signature scan (SQLi/XSS/path
// traversal patterns) in the last 7 days.
$suspiciousIps = $pdo->query("
    SELECT ip_address, COALESCE(country, 'Unknown') country, COUNT(*) hits, MAX(created_at) last_seen
    FROM security_log
    WHERE event_type = 'suspicious_request' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY ip_address, country
    ORDER BY hits DESC
")->fetchAll();

$recentEvents = $pdo->query("SELECT * FROM security_log ORDER BY created_at DESC LIMIT 100")->fetchAll();

$badgeMap = [
    'login_failed' => 'bg-warning',
    'login_locked' => 'bg-danger',
    'login_success' => 'bg-success',
    'suspicious_request' => 'bg-dark',
];

$pageTitle = 'Security Log';
require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($bruteForceIps): ?>
<div class="stat-card bg-white p-3 mb-4 border border-danger">
  <h6 class="fw-heading mb-1 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Possible Brute-Force IPs (last 24h, 3+ failed logins)</h6>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>IP Address</th><th>Country</th><th>Failed Attempts</th><th>Last Seen</th></tr></thead>
      <tbody>
      <?php foreach ($bruteForceIps as $row): ?>
        <tr>
          <td class="fw-semibold"><?= e($row['ip_address']) ?></td>
          <td><?= e($row['country']) ?></td>
          <td><span class="badge bg-danger"><?= (int)$row['attempts'] ?></span></td>
          <td class="text-muted small"><?= e(date('M j, g:i A', strtotime($row['last_seen']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($suspiciousIps): ?>
<div class="stat-card bg-white p-3 mb-4 border border-warning">
  <h6 class="fw-heading mb-1 text-warning-emphasis"><i class="bi bi-bug-fill me-1"></i>IPs Sending Suspicious Requests (last 7 days)</h6>
  <p class="text-muted small">Requests containing common attack patterns (SQL injection, XSS, path traversal). These are logged only, never blocked automatically.</p>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>IP Address</th><th>Country</th><th>Hits</th><th>Last Seen</th></tr></thead>
      <tbody>
      <?php foreach ($suspiciousIps as $row): ?>
        <tr>
          <td class="fw-semibold"><?= e($row['ip_address']) ?></td>
          <td><?= e($row['country']) ?></td>
          <td><span class="badge bg-dark"><?= (int)$row['hits'] ?></span></td>
          <td class="text-muted small"><?= e(date('M j, g:i A', strtotime($row['last_seen']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!$bruteForceIps && !$suspiciousIps): ?>
<div class="alert alert-success"><i class="bi bi-shield-check me-1"></i>No brute-force or suspicious-request activity detected recently.</div>
<?php endif; ?>

<div class="stat-card bg-white p-3">
  <h6 class="fw-heading mb-3">Recent Security Events</h6>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead class="table-light">
        <tr><th>Time</th><th>Event</th><th>IP Address</th><th>Country</th><th>Detail</th></tr>
      </thead>
      <tbody>
      <?php foreach ($recentEvents as $ev): ?>
        <tr>
          <td class="text-muted small text-nowrap"><?= e(date('M j, g:i A', strtotime($ev['created_at']))) ?></td>
          <td><span class="badge <?= e($badgeMap[$ev['event_type']] ?? 'bg-secondary') ?>"><?= e(str_replace('_', ' ', ucfirst($ev['event_type']))) ?></span></td>
          <td class="small"><?= e($ev['ip_address']) ?></td>
          <td class="small"><?= e($ev['country'] ?: 'Unknown') ?></td>
          <td class="small text-truncate text-muted" style="max-width:320px;"><?= e($ev['detail'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recentEvents): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No security events recorded yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
