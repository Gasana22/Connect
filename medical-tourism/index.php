<?php
require_once __DIR__ . '/includes/init.php';

$treatments = $pdo->query("SELECT * FROM treatments WHERE status = 'published' AND featured = 1 ORDER BY created_at DESC LIMIT 8")->fetchAll();
$destinations = $pdo->query("SELECT * FROM destinations WHERE status = 'published' ORDER BY created_at ASC LIMIT 6")->fetchAll();
$packages = $pdo->query("
    SELECT p.*, t.name AS treatment_name, h.name AS hospital_name, d.name AS destination_name
    FROM packages p
    JOIN treatments t ON t.id = p.treatment_id
    JOIN hospitals h ON h.id = p.hospital_id
    JOIN destinations d ON d.id = h.destination_id
    WHERE p.status = 'published' AND p.featured = 1
    ORDER BY p.created_at DESC LIMIT 3
")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials WHERE status = 'published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
$blogPosts = $pdo->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 3")->fetchAll();
$hospitalCount = (int)$pdo->query("SELECT COUNT(*) c FROM hospitals WHERE status='published'")->fetch()['c'];
$destinationCount = (int)$pdo->query("SELECT COUNT(*) c FROM destinations WHERE status='published'")->fetch()['c'];
$treatmentCount = (int)$pdo->query("SELECT COUNT(*) c FROM treatments WHERE status='published'")->fetch()['c'];

$pageTitle = 'World-Class Healthcare, Made Affordable';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container position-relative">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <span class="section-title-badge bg-white bg-opacity-10 text-white">Trusted by 12,000+ patients worldwide</span>
        <h1 class="mt-3">Quality healthcare abroad, without the guesswork.</h1>
        <p class="lead mt-3">Compare accredited hospitals, specialist doctors and all-inclusive treatment packages. Get a free personalized quote in under 24 hours.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-accent btn-lg rounded-pill px-4">Get My Free Quote</a>
          <a href="<?= e(BASE_URL) ?>/treatments.php" class="btn btn-outline-light btn-lg rounded-pill px-4">Browse Treatments</a>
        </div>
        <div class="row hero-stats mt-5 py-3 mx-0">
          <div class="col-4 text-center">
            <div class="stat-num"><?= $treatmentCount ?>+</div>
            <div class="small">Treatments</div>
          </div>
          <div class="col-4 text-center border-start border-end border-light border-opacity-25">
            <div class="stat-num"><?= $hospitalCount ?>+</div>
            <div class="small">Partner Hospitals</div>
          </div>
          <div class="col-4 text-center">
            <div class="stat-num"><?= $destinationCount ?>+</div>
            <div class="small">Destinations</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-wave">
    <svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path fill="#ffffff" d="M0,32 C240,70 480,0 720,18 C960,36 1200,70 1440,28 L1440,70 L0,70 Z"></path></svg>
  </div>
</section>

<div class="container">
  <div class="search-card p-4 p-md-5">
    <form action="<?= e(BASE_URL) ?>/search.php" method="get" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label fw-semibold small">What treatment do you need?</label>
        <input type="text" name="q" class="form-control" placeholder="e.g. Dental Implants, Rhinoplasty">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold small">Destination</label>
        <select name="destination" class="form-select">
          <option value="">Any destination</option>
          <?php foreach ($destinations as $d): ?>
            <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold small">Category</label>
        <select name="category" class="form-select">
          <option value="">Any category</option>
          <?php foreach ($pdo->query("SELECT DISTINCT category FROM treatments ORDER BY category") as $cat): ?>
            <option value="<?= e($cat['category']) ?>"><?= e($cat['category']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Search</button>
      </div>
    </form>
  </div>
</div>

<!-- Popular Treatments -->
<section class="py-5 mt-4">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">Popular Treatments</span>
      <h2 class="mt-3 fw-heading">Explore Top Treatments Abroad</h2>
      <p class="text-muted">Transparent pricing on the procedures patients request most.</p>
    </div>
    <div class="row g-4">
      <?php foreach ($treatments as $t): ?>
        <div class="col-lg-3 col-md-6">
          <div class="card shadow-card treatment-card">
            <div class="card-img-wrap">
              <span class="badge-featured">Featured</span>
              <img src="<?= e(img_url('treatments', $t['image'])) ?>" alt="<?= e($t['name']) ?>">
            </div>
            <div class="card-body">
              <span class="badge-category"><?= e($t['category']) ?></span>
              <h5 class="mt-3"><a href="<?= e(BASE_URL) ?>/treatment-detail.php?slug=<?= e($t['slug']) ?>" class="text-dark stretched-link"><?= e($t['name']) ?></a></h5>
              <p class="text-muted small mb-2"><?= e($t['summary']) ?></p>
              <div class="price-tag">From <?= format_price($t['min_price']) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="<?= e(BASE_URL) ?>/treatments.php" class="btn btn-outline-primary rounded-pill px-4">View All Treatments</a>
    </div>
  </div>
</section>

<!-- Why choose us -->
<section class="py-5 bg-brand-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">Why <?= e($siteName) ?></span>
      <h2 class="mt-3 fw-heading">Care You Can Trust, From Enquiry to Recovery</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-3 col-6">
        <div class="icon-box mb-3"><i class="bi bi-patch-check-fill"></i></div>
        <h6 class="fw-semibold">Accredited Hospitals</h6>
        <p class="text-muted small">Every partner hospital is vetted for international accreditation and safety standards.</p>
      </div>
      <div class="col-md-3 col-6">
        <div class="icon-box mb-3"><i class="bi bi-cash-coin"></i></div>
        <h6 class="fw-semibold">Transparent Pricing</h6>
        <p class="text-muted small">See upfront package pricing with no hidden fees before you decide to travel.</p>
      </div>
      <div class="col-md-3 col-6">
        <div class="icon-box mb-3"><i class="bi bi-person-workspace"></i></div>
        <h6 class="fw-semibold">Dedicated Coordinator</h6>
        <p class="text-muted small">A personal patient coordinator supports you from first enquiry through recovery.</p>
      </div>
      <div class="col-md-3 col-6">
        <div class="icon-box mb-3"><i class="bi bi-airplane-fill"></i></div>
        <h6 class="fw-semibold">Full Travel Support</h6>
        <p class="text-muted small">Hotel, transfers and itinerary planning handled so you can focus on your health.</p>
      </div>
    </div>
  </div>
</section>

<!-- Destinations -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">Top Destinations</span>
      <h2 class="mt-3 fw-heading">Where Patients Are Traveling</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($destinations as $d): ?>
        <div class="col-lg-4 col-md-6">
          <div class="card shadow-card destination-card">
            <div class="card-img-wrap">
              <img src="<?= e(img_url('destinations', $d['image'])) ?>" alt="<?= e($d['name']) ?>">
            </div>
            <div class="card-body">
              <h5><a href="<?= e(BASE_URL) ?>/destination-detail.php?slug=<?= e($d['slug']) ?>" class="text-dark stretched-link"><?= e($d['name']) ?></a></h5>
              <p class="text-muted small mb-2"><?= e($d['summary']) ?></p>
              <span class="badge-category">Save <?= e($d['avg_savings']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Featured Packages -->
<?php if ($packages): ?>
<section class="py-5 bg-brand-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">All-Inclusive Packages</span>
      <h2 class="mt-3 fw-heading">Featured Treatment Packages</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($packages as $p): ?>
        <div class="col-lg-4">
          <div class="card shadow-card package-card">
            <div class="card-img-wrap">
              <span class="badge-featured">Featured</span>
              <img src="<?= e(img_url('packages', $p['image'])) ?>" alt="<?= e($p['title']) ?>">
            </div>
            <div class="card-body">
              <span class="badge-category"><?= e($p['treatment_name']) ?></span>
              <h5 class="mt-3"><a href="<?= e(BASE_URL) ?>/package-detail.php?slug=<?= e($p['slug']) ?>" class="text-dark stretched-link"><?= e($p['title']) ?></a></h5>
              <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?= e($p['hospital_name']) ?>, <?= e($p['destination_name']) ?></p>
              <div class="d-flex justify-content-between align-items-center">
                <div class="price-tag"><?= format_price($p['price']) ?></div>
                <span class="text-muted small"><?= e($p['duration']) ?></span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- How it works -->
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">Simple Process</span>
      <h2 class="mt-3 fw-heading">Your Journey in 4 Easy Steps</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-3 col-6">
        <div class="step-num mb-3">1</div>
        <h6 class="fw-semibold">Request a Quote</h6>
        <p class="text-muted small">Tell us your treatment needs and preferred destination.</p>
      </div>
      <div class="col-md-3 col-6">
        <div class="step-num mb-3">2</div>
        <h6 class="fw-semibold">Compare Options</h6>
        <p class="text-muted small">Receive personalized hospital and package recommendations.</p>
      </div>
      <div class="col-md-3 col-6">
        <div class="step-num mb-3">3</div>
        <h6 class="fw-semibold">Plan Your Trip</h6>
        <p class="text-muted small">We help arrange travel, accommodation and appointments.</p>
      </div>
      <div class="col-md-3 col-6">
        <div class="step-num mb-3">4</div>
        <h6 class="fw-semibold">Treatment & Recovery</h6>
        <p class="text-muted small">Receive care and ongoing support through your recovery.</p>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<?php if ($testimonials): ?>
<section class="py-5 bg-brand-light">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">Patient Stories</span>
      <h2 class="mt-3 fw-heading">What Our Patients Say</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($testimonials as $t): ?>
        <div class="col-lg-4">
          <div class="testimonial-card p-4 h-100">
            <div class="mb-2"><?= star_rating($t['rating']) ?></div>
            <p class="fst-italic">&ldquo;<?= e($t['content']) ?>&rdquo;</p>
            <div class="d-flex align-items-center gap-3 mt-3">
              <img src="<?= e(img_url('testimonials', $t['photo'])) ?>" class="avatar-circle" alt="<?= e($t['patient_name']) ?>">
              <div>
                <div class="fw-semibold"><?= e($t['patient_name']) ?></div>
                <div class="text-muted small"><?= e($t['patient_country']) ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Blog -->
<?php if ($blogPosts): ?>
<section class="py-5">
  <div class="container">
    <div class="text-center mb-5">
      <span class="section-title-badge">Patient Resources</span>
      <h2 class="mt-3 fw-heading">Latest From Our Blog</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($blogPosts as $post): ?>
        <div class="col-lg-4">
          <div class="card shadow-card blog-card">
            <div class="card-img-wrap sm">
              <img src="<?= e(img_url('blog', $post['image'])) ?>" alt="<?= e($post['title']) ?>">
            </div>
            <div class="card-body">
              <div class="text-muted small mb-2"><?= e(date('F j, Y', strtotime($post['published_at']))) ?></div>
              <h6><a href="<?= e(BASE_URL) ?>/blog-post.php?slug=<?= e($post['slug']) ?>" class="text-dark stretched-link"><?= e($post['title']) ?></a></h6>
              <p class="text-muted small"><?= e($post['excerpt']) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="py-5">
  <div class="container">
    <div class="cta-banner p-5 text-center">
      <h2 class="fw-heading">Ready to start your medical journey?</h2>
      <p class="mb-4">Get a free, no-obligation quote from our patient care team within 24 hours.</p>
      <a href="<?= e(BASE_URL) ?>/quote.php" class="btn btn-light rounded-pill px-4 fw-semibold">Get My Free Quote</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
