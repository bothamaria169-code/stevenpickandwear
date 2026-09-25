<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$repo = new ProductRepository();
$uploader = new ImageUploader();
$productId = safe_int($_GET['id'] ?? 0);
$product = $productId ? $repo->getById($productId) : null;
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? 'save_product';

    if ($action === 'save_product') {
        $name = sanitize(trim($_POST['name'] ?? ''));
        if (!$name) {
            $errors[] = 'Product name is required.';
        } else {
            $data = [
                'category_id'      => safe_int($_POST['category_id'] ?? 0) ?: null,
                'name'             => $name,
                'short_desc'       => sanitize(trim($_POST['short_desc'] ?? '')),
                'description'      => trim($_POST['description'] ?? ''),
                'base_price'       => safe_float($_POST['base_price'] ?? 0),
                'sale_price'       => !empty($_POST['sale_price']) ? safe_float($_POST['sale_price']) : null,
                'is_sale'          => !empty($_POST['is_sale']) ? 1 : 0,
                'sku'              => sanitize(trim($_POST['sku'] ?? '')) ?: null,
                'stock_status'     => in_array($_POST['stock_status'] ?? '', STOCK_STATUSES) ? $_POST['stock_status'] : 'in_stock',
                'stock_qty'        => safe_int($_POST['stock_qty'] ?? 0),
                'is_featured'      => !empty($_POST['is_featured']) ? 1 : 0,
                'is_one_of_a_kind' => 0,
                'status'           => in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft',
                'tags'             => sanitize(trim($_POST['tags'] ?? '')),
                'type'             => 'variable',
            ];

            if (!empty($_FILES['featured_image']['tmp_name'])) {
                try {
                    $upload = $uploader->uploadFromRequest($_FILES['featured_image'], 'products');
                    $data['featured_image'] = $upload['secure_url'];
                } catch (Throwable $e) {
                    $errors[] = 'Featured image upload failed: ' . $e->getMessage();
                }
            }

            if (empty($errors)) {
                if ($product) {
                    $data['slug'] = $product['slug'];
                    $repo->update($productId, $data);
                } else {
                    $data['slug'] = $repo->generateUniqueSlug($name);
                    $productId = $repo->create($data);
                }

                if (!empty($_FILES['gallery_images']['tmp_name'][0])) {
                    foreach ($_FILES['gallery_images']['tmp_name'] as $i => $tmp) {
                        if (!$tmp) continue;
                        $file = [
                            'tmp_name' => $tmp, 'name' => $_FILES['gallery_images']['name'][$i],
                            'error' => $_FILES['gallery_images']['error'][$i], 'size' => $_FILES['gallery_images']['size'][$i],
                        ];
                        try {
                            $upload = $uploader->uploadFromRequest($file, 'products/gallery');
                            $repo->addGalleryImage($productId, $upload['secure_url'], $i);
                        } catch (Throwable $e) {
                            $errors[] = 'A gallery image failed to upload: ' . $e->getMessage();
                        }
                    }
                }

                $success = 'Product saved.';
                $product = $repo->getById($productId);
            }
        }
    } elseif ($action === 'delete_gallery_image') {
        $repo->deleteGalleryImage(safe_int($_POST['gallery_id'] ?? 0));
        $success = 'Image removed.';
    } elseif ($action === 'add_variant' && $productId) {
        $variantData = [
            'product_id'       => $productId,
            'size_sa'          => sanitize($_POST['v_size'] ?? ''),
            'color_name'       => sanitize($_POST['v_color_name'] ?? ''),
            'color_hex'        => sanitize($_POST['v_color_hex'] ?? ''),
            'pattern_note'     => sanitize(trim($_POST['v_pattern_note'] ?? '')),
            'additional_price' => safe_float($_POST['v_additional_price'] ?? 0),
            'stock_qty'        => safe_int($_POST['v_stock_qty'] ?? 1),
            'sku'              => sanitize(trim($_POST['v_sku'] ?? '')) ?: null,
        ];
        if (!empty($_FILES['v_image']['tmp_name'])) {
            try {
                $upload = $uploader->uploadFromRequest($_FILES['v_image'], 'products/variants');
                $variantData['image'] = $upload['secure_url'];
            } catch (Throwable $e) {
                $errors[] = 'Size photo upload failed: ' . $e->getMessage();
            }
        }
        if (empty($errors)) {
            // Fall back to the product's featured image so every size has a photo.
            if (empty($variantData['image'])) {
                $variantData['image'] = $product['featured_image'] ?? null;
            }
            $repo->addVariant($variantData);
            $success = 'Size added.';
        }
    } elseif ($action === 'update_variant_stock' && !empty($_POST['variant_id'])) {
        $repo->updateVariant((int) $_POST['variant_id'], ['stock_qty' => safe_int($_POST['v_stock_qty'] ?? 0)]);
        $success = 'Stock updated.';
    } elseif ($action === 'delete_variant' && !empty($_POST['variant_id'])) {
        $repo->deleteVariant((int) $_POST['variant_id']);
        $success = 'Variant removed.';
    }
}

