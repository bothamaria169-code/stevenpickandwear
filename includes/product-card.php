<?php
/**
 * Reusable product card.
 * Expects: $product (array), optional $in_wishlist (bool)
 */
$repo = $repo ?? new ProductRepository();
$disc = $repo->discountPercent($product);
$price = $repo->getPrice($product);
$img = product_image($product['featured_image']);
$in_wishlist = $in_wishlist ?? false;
?>
<div class="product-card" data-product-id="<?= $product['id'] ?>">
  <div class="product-card-img-wrap">
    <a href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= urlencode($product['slug']) ?>">
      <img class="product-card-img" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy"
           onerror="this.onerror=null;this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'">
    </a>
    <div class="product-card-badges">
      <?php if (!empty($product['is_one_of_a_kind'])): ?><span class="badge-material"><i class="fa-solid fa-hand"></i>Handcrafted</span><?php endif; ?>
      <?php if ($product['is_sale'] && $disc > 0): ?><span class="badge-sale">-<?= $disc ?>%</span><?php endif; ?>
      <?php if ($product['stock_status'] === 'out_of_stock'): ?><span class="badge-out">Sold Out</span><?php endif; ?>
    </div>
    <div class="product-card-actions">
      <?php if (is_logged_in()): ?>
      <button class="product-action-btn wishlist-btn <?= $in_wishlist ? 'active' : '' ?>" data-product-id="<?= $product['id'] ?>" title="Wishlist">
        <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
      </button>
      <?php endif; ?>
      <a class="product-action-btn" href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= urlencode($product['slug']) ?>" title="View">
        <i class="fas fa-eye"></i>
      </a>
    </div>
  </div>
  <div class="product-card-body">
    <?php if (!empty($product['category_name'])): ?><div class="product-card-category"><?= htmlspecialchars($product['category_name']) ?></div><?php endif; ?>
    <h3 class="product-card-name">
      <a href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= urlencode($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a>
    </h3>
    <div class="product-card-price">
      <span class="price-current"><?= format_price($price) ?></span>
      <?php if ($product['is_sale'] && $product['sale_price']): ?><span class="price-original"><?= format_price((float) $product['base_price']) ?></span><?php endif; ?>
    </div>
  </div>
  <div class="product-card-footer">
    <a href="<?= SITE_URL ?>/modules/shop/product.php?slug=<?= urlencode($product['slug']) ?>" class="add-to-cart-btn" style="display:block;text-align:center;">
      <?= $product['stock_status'] === 'out_of_stock' ? 'Sold Out' : 'Select Size' ?>
    </a>
  </div>
</div>
