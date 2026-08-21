<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = ['title' => '', 'excerpt' => '', 'content' => '', 'image' => '', 'author' => $_SESSION['admin_name'] ?? '', 'status' => 'published', 'published_at' => date('Y-m-d')];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Blog post not found.');
        redirect(BASE_URL . '/marinka/blog.php');
    }
    $post = $found;
    $post['published_at'] = $post['published_at'] ? date('Y-m-d', strtotime($post['published_at'])) : date('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $status = $_POST['status'] === 'draft' ? 'draft' : 'published';
    $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] . ' 09:00:00' : date('Y-m-d H:i:s');

    if ($title === '') {
        flash_set('danger', 'Title is required.');
    } else {
        $image = handle_image_upload('image', __DIR__ . '/../uploads/blog', $post['image']);
        $slug = unique_slug($pdo, 'blog_posts', $title, $id ?: null);

        if ($id) {
            $stmt = $pdo->prepare("UPDATE blog_posts SET title=?, slug=?, excerpt=?, content=?, image=?, author=?, status=?, published_at=? WHERE id=?");
            $stmt->execute([$title, $slug, $excerpt, $content, $image, $author, $status, $publishedAt, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, image, author, status, published_at) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $slug, $excerpt, $content, $image, $author, $status, $publishedAt]);
        }
        flash_set('success', 'Blog post saved successfully.');
        redirect(BASE_URL . '/marinka/blog.php');
    }
}

$pageTitle = $id ? 'Edit Blog Post' : 'Write New Post';
require_once __DIR__ . '/includes/admin_header.php';
?>

<form method="post" enctype="multipart/form-data" class="stat-card bg-white p-4">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-12">
      <label class="form-label small fw-semibold">Title</label>
      <input type="text" name="title" class="form-control" value="<?= e($post['title']) ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Excerpt</label>
      <input type="text" name="excerpt" class="form-control" value="<?= e($post['excerpt']) ?>" maxlength="300">
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Content (HTML allowed)</label>
      <textarea name="content" rows="10" class="form-control"><?= e($post['content']) ?></textarea>
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Author</label>
      <input type="text" name="author" class="form-control" value="<?= e($post['author']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Publish Date</label>
      <input type="date" name="published_at" class="form-control" value="<?= e($post['published_at']) ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Status</label>
      <select name="status" class="form-select">
        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label small fw-semibold">Featured Image</label>
      <input type="file" name="image" class="form-control" accept="image/*" data-preview="#imgPreview">
      <img id="imgPreview" src="<?= e(img_url('blog', $post['image'])) ?>" class="thumb-sm mt-2" style="width:100px;height:100px;">
    </div>
  </div>
  <hr class="my-4">
  <button type="submit" class="btn btn-primary rounded-pill px-4">Save Post</button>
  <a href="<?= e(BASE_URL) ?>/marinka/blog.php" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