$variants = $productId ? $repo->getVariants($productId) : [];
$gallery = $productId ? $repo->getGallery($productId) : [];
$categories = get_categories(false);

$page_title = ($product ? 'Edit' : 'Add') . ' Product — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0"><?= $product ? 'Edit' : 'Add' ?> Product</h1></div>
      <a href="<?= SITE_URL ?>/dashboards/admin/products.php" class="btn-ric-outline">Back To Products</a>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_product">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Product Details</h3>
            <div class="row g-3">
              <div class="col-12"><label class="form-label-ric">Name</label><input type="text" name="name" class="form-control-ric" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required></div>
              <div class="col-12"><label class="form-label-ric">Short Description <span class="text-muted-ric">(shown on product cards)</span></label><textarea name="short_desc" class="form-control-ric" rows="2"><?= htmlspecialchars($product['short_desc'] ?? '') ?></textarea></div>
              <div class="col-12"><label class="form-label-ric">Full Description</label><textarea name="description" class="form-control-ric" rows="6"><?= htmlspecialchars($product['description'] ?? '') ?></textarea></div>
              <div class="col-md-6"><label class="form-label-ric">Category</label>
                <select name="category_id" class="form-control-ric">
                  <option value="">None</option>
                  <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label-ric">Style Tags <span class="text-muted-ric">(comma separated)</span></label><input type="text" name="tags" class="form-control-ric" list="styleTags" value="<?= htmlspecialchars($product['tags'] ?? '') ?>" placeholder="e.g. suits">
                <datalist id="styleTags"><?php foreach (array_unique(array_merge(...array_map('array_keys', array_values(PRODUCT_STYLES)))) as $t): ?><option value="<?= $t ?>"><?php endforeach; ?></datalist>
                <div class="small text-muted-ric mt-1">Include a style so the piece shows under the shop's style filters: <?= implode(', ', array_unique(array_merge(...array_map('array_keys', array_values(PRODUCT_STYLES))))) ?>.</div></div>
            </div>
          </div>

          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Pricing & Stock</h3>
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label-ric">Base Price (R)</label><input type="number" step="0.01" name="base_price" class="form-control-ric" value="<?= htmlspecialchars($product['base_price'] ?? '') ?>" required></div>
              <div class="col-md-4"><label class="form-label-ric">Sale Price (R)</label><input type="number" step="0.01" name="sale_price" class="form-control-ric" value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>"></div>
              <div class="col-md-4"><label class="form-label-ric">SKU</label><input type="text" name="sku" class="form-control-ric" value="<?= htmlspecialchars($product['sku'] ?? '') ?>"></div>
              <div class="col-md-4"><label class="form-label-ric">Stock Status</label>
                <select name="stock_status" class="form-control-ric">
                  <?php foreach (STOCK_STATUSES as $s): ?><option value="<?= $s ?>" <?= ($product['stock_status'] ?? 'in_stock') === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $s)) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4"><label class="form-label-ric">Overall Stock Qty <span class="text-muted-ric">(informational — sizes below track actual stock)</span></label><input type="number" name="stock_qty" class="form-control-ric" value="<?= htmlspecialchars($product['stock_qty'] ?? 0) ?>"></div>
              <div class="col-12 d-flex gap-4 flex-wrap pt-2">
                <label class="d-flex align-items-center gap-2"><input type="checkbox" name="is_sale" value="1" <?= !empty($product['is_sale']) ? 'checked' : '' ?>> On Sale</label>
                <label class="d-flex align-items-center gap-2"><input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> Featured on Homepage</label>
              </div>
            </div>
          </div>

          <?php if (!$productId): ?>
            <div class="glass-panel p-3 mb-3" style="background:var(--cream);"><p class="small mb-0"><i class="fa-solid fa-circle-info text-saddle me-2"></i>Save this product first, then add the sizes you offer it in below.</p></div>
          <?php endif; ?>
        </div>

        <div class="col-lg-4">
          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Featured Image</h3>
            <img src="<?= htmlspecialchars(product_image($product['featured_image'] ?? null)) ?>" style="width:100%;aspect-ratio:3/4;object-fit:cover;border-radius:var(--radius);margin-bottom:.8rem;" onerror="this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'">
            <input type="file" name="featured_image" class="form-control-ric" accept="image/*">
          </div>

          <?php if ($productId): ?>
          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Gallery Images</h3>
            <?php if ($gallery): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($gallery as $g): ?>
              <div class="position-relative">
                <img src="<?= htmlspecialchars(product_image($g['image'])) ?>" style="width:64px;height:64px;object-fit:cover;border-radius:6px;">
                <button type="button" onclick="deleteGalleryImage(<?= $g['id'] ?>)" style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:var(--danger);color:#fff;border:none;font-size:.6rem;">✕</button>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <input type="file" name="gallery_images[]" class="form-control-ric" accept="image/*" multiple>
          </div>
          <?php endif; ?>

          <div class="admin-card">
            <h3 class="h6 mb-3">Publish</h3>
            <select name="status" class="form-control-ric mb-3">
              <option value="draft" <?= ($product['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
              <option value="published" <?= ($product['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
            <button type="submit" class="btn-ric w-100">Save Product</button>
          </div>
        </div>
      </div>
    </form>

    <?php if ($productId): ?>
    <!-- Sizes management lives OUTSIDE the product form: each row is its own
         standalone form. (Nesting these inside the product form is invalid
         HTML and makes Save Product submit the wrong action.) -->
    <div class="admin-card mt-3">
      <h3 class="h6 mb-1">Sizes</h3>
      <p class="small text-muted-ric mb-3">Add each size you offer this piece in, with the stock you have per size. Sizes at 0 stock show as sold out. <strong>These save on their own — separately from “Save Product” above.</strong></p>

      <?php if ($variants): ?>
      <div class="table-responsive mb-3">
        <table class="admin-table">
          <thead><tr><th></th><th>Size</th><th>Colour</th><th>+Price</th><th>Stock</th><th>Note</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($variants as $v): ?>
            <tr>
              <td><img src="<?= htmlspecialchars(product_image($v['image'])) ?>" style="width:40px;height:48px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'"></td>
              <td class="fw-600"><?= htmlspecialchars($v['size_sa']) ?></td>
              <td><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= htmlspecialchars($v['color_hex'] ?: '#ccc') ?>;margin-right:6px;"></span><?= htmlspecialchars($v['color_name'] ?? '') ?></td>
              <td><?= $v['additional_price'] > 0 ? format_price((float) $v['additional_price']) : '—' ?></td>
              <td>
                <form method="POST" class="d-flex gap-1">
                  <?= csrf_field() ?><input type="hidden" name="action" value="update_variant_stock"><input type="hidden" name="variant_id" value="<?= $v['id'] ?>">
                  <input type="number" name="v_stock_qty" value="<?= $v['stock_qty'] ?>" class="form-control-ric" style="width:60px;padding:.4rem;">
                  <button type="submit" class="btn-neu" style="padding:.4rem .6rem;"><i class="fa-solid fa-check small"></i></button>
                </form>
              </td>
              <td class="small text-muted-ric" style="max-width:180px;"><?= htmlspecialchars($v['pattern_note'] ?? '') ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('Remove this size?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete_variant"><input type="hidden" name="variant_id" value="<?= $v['id'] ?>">
                  <button type="submit" class="btn-icon-neu" style="width:32px;height:32px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <details>
        <summary class="btn-ric-sm btn-neu d-inline-block" style="cursor:pointer;">+ Add Size</summary>
        <form method="POST" enctype="multipart/form-data" class="neu-cluster mt-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_variant">
          <div class="row g-2">
            <div class="col-md-2">
              <label class="form-label-ric">Size</label>
              <select name="v_size" class="form-control-ric" required>
                <optgroup label="Adult"><?php foreach (JACKET_SIZES as $sz): ?><option value="<?= $sz ?>"><?= $sz ?></option><?php endforeach; ?></optgroup>
                <optgroup label="Kids"><?php foreach (KIDS_SIZES as $sz): ?><option value="<?= $sz ?>"><?= $sz ?></option><?php endforeach; ?></optgroup>
                <optgroup label="Other"><option value="One Size">One Size</option></optgroup>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label-ric">Colour</label>
              <select name="v_color_name" class="form-control-ric" onchange="document.getElementById('vColorHex').value=this.selectedOptions[0].dataset.hex">
                <?php foreach (HIDE_TONES as $tone => $hex): ?><option value="<?= $tone ?>" data-hex="<?= $hex ?>"><?= $tone ?></option><?php endforeach; ?>
              </select>
              <input type="hidden" name="v_color_hex" id="vColorHex" value="<?= array_values(HIDE_TONES)[0] ?>">
            </div>
            <div class="col-md-2"><label class="form-label-ric">+Price (R)</label><input type="number" step="0.01" name="v_additional_price" class="form-control-ric" value="0"></div>
            <div class="col-md-2"><label class="form-label-ric">Stock</label><input type="number" name="v_stock_qty" class="form-control-ric" value="5"></div>
            <div class="col-md-3"><label class="form-label-ric">SKU</label><input type="text" name="v_sku" class="form-control-ric"></div>
            <div class="col-md-6"><label class="form-label-ric">Note <span class="text-muted-ric">(optional, shown when this size is picked)</span></label><input type="text" name="v_pattern_note" class="form-control-ric" placeholder="e.g. Slim fit, trousers 32in waist" maxlength="160"></div>
            <div class="col-md-6"><label class="form-label-ric">Size Photo <span class="text-muted-ric">(optional)</span></label><input type="file" name="v_image" class="form-control-ric" accept="image/*"></div>
          </div>
          <button type="submit" class="btn-ric-sm btn-ric mt-3">Add Size</button>
        </form>
      </details>
    </div>
    <?php endif; ?>
  </main>
</div>

<form method="POST" id="deleteGalleryForm" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="delete_gallery_image">
  <input type="hidden" name="gallery_id" id="deleteGalleryId">
</form>
<script>
function deleteGalleryImage(id) {
  if (!confirm('Remove this image?')) return;
  document.getElementById('deleteGalleryId').value = id;
  document.getElementById('deleteGalleryForm').submit();
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
