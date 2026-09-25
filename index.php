<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Steven Picknwear — The International Brand Outlet';
$meta_description = 'Steven Picknwear: suits, denim and occasion wear for women, men and kids. Shop online and pay safely by EFT, with delivery across South Africa.';
$extra_js = [
    'https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js',
    SITE_URL . '/assets/js/hero-three.js',
];
require_once __DIR__ . '/includes/header.php';

$repo = new ProductRepository();
$featured = $repo->getFeatured(8);
if (count($featured) < 4) {
    $featured = $repo->getAll(['sort' => 'newest'], 1, 8)['items'];
}
$blogs = get_blogs(['status' => 'published'], 1, 3)['items'];
$catCounts = array_column(get_categories(), 'product_count', 'slug');
?>

<!-- ── Hero ─────────────────────────────────────────────────────────── -->
<section class="hero">
  <canvas id="heroCanvas"></canvas>
  <div class="container-ric">
    <div class="hero-content">
      <span class="eyebrow">The International Brand Outlet</span>
      <div class="hero-brand">Steven Picknwear</div>
      <h1 class="hero-title">Dress like you mean it.</h1>
      <p class="hero-lede">Tailored suits, everyday denim and show-stopping occasion wear for women, men and kids. Pick your size, pay safely by EFT, and we'll deliver anywhere in South Africa.</p>
      <div class="d-flex gap-3 flex-wrap">
        <a href="<?= SITE_URL ?>/shop.php?category=women" class="btn-ric">Shop Women</a>
        <a href="<?= SITE_URL ?>/shop.php?category=men" class="btn-ghost-light">Shop Men</a>
      </div>
    </div>
  </div>
  <div class="hero-scroll-cue"><span>Scroll</span><span class="line"></span></div>
</section>

<!-- ── Featured pieces ──────────────────────────────────────────────── -->
<section class="section">
  <div class="container-ric">
    <div class="section-heading reveal">
      <div>
        <span class="eyebrow">New In</span>
        <h2>This Week's Picks</h2>
      </div>
      <a href="<?= SITE_URL ?>/shop.php" class="btn-ric-outline">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>

    <?php if ($featured): ?>
      <div class="product-grid">
        <?php foreach ($featured as $product): ?>
          <div class="reveal"><?php include __DIR__ . '/includes/product-card.php'; ?></div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="glass-panel text-center py-5 px-4" style="background:var(--cream);border-color:var(--border-light);">
        <i class="fa-solid fa-shirt fa-2x text-saddle mb-3"></i>
        <h3 class="h5">New stock is on its way</h3>
        <p class="text-muted-ric mb-0">Check back soon, or join the newsletter below to hear first.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Shop by department ───────────────────────────────────────────── -->
<section class="section-sm" style="background:var(--cream);">
  <div class="container-ric">
    <div class="section-heading reveal">
      <div><span class="eyebrow">Explore</span><h2>Shop By Department</h2></div>
      <a href="<?= SITE_URL ?>/shop.php" class="btn-ric-outline d-none d-md-inline-flex">Shop All <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php
      $ctaKicker = ['women' => 'For Her', 'men' => 'For Him', 'kids' => 'For The Little Ones'];
      foreach (MAIN_CATEGORIES as $slug => $label):
          $img   = editorial_image('cta_' . $slug);
          $count = (int) ($catCounts[$slug] ?? 0);
      ?>
        <div class="col-12 col-sm-6 col-lg-4 reveal">
          <a href="<?= SITE_URL ?>/shop.php?category=<?= $slug ?>" class="cta-card hover-lift"<?= $img ? '' : ' style="background:linear-gradient(155deg,var(--espresso),var(--saddle-dark));"' ?>>
            <?php if ($img): ?>
              <img src="<?= htmlspecialchars($img) ?>" alt="<?= $label ?>'s fashion at Steven Picknwear" loading="lazy">
            <?php endif; ?>
            <span class="cta-overlay">
              <span class="cta-kicker"><?= $ctaKicker[$slug] ?></span>
              <span class="cta-title"><?= $label ?></span>
              <span class="cta-go"><?= $count > 0 ? 'Shop ' . $count . ' styles' : 'Coming soon' ?> <i class="fa-solid fa-arrow-right"></i></span>
            </span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($featureImg = editorial_image('feature')): ?>
    <!-- Feature CTA band -->
    <div class="cta-feature mt-3 reveal">
      <img src="<?= htmlspecialchars($featureImg) ?>" alt="Tailored suits at Steven Picknwear" loading="lazy">
      <div class="cta-feature-veil"></div>
      <div class="cta-feature-body">
        <span class="eyebrow" style="color:var(--saddle-light);">The Suit Edit</span>
        <h2 class="mt-2 mb-3" style="color:var(--ivory);">Sharp tailoring for every occasion</h2>
        <p style="color:var(--text-on-dark-muted);line-height:1.7;max-width:440px;">Double-breasted, three-piece or a classic two-piece. Suits for the office, weddings and every big day in between.</p>
        <div class="d-flex gap-2 flex-wrap mt-2">
          <a href="<?= SITE_URL ?>/shop.php?category=men&amp;style=suits" class="btn-ric">Men's Suits</a>
          <a href="<?= SITE_URL ?>/shop.php?category=women&amp;style=suits" class="btn-ghost-light">Women's Suits</a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Why shop with us ─────────────────────────────────────────────── -->
