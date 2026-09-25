<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$uploader = new ImageUploader();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        if (empty($_FILES['images']['tmp_name'][0])) {
            $errors[] = 'Please choose at least one image.';
        } else {
            $maxSort = (int) db()->query("SELECT COALESCE(MAX(sort_order),0) FROM gallery_images")->fetchColumn();
            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if (!$tmp) continue;
                $file = ['tmp_name' => $tmp, 'name' => $_FILES['images']['name'][$i], 'error' => $_FILES['images']['error'][$i], 'size' => $_FILES['images']['size'][$i]];
                try {
                    $upload = $uploader->uploadFromRequest($file, 'gallery');
                    db_insert('gallery_images', [
                        'title' => sanitize(trim($_POST['title'] ?? '')) ?: null,
                        'caption' => sanitize(trim($_POST['caption'] ?? '')) ?: null,
                        'image' => $upload['secure_url'],
                        'sort_order' => ++$maxSort,
                    ]);
                } catch (Throwable $e) {
                    $errors[] = $_FILES['images']['name'][$i] . ': ' . $e->getMessage();
                }
            }
            $success = 'Gallery updated.';
        }
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM gallery_images WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Image removed.';
    } elseif ($action === 'reorder') {
        foreach ($_POST['sort'] ?? [] as $id => $order) {
            db_update('gallery_images', (int) $id, ['sort_order' => (int) $order]);
        }
        $success = 'Order saved.';
    }
}

$images = db()->query("SELECT * FROM gallery_images ORDER BY sort_order, id")->fetchAll();
$page_title = 'Gallery — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Content</span><h1 class="h3 mb-0">Gallery</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="admin-card mb-3">
      <h3 class="h6 mb-3">Upload Images</h3>
      <form method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <div class="col-md-4"><label class="form-label-ric">Title (optional)</label><input type="text" name="title" class="form-control-ric"></div>
        <div class="col-md-4"><label class="form-label-ric">Caption (optional)</label><input type="text" name="caption" class="form-control-ric"></div>
        <div class="col-md-4"><label class="form-label-ric">Images</label><input type="file" name="images[]" class="form-control-ric" accept="image/*" multiple required></div>
        <div class="col-12"><button type="submit" class="btn-ric">Upload</button></div>
      </form>
    </div>

    <div class="admin-card">
      <div class="row g-3">
        <?php foreach ($images as $img): ?>
        <div class="col-6 col-md-3">
          <div class="position-relative rounded-4 overflow-hidden" style="aspect-ratio:3/4;">
            <img src="<?= htmlspecialchars(product_image($img['image'])) ?>" style="width:100%;height:100%;object-fit:cover;">
            <form method="POST" onsubmit="return confirm('Remove this image?');" style="position:absolute;top:8px;right:8px;">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $img['id'] ?>">
              <button type="submit" style="width:28px;height:28px;border-radius:50%;background:rgba(43,27,18,.7);color:#fff;border:none;"><i class="fa-solid fa-trash-can" style="font-size:.65rem;"></i></button>
            </form>
          </div>
          <?php if ($img['title']): ?><div class="small fw-600 mt-1"><?= htmlspecialchars($img['title']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (!$images): ?><p class="text-muted-ric mb-0">No gallery images yet.</p><?php endif; ?>
      </div>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
