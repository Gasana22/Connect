<?php
require_once __DIR__ . '/includes/init.php';

[$page, $perPage, $offset] = paginate_params(6);
$totalCount = (int)$pdo->query("SELECT COUNT(*) c FROM blog_posts WHERE status='published'")->fetch()['c'];
$stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE status='published' ORDER BY published_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = 'Blog';
$pageDescription = 'Guides and articles to help you plan your medical travel journey with confidence.';
$ph = page_header_banner($pdo, 'blog_banner');
require_once __DIR__ . '/includes/header.php';
?>

<div class="<?= e($ph['class']) ?>" style="<?= e($ph['style']) ?>">
  <div class="container">
    <h1>Patient Resources &amp; Blog</h1>
    <nav class="breadcrumb-light"><a href="<?= e(BASE_URL) ?>/index.php">Home</a> / <span class="active">Blog</span></nav>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <?php if (!$posts): ?>
      <div class="col-12"><div class="alert alert-info">No articles published yet. Check back soon.</div></div>
    <?php endif; ?>
    <?php foreach ($posts as $post): ?>
      <div class="col-lg-4 col-md-6">
        <div class="card shadow-card blog-card">
          <div class="card-img-wrap">
            <img src="<?= e(img_url('blog', $post['image'])) ?>" alt="<?= e($post['title']) ?>">
          </div>
          <div class="card-body">
            <div class="text-muted small mb-2"><i class="bi bi-calendar3"></i> <?= e(date('F j, Y', strtotime($post['published_at']))) ?> &middot; <?= e($post['author']) ?></div>
            <h5><a href="<?= e(BASE_URL) ?>/blog-post.php?slug=<?= e($post['slug']) ?>" class="text-dark stretched-link"><?= e($post['title']) ?></a></h5>
            <p class="text-muted small"><?= e($post['excerpt']) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?= render_pagination($page, $totalCount, $perPage, BASE_URL . '/blog.php') ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
