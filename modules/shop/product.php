<?php
require_once __DIR__ . '/../../includes/functions.php';

$repo = new ProductRepository();
$slug = sanitize($_GET['slug'] ?? '');
$product = $slug ? $repo->getBySlug($slug) : null;

if (!$product || ($product['status'] !== 'published' && !is_admin())) {
    http_response_code(404);
    $page_title = 'Piece Not Found — ' . SITE_NAME;
    require_once __DIR__ . '/../../includes/header.php';
    echo '<section class="section text-center"><div class="container-ric"><i class="fa-solid fa-shirt fa-3x text-saddle mb-3"></i><h1 class="h3">This piece could not be found</h1><p class="text-muted-ric">It may have sold, or the link may be incorrect.</p><a href="' . SITE_URL . '/shop.php" class="btn-ric">Back To Shop</a></div></section>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$variants   = $repo->getVariants((int) $product['id']);
$gallery    = $repo->getGallery((int) $product['id']);
$related    = $repo->getRelated($product, 4);
$reviewStats = $repo->getReviewStats((int) $product['id']);
$reviews    = $repo->getApprovedReviews((int) $product['id']);
$price      = $repo->getPrice($product);
$disc       = $repo->discountPercent($product);
$wishlistIds = is_logged_in() ? wishlist_ids((int) $current_user['id']) : [];
$in_wishlist = in_array($product['id'], $wishlistIds);

$mainImage = $variants[0]['image'] ?? null ?: $product['featured_image'];

$page_title = $product['name'] . ' — ' . SITE_NAME;
$meta_description = $product['short_desc'] ?: $product['name'];
$extra_js = [SITE_URL . '/assets/js/product-variants.js'];
require_once __DIR__ . '/../../includes/header.php';
?>

<section class="section-sm">
  <div class="container-ric">
    <nav class="small text-muted-ric mb-4">
      <a href="<?= SITE_URL ?>/index.php" class="text-muted-ric">Home</a> /
      <a href="<?= SITE_URL ?>/shop.php" class="text-muted-ric">Shop</a> /
      <span><?= htmlspecialchars($product['name']) ?></span>
    </nav>

    <div class="row g-5">
      <!-- Gallery -->
      <div class="col-lg-6">
        <div class="rounded-4 overflow-hidden mb-3" style="aspect-ratio:3/4;background:var(--cream);">
          <img id="productMainImage" src="<?= htmlspecialchars(product_image($mainImage)) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <?php if ($gallery): ?>
        <div class="d-flex gap-2 flex-wrap">
          <div class="gallery-thumb is-active" data-full-image="<?= htmlspecialchars(product_image($mainImage)) ?>" style="width:76px;height:76px;border-radius:var(--radius);overflow:hidden;cursor:pointer;border:2px solid var(--saddle);">
            <img src="<?= htmlspecialchars(product_image($mainImage)) ?>" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <?php foreach ($gallery as $g): ?>
          <div class="gallery-thumb" data-full-image="<?= htmlspecialchars(product_image($g['image'])) ?>" style="width:76px;height:76px;border-radius:var(--radius);overflow:hidden;cursor:pointer;border:2px solid transparent;">
            <img src="<?= htmlspecialchars(product_image($g['image'])) ?>" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <div class="col-lg-6">
        <?php if (!empty($product['category_name'])): ?><div class="eyebrow mb-2"><?= htmlspecialchars($product['category_name']) ?></div><?php endif; ?>
        <h1 class="mb-2" style="font-size:clamp(1.7rem,3vw,2.4rem);"><?= htmlspecialchars($product['name']) ?></h1>

        <?php if ($reviewStats['count'] > 0): ?>
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="star-rating"><?php for ($i=1;$i<=5;$i++): ?><i class="fa-<?= $i <= round($reviewStats['average']) ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?></span>
          <span class="small text-muted-ric"><?= $reviewStats['average'] ?> (<?= $reviewStats['count'] ?> review<?= $reviewStats['count']===1?'':'s' ?>)</span>
        </div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-3 mb-3">
          <span id="productCurrentPrice" style="font-family:var(--font-serif);font-size:1.7rem;font-weight:700;color:var(--espresso);"><?= format_price($price) ?></span>
          <?php if ($product['is_sale'] && $product['sale_price']): ?><span class="price-original" style="font-size:1.1rem;"><?= format_price((float) $product['base_price']) ?></span><span class="badge-sale">-<?= $disc ?>%</span><?php endif; ?>
        </div>

        <?php if ($product['short_desc']): ?><p class="text-muted-ric mb-4" style="line-height:1.7;"><?= nl2br(htmlspecialchars($product['short_desc'])) ?></p><?php endif; ?>


        <?php if ($variants): ?>
        <div class="mb-4">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <label class="form-label-ric mb-0">Select Size</label>
            <button type="button" class="small text-saddle fw-600" style="background:none;border:none;" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">Size Guide</button>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($variants as $v): ?>
              <button type="button" class="size-chip variant-size-chip" data-variant-id="<?= $v['id'] ?>" <?= $v['stock_qty'] <= 0 ? 'disabled' : '' ?>><?= htmlspecialchars($v['size_sa'] ?: '—') ?></button>
            <?php endforeach; ?>
          </div>
          <p id="productPatternNote" class="small text-muted-ric mt-2" style="display:none;"></p>
          <p id="productStockNote" class="small text-muted-ric mt-2" style="display:none;"></p>
        </div>
        <?php endif; ?>

        <form id="addToCartForm" class="d-flex gap-3 align-items-center flex-wrap mb-4">
          <input type="hidden" id="selectedVariantId" name="variant_id" value="">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <div class="qty-stepper">
            <button type="button" data-step="-1">−</button>
            <input type="number" name="qty" value="1" min="1" max="10" readonly>
            <button type="button" data-step="1">+</button>
          </div>
          <button type="submit" id="addToCartBtn" class="btn-ric flex-fill" <?= $variants ? '' : 'disabled' ?>>Add To Cart</button>
          <?php if (is_logged_in()): ?>
          <button type="button" class="btn-icon-neu wishlist-btn <?= $in_wishlist ? 'active' : '' ?>" data-product-id="<?= $product['id'] ?>"><i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i></button>
          <?php endif; ?>
        </form>

        <div class="d-flex flex-column gap-2 small text-muted-ric" style="border-top:1px solid var(--border-light);padding-top:1.2rem;">
          <div><i class="fa-solid fa-truck me-2 text-saddle"></i>Nationwide courier delivery</div>
          <div><i class="fa-solid fa-building-columns me-2 text-saddle"></i>Pay safely by EFT, upload your proof of payment</div>
          <div><i class="fa-solid fa-arrows-rotate me-2 text-saddle"></i>Returns within 10 days on unworn items (tags on)</div>
        </div>
      </div>
    </div>

    <?php if ($product['description']): ?>
    <div class="row mt-5 pt-4" style="border-top:1px solid var(--border-light);">
      <div class="col-lg-8">
        <h3 class="h5 mb-3">The Details</h3>
        <div class="text-muted-ric" style="line-height:1.8;"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <div class="row mt-5 pt-4" style="border-top:1px solid var(--border-light);">
      <div class="col-lg-8">
        <h3 class="h5 mb-4">Reviews (<?= $reviewStats['count'] ?>)</h3>
        <?php foreach ($reviews as $rev): ?>
          <div class="mb-4 pb-4" style="border-bottom:1px solid var(--border-light);">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <strong><?= htmlspecialchars($rev['user_name']) ?></strong>
              <span class="star-rating small"><?php for ($i=1;$i<=5;$i++): ?><i class="fa-<?= $i <= $rev['rating'] ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?></span>
            </div>
            <p class="text-muted-ric small mb-0"><?= htmlspecialchars($rev['comment']) ?></p>
          </div>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><p class="text-muted-ric">No reviews yet — be the first to share your fit.</p><?php endif; ?>

        <?php if (is_logged_in()): ?>
        <form id="reviewForm" class="neu-cluster mt-4">
          <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
          <label class="form-label-ric">Your Rating</label>
          <div class="d-flex gap-2 mb-3" id="reviewStars">
            <?php for ($i=1;$i<=5;$i++): ?><i class="fa-regular fa-star" data-rating="<?= $i ?>" style="cursor:pointer;font-size:1.3rem;color:var(--saddle);"></i><?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="reviewRatingInput" value="5">
          <textarea name="comment" class="form-control-ric mb-3" rows="3" placeholder="Share how it fits and feels…" required></textarea>
          <button type="submit" class="btn-ric-sm btn-ric">Submit Review</button>
        </form>
        <?php else: ?>
        <p class="small text-muted-ric"><a href="<?= SITE_URL ?>/modules/auth/login.php" class="text-saddle">Sign in</a> to leave a review.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Data + behavior wired up here, before the related-products loop below
         reuses $product/$price as its own loop variables via product-card.php. -->
    <script>
    window.productVariants = <?= json_encode(array_map(fn($v) => [
        'id' => $v['id'], 'size_sa' => $v['size_sa'], 'additional_price' => $v['additional_price'],
        'image' => $v['image'] ? product_image($v['image']) : null, 'pattern_note' => $v['pattern_note'], 'stock_qty' => $v['stock_qty'],
    ], $variants)) ?>;
    window.productBasePrice = <?= json_encode($price) ?>;
    window.CURRENCY_SYMBOL = <?= json_encode(CURRENCY) ?>;
    </script>

    <?php if ($related): ?>
    <div class="mt-5 pt-4" style="border-top:1px solid var(--border-light);">
      <h3 class="h5 mb-4">You May Also Like</h3>
      <div class="product-grid">
        <?php foreach ($related as $rp): $in_wishlist = in_array($rp['id'], $wishlistIds); $product = $rp; ?>
          <?php include __DIR__ . '/../../includes/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../../includes/size-guide-modal.php'; ?>

<script>
document.querySelectorAll('.qty-stepper button').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = btn.parentElement.querySelector('input');
    const step = parseInt(btn.dataset.step, 10);
    input.value = Math.max(1, Math.min(10, parseInt(input.value, 10) + step));
  });
});

document.getElementById('reviewStars')?.addEventListener('click', (e) => {
  const star = e.target.closest('[data-rating]');
  if (!star) return;
  const rating = parseInt(star.dataset.rating, 10);
  document.getElementById('reviewRatingInput').value = rating;
  document.querySelectorAll('#reviewStars i').forEach((s, i) => {
    s.className = i < rating ? 'fa-solid fa-star' : 'fa-regular fa-star';
    s.style.color = 'var(--saddle)';
  });
});

document.getElementById('reviewForm')?.addEventListener('submit', async function (e) {
  e.preventDefault();
  const res = await fetch(SITE_URL + '/modules/api/review.php', {
    method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(this),
  });
  const data = await res.json();
  showToast(data.message, data.success ? 'success' : 'error');
  if (data.success) this.reset();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
