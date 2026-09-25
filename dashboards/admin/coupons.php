<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $code = strtoupper(sanitize(trim($_POST['code'] ?? '')));
        if (!$code) {
            $errors[] = 'Coupon code is required.';
        } else {
            db()->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order, max_uses, expiry_date, status) VALUES (:code,:type,:val,:min,:max,:exp,:status)
                ON DUPLICATE KEY UPDATE discount_type=:type2, discount_value=:val2, min_order=:min2, max_uses=:max2, expiry_date=:exp2, status=:status2")
              ->execute([
                  ':code' => $code, ':type' => $_POST['discount_type'] ?? 'percentage', ':val' => safe_float($_POST['discount_value'] ?? 0),
                  ':min' => safe_float($_POST['min_order'] ?? 0), ':max' => $_POST['max_uses'] !== '' ? safe_int($_POST['max_uses']) : null,
                  ':exp' => $_POST['expiry_date'] ?: null, ':status' => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active',
                  ':type2' => $_POST['discount_type'] ?? 'percentage', ':val2' => safe_float($_POST['discount_value'] ?? 0),
                  ':min2' => safe_float($_POST['min_order'] ?? 0), ':max2' => $_POST['max_uses'] !== '' ? safe_int($_POST['max_uses']) : null,
                  ':exp2' => $_POST['expiry_date'] ?: null, ':status2' => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active',
              ]);
            $success = 'Coupon saved.';
        }
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM coupons WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Coupon deleted.';
    } elseif ($action === 'toggle') {
        $stmt = db()->prepare("SELECT status FROM coupons WHERE id = :id");
        $stmt->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $s = $stmt->fetchColumn();
        db_update('coupons', safe_int($_POST['id'] ?? 0), ['status' => $s === 'active' ? 'inactive' : 'active']);
        $success = 'Coupon updated.';
    }
}

$coupons = db()->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$page_title = 'Coupons — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0">Coupons</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="admin-card">
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($coupons as $c): ?>
                <tr>
                  <td class="fw-700"><?= htmlspecialchars($c['code']) ?></td>
                  <td><?= $c['discount_type'] === 'percentage' ? $c['discount_value'] . '%' : format_price((float) $c['discount_value']) ?></td>
                  <td class="small text-muted-ric"><?= format_price((float) $c['min_order']) ?></td>
                  <td class="small text-muted-ric"><?= $c['used_count'] ?><?= $c['max_uses'] ? ' / ' . $c['max_uses'] : '' ?></td>
                  <td class="small text-muted-ric"><?= $c['expiry_date'] ? date('d M Y', strtotime($c['expiry_date'])) : '—' ?></td>
                  <td>
                    <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button type="submit" class="status-pill <?= $c['status'] ?>" style="border:none;cursor:pointer;"><?= ucfirst($c['status']) ?></button>
                    </form>
                  </td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Delete this coupon?');">
                      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button type="submit" class="btn-icon-neu" style="width:30px;height:30px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$coupons): ?><tr><td colspan="7" class="text-center text-muted-ric py-4">No coupons yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="admin-card">
          <h3 class="h6 mb-3">New Coupon</h3>
          <form method="POST">
            <?= csrf_field() ?><input type="hidden" name="action" value="save">
            <div class="mb-3"><label class="form-label-ric">Code</label><input type="text" name="code" class="form-control-ric" style="text-transform:uppercase;" required placeholder="STEVEN10"></div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label-ric">Type</label>
                <select name="discount_type" class="form-control-ric"><option value="percentage">% Off</option><option value="fixed">R Off</option></select>
              </div>
              <div class="col-6"><label class="form-label-ric">Value</label><input type="number" step="0.01" name="discount_value" class="form-control-ric" required></div>
            </div>
            <div class="mb-3"><label class="form-label-ric">Minimum Order (R)</label><input type="number" step="0.01" name="min_order" class="form-control-ric" value="0"></div>
            <div class="mb-3"><label class="form-label-ric">Max Uses (optional)</label><input type="number" name="max_uses" class="form-control-ric"></div>
            <div class="mb-3"><label class="form-label-ric">Expiry Date (optional)</label><input type="date" name="expiry_date" class="form-control-ric"></div>
            <button type="submit" class="btn-ric w-100">Save Coupon</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
