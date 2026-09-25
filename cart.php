<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Your Bag — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

$items = cart_items();
$subtotal = array_sum(array_column($items, 'line_total'));
$couponCode = $_SESSION['coupon_code'] ?? null;
$discount = 0.0;
if ($couponCode) {
    $check = validate_coupon($couponCode, $subtotal);
    $discount = $check['valid'] ? $check['discount'] : 0.0;
}
$freeThreshold = (float) get_setting('free_shipping_threshold', '999999');
$flatRate      = (float) get_setting('shipping_flat_rate', '0');
$shipping      = $subtotal > 0 ? (($subtotal - $discount) >= $freeThreshold ? 0.0 : $flatRate) : 0.0;
$total         = max(0, $subtotal - $discount + $shipping);
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="section-heading">
      <div><span class="eyebrow">Your Selection</span><h2>Shopping Bag</h2></div>
    </div>

    <?php if (!$items): ?>
      <div class="glass-panel text-center py-5 px-4" style="background:var(--cream);border-color:var(--border-light);">
        <i class="fa-solid fa-bag-shopping fa-2x text-saddle mb-3"></i>
        <h3 class="h5">Your bag is empty</h3>
        <p class="text-muted-ric mb-3">Your next favourite outfit is waiting. Go find it.</p>
        <a href="<?= SITE_URL ?>/shop.php" class="btn-ric">Shop The Collection</a>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <div class="col-lg-8">
          <div id="cartItemsWrap" class="d-flex flex-column gap-3">
            <?php foreach ($items as $item): ?>
              <div class="admin-card d-flex gap-3 align-items-center cart-line" data-key="<?= htmlspecialchars($item['key']) ?>">
                <img src="<?= htmlspecialchars(product_image($item['image'])) ?>" alt="" style="width:84px;height:100px;object-fit:cover;border-radius:var(--radius);flex-shrink:0;">
                <div class="flex-fill">
                  <div class="fw-700" style="font-family:var(--font-serif);"><?= htmlspecialchars($item['product']['name']) ?></div>
                  <div class="small text-muted-ric mb-2">Size <?= htmlspecialchars($item['variant']['size_sa'] ?: '—') ?> </div>
                  <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="qty-stepper">
                      <button type="button" class="cart-qty-btn" data-step="-1">−</button>
                      <input type="number" class="cart-qty-input" value="<?= $item['qty'] ?>" min="1" max="10" readonly>
                      <button type="button" class="cart-qty-btn" data-step="1">+</button>
                    </div>
                    <button type="button" class="cart-remove-btn small text-muted-ric" style="background:none;border:none;text-decoration:underline;"><i class="fa-solid fa-trash-can me-1"></i>Remove</button>
                  </div>
                </div>
                <div class="fw-700 line-total"><?= format_price($item['line_total']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="admin-card">
            <h3 class="h6 mb-3">Order Summary</h3>
            <div class="d-flex justify-content-between small mb-2"><span class="text-muted-ric">Subtotal</span><span id="sumSubtotal"><?= format_price($subtotal) ?></span></div>
            <div class="d-flex justify-content-between small mb-2" id="sumDiscountRow" style="<?= $discount > 0 ? '' : 'display:none;' ?>"><span class="text-muted-ric">Discount</span><span id="sumDiscount">-<?= format_price($discount) ?></span></div>
            <div class="d-flex justify-content-between small mb-2"><span class="text-muted-ric">Shipping</span><span id="sumShipping"><?= $shipping > 0 ? format_price($shipping) : 'Free' ?></span></div>
            <div class="d-flex justify-content-between fw-700 mt-3 pt-3" style="border-top:1px solid var(--border-light);font-size:1.05rem;"><span>Total</span><span id="sumTotal"><?= format_price($total) ?></span></div>

            <form id="couponForm" class="d-flex gap-2 mt-4">
              <input type="text" name="code" class="form-control-ric" placeholder="Coupon code" value="<?= htmlspecialchars($couponCode ?? '') ?>">
              <button type="submit" class="btn-ric-sm btn-ric-outline">Apply</button>
            </form>
            <div id="couponMsg" class="small mt-2"></div>

            <a href="<?= SITE_URL ?>/checkout.php" class="btn-ric w-100 mt-4">Proceed To Checkout</a>
            <a href="<?= SITE_URL ?>/shop.php" class="d-block text-center small text-muted-ric mt-3">Continue Shopping</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
