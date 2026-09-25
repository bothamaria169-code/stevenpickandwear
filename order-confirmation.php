<?php
require_once __DIR__ . '/includes/functions.php';

$orderNumber = sanitize($_GET['order'] ?? '');
$orderRepo = new OrderRepository();
$order = $orderNumber ? $orderRepo->getByOrderNumber($orderNumber) : null;

// Only the buyer (this browser session, their account, or whoever knows the
// order's email address) or an admin may see an order.
$lookupError = '';
if ($order && !can_view_order($order) && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['lookup_email']) && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!rate_limit('order_lookup', 6, 600)) {
        $lookupError = 'Too many attempts. Please wait a few minutes.';
    } elseif (strcasecmp(trim($_POST['lookup_email']), (string) ($order['guest_email'] ?? '')) === 0) {
        remember_order($order['order_number']);
    } else {
        $lookupError = "That email doesn't match this order.";
    }
}
$locked = $order && !can_view_order($order);

$uploadError = '';
$uploaded = false;
if ($order && !$locked && isset($_FILES['pop_file']) && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if ($order['payment_status'] === 'paid') {
        $uploadError = 'This order is already paid.';
    } elseif (($_FILES['pop_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $uploadError = 'Choose a file to upload.';
    } else {
        $stored = store_proof_of_payment($_FILES['pop_file']);
        if ($stored['ok']) {
            $orderRepo->attachProofOfPayment((int) $order['id'], $stored['path']);
            $order = $orderRepo->getById((int) $order['id']);
            notify_pop_uploaded($order);
            $uploaded = true;
        } else {
            $uploadError = $stored['error'];
        }
    }
}

$page_title = 'Your Order — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

if (!$order) {
    echo '<section class="section text-center"><div class="container-ric"><h1 class="h4">Order not found</h1><a href="' . SITE_URL . '/shop.php" class="btn-ric mt-3">Back To Shop</a></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
if ($locked): ?>
<section class="section d-flex align-items-center" style="min-height:60vh;">
  <div class="container-ric" style="max-width:440px;">
    <div class="admin-card text-center">
      <span class="eyebrow">Order <?= htmlspecialchars($order['order_number']) ?></span>
      <h1 class="h5 mt-2 mb-2">Confirm it's you</h1>
      <p class="small text-muted-ric mb-3">Enter the email address you used for this order.</p>
      <?php if ($lookupError): ?><p class="small" style="color:var(--danger);"><?= htmlspecialchars($lookupError) ?></p><?php endif; ?>
      <form method="POST" class="d-flex flex-column gap-2">
        <?= csrf_field() ?>
        <input type="email" name="lookup_email" class="form-control-ric" placeholder="you@example.com" required>
        <button type="submit" class="btn-ric">Open My Order</button>
      </form>
      <a href="<?= SITE_URL ?>/modules/auth/login.php?redirect=<?= urlencode('/order-confirmation.php?order=' . $order['order_number']) ?>" class="d-block small text-muted-ric mt-3">Or sign in to your account</a>
    </div>
  </div>
</section>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
endif;
$orderItems = $orderRepo->getItems((int) $order['id']);
[$payClass, $payLabel] = payment_label($order);
$needsPop = $order['payment_status'] !== 'paid' && $order['status'] !== 'cancelled';
?>
<section class="section">
  <div class="container-ric" style="max-width:720px;">
    <div class="text-center">
      <div class="btn-icon-neu mx-auto mb-4" style="width:76px;height:76px;color:var(--success);"><i class="fa-solid fa-check fa-2x"></i></div>
      <span class="eyebrow">Thank You</span>
      <h1 class="h3 mb-2">Your order is in</h1>
      <p class="text-muted-ric mb-2">Order <strong><?= htmlspecialchars($order['order_number']) ?></strong> &middot; <span class="status-pill <?= $payClass ?>"><?= $payLabel ?></span></p>
    </div>

    <?php if ($uploaded): ?>
      <div class="small my-3 p-3 rounded-3 text-center" style="background:rgba(76,122,74,.1);color:var(--success);"><i class="fa-solid fa-circle-check me-2"></i>Proof of payment received. We'll confirm your order as soon as the payment reflects.</div>
    <?php endif; ?>
    <?php if ($uploadError): ?>
      <div class="small my-3 p-3 rounded-3 text-center" style="background:rgba(176,67,46,.08);color:var(--danger);"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($uploadError) ?></div>
    <?php endif; ?>

    <?php if ($needsPop && empty($order['pop_path'])): ?>
      <div class="admin-card my-4">
        <h2 class="h6 mb-2">Step 1: Pay by EFT</h2>
        <p class="small text-muted-ric mb-3">Pay <strong><?= format_price((float) $order['total_amount']) ?></strong> into this account. Use <strong><?= htmlspecialchars($order['order_number']) ?></strong> as the reference.</p>
        <div class="neu-cluster">
          <div class="row g-2 small">
            <?php foreach (bank_details() as $label => $value): ?>
              <div class="col-6 text-muted-ric"><?= $label ?></div><div class="col-6 fw-600"><?= htmlspecialchars($value) ?></div>
            <?php endforeach; ?>
            <div class="col-6 text-muted-ric">Reference</div><div class="col-6 fw-700 text-saddle"><?= htmlspecialchars($order['order_number']) ?></div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($needsPop): ?>
      <div class="admin-card mb-4">
        <h2 class="h6 mb-2"><?= empty($order['pop_path']) ? 'Step 2: Upload your proof of payment' : 'Upload a new proof of payment' ?></h2>
        <?php if ($order['payment_status'] === 'failed'): ?>
          <p class="small mb-2" style="color:var(--danger);">We couldn't match your last proof of payment. Please upload a clear copy of the bank's payment confirmation.</p>
        <?php elseif (!empty($order['pop_path'])): ?>
          <p class="small text-muted-ric mb-2">We've got your proof of payment and are checking it. Only upload again if we ask you to.</p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-sm-row gap-2">
          <?= csrf_field() ?>
          <input type="file" name="pop_file" class="form-control-ric" accept=".jpg,.jpeg,.png,.pdf" required>
          <button type="submit" class="btn-ric" style="white-space:nowrap;">Upload</button>
        </form>
        <p class="small text-muted-ric mt-2 mb-0">JPG, PNG or PDF, up to 8MB.</p>
      </div>
    <?php endif; ?>

    <div class="admin-card text-start mb-4">
      <?php foreach ($orderItems as $item): ?>
        <div class="d-flex justify-content-between small mb-2">
          <span><?= htmlspecialchars($item['name']) ?> <?= $item['size_sa'] ? '(' . htmlspecialchars($item['size_sa']) . ')' : '' ?> &times; <?= $item['qty'] ?></span>
          <span><?= format_price((float) $item['price'] * (int) $item['qty']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between small text-muted-ric"><span>Shipping</span><span><?= (float) $order['shipping_amount'] > 0 ? format_price((float) $order['shipping_amount']) : 'Free' ?></span></div>
      <div class="d-flex justify-content-between fw-700 mt-3 pt-3" style="border-top:1px solid var(--border-light);"><span>Total</span><span><?= format_price((float) $order['total_amount']) ?></span></div>
    </div>

    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= SITE_URL ?>/shop.php" class="btn-ric-outline">Continue Shopping</a>
      <?php if (is_logged_in()): ?><a href="<?= SITE_URL ?>/dashboards/customer/orders.php" class="btn-ric">View My Orders</a><?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
