<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'About Us — ' . SITE_NAME;
$meta_description = 'Steven Picknwear is The International Brand Outlet: suits, denim and occasion wear for women, men and kids, delivered across South Africa.';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container-ric">
    <div class="text-center mb-5" style="max-width:640px;margin-left:auto;margin-right:auto;">
      <span class="eyebrow">About Us</span>
      <h1 class="mt-2" style="font-size:clamp(2rem,4vw,3rem);">International style, within reach.</h1>
    </div>

    <div class="story-split mb-5">
      <div class="reveal">
        <img src="<?= htmlspecialchars(get_setting('about_image') ?: editorial_image('about') ?: SITE_URL . '/assets/img/logo/logo.png') ?>" alt="Tailored suit at Steven Picknwear" style="width:100%;max-height:640px;object-fit:cover;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);">
      </div>
      <div class="reveal">
        <span class="eyebrow">The International Brand Outlet</span>
        <h2 class="mb-3">Steven Picknwear</h2>
        <p class="text-muted-ric" style="line-height:1.85;">
          Steven Picknwear brings international fashion to South Africa at outlet prices. From sharp two- and three-piece suits to everyday denim and statement gowns, we pick styles that make you look and feel your best.
        </p>
        <p class="text-muted-ric" style="line-height:1.85;">
          We dress women, men and kids, for the office, the weekend and every big occasion. Pick your style, choose your size, and we'll get it to your door.
        </p>
      </div>
    </div>

    <div class="row g-4 text-center">
      <div class="col-md-4">
        <div class="btn-icon-neu mx-auto mb-3" style="width:64px;height:64px;"><i class="fa-solid fa-earth-africa fa-lg"></i></div>
        <h3 class="h6">International Styles</h3>
        <p class="small text-muted-ric">Current international fashion, picked for South African wardrobes.</p>
      </div>
      <div class="col-md-4">
        <div class="btn-icon-neu mx-auto mb-3" style="width:64px;height:64px;"><i class="fa-solid fa-tags fa-lg"></i></div>
        <h3 class="h6">Outlet Prices</h3>
        <p class="small text-muted-ric">Quality pieces at prices that make sense.</p>
      </div>
      <div class="col-md-4">
        <div class="btn-icon-neu mx-auto mb-3" style="width:64px;height:64px;"><i class="fa-solid fa-truck-fast fa-lg"></i></div>
        <h3 class="h6">Delivered Nationwide</h3>
        <p class="small text-muted-ric">Pay safely by EFT and we'll courier your order anywhere in South Africa.</p>
      </div>
    </div>

    <div class="mt-5 pt-4" style="border-top:1px solid var(--border-light);">
      <div class="section-heading reveal"><div><span class="eyebrow">Shop By Department</span><h2>Find Your Look</h2></div></div>
      <div class="row g-3">
        <?php foreach (MAIN_CATEGORIES as $slug => $label): $img = editorial_image('cta_' . $slug); ?>
        <div class="col-12 col-sm-6 col-lg-4 reveal">
          <a href="<?= SITE_URL ?>/shop.php?category=<?= $slug ?>" class="cta-card hover-lift"<?= $img ? '' : ' style="background:linear-gradient(155deg,var(--espresso),var(--saddle-dark));"' ?>>
            <?php if ($img): ?><img src="<?= htmlspecialchars($img) ?>" alt="<?= $label ?>" loading="lazy"><?php endif; ?>
            <span class="cta-overlay">
              <span class="cta-kicker">Shop</span>
              <span class="cta-title"><?= $label ?></span>
              <span class="cta-go">Explore <i class="fa-solid fa-arrow-right"></i></span>
            </span>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="<?= SITE_URL ?>/shop.php" class="btn-ric">Shop The Collection</a>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
