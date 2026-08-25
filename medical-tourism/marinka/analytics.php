<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$totalViews = (int)$pdo->query("SELECT COUNT(*) c FROM page_views")->fetch()['c'];
$viewsToday = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE created_at >= CURDATE()")->fetch()['c'];
$views7d = (int)$pdo->query("SELECT COUNT(*) c FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['c'];
$unique7d = (int)$pdo->query("SELECT COUNT(DISTINCT visitor_hash) c FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['c'];

$topPages = $pdo->query("
    SELECT url, COUNT(*) c FROM page_views
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY url ORDER BY c DESC LIMIT 10
")->fetchAll();

$topCountries = $pdo->query("
    SELECT COALESCE(country, 'Unknown') country, COUNT(*) c FROM page_views
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY country ORDER BY c DESC LIMIT 10
")->fetchAll();

$recentVisits = $pdo->query("SELECT * FROM page_views ORDER BY created_at DESC LIMIT 50")->fetchAll();

$pageTitle = 'Analytics';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-3 text-center">
      <div class="price-tag fs-2"><?= number_format($totalViews) ?></div>
      <div class="text-muted small">Total Page Views</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-3 text-center">
      <div class="price-tag fs-2"><?= number_format($viewsToday) ?></div>
      <div class="text-muted small">Views Today</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-3 text-center">
      <div class="price-tag fs-2"><?= number_format($views7d) ?></div>
      <div class="text-muted small">Views (7 Days)</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-white p-3 text-center">
      <div class="price-tag fs-2"><?= number_format($unique7d) ?></div>
      <div class="text-muted small">Unique Visitors (7 Days)</div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="stat-card bg-white p-3 h-100">
      <h6 class="fw-heading mb-3">Top Pages (30 Days)</h6>
      <table class="table table-sm align-middle mb-0">
        <?php foreach ($topPages as $p): ?>
          <tr><td class="text-truncate" style="max-width:280px;"><?= e($p['url']) ?></td><td class="text-end fw-semibold"><?= (int)$p['c'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topPages): ?><tr><td class="text-muted small">No data yet.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="stat-card bg-white p-3 h-100">
      <h6 class="fw-heading mb-3">Top Countries (30 Days)</h6>
      <table class="table table-sm align-middle mb-0">
        <?php foreach ($topCountries as $c): ?>
          <tr><td><?= e($c['country']) ?></td><td class="text-end fw-semibold"><?= (int)$c['c'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topCountries): ?><tr><td class="text-muted small">No data yet.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>

<div class="stat-card bg-white p-3">
  <h6 class="fw-heading mb-3">Recent Visits</h6>
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead class="table-light">
        <tr><th>Time</th><th>IP Address</th><th>Country</th><th>Page</th><th>Referrer</th></tr>
      </thead>
      <tbody>
      <?php foreach ($recentVisits as $v): ?>
        <tr>
          <td class="text-muted small text-nowrap"><?= e(date('M j, g:i A', strtotime($v['created_at']))) ?></td>
          <td class="small"><?= e($v['ip_address']) ?></td>
          <td class="small"><?= e($v['country'] ?: 'Unknown') ?></td>
          <td class="small text-truncate" style="max-width:220px;"><?= e($v['url']) ?></td>
          <td class="small text-truncate text-muted" style="max-width:200px;"><?= e($v['referrer'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$recentVisits): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No visits recorded yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="text-muted small mb-0">Country lookups require this server to have outbound internet access (uses the free ip-api.com service). "Unique visitors" is estimated from a same-day hash of IP + browser, not a tracking cookie.</p>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
