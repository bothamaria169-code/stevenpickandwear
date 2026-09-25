<?php
require_once __DIR__ . '/../../includes/functions.php';

$query = sanitize($_GET['q'] ?? '');
$page_title = 'Search — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';

$repo = new ProductRepository();
$result = $query ? $repo->getAll(['search' => $query, 'status' => 'published'], max(1, (int) ($_GET['page'] ?? 1))) : ['items' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
$wishlistIds = is_logged_in() ? wishlist_ids((int) $current_user['id']) : [];
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="section-heading">
      <div>
        <span class="eyebrow">Search Results</span>
        <h2>"<?= htmlspecialchars($query) ?>"</h2>
      </div>
      <span class="text-muted-ric small"><?= $result['total'] ?> result<?= $result['total'] === 1 ? '' : 's' ?></span>
    </div>

    <?php if ($result['items']): ?>
      <div class="product-grid">
        <?php foreach ($result['items'] as $product): $in_wishlist = in_array($product['id'], $wishlistIds); ?>
          <?php include __DIR__ . '/../../includes/product-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="glass-panel text-center py-5 px-4" style="background:var(--cream);border-color:var(--border-light);">
        <i class="fa-solid fa-magnifying-glass fa-2x text-saddle mb-3"></i>
        <h3 class="h5">No pieces found</h3>
        <p class="text-muted-ric mb-3">Try a different search term.</p>
        <a href="<?= SITE_URL ?>/shop.php" class="btn-ric-outline">Browse All Pieces</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
