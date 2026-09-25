<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$repo = new ProductRepository();
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $ids = array_map('intval', $_POST['selected_ids'] ?? []);

    if ($action === 'delete' && !empty($_POST['id'])) {
        $repo->delete((int) $_POST['id']);
        $success = 'Product deleted.';
    } elseif ($action === 'toggle_featured' && !empty($_POST['id'])) {
        $p = $repo->getById((int) $_POST['id']);
        $repo->update((int) $_POST['id'], ['is_featured' => $p['is_featured'] ? 0 : 1]);
        $success = 'Updated.';
    } elseif ($action === 'toggle_status' && !empty($_POST['id'])) {
        $p = $repo->getById((int) $_POST['id']);
        $repo->update((int) $_POST['id'], ['status' => $p['status'] === 'published' ? 'draft' : 'published']);
        $success = 'Status updated.';
    } elseif ($action === 'bulk_delete' && $ids) {
        foreach ($ids as $id) { $repo->delete($id); }
        $success = count($ids) . ' product(s) deleted.';
    } elseif ($action === 'bulk_publish' && $ids) {
        foreach ($ids as $id) { $repo->update($id, ['status' => 'published']); }
        $success = count($ids) . ' product(s) published.';
    }
}

$filters = ['status' => 'all'];
if (!empty($_GET['search'])) $filters['search'] = sanitize($_GET['search']);
if (!empty($_GET['category'])) $filters['category'] = sanitize($_GET['category']);
if (!empty($_GET['status']) && $_GET['status'] !== 'all') $filters['status'] = sanitize($_GET['status']);

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = $repo->getAll($filters, $page, 20);
$categories = get_categories(false);

$page_title = 'Products — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0">Products</h1></div>
      <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/dashboards/admin/bulk-import.php" class="btn-ric-outline"><i class="fa-solid fa-images me-2"></i>Bulk Import</a>
        <a href="<?= SITE_URL ?>/dashboards/admin/product-form.php" class="btn-ric"><i class="fa-solid fa-plus me-2"></i>Add Product</a>
      </div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="admin-card mb-3">
      <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="text" name="search" class="form-control-ric" style="max-width:240px;" placeholder="Search products…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <select name="category" class="form-control-ric" style="max-width:180px;">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?><option value="<?= $c['slug'] ?>" <?= ($_GET['category'] ?? '') === $c['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
        </select>
        <select name="status" class="form-control-ric" style="max-width:160px;">
          <?php foreach (['all' => 'All Status', 'published' => 'Published', 'draft' => 'Draft'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($_GET['status'] ?? 'all') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-neu">Filter</button>
      </form>
    </div>

    <form method="POST" id="bulkForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="bulkAction" value="">
      <div class="admin-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="small text-muted-ric"><?= $result['total'] ?> product(s)</span>
          <div class="d-flex gap-2">
            <button type="button" onclick="submitBulk('bulk_publish')" class="btn-ric-sm btn-neu">Publish Selected</button>
            <button type="button" onclick="if(confirm('Delete selected products?'))submitBulk('bulk_delete')" class="btn-ric-sm btn-neu" style="color:var(--danger);">Delete Selected</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="admin-table">
            <thead><tr><th style="width:30px;"><input type="checkbox" id="selectAll"></th><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($result['items'] as $p): ?>
              <tr>
                <td><input type="checkbox" name="selected_ids[]" value="<?= $p['id'] ?>" class="row-check"></td>
                <td><img src="<?= htmlspecialchars(product_image($p['featured_image'])) ?>" style="width:42px;height:50px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'"></td>
                <td>
                  <div class="fw-600"><?= htmlspecialchars($p['name']) ?></div>
                  <?php if ($p['is_one_of_a_kind']): ?><span class="small text-saddle">Handcrafted</span><?php endif; ?>
                </td>
                <td class="small text-muted-ric"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                <td><?= format_price((float) $p['base_price']) ?></td>
                <td><?= (int) $p['stock_qty'] ?: '—' ?></td>
                <td>
                  <!-- Buttons submit the standalone #rowForm below (no nested forms). -->
                  <button type="button" onclick="rowAction('toggle_status', <?= $p['id'] ?>)" class="status-pill <?= $p['status'] ?>" style="border:none;cursor:pointer;"><?= ucfirst($p['status']) ?></button>
                </td>
                <td class="text-nowrap">
                  <a href="<?= SITE_URL ?>/dashboards/admin/product-form.php?id=<?= $p['id'] ?>" class="btn-icon-neu" style="width:34px;height:34px;"><i class="fa-solid fa-pen small"></i></a>
                  <button type="button" onclick="rowAction('delete', <?= $p['id'] ?>, true)" class="btn-icon-neu" style="width:34px;height:34px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$result['items']): ?><tr><td colspan="8" class="text-center text-muted-ric py-4">No products found.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($result['pages'] > 1): ?>
          <div class="pagination-ric">
            <?php for ($p2 = 1; $p2 <= $result['pages']; $p2++): ?><a href="?page=<?= $p2 ?>" class="<?= $p2 === $result['page'] ? 'active' : '' ?>"><?= $p2 ?></a><?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </form>

    <!-- Standalone form for single-row actions (kept OUTSIDE the bulk form so
         no forms are nested — nested forms break submission in browsers). -->
    <form method="POST" id="rowForm" style="display:none;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="rowActionField" value="">
      <input type="hidden" name="id" id="rowIdField" value="">
    </form>
  </main>
</div>
<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});
function submitBulk(action) {
  if (!document.querySelectorAll('.row-check:checked').length) { alert('Select at least one product.'); return; }
  document.getElementById('bulkAction').value = action;
  document.getElementById('bulkForm').submit();
}
function rowAction(action, id, confirmDelete) {
  if (confirmDelete && !confirm('Delete this product?')) return;
  document.getElementById('rowActionField').value = action;
  document.getElementById('rowIdField').value = id;
  document.getElementById('rowForm').submit();
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
