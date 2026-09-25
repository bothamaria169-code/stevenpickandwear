<?php
require_once __DIR__ . '/includes/functions.php';

$items = cart_items();
if (!$items) {
    redirect('cart.php');
}

$errors = [];
$subtotal = array_sum(array_column($items, 'line_total'));
$couponCode = $_SESSION['coupon_code'] ?? null;
$discount = 0.0;
if ($couponCode) {
    $check = validate_coupon($couponCode, $subtotal);
    $discount = $check['valid'] ? $check['discount'] : 0.0;
}
$freeThreshold = (float) get_setting('free_shipping_threshold', '999999');
$flatRate      = (float) get_setting('shipping_flat_rate', '0');
$shipping      = ($subtotal - $discount) >= $freeThreshold ? 0.0 : $flatRate;
$total         = max(0, $subtotal - $discount + $shipping);

$provinces = ['Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape', 'Western Cape'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $name    = sanitize(trim($_POST['name'] ?? ''));
    $email   = safe_email($_POST['email'] ?? '');
    $phone   = sanitize(trim($_POST['phone'] ?? ''));
    $address = sanitize(trim($_POST['address'] ?? ''));
    $city    = sanitize(trim($_POST['city'] ?? ''));
    $province = sanitize($_POST['province'] ?? '');
    $postal  = sanitize(trim($_POST['postal'] ?? ''));
    $notes   = sanitize(trim($_POST['notes'] ?? ''));
    $wantsAccount = !empty($_POST['create_account']) && !is_logged_in();
    $password = $_POST['password'] ?? '';

    if (!$name) $errors[] = 'Full name is required.';
    if (!$email) $errors[] = 'A valid email address is required.';
    if (!$phone) $errors[] = 'Phone number is required.';
    if (!$address || !$city || !$province || !$postal) $errors[] = 'Complete delivery address is required.';
    if ($wantsAccount && strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    // Proof of payment is optional here: most customers place the order first,
    // pay with the order number as reference, then upload on the confirmation page.
    $popPath = null;
    if (empty($errors) && ($_FILES['pop_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $stored = store_proof_of_payment($_FILES['pop_file']);
        if ($stored['ok']) {
            $popPath = $stored['path'];
        } else {
            $errors[] = $stored['error'];
        }
    }

    if (empty($errors)) {
        $userId = $current_user['id'] ?? null;

        if ($wantsAccount) {
            $stmt = db()->prepare("SELECT id FROM users WHERE email = :e");
            $stmt->execute([':e' => $email]);
            if (!$stmt->fetch()) {
                $userId = db_insert('users', [
                    'name' => $name, 'email' => $email, 'phone' => $phone,
                    'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    'role' => 'customer',
                ]);
            }
        }

        $addressText = "$address, $city, $province, $postal, South Africa";
        $orderRepo = new OrderRepository();

        try {
            $order = $orderRepo->create([
                'user_id'          => $userId,
                'guest_name'       => $name,
                'guest_email'      => $email,
                'guest_phone'      => $phone,
                'total_amount'     => $total,
                'discount_amount'  => $discount,
                'shipping_amount'  => $shipping,
                'coupon_code'      => $couponCode,
                'shipping_method'  => $shipping > 0 ? 'Standard Courier' : 'Free Shipping',
                'shipping_address' => $addressText,
                'billing_address'  => $addressText,
                'pop_path'         => $popPath,
                'pop_uploaded_at'  => $popPath ? date('Y-m-d H:i:s') : null,
                'notes'            => $notes,
            ], array_map(fn($item) => [
                'product_id' => $item['product']['id'],
                'variant_id' => $item['variant']['id'],
                'name'       => $item['product']['name'],
                'size_sa'    => $item['variant']['size_sa'],
                'qty'        => $item['qty'],
                'price'      => $item['price'],
                'image'      => $item['image'],
            ], $items));

            // Decrement the size's stock so the admin can track what's left.
            $repo = new ProductRepository();
            foreach ($items as $item) {
                $repo->updateVariant((int) $item['variant']['id'], ['stock_qty' => max(0, (int) $item['variant']['stock_qty'] - (int) $item['qty'])]);
            }

            if ($couponCode && $discount > 0) {
                db()->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = :c")->execute([':c' => $couponCode]);
            }

            notify_order_confirmation(['order_number' => $order['order_number'], 'guest_name' => $name, 'guest_email' => $email, 'total_amount' => $total, 'pop_path' => $popPath], $orderRepo->getItems($order['id']));

            remember_order($order['order_number']);
            cart_clear();
            unset($_SESSION['coupon_code']);

            redirect('order-confirmation.php?order=' . urlencode($order['order_number']));
        } catch (Throwable $e) {
            error_log('Checkout order creation failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong placing your order. Please try again.';
        }
    }
}

$page_title = 'Checkout — ' . SITE_NAME;
$extra_js = [SITE_URL . '/assets/js/checkout.js'];
require_once __DIR__ . '/includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="section-heading"><div><span class="eyebrow">Almost There</span><h2>Checkout</h2></div></div>

    <?php if ($errors): ?>
      <div class="glass-panel p-3 mb-4" style="background:rgba(176,67,46,.08);border-color:rgba(176,67,46,.3);">
        <?php foreach ($errors as $err): ?><div class="small" style="color:var(--danger);"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="admin-card mb-4">
            <h3 class="h6 mb-3">Delivery Details</h3>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label-ric">Full Name</label><input type="text" name="name" class="form-control-ric" value="<?= htmlspecialchars($_POST['name'] ?? ($current_user['name'] ?? '')) ?>" required></div>
              <div class="col-md-6"><label class="form-label-ric">Email</label><input type="email" name="email" class="form-control-ric" value="<?= htmlspecialchars($_POST['email'] ?? ($current_user['email'] ?? '')) ?>" required></div>
              <div class="col-md-6"><label class="form-label-ric">Phone</label><input type="tel" name="phone" class="form-control-ric" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required></div>
              <div class="col-md-6"><label class="form-label-ric">Postal Code</label><input type="text" name="postal" class="form-control-ric" value="<?= htmlspecialchars($_POST['postal'] ?? '') ?>" required></div>
              <div class="col-12"><label class="form-label-ric">Street Address</label><input type="text" name="address" class="form-control-ric" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required></div>
              <div class="col-md-6"><label class="form-label-ric">City</label><input type="text" name="city" class="form-control-ric" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required></div>
              <div class="col-md-6">
                <label class="form-label-ric">Province</label>
                <select name="province" class="form-control-ric" required>
                  <option value="">Select…</option>
                  <?php foreach ($provinces as $p): ?><option value="<?= $p ?>" <?= ($_POST['province'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-12"><label class="form-label-ric">Order Notes (optional)</label><textarea name="notes" class="form-control-ric" rows="2"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea></div>
            </div>

            <?php if (!is_logged_in()): ?>
            <div class="mt-3 pt-3" style="border-top:1px solid var(--border-light);">
              <label class="d-flex align-items-center gap-2"><input type="checkbox" name="create_account" id="createAccountCheck" value="1"> <span class="small">Create an account to track this order</span></label>
              <div id="accountPasswordWrap" class="mt-2" style="display:none;">
                <input type="password" name="password" class="form-control-ric" placeholder="Choose a password (min. 8 characters)">
              </div>
            </div>
            <?php endif; ?>
          </div>

          <div class="admin-card">
            <h3 class="h6 mb-2">Payment — Bank Transfer (EFT)</h3>
            <p class="small text-muted-ric mb-3">Place your order, then pay by EFT into the account below. Use your <strong>order number</strong> as the reference (you'll see it on the next page and in your email). We'll ship once the payment reflects.</p>
            <div class="neu-cluster mb-3">
              <div class="row g-2 small">
                <?php foreach (bank_details() as $label => $value): ?>
                  <div class="col-6 text-muted-ric"><?= $label ?></div><div class="col-6 fw-600"><?= htmlspecialchars($value) ?></div>
                <?php endforeach; ?>
                <div class="col-6 text-muted-ric">Reference</div><div class="col-6 fw-600">Your order number</div>
              </div>
            </div>
            <label class="form-label-ric">Already paid? Upload proof of payment <span class="text-muted-ric fw-normal">(optional)</span></label>
            <input type="file" name="pop_file" class="form-control-ric" accept=".jpg,.jpeg,.png,.pdf">
            <p class="small text-muted-ric mt-2 mb-0">JPG, PNG or PDF, up to 8MB. You can also upload it after placing the order.</p>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="admin-card">
            <h3 class="h6 mb-3">Order Summary</h3>
            <div class="d-flex flex-column gap-3 mb-3">
              <?php foreach ($items as $item): ?>
                <div class="d-flex gap-2 align-items-center">
                  <img src="<?= htmlspecialchars(product_image($item['image'])) ?>" style="width:52px;height:60px;object-fit:cover;border-radius:var(--radius-sm);">
                  <div class="flex-fill small">
                    <div class="fw-600"><?= htmlspecialchars($item['product']['name']) ?></div>
                    <div class="text-muted-ric">Size <?= htmlspecialchars($item['variant']['size_sa'] ?: '—') ?> &times; <?= $item['qty'] ?></div>
                  </div>
                  <div class="small fw-600"><?= format_price($item['line_total']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="d-flex justify-content-between small mb-2"><span class="text-muted-ric">Subtotal</span><span><?= format_price($subtotal) ?></span></div>
            <?php if ($discount > 0): ?><div class="d-flex justify-content-between small mb-2"><span class="text-muted-ric">Discount (<?= htmlspecialchars($couponCode) ?>)</span><span>-<?= format_price($discount) ?></span></div><?php endif; ?>
            <div class="d-flex justify-content-between small mb-2"><span class="text-muted-ric">Shipping</span><span><?= $shipping > 0 ? format_price($shipping) : 'Free' ?></span></div>
            <div class="d-flex justify-content-between fw-700 mt-3 pt-3" style="border-top:1px solid var(--border-light);font-size:1.05rem;"><span>Total</span><span><?= format_price($total) ?></span></div>

            <button type="submit" class="btn-ric w-100 mt-4">Place Order</button>
            <a href="<?= SITE_URL ?>/cart.php" class="d-block text-center small text-muted-ric mt-3">Back To Bag</a>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

<script>
document.getElementById('createAccountCheck')?.addEventListener('change', function () {
  document.getElementById('accountPasswordWrap').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
