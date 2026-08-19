<?php
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$user = currentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $bio = sanitize_input($_POST['bio'] ?? '');
    $businessName = sanitize_input($_POST['business_name'] ?? '');
    $businessCategory = sanitize_input($_POST['business_category'] ?? '');
    $businessWebsite = sanitize_input($_POST['business_website'] ?? '');

    $profilePicPath = $user['profile_pic'];

    if (!empty($_FILES['profile_pic']['name'])) {
        $result = validateImageUpload($_FILES['profile_pic']);
        if (!$result['ok']) {
            $error = $result['error'];
        } else {
            $filename = randomFilename($result['ext']);
            $dest = UPLOAD_DIR_PROFILE_PICS . $filename;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
                if ($profilePicPath && file_exists(__DIR__ . '/' . $profilePicPath)) {
                    unlink(__DIR__ . '/' . $profilePicPath);
                }
                $profilePicPath = UPLOAD_URL_PROFILE_PICS . $filename;
            } else {
                $error = 'Failed to save profile picture.';
            }
        }
    }

    if (!$error) {
        $stmt = getPDO()->prepare(
            'UPDATE users SET bio = ?, profile_pic = ?, business_name = ?, business_category = ?, business_website = ? WHERE id = ?'
        );
        $stmt->execute([
            $bio,
            $profilePicPath,
            $user['account_type'] === 'business' ? $businessName : $user['business_name'],
            $user['account_type'] === 'business' ? $businessCategory : $user['business_category'],
            $user['account_type'] === 'business' ? $businessWebsite : $user['business_website'],
            $user['id'],
        ]);
        $success = 'Profile updated.';
        $stmt = getPDO()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([currentUserId()]);
        $user = $stmt->fetch();
    }
}

$pageTitle = 'Edit Profile';
$activeNav = '';
include __DIR__ . '/includes/layout_header.php';
?>
<h1 class="mt-1" style="margin-bottom: 1.5rem;">Edit Profile</h1>

<div class="card card-pad" style="max-width: 560px;">
  <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= e($success) ?></div><?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>

    <div class="form-group flex items-center gap-2">
      <img src="<?= e(profilePicUrl($user['profile_pic'])) ?>" class="user-avatar" style="width:64px;height:64px;" alt="">
      <div>
        <label class="form-label" for="profile_pic">Profile picture</label>
        <input class="form-control" id="profile_pic" name="profile_pic" type="file" accept="image/*">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="bio">Bio</label>
      <textarea class="form-control" id="bio" name="bio" maxlength="500"><?= e($user['bio'] ?? '') ?></textarea>
    </div>

    <?php if ($user['account_type'] === 'business'): ?>
      <div class="form-group">
        <label class="form-label" for="business_name">Business name</label>
        <input class="form-control" id="business_name" name="business_name" type="text" value="<?= e($user['business_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="business_category">Category</label>
        <select class="form-control" id="business_category" name="business_category">
          <?php foreach (BUSINESS_CATEGORIES as $slug => $label): ?>
            <option value="<?= e($slug) ?>" <?= $user['business_category'] === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label" for="business_website">Website</label>
        <input class="form-control" id="business_website" name="business_website" type="url" value="<?= e($user['business_website'] ?? '') ?>">
      </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Save changes</button>
    <a href="profile.php?id=<?= (int) $user['id'] ?>" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/includes/layout_footer.php'; ?>
