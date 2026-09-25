<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

// A batch of hi-res photos can take a while to process.
@set_time_limit(1800);

$repo = new ProductRepository();
$uploader = new ImageUploader();
$errors = [];
$createdCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $categoryId = safe_int($_POST['category_id'] ?? 0) ?: null;
    $basePrice  = safe_float($_POST['base_price'] ?? 0);
    $namePrefix = sanitize(trim($_POST['name_prefix'] ?? 'New Product'));

    if (empty($_FILES['photos']['tmp_name'][0])) {
        $errors[] = 'Please choose at least one photo.';
    } else {
        $existingCount = (int) db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
        foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
            if (!$tmp) continue;
            $file = [
                'tmp_name' => $tmp, 'name' => $_FILES['photos']['name'][$i],
                'error' => $_FILES['photos']['error'][$i], 'size' => $_FILES['photos']['size'][$i],
            ];
            try {
                $upload = $uploader->uploadFromRequest($file, 'products/bulk');
                $seq = $existingCount + $createdCount + 1;
                $name = $namePrefix . ' ' . $seq;
                $repo->create([
                    'category_id'      => $categoryId,
                    'name'             => $name,
                    'slug'             => $repo->generateUniqueSlug($name),
                    'short_desc'       => 'Newly catalogued piece — details to be added.',
                    'type'             => 'variable',
                    'base_price'       => $basePrice,
                    'featured_image'   => $upload['secure_url'],
                    'is_one_of_a_kind' => 1,
                    'status'           => 'draft',
                ]);
                $createdCount++;
            } catch (Throwable $e) {
                $errors[] = $_FILES['photos']['name'][$i] . ': ' . $e->getMessage();
            }
        }
    }
}

$categories = get_categories(false);
$page_title = 'Bulk Import — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0">Bulk Import Photos</h1></div>
      <a href="<?= SITE_URL ?>/dashboards/admin/products.php" class="btn-ric-outline">Back To Products</a>
    </div>

    <div class="glass-panel p-3 mb-4" style="background:var(--cream);">
      <p class="small mb-0"><i class="fa-solid fa-circle-info text-saddle me-2"></i>New stock usually starts with a batch of photos. Upload them here — each becomes a <strong>draft</strong> product with that photo as its featured image, ready for you to name, price and size in <a href="<?= SITE_URL ?>/dashboards/admin/products.php" class="text-saddle fw-600">Products</a>.</p>
    </div>

    <?php if ($createdCount): ?>
      <div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= $createdCount ?> draft product(s) created.</div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="admin-card" style="max-width:640px;">
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label-ric">Photos</label>
          <input type="file" name="photos[]" class="form-control-ric" accept="image/*" multiple required>
          <p class="small text-muted-ric mt-1">Select as many as you like — each photo becomes its own draft product.</p>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label-ric">Category (optional)</label>
            <select name="category_id" class="form-control-ric">
              <option value="">None</option>
              <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label-ric">Default Price (R, optional)</label><input type="number" step="0.01" name="base_price" class="form-control-ric" value="0"></div>
        </div>
        <div class="mb-3"><label class="form-label-ric">Name Prefix</label><input type="text" name="name_prefix" class="form-control-ric" value="New Product"></div>
        <button type="submit" class="btn-ric">Upload & Create Drafts</button>
      </form>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
