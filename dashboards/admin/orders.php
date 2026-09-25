<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$orderRepo = new OrderRepository();
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = sanitize($_GET['status']);
if (!empty($_GET['search'])) $filters['search'] = sanitize($_GET['search']);
if (!empty($_GET['payment'])) $filters['payment'] = sanitize($_GET['payment']);
$paymentFilters = ['review' => 'Proof to check', 'awaiting' => 'Awaiting payment', 'paid' => 'Paid', 'failed' => 'Proof rejected'];

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = $orderRepo->getAll($filters, $page, 20);

$page_title = 'Orders — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0">Orders</h1></div>
    </div>

    <div class="admin-card mb-3">
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" class="form-control-ric" style="max-width:240px;" placeholder="Order # or customer…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <select name="status" class="form-control-ric" style="max-width:180px;">
          <option value="">All Status</option>
          <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select>
        <select name="payment" class="form-control-ric" style="max-width:200px;">
          <option value="">All Payments</option>
          <?php foreach ($paymentFilters as $val => $label): ?><option value="<?= $val ?>" <?= ($_GET['payment'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn-neu">Filter</button>
      </form>
    </div>

    <div class="admin-card">
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($result['items'] as $o): ?>
            <tr>
              <td class="fw-600"><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['user_name'] ?? $o['guest_name']) ?></td>
              <td class="small text-muted-ric"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
              <td><?= format_price((float) $o['total_amount']) ?></td>
              <td><?php [$pc, $pl] = payment_label($o); ?><span class="status-pill <?= $pc ?>"><?= $pl ?></span></td>
              <td><span class="status-pill <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              <td><a href="<?= SITE_URL ?>/dashboards/admin/order-detail.php?id=<?= $o['id'] ?>" class="text-saddle small fw-600">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$result['items']): ?><tr><td colspan="7" class="text-center text-muted-ric py-4">No orders found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($result['pages'] > 1): ?>
        <div class="pagination-ric"><?php for ($p = 1; $p <= $result['pages']; $p++): ?><a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $p]))) ?>" class="<?= $p === $result['page'] ? 'active' : '' ?>"><?= $p ?></a><?php endfor; ?></div>
      <?php endif; ?>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
