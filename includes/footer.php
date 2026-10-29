<?php $extra_js = $extra_js ?? []; ?>
</main>

<footer class="site-footer">
  <div class="container-ric">
    <div class="row g-4 mb-4">
      <div class="col-lg-4 col-md-6">
        <div class="mb-3">
          <img src="<?= SITE_URL ?>/assets/img/logo/logo-white.png" alt="<?= SITE_NAME ?>" style="height:64px;width:auto;">
        </div>
        <p style="color:var(--text-on-dark-muted);font-size:.88rem;line-height:1.7;max-width:320px;">
          The International Brand Outlet. Suits, denim and occasion wear for women, men and kids, delivered across South Africa.
        </p>
        <div class="d-flex gap-2 mt-3">
          <a href="<?= htmlspecialchars(get_setting('instagram_url') ?: '#') ?>" <?= get_setting('instagram_url') ? 'target="_blank" rel="noopener"' : '' ?> class="header-icon-btn" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="<?= htmlspecialchars(get_setting('facebook_url') ?: '#') ?>" <?= get_setting('facebook_url') ? 'target="_blank" rel="noopener"' : '' ?> class="header-icon-btn" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="<?= htmlspecialchars(get_setting('tiktok_url') ?: '#') ?>" <?= get_setting('tiktok_url') ? 'target="_blank" rel="noopener"' : '' ?> class="header-icon-btn" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 col-6">
        <h6>Shop</h6>
        <div class="d-flex flex-column gap-2">
          <a href="<?= SITE_URL ?>/shop.php">Shop All</a>
          <?php foreach (MAIN_CATEGORIES as $slug => $label): ?>
            <a href="<?= SITE_URL ?>/shop.php?category=<?= $slug ?>"><?= $label ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 col-6">
        <h6>Steven Picknwear</h6>
        <div class="d-flex flex-column gap-2">
          <a href="<?= SITE_URL ?>/about.php">About Us</a>
          <a href="<?= SITE_URL ?>/modules/blog/index.php">Journal</a>
          <a href="<?= SITE_URL ?>/gallery.php">Lookbook</a>
          <a href="<?= SITE_URL ?>/contact.php">Contact</a>
        </div>
      </div>
      <div class="col-lg-4 col-md-6">
        <h6>Stay In The Loop</h6>
        <p style="color:var(--text-on-dark-muted);font-size:.85rem;">First look at new stock, specials and styling tips.</p>
        <form id="footerNewsletterForm" class="d-flex gap-2 newsletter-box mt-2">
          <input type="email" name="email" required placeholder="Your email" class="form-control-ric" style="background:rgba(255,255,255,.08);box-shadow:none;color:var(--ivory);">
          <button type="submit" class="btn-ric" style="padding:.85rem 1.3rem;"><i class="fa-solid fa-arrow-right"></i></button>
        </form>
        <div id="footerNewsletterMsg" style="font-size:.78rem;margin-top:.6rem;"></div>
      </div>
    </div>
    <div class="footer-bottom d-flex flex-wrap justify-content-between gap-3">
      <span>&copy; <?= date('Y') ?> Steven Picknwear. All rights reserved.</span>
      <div class="d-flex flex-wrap gap-3">
        <a href="<?= SITE_URL ?>/terms.php">Terms</a>
        <a href="<?= SITE_URL ?>/returns-refunds.php">Returns &amp; Refunds</a>
        <a href="<?= SITE_URL ?>/shipping.php">Shipping</a>
        <a href="<?= SITE_URL ?>/payment-policy.php">Payment</a>
        <a href="<?= SITE_URL ?>/privacy.php">Privacy</a>
        <a href="<?= SITE_URL ?>/cookies.php">Cookies</a>
      </div>
    </div>
  </div>
</footer>

<?php
// Floating WhatsApp chat button (number from Admin > Settings > Contact & Social). Hidden in the admin.
$waNumber = preg_replace('/[^0-9]/', '', (string) get_setting('contact_whatsapp', ''));
if ($waNumber !== '' && !str_contains($body_class ?? '', 'admin-body')):
    $waText = rawurlencode('Hi ' . SITE_NAME . ', I have a question about ');
?>
<a href="https://wa.me/<?= $waNumber ?>?text=<?= $waText ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
  <i class="fa-brands fa-whatsapp"></i>
</a>
<?php endif; ?>

<div class="ric-toast-wrap" id="toastWrap"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>const SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
<script src="<?= SITE_URL ?>/assets/js/app.js"></script>
<script src="<?= SITE_URL ?>/assets/js/cart.js"></script>
<script src="<?= SITE_URL ?>/assets/js/wishlist.js"></script>
<?php foreach ($extra_js as $src): ?>
<script src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
