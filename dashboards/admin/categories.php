<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$uploader = new ImageUploader();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = safe_int($_POST['id'] ?? 0);
        $name = sanitize(trim($_POST['name'] ?? ''));
        if (!$name) {
            $errors[] = 'Category name is required.';
        } else {
            $data = [
                'name' => $name,
                'status' => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active',
                'sort_order' => safe_int($_POST['sort_order'] ?? 0),
            ];
            if (!empty($_FILES['image']['tmp_name'])) {
                try {
                    $upload = $uploader->uploadFromRequest($_FILES['image'], 'categories');
                    $data['image'] = $upload['secure_url'];
                } catch (Throwable $e) {
                    $errors[] = 'Image upload failed: ' . $e->getMessage();
                }
            }
            if (empty($errors)) {
                if ($id) {
                    db_update('categories', $id, $data);
                    $success = 'Category updated.';
                } else {
                    $data['slug'] = slugify($name);
                    db_insert('categories', $data);
                    $success = 'Category created.';
                }
            }
        }
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM categories WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Category deleted.';
    }
}

$categories = get_categories(false);
$page_title = 'Categories — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0">Categories</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="admin-card">
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th></th><th>Name</th><th>Products</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                  <td><img src="<?= htmlspecialchars(product_image($c['image'])) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'"></td>
                  <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
                  <td class="small text-muted-ric"><?= (int) $c['product_count'] ?></td>
                  <td><span class="status-pill <?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                  <td class="text-nowrap">
                    <button type="button" class="btn-icon-neu" style="width:32px;height:32px;" onclick='editCategory(<?= json_encode($c) ?>)'><i class="fa-solid fa-pen small"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button type="submit" class="btn-icon-neu" style="width:32px;height:32px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="admin-card">
          <h3 class="h6 mb-3" id="formTitle">Add Category</h3>
          <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="catId" value="">
            <div class="mb-3"><label class="form-label-ric">Name</label><input type="text" name="name" id="catName" class="form-control-ric" required></div>
            <div class="mb-3"><label class="form-label-ric">Cover Image</label><input type="file" name="image" class="form-control-ric" accept="image/jpeg,image/png,image/webp"></div>
            <div class="mb-3"><label class="form-label-ric">Status</label>
              <select name="status" id="catStatus" class="form-control-ric"><option value="active">Active</option><option value="inactive">Inactive</option></select>
            </div>
            <div class="mb-3"><label class="form-label-ric">Sort Order</label><input type="number" name="sort_order" id="catSort" class="form-control-ric" value="0"></div>
            <button type="submit" class="btn-ric w-100">Save Category</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>
<script>
function editCategory(c) {
  document.getElementById('formTitle').textContent = 'Edit Category';
  document.getElementById('catId').value = c.id;
  document.getElementById('catName').value = c.name;
  document.getElementById('catStatus').value = c.status;
  document.getElementById('catSort').value = c.sort_order;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
