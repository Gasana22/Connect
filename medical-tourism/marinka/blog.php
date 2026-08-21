<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([(int)$_POST['delete_id']]);
    flash_set('success', 'Blog post deleted.');
    redirect(BASE_URL . '/marinka/blog.php');
}

$posts = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Blog Posts';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?= count($posts) ?> post(s)</p>
  <a href="<?= e(BASE_URL) ?>/marinka/blog-form.php" class="btn btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Write New Post</a>
</div>

<div class="stat-card bg-white p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr><th>Image</th><th>Title</th><th>Author</th><th>Published</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($posts as $post): ?>
        <tr>
          <td><img src="<?= e(img_url('blog', $post['image'])) ?>" class="thumb-sm"></td>
          <td><?= e($post['title']) ?></td>
          <td><?= e($post['author']) ?></td>
          <td class="text-muted small"><?= $post['published_at'] ? e(date('M j, Y', strtotime($post['published_at']))) : '-' ?></td>
          <td><span class="badge bg-<?= $post['status'] === 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($post['status'])) ?></span></td>
          <td class="text-end">
            <a href="<?= e(BASE_URL) ?>/marinka/blog-form.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form action="" method="post" class="d-inline" onsubmit="return confirm('Delete this blog post?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= (int)$post['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$posts): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No blog posts yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
