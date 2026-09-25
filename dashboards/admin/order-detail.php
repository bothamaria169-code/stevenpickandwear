<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$orderRepo = new OrderRepository();
$id = safe_int($_GET['id'] ?? 0);
$order = $orderRepo->getById($id);

if (!$order) {
    redirect('dashboards/admin/orders.php');
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $orderRepo->updateStatus($id, sanitize($_POST['status'] ?? $order['status']));
        $order = $orderRepo->getById($id);
        notify_order_status_change(['order_number' => $order['order_number'], 'guest_name' => $order['guest_name'] ?? $order['user_name'], 'guest_email' => $order['guest_email'] ?? $order['user_email'], 'status' => $order['status']]);
        $success = 'Order status updated — customer notified.';
    } elseif ($action === 'update_payment') {
        $newStatus = sanitize($_POST['payment_status'] ?? $order['payment_status']);
        if (in_array($newStatus, ['unpaid', 'paid', 'failed'], true)) {
            $orderRepo->updatePaymentStatus($id, $newStatus);
        }
        $order = $orderRepo->getById($id);
        $success = 'Payment status updated.';
    } elseif ($action === 'approve_payment') {
        $orderRepo->updatePaymentStatus($id, 'paid');
        if ($order['status'] === 'pending') {
            $orderRepo->updateStatus($id, 'processing');
        }
        $order = $orderRepo->getById($id);
        notify_payment_decision($order, true);
        $success = 'Payment approved. The order is now processing and the customer has been emailed.';
    } elseif ($action === 'reject_payment') {
        $orderRepo->updatePaymentStatus($id, 'failed');
        $order = $orderRepo->getById($id);
        notify_payment_decision($order, false, sanitize(trim($_POST['reason'] ?? '')));
        $success = 'Proof rejected. The customer has been emailed a link to upload a new one.';
    }
}

$items = $orderRepo->getItems($id);
$page_title = 'Order ' . $order['order_number'] . ' — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Order</span><h1 class="h3 mb-0"><?= htmlspecialchars($order['order_number']) ?></h1></div>
      <a href="<?= SITE_URL ?>/dashboards/admin/orders.php" class="btn-ric-outline">Back To Orders</a>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="admin-card mb-3">
          <h3 class="h6 mb-3">Items</h3>
          <?php foreach ($items as $item): ?>
            <div class="d-flex gap-3 align-items-center mb-3 pb-3" style="border-bottom:1px solid var(--border-light);">
              <img src="<?= htmlspecialchars(product_image($item['image'])) ?>" style="width:56px;height:66px;object-fit:cover;border-radius:6px;">
              <div class="flex-fill">
                <div class="fw-600"><?= htmlspecialchars($item['name']) ?></div>
                <div class="small text-muted-ric">Size <?= htmlspecialchars($item['size_sa'] ?: '—') ?> &times; <?= $item['qty'] ?></div>
              </div>
              <div class="fw-600"><?= format_price((float) $item['price'] * (int) $item['qty']) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="d-flex justify-content-between fw-700 mt-2 pt-2" style="border-top:1px solid var(--border-light);"><span>Total</span><span><?= format_price((float) $order['total_amount']) ?></span></div>
        </div>

        <div class="admin-card">
          <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
            <h3 class="h6 mb-0">Proof of Payment</h3>
            <?php [$payClass, $payLabel] = payment_label($order); ?><span class="status-pill <?= $payClass ?>"><?= $payLabel ?></span>
          </div>
          <p class="small text-muted-ric mb-3">Check your bank for <strong class="text-dark"><?= format_price((float) $order['total_amount']) ?></strong> with reference <strong class="text-dark"><?= htmlspecialchars($order['order_number']) ?></strong> before approving. A screenshot alone isn't proof the money arrived.</p>
          <?php if ($order['pop_path']): $ext = strtolower(pathinfo($order['pop_path'], PATHINFO_EXTENSION)); $popUrl = SITE_URL . '/pop.php?order=' . urlencode($order['order_number']); ?>
            <?php if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
              <a href="<?= $popUrl ?>" target="_blank"><img src="<?= $popUrl ?>" alt="Proof of payment" style="max-width:280px;border-radius:var(--radius);"></a>
            <?php else: ?>
              <a href="<?= $popUrl ?>" target="_blank" class="btn-ric-outline btn-ric-sm"><i class="fa-solid fa-file-pdf me-2"></i>View PDF</a>
            <?php endif; ?>
            <?php if (!empty($order['pop_uploaded_at'])): ?><p class="small text-muted-ric mt-2 mb-0">Uploaded <?= date('d M Y, H:i', strtotime($order['pop_uploaded_at'])) ?></p><?php endif; ?>
          <?php else: ?>
            <p class="small text-muted-ric mb-0">The customer hasn't uploaded a proof of payment yet.</p>
          <?php endif; ?>

          <?php if ($order['payment_status'] !== 'paid'): ?>
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3" style="border-top:1px solid var(--border-light);">
              <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="approve_payment">
                <button type="submit" class="btn-ric btn-ric-sm" onclick="return confirm('Confirm the money is in the bank account?')"><i class="fa-solid fa-check me-2"></i>Approve Payment</button>
              </form>
              <?php if ($order['pop_path'] && $order['payment_status'] !== 'failed'): ?>
              <form method="POST" class="d-flex gap-2 flex-wrap"><?= csrf_field() ?><input type="hidden" name="action" value="reject_payment">
                <input type="text" name="reason" class="form-control-ric" style="max-width:240px;" placeholder="Reason (optional, emailed)">
                <button type="submit" class="btn-ric-outline btn-ric-sm">Reject Proof</button>
              </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="admin-card mb-3">
          <h3 class="h6 mb-2">Customer</h3>
          <p class="small mb-1"><?= htmlspecialchars($order['user_name'] ?? $order['guest_name']) ?></p>
          <p class="small text-muted-ric mb-1"><?= htmlspecialchars($order['user_email'] ?? $order['guest_email']) ?></p>
          <p class="small text-muted-ric mb-0"><?= htmlspecialchars($order['guest_phone'] ?? '') ?></p>
        </div>

        <div class="admin-card mb-3">
          <h3 class="h6 mb-2">Delivery Address</h3>
          <p class="small text-muted-ric mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
        </div>

        <div class="admin-card mb-3">
          <h3 class="h6 mb-3">Order Status</h3>
          <form method="POST" class="d-flex gap-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="update_status">
            <select name="status" class="form-control-ric">
              <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn-neu">Update</button>
          </form>
        </div>

        <div class="admin-card">
          <h3 class="h6 mb-3">Payment Status</h3>
          <form method="POST" class="d-flex gap-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="update_payment">
            <select name="payment_status" class="form-control-ric">
              <?php foreach (['unpaid', 'paid', 'failed'] as $s): ?><option value="<?= $s ?>" <?= $order['payment_status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn-neu">Update</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
