<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('customer');

$repo = new ProductRepository();
$stmt = db()->prepare("SELECT product_id FROM wishlist WHERE user_id = :u ORDER BY added_at DESC");
$stmt->execute([':u' => $current_user['id']]);
$productIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
$products = array_filter(array_map(fn($id) => $repo->getById($id), $productIds));
$wishlistIds = $productIds;

$page_title = 'My Wishlist — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="row g-4">
      <div class="col-lg-3"><?php include __DIR__ . '/../../includes/customer-sidebar.php'; ?></div>
      <div class="col-lg-9">
        <h2 class="h4 mb-4">My Wishlist</h2>
        <?php if ($products): ?>
          <div class="product-grid" style="grid-template-columns:repeat(3,1fr);">
            <?php foreach ($products as $product): $in_wishlist = true; ?>
              <?php include __DIR__ . '/../../includes/product-card.php'; ?>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="admin-card text-center py-5">
            <i class="fa-regular fa-heart fa-2x text-saddle mb-3"></i>
            <h3 class="h6">Your wishlist is empty</h3>
            <p class="text-muted-ric small mb-3">Save pieces you love to find them again later.</p>
            <a href="<?= SITE_URL ?>/shop.php" class="btn-ric-outline">Browse The Collection</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
