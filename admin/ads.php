<?php
require_once __DIR__ . '/includes/auth.php';
requireAdminAuth();

$pdo = getPDO();
$error = flash('ad_error');
$message = flash('ad_message');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateAdminCsrf();

    if (isset($_POST['delete_ad'])) {
        $adId = filter_input(INPUT_POST, 'ad_id', FILTER_VALIDATE_INT);
        if ($adId) {
            $stmt = $pdo->prepare('SELECT image_url FROM ads WHERE id = ?');
            $stmt->execute([$adId]);
            $img = $stmt->fetchColumn();
            $pdo->prepare('DELETE FROM ads WHERE id = ?')->execute([$adId]);
            if ($img && file_exists(__DIR__ . '/../' . ltrim($img, '/'))) {
                unlink(__DIR__ . '/../' . ltrim($img, '/'));
            }
            flash('ad_message', 'Ad deleted.');
        }
        header('Location: ads.php');
        exit;
    }

    $data = [
        'title'           => sanitize_input($_POST['title'] ?? ''),
        'description'     => sanitize_input($_POST['description'] ?? ''),
        'target_url'      => filter_var($_POST['target_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
        'sponsor'         => sanitize_input($_POST['sponsor'] ?? ''),
        'duration'        => (int) ($_POST['duration'] ?? 15),
        'start_date'      => str_replace('T', ' ', $_POST['start_date'] ?? '') . ':00',
        'end_date'        => str_replace('T', ' ', $_POST['end_date'] ?? '') . ':00',
        'max_impressions' => (int) ($_POST['max_impressions'] ?? 0),
        'budget'          => is_numeric($_POST['budget'] ?? null) ? (float) $_POST['budget'] : null,
        'is_active'       => isset($_POST['is_active']) ? 1 : 0,
    ];
    $adId = filter_input(INPUT_POST, 'ad_id', FILTER_VALIDATE_INT);

    if ($data['title'] === '' || $data['target_url'] === '' || $data['sponsor'] === '') {
        flash('ad_error', 'Title, sponsor, and a valid target URL are required.');
        header('Location: ads.php');
        exit;
    }

    $imageUrl = null;
    if (!empty($_FILES['image']['name'])) {
        $result = validateImageUpload($_FILES['image']);
        if (!$result['ok']) {
            flash('ad_error', $result['error']);
            header('Location: ads.php');
            exit;
        }
        $filename = randomFilename($result['ext']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR_ADS . $filename)) {
            $imageUrl = UPLOAD_URL_ADS . $filename;
        }
    }

    if ($adId) {
        $data['id'] = $adId;
        $data['image_url'] = $imageUrl;
        $stmt = $pdo->prepare(
            "UPDATE ads SET title=:title, description=:description, image_url=COALESCE(:image_url, image_url),
             target_url=:target_url, sponsor=:sponsor, duration=:duration, start_date=:start_date, end_date=:end_date,
             max_impressions=:max_impressions, budget=:budget, is_active=:is_active WHERE id=:id"
        );
        $stmt->execute($data);
        flash('ad_message', 'Ad updated.');
    } else {
        if (!$imageUrl) {
            flash('ad_error', 'An image is required for a new ad.');
            header('Location: ads.php');
            exit;
        }
        $data['image_url'] = $imageUrl;
        $stmt = $pdo->prepare(
            'INSERT INTO ads (title, description, image_url, target_url, sponsor, duration, start_date, end_date, max_impressions, budget, is_active)
             VALUES (:title, :description, :image_url, :target_url, :sponsor, :duration, :start_date, :end_date, :max_impressions, :budget, :is_active)'
        );
        $stmt->execute($data);
        flash('ad_message', 'Ad created.');
    }
    header('Location: ads.php');
    exit;
}

$ads = $pdo->query('SELECT * FROM ads ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Ad Management';
$activeAdminNav = 'ads';
include __DIR__ . '/includes/layout_header.php';
?>
<div class="admin-header"><h1>Ad Management</h1></div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

<div class="card card-pad mt-2" style="margin-bottom: 2rem;">
  <h2 style="margin-bottom:1rem;">New ad</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
    <div class="form-group"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
    <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" name="description"></textarea></div>
    <div class="form-group"><label class="form-label">Sponsor</label><input class="form-control" name="sponsor" required></div>
    <div class="form-group"><label class="form-label">Target URL</label><input class="form-control" name="target_url" type="url" required></div>
    <div class="form-group"><label class="form-label">Image</label><input class="form-control" name="image" type="file" accept="image/*" required></div>
    <div class="form-group"><label class="form-label">Start date</label><input class="form-control" name="start_date" type="datetime-local" required></div>
    <div class="form-group"><label class="form-label">End date</label><input class="form-control" name="end_date" type="datetime-local" required></div>
    <div class="form-group"><label class="form-label">Max impressions (0 = unlimited)</label><input class="form-control" name="max_impressions" type="number" min="0" value="100000"></div>
    <div class="form-group"><label class="form-label">Budget</label><input class="form-control" name="budget" type="number" step="0.01" min="0"></div>
    <div class="form-group"><label><input type="checkbox" name="is_active" checked> Active</label></div>
    <button type="submit" class="btn btn-primary">Create ad</button>
  </form>
</div>

<h2 style="margin-bottom:1rem;">All ads</h2>
<table class="data-table">
  <thead><tr><th>Title</th><th>Sponsor</th><th>Active</th><th>Impressions cap</th><th>Dates</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($ads as $ad): ?>
      <tr>
        <td><?= e($ad['title']) ?></td>
        <td><?= e($ad['sponsor']) ?></td>
        <td><?= $ad['is_active'] ? '<span class="badge badge-business">Active</span>' : '<span class="badge badge-category">Paused</span>' ?></td>
        <td><?= $ad['max_impressions'] == 0 ? 'Unlimited' : number_format($ad['max_impressions']) ?></td>
        <td><?= e(date('M j', strtotime($ad['start_date']))) ?> – <?= e(date('M j', strtotime($ad['end_date']))) ?></td>
        <td>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this ad?');">
            <input type="hidden" name="csrf_token" value="<?= e(adminCsrfToken()) ?>">
            <input type="hidden" name="ad_id" value="<?= (int) $ad['id'] ?>">
            <button type="submit" name="delete_ad" value="1" class="btn btn-danger" style="padding:4px 10px;"><i class="fas fa-trash"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
