<?php
require_once __DIR__ . '/includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Article Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Article not found</h1><a href="' . e(BASE_URL) . '/blog.php" class="btn btn-primary rounded-pill">Back to Blog</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$related = $pdo->prepare("SELECT * FROM blog_posts WHERE id != ? AND status='published' ORDER BY published_at DESC LIMIT 3");
$related->execute([$post['id']]);
$relatedPosts = $related->fetchAll();

$pageTitle = $post['title'];
$pageDescription = $post['excerpt'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1><?= e($post['title']) ?></h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <a href="<?= e(BASE_URL) ?>/blog.php">Blog</a> / <span class="active"><?= e($post['title']) ?></span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-8">
      <img src="<?= e(img_url('blog', $post['image'])) ?>" class="w-100 rounded-4 mb-4" alt="<?= e($post['title']) ?>" style="max-height:420px;object-fit:cover;">
      <div class="text-muted small mb-3"><i class="bi bi-calendar3"></i> <?= e(date('F j, Y', strtotime($post['published_at']))) ?> &middot; By <?= e($post['author']) ?></div>
      <div class="blog-content"><?= $post['content'] ?></div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-card p-4 mb-4">
        <h6 class="fw-heading">Have a question?</h6>
        <p class="text-muted small">Our patient coordinators are happy to help you plan your treatment abroad.</p>
        <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-primary rounded-pill w-100">Get Free Quote</a>
      </div>
      <?php if ($relatedPosts): ?>
      <h6 class="fw-heading">More Articles</h6>
      <?php foreach ($relatedPosts as $rp): ?>
        <a href="<?= e(BASE_URL) ?>/blog-post.php?slug=<?= e($rp['slug']) ?>" class="d-flex align-items-center gap-3 py-2 text-dark border-bottom">
          <img src="<?= e(img_url('blog', $rp['image'])) ?>" class="thumb-sm" alt="<?= e($rp['title']) ?>">
          <div class="small fw-semibold"><?= e($rp['title']) ?></div>
        </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