<section class="section">
  <div class="container-ric">
    <div class="story-split">
      <div class="reveal">
        <span class="eyebrow">Why Steven Picknwear</span>
        <h2 class="mb-3">International style, local service</h2>
        <p class="text-muted-ric" style="line-height:1.8;">We bring you international fashion at outlet prices, from boardroom suits to weekend denim. Every order is checked and packed by our team and sent by courier to your door.</p>
        <div class="d-flex flex-column gap-3 my-4">
          <div class="d-flex gap-3"><i class="fa-solid fa-building-columns text-saddle mt-1"></i><div><strong>Pay safely by EFT.</strong> <span class="text-muted-ric">Pay into our bank account and upload your proof of payment. No card details needed.</span></div></div>
          <div class="d-flex gap-3"><i class="fa-solid fa-ruler text-saddle mt-1"></i><div><strong>Sizes XS to XXL.</strong> <span class="text-muted-ric">Check the size guide on every product, or ask us for help.</span></div></div>
          <div class="d-flex gap-3"><i class="fa-solid fa-truck-fast text-saddle mt-1"></i><div><strong>Nationwide delivery.</strong> <span class="text-muted-ric">Courier delivery across South Africa.</span></div></div>
        </div>
        <a href="<?= SITE_URL ?>/about.php" class="btn-ric-outline">About Us</a>
      </div>
      <div class="reveal">
        <img src="<?= htmlspecialchars(editorial_image('story') ?: SITE_URL . '/assets/img/logo/logo.png') ?>" alt="Denim styles at Steven Picknwear" style="width:100%;max-height:640px;object-fit:cover;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);">
      </div>
    </div>
  </div>
</section>

<!-- ── Journal teaser ───────────────────────────────────────────────── -->
<?php if ($blogs): ?>
<section class="section" style="background:var(--cream);">
  <div class="container-ric">
    <div class="section-heading reveal">
      <div><span class="eyebrow">Journal</span><h2>Style Notes</h2></div>
      <a href="<?= SITE_URL ?>/modules/blog/index.php" class="btn-ric-outline">Read The Journal</a>
    </div>
    <div class="row g-4">
      <?php foreach ($blogs as $post): ?>
        <div class="col-md-4 reveal">
          <a href="<?= SITE_URL ?>/modules/blog/post.php?slug=<?= urlencode($post['slug']) ?>" class="d-block rounded-4 overflow-hidden hover-lift" style="background:var(--white);border:1px solid var(--border-light);">
            <div style="aspect-ratio:4/3;overflow:hidden;background:var(--cream-2);">
              <img src="<?= product_image($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div class="p-3">
              <h3 class="h6 mb-0"><?= htmlspecialchars($post['title']) ?></h3>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Newsletter CTA ───────────────────────────────────────────────── -->
<section class="section-sm">
  <div class="container-ric">
    <div class="glass-panel-dark p-5 text-center reveal" style="background:linear-gradient(135deg,var(--espresso),var(--espresso-2));">
      <span class="eyebrow" style="color:var(--saddle-light);">Stay In The Loop</span>
      <h2 class="mt-2 mb-3" style="color:var(--ivory);">Be first to know about new stock</h2>
      <form id="heroNewsletterForm" class="d-flex gap-2 justify-content-center flex-wrap" style="max-width:460px;margin:0 auto;">
        <input type="email" name="email" required placeholder="Your email address" class="form-control-ric" style="flex:1;min-width:220px;background:rgba(255,255,255,.08);box-shadow:none;color:var(--ivory);">
        <button type="submit" class="btn-ric">Join The List</button>
      </form>
      <div id="heroNewsletterMsg" class="mt-3" style="font-size:.85rem;color:var(--saddle-light);"></div>
    </div>
  </div>
</section>

<script>
document.getElementById('heroNewsletterForm')?.addEventListener('submit', async function (e) {
  e.preventDefault();
  const email = this.email.value.trim();
  const msg = document.getElementById('heroNewsletterMsg');
  try {
    const res = await fetch(SITE_URL + '/modules/api/newsletter.php?action=subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ email }),
    });
    const data = await res.json();
    msg.textContent = data.message;
    if (data.success) this.reset();
  } catch (err) {
    msg.textContent = 'Something went wrong. Please try again.';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
