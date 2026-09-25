<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('customer');

$orderRepo = new OrderRepository();
$order = $orderRepo->getByOrderNumber(sanitize($_GET['order'] ?? ''));

if (!$order || (int) $order['user_id'] !== (int) $current_user['id']) {
    http_response_code(404);
    $page_title = 'Order Not Found';
    require_once __DIR__ . '/../../includes/header.php';
    echo '<section class="section text-center"><div class="container-ric"><h1 class="h4">Order not found</h1><a href="' . SITE_URL . '/dashboards/customer/orders.php" class="btn-ric mt-3">Back To Orders</a></div></section>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$popMessage = '';
$popError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '') && $order['payment_status'] !== 'paid') {
    $stored = ($_FILES['pop_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ? ['ok' => false, 'error' => 'Choose a file to upload.']
        : store_proof_of_payment($_FILES['pop_file']);
    if ($stored['ok']) {
        $orderRepo->attachProofOfPayment((int) $order['id'], $stored['path']);
        $order = $orderRepo->getByOrderNumber($order['order_number']);
        notify_pop_uploaded($order);
        $popMessage = "Proof of payment received. We'll confirm as soon as the payment reflects.";
    } else {
        $popError = $stored['error'];
    }
}
[$payClass, $payLabel] = payment_label($order);

$items = $orderRepo->getItems((int) $order['id']);
$page_title = 'Order ' . $order['order_number'] . ' — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="row g-4">
      <div class="col-lg-3"><?php include __DIR__ . '/../../includes/customer-sidebar.php'; ?></div>
      <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <h2 class="h4 mb-0">Order <?= htmlspecialchars($order['order_number']) ?></h2>
          <span class="status-pill <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
        </div>

        <div class="admin-card mb-4">
          <?php foreach ($items as $item): ?>
            <div class="d-flex gap-3 align-items-center mb-3 pb-3" style="border-bottom:1px solid var(--border-light);">
              <img src="<?= htmlspecialchars(product_image($item['image'])) ?>" style="width:64px;height:76px;object-fit:cover;border-radius:var(--radius-sm);">
              <div class="flex-fill">
                <div class="fw-600"><?= htmlspecialchars($item['name']) ?></div>
                <div class="small text-muted-ric">Size <?= htmlspecialchars($item['size_sa'] ?: '—') ?> &times; <?= $item['qty'] ?></div>
              </div>
              <div class="fw-600"><?= format_price((float) $item['price'] * (int) $item['qty']) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between small mb-1"><span class="text-muted-ric">Discount</span><span>-<?= format_price((float) $order['discount_amount']) ?></span></div>
          <div class="d-flex justify-content-between small mb-1"><span class="text-muted-ric">Shipping</span><span><?= (float) $order['shipping_amount'] > 0 ? format_price((float) $order['shipping_amount']) : 'Free' ?></span></div>
          <div class="d-flex justify-content-between fw-700 mt-2 pt-2" style="border-top:1px solid var(--border-light);"><span>Total</span><span><?= format_price((float) $order['total_amount']) ?></span></div>
        </div>

        <?php if ($popMessage): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($popMessage) ?></div><?php endif; ?>
        <?php if ($popError): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.08);color:var(--danger);"><?= htmlspecialchars($popError) ?></div><?php endif; ?>

        <?php if ($order['payment_status'] !== 'paid' && $order['status'] !== 'cancelled'): ?>
        <div class="admin-card mb-4">
          <h3 class="h6 mb-2"><?= empty($order['pop_path']) ? 'Pay by EFT and upload your proof of payment' : 'Need to send a new proof of payment?' ?></h3>
          <?php if ($order['payment_status'] === 'failed'): ?>
            <p class="small mb-2" style="color:var(--danger);">We couldn't match your last proof of payment. Please upload a clear copy of the bank confirmation.</p>
          <?php endif; ?>
          <?php if (empty($order['pop_path'])): ?>
            <div class="neu-cluster mb-3">
              <div class="row g-2 small">
                <?php foreach (bank_details() as $label => $value): ?>
                  <div class="col-6 text-muted-ric"><?= $label ?></div><div class="col-6 fw-600"><?= htmlspecialchars($value) ?></div>
                <?php endforeach; ?>
                <div class="col-6 text-muted-ric">Amount</div><div class="col-6 fw-600"><?= format_price((float) $order['total_amount']) ?></div>
                <div class="col-6 text-muted-ric">Reference</div><div class="col-6 fw-700 text-saddle"><?= htmlspecialchars($order['order_number']) ?></div>
              </div>
            </div>
          <?php endif; ?>
          <form method="POST" enctype="multipart/form-data" class="d-flex flex-column flex-sm-row gap-2">
            <?= csrf_field() ?>
            <input type="file" name="pop_file" class="form-control-ric" accept=".jpg,.jpeg,.png,.pdf" required>
            <button type="submit" class="btn-ric" style="white-space:nowrap;">Upload Proof</button>
          </form>
          <p class="small text-muted-ric mt-2 mb-0">JPG, PNG or PDF, up to 8MB.</p>
        </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="admin-card h-100">
              <h3 class="h6 mb-2">Shipping Address</h3>
              <p class="small text-muted-ric mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-card h-100">
              <h3 class="h6 mb-2">Payment</h3>
              <p class="small text-muted-ric mb-1">Method: Bank Transfer (EFT)</p>
              <p class="small text-muted-ric mb-1">Reference: <strong class="text-dark"><?= htmlspecialchars($order['order_number']) ?></strong></p>
              <p class="small text-muted-ric mb-0">Status: <span class="status-pill <?= $payClass ?>"><?= $payLabel ?></span></p>
              <?php if (!empty($order['pop_path'])): ?>
                <a href="<?= SITE_URL ?>/pop.php?order=<?= urlencode($order['order_number']) ?>" target="_blank" class="small text-saddle fw-600 d-inline-block mt-2"><i class="fa-solid fa-paperclip me-1"></i>View my proof of payment</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
