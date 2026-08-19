<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getPDO();

function dailySeries(PDO $pdo, string $table, string $dateCol, int $days = 7): array {
    $stmt = $pdo->prepare(
        "SELECT DATE($dateCol) AS d, COUNT(*) AS c FROM $table
         WHERE $dateCol >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
         GROUP BY DATE($dateCol)"
    );
    $stmt->bindValue(':days', $days - 1, PDO::PARAM_INT);
    $stmt->execute();
    $rows = array_column($stmt->fetchAll(), 'c', 'd');

    $series = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $series[] = ['label' => date('D', strtotime($date)), 'value' => (int) ($rows[$date] ?? 0)];
    }
    return $series;
}

function renderBarChart(array $series, string $color): void {
    $max = max(1, max(array_column($series, 'value')));
    $barWidth = 36;
    $gap = 20;
    $chartHeight = 120;
    $width = count($series) * ($barWidth + $gap);
    echo '<svg width="100%" viewBox="0 0 ' . $width . ' ' . ($chartHeight + 30) . '" role="img" aria-label="Bar chart">';
    foreach ($series as $i => $point) {
        $barHeight = $point['value'] > 0 ? max(4, ($point['value'] / $max) * $chartHeight) : 2;
        $x = $i * ($barWidth + $gap);
        $y = $chartHeight - $barHeight;
        echo '<rect x="' . $x . '" y="' . $y . '" width="' . $barWidth . '" height="' . $barHeight . '" rx="6" fill="' . $color . '"><title>' . e($point['label']) . ': ' . $point['value'] . '</title></rect>';
        echo '<text x="' . ($x + $barWidth / 2) . '" y="' . ($chartHeight + 20) . '" text-anchor="middle" font-size="12" fill="var(--ink-faint)">' . e($point['label']) . '</text>';
    }
    echo '</svg>';
}

$impressions7d = dailySeries($pdo, 'ad_impressions', 'view_time');
$newUsers7d = dailySeries($pdo, 'users', 'created_at');
$newVideos7d = dailySeries($pdo, 'videos', 'created_at');

$topAds = $pdo->query(
    'SELECT a.title, a.sponsor, COUNT(ai.id) AS impressions
     FROM ads a LEFT JOIN ad_impressions ai ON ai.ad_id = a.id
     GROUP BY a.id ORDER BY impressions DESC LIMIT 5'
)->fetchAll();

$pageTitle = 'Analytics';
$activeAdminNav = 'analytics';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="admin-header"><h1>Analytics</h1></div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));">
  <div class="stat-card">
    <h3>Ad impressions — last 7 days</h3>
    <?php renderBarChart($impressions7d, 'var(--brand)'); ?>
  </div>
  <div class="stat-card">
    <h3>New users — last 7 days</h3>
    <?php renderBarChart($newUsers7d, 'var(--accent)'); ?>
  </div>
  <div class="stat-card">
    <h3>New videos — last 7 days</h3>
    <?php renderBarChart($newVideos7d, 'var(--brand)'); ?>
  </div>
</div>

<h2 style="margin-bottom:1rem;">Top ads by impressions</h2>
<table class="data-table">
  <thead><tr><th>Title</th><th>Sponsor</th><th>Impressions</th></tr></thead>
  <tbody>
    <?php foreach ($topAds as $ad): ?>
      <tr><td><?= e($ad['title']) ?></td><td><?= e($ad['sponsor']) ?></td><td><?= number_format($ad['impressions']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
