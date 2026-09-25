<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = safe_int($_POST['id'] ?? 0);
    if ($action === 'approve') {
        db_update('reviews', $id, ['is_approved' => 1]);
        $success = 'Review approved.';
    } elseif ($action === 'unapprove') {
        db_update('reviews', $id, ['is_approved' => 0]);
        $success = 'Review hidden.';
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM reviews WHERE id = :id")->execute([':id' => $id]);
        $success = 'Review deleted.';
    }
}

$filter = $_GET['filter'] ?? 'pending';
$where = $filter === 'pending' ? 'r.is_approved = 0' : ($filter === 'approved' ? 'r.is_approved = 1' : '1=1');
$reviews = db()->query("SELECT r.*, p.name AS product_name, p.slug AS product_slug, u.name AS user_name FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.user_id WHERE $where ORDER BY r.created_at DESC")->fetchAll();

$page_title = 'Reviews — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Store</span><h1 class="h3 mb-0">Reviews</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="d-flex gap-2 mb-4">
      <a href="?filter=pending" class="chip <?= $filter === 'pending' ? 'is-active' : '' ?>">Pending</a>
      <a href="?filter=approved" class="chip <?= $filter === 'approved' ? 'is-active' : '' ?>">Approved</a>
      <a href="?filter=all" class="chip <?= $filter === 'all' ? 'is-active' : '' ?>">All</a>
    </div>

    <div class="d-flex flex-column gap-3">
      <?php foreach ($reviews as $r): ?>
      <div class="admin-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <div class="star-rating mb-1"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-<?= $i <= $r['rating'] ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?></div>
            <p class="mb-1"><?= htmlspecialchars($r['comment']) ?></p>
            <div class="small text-muted-ric"><?= htmlspecialchars($r['user_name']) ?> on <a href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= urlencode($r['product_slug']) ?>" class="text-saddle"><?= htmlspecialchars($r['product_name']) ?></a> &middot; <?= date('d M Y', strtotime($r['created_at'])) ?></div>
          </div>
          <div class="d-flex gap-2">
            <?php if (!$r['is_approved']): ?>
              <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn-ric-sm btn-neu">Approve</button></form>
            <?php else: ?>
              <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="unapprove"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn-ric-sm btn-neu">Hide</button></form>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Delete this review?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button type="submit" class="btn-icon-neu" style="width:34px;height:34px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button></form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$reviews): ?><div class="admin-card text-center py-4 text-muted-ric">No reviews here.</div><?php endif; ?>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
