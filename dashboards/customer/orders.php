<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('customer');

$page = max(1, (int) ($_GET['page'] ?? 1));
$orderRepo = new OrderRepository();
$result = $orderRepo->getForUser((int) $current_user['id'], $page, 10);

$page_title = 'My Orders — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="row g-4">
      <div class="col-lg-3"><?php include __DIR__ . '/../../includes/customer-sidebar.php'; ?></div>
      <div class="col-lg-9">
        <h2 class="h4 mb-4">My Orders</h2>
        <div class="admin-card">
          <?php if ($result['items']): ?>
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($result['items'] as $o): ?>
                <tr>
                  <td class="fw-600"><?= htmlspecialchars($o['order_number']) ?></td>
                  <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                  <td><?= count((new OrderRepository())->getItems((int) $o['id'])) ?></td>
                  <td><?= format_price((float) $o['total_amount']) ?></td>
                  <td><?php [$pc, $pl] = payment_label($o); ?><span class="status-pill <?= $pc ?>"><?= $pl ?></span></td>
                  <td><span class="status-pill <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                  <td><a href="<?= SITE_URL ?>/dashboards/customer/order-detail.php?order=<?= urlencode($o['order_number']) ?>" class="text-saddle small fw-600">View</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($result['pages'] > 1): ?>
            <div class="pagination-ric">
              <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <a href="?page=<?= $p ?>" class="<?= $p === $result['page'] ? 'active' : '' ?>"><?= $p ?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
          <?php else: ?>
            <p class="text-muted-ric mb-0">No orders yet. <a href="<?= SITE_URL ?>/shop.php" class="text-saddle">Start shopping</a>.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
