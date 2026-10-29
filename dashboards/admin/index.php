<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$orderRepo = new OrderRepository();
$stats = $orderRepo->stats();

$pending = $orderRepo->getAll(['status' => 'pending'], 1, 5)['items'];

$stmt = db()->query("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
$byDay = [];
foreach ($stmt->fetchAll() as $row) { $byDay[$row['d']] = (int) $row['c']; }
$chartLabels = []; $chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $chartLabels[] = date('D', strtotime($d));
    $chartData[] = $byDay[$d] ?? 0;
}

$lowStock = db()->query("SELECT p.name, v.size_sa, v.stock_qty FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.stock_qty BETWEEN 1 AND 2 ORDER BY v.stock_qty ASC, v.id DESC LIMIT 5")->fetchAll();

$page_title = 'Admin Dashboard — ' . SITE_NAME;
$body_class = 'admin-body';
$extra_js = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Overview</span><h1 class="h3 mb-0">Dashboard</h1></div>
      <a href="<?= SITE_URL ?>/dashboards/admin/product-form.php" class="btn-ric"><i class="fa-solid fa-plus me-2"></i>Add Product</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3"><div class="glass-panel-dark stat-card" style="background:linear-gradient(135deg,var(--espresso),var(--espresso-2));"><div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div><div><div class="stat-value"><?= $stats['total_orders'] ?></div><div class="stat-label">Orders</div></div></div></div>
      <div class="col-6 col-lg-3"><a href="<?= SITE_URL ?>/dashboards/admin/orders.php?payment=review" class="glass-panel-dark stat-card d-flex" style="background:linear-gradient(135deg,var(--espresso),var(--espresso-2));"><div class="stat-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div><div><div class="stat-value"><?= $stats['pop_to_review'] ?></div><div class="stat-label">Proofs To Check</div></div></a></div>
      <div class="col-6 col-lg-3"><div class="glass-panel-dark stat-card" style="background:linear-gradient(135deg,var(--espresso),var(--espresso-2));"><div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div><div><div class="stat-value" style="font-size:1.3rem;"><?= format_price($stats['total_revenue']) ?></div><div class="stat-label">Revenue (Paid)</div></div></div></div>
      <div class="col-6 col-lg-3"><div class="glass-panel-dark stat-card" style="background:linear-gradient(135deg,var(--espresso),var(--espresso-2));"><div class="stat-icon"><i class="fa-solid fa-shirt"></i></div><div><div class="stat-value"><?= $stats['total_products'] ?></div><div class="stat-label">Pieces Listed</div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-lg-7">
        <div class="admin-card h-100">
          <h3 class="h6 mb-3">Orders — Last 7 Days</h3>
          <canvas id="ordersChart" height="110"></canvas>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="admin-card h-100">
          <h3 class="h6 mb-3">Low Stock <span class="small text-muted-ric fw-400">(2 or fewer left)</span></h3>
          <?php if ($lowStock): ?>
            <?php foreach ($lowStock as $ls): ?>
              <div class="d-flex justify-content-between small mb-2 pb-2" style="border-bottom:1px solid var(--border-light);">
                <span><?= htmlspecialchars($ls['name']) ?> <span class="text-muted-ric">(<?= htmlspecialchars($ls['size_sa'] ?: '—') ?>)</span></span>
                <span class="text-saddle fw-600">1 left</span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="small text-muted-ric mb-0">No sizes are running low.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="admin-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h6 mb-0">Pending Orders</h3>
        <a href="<?= SITE_URL ?>/dashboards/admin/orders.php" class="small text-saddle fw-600">View All</a>
      </div>
      <?php if ($pending): ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($pending as $o): ?>
            <tr>
              <td class="fw-600"><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['user_name'] ?? $o['guest_name']) ?></td>
              <td><?= format_price((float) $o['total_amount']) ?></td>
              <td><span class="status-pill <?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
              <td><a href="<?= SITE_URL ?>/dashboards/admin/order-detail.php?id=<?= $o['id'] ?>" class="text-saddle small fw-600">Review</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p class="small text-muted-ric mb-0">No pending orders — all caught up.</p>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>
new Chart(document.getElementById('ordersChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{ label: 'Orders', data: <?= json_encode($chartData) ?>, backgroundColor: '#A50E14', borderRadius: 6, maxBarThickness: 36 }],
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(58,6,8,.08)' } }, x: { grid: { display: false } } },
  },
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
