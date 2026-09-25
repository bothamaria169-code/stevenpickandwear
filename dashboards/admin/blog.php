<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$uploader = new ImageUploader();
$postId = safe_int($_GET['id'] ?? 0);
$editing = null;
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = safe_int($_POST['id'] ?? 0);
        $title = sanitize(trim($_POST['title'] ?? ''));
        $content = trim($_POST['content'] ?? '');

        if (!$title) {
            $errors[] = 'Title is required.';
        } else {
            $data = [
                'title'       => $title,
                'excerpt'     => sanitize(trim($_POST['excerpt'] ?? '')),
                'content'     => $content,
                'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
                'status'      => in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft',
                'author_id'   => $current_user['id'],
            ];

            if (!empty($_FILES['image']['tmp_name'])) {
                try {
                    $upload = $uploader->uploadFromRequest($_FILES['image'], 'blog');
                    $data['image'] = $upload['secure_url'];
                } catch (Throwable $e) {
                    $errors[] = 'Cover image upload failed: ' . $e->getMessage();
                }
            }

            if (empty($errors)) {
                if ($id) {
                    unset($data['author_id']);
                    db_update('blogs', $id, $data);
                    $success = 'Article updated.';
                } else {
                    $data['slug'] = (function ($title) {
                        $base = slugify($title); $slug = $base; $i = 1;
                        while (true) {
                            $stmt = db()->prepare("SELECT id FROM blogs WHERE slug = :s");
                            $stmt->execute([':s' => $slug]);
                            if (!$stmt->fetch()) return $slug;
                            $slug = $base . '-' . (++$i);
                        }
                    })($title);
                    $id = db_insert('blogs', $data);
                    $success = 'Article created.';
                }
                $postId = $id;
            }
        }
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM blogs WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Article deleted.';
        $postId = 0;
    }
}

if ($postId) {
    $stmt = db()->prepare("SELECT * FROM blogs WHERE id = :id");
    $stmt->execute([':id' => $postId]);
    $editing = $stmt->fetch();
}

$posts = db()->query("SELECT b.*, u.name AS author_name FROM blogs b LEFT JOIN users u ON u.id = b.author_id ORDER BY b.created_at DESC")->fetchAll();

$page_title = 'Journal — Admin';
$body_class = 'admin-body';
$extra_css = ['https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css'];
$extra_js = ['https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js', SITE_URL . '/assets/js/wysiwyg.js'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Content</span><h1 class="h3 mb-0">Journal</h1></div>
      <?php if ($editing): ?><a href="<?= SITE_URL ?>/dashboards/admin/blog.php" class="btn-ric-outline">+ New Article</a><?php endif; ?>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="admin-card">
          <h3 class="h6 mb-3"><?= $editing ? 'Edit Article' : 'Write New Article' ?></h3>
          <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $editing['id'] ?? '' ?>">
            <div class="mb-3"><label class="form-label-ric">Title</label><input type="text" name="title" class="form-control-ric" value="<?= htmlspecialchars($editing['title'] ?? '') ?>" required></div>
            <div class="mb-3"><label class="form-label-ric">Excerpt</label><textarea name="excerpt" class="form-control-ric" rows="2"><?= htmlspecialchars($editing['excerpt'] ?? '') ?></textarea></div>
            <div class="mb-3">
              <label class="form-label-ric">Content</label>
              <div id="blogEditor" style="background:#fff;border-radius:var(--radius);min-height:280px;"></div>
              <textarea name="content" id="blogContentHidden" style="display:none;"><?= htmlspecialchars($editing['content'] ?? '') ?></textarea>
            </div>
            <div class="mb-3"><label class="form-label-ric">Cover Image</label><input type="file" name="image" class="form-control-ric" accept="image/*"></div>
            <div class="d-flex gap-4 mb-3">
              <label class="d-flex align-items-center gap-2"><input type="checkbox" name="is_featured" value="1" <?= !empty($editing['is_featured']) ? 'checked' : '' ?>> Featured</label>
            </div>
            <div class="row g-2">
              <div class="col-md-6">
                <select name="status" class="form-control-ric">
                  <option value="draft" <?= ($editing['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                  <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
              </div>
              <div class="col-md-6"><button type="submit" class="btn-ric w-100">Save Article</button></div>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="admin-card">
          <h3 class="h6 mb-3">All Articles</h3>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($posts as $p): ?>
              <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3" style="background:<?= (int) $p['id'] === (int) $postId ? 'var(--cream)' : 'transparent' ?>;">
                <a href="<?= SITE_URL ?>/dashboards/admin/blog.php?id=<?= $p['id'] ?>" class="small fw-600 flex-fill text-truncate"><?= htmlspecialchars($p['title']) ?></a>
                <span class="status-pill <?= $p['status'] ?>" style="font-size:.6rem;"><?= ucfirst($p['status']) ?></span>
                <form method="POST" onsubmit="return confirm('Delete this article?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn-icon-neu" style="width:28px;height:28px;color:var(--danger);"><i class="fa-solid fa-trash-can" style="font-size:.65rem;"></i></button>
                </form>
              </div>
            <?php endforeach; ?>
            <?php if (!$posts): ?><p class="small text-muted-ric mb-0">No articles yet.</p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  initRicEditor('blogEditor', 'blogContentHidden', document.getElementById('blogContentHidden').value);
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
