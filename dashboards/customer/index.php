<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('customer');

$orderRepo = new OrderRepository();
$recentOrders = $orderRepo->getForUser((int) $current_user['id'], 1, 5)['items'];
$wishlistCount = count(wishlist_ids((int) $current_user['id']));
$totalOrders = $orderRepo->getForUser((int) $current_user['id'], 1, 1)['total'];

$page_title = 'My Account — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="row g-4">
      <div class="col-lg-3"><?php include __DIR__ . '/../../includes/customer-sidebar.php'; ?></div>
      <div class="col-lg-9">
        <h2 class="h4 mb-4">Welcome back, <?= htmlspecialchars(explode(' ', $current_user['name'])[0]) ?></h2>

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="admin-card d-flex align-items-center gap-3">
              <div class="btn-icon-neu" style="width:48px;height:48px;color:var(--saddle);"><i class="fa-solid fa-bag-shopping"></i></div>
              <div><div class="h5 mb-0"><?= $totalOrders ?></div><div class="small text-muted-ric">Orders</div></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="admin-card d-flex align-items-center gap-3">
              <div class="btn-icon-neu" style="width:48px;height:48px;color:var(--saddle);"><i class="fa-solid fa-heart"></i></div>
              <div><div class="h5 mb-0"><?= $wishlistCount ?></div><div class="small text-muted-ric">Wishlist</div></div>
            </div>
          </div>
          <div class="col-md-4">
            <a href="<?= SITE_URL ?>/shop.php" class="admin-card d-flex align-items-center gap-3 hover-lift">
              <div class="btn-icon-neu" style="width:48px;height:48px;color:var(--saddle);"><i class="fa-solid fa-shirt"></i></div>
              <div><div class="h6 mb-0">Shop</div><div class="small text-muted-ric">Browse pieces</div></div>
            </a>
          </div>
        </div>

        <div class="admin-card">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h6 mb-0">Recent Orders</h3>
            <a href="<?= SITE_URL ?>/dashboards/customer/orders.php" class="small text-saddle fw-600">View All</a>
          </div>
          <?php if ($recentOrders): ?>
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td><a href="<?= SITE_URL ?>/dashboards/customer/order-detail.php?order=<?= urlencode($o['order_number']) ?>" class="text-saddle fw-600"><?= htmlspecialchars($o['order_number']) ?></a></td>
                  <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                  <td><?= format_price((float) $o['total_amount']) ?></td>
                  <td><span class="status-pill <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
            <p class="text-muted-ric small mb-0">No orders yet. <a href="<?= SITE_URL ?>/shop.php" class="text-saddle">Start shopping</a>.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
