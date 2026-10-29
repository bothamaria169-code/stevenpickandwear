<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Shop The Collection — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';

$repo = new ProductRepository();

$filters = ['status' => 'published'];
if (!empty($_GET['category'])) $filters['category'] = sanitize($_GET['category']);
if (!empty($_GET['search']))   $filters['search']   = sanitize($_GET['search']);
if (!empty($_GET['style']))    $filters['style']    = sanitize($_GET['style']);
if (!empty($_GET['size']))     $filters['size']      = sanitize($_GET['size']);
if (($_GET['filter'] ?? '') === 'sale') $filters['sale'] = true;
if (($_GET['filter'] ?? '') === 'new')  $filters['sort'] = 'newest';
$filters['sort'] = sanitize($_GET['sort'] ?? ($filters['sort'] ?? 'newest'));

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = $repo->getAll($filters, $page, PRODUCTS_PER_PAGE);
$categories = get_categories();
$activeCat  = $filters['category'] ?? '';
$styles     = PRODUCT_STYLES[$activeCat] ?? [];
$sizes      = $activeCat === 'kids' ? KIDS_SIZES : JACKET_SIZES;
$heading    = MAIN_CATEGORIES[$activeCat] ?? ($activeCat !== '' ? ucfirst($activeCat) : 'All Products');
if (!empty($filters['style']) && isset($styles[$filters['style']])) {
    $heading .= ' — ' . $styles[$filters['style']];
}
$wishlistIds = is_logged_in() ? wishlist_ids((int) $current_user['id']) : [];

function shop_qs(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
}
?>

<?php
$shopBanners = [
    ''      => ['assets/img/banners/collection.jpg', 'Shop The Collection', 'International style at outlet prices'],
    'women' => ['assets/img/banners/women.jpg', 'For Her', 'Suits, denim and occasion wear'],
    'men'   => ['assets/img/banners/men.jpg', 'For Him', 'Tailoring that means business'],
    'kids'  => ['assets/img/banners/kids.jpg', 'For The Little Ones', 'Kidswear for every celebration'],
];
[$bImg, $bEyebrow, $bTitle] = $shopBanners[$activeCat] ?? $shopBanners[''];
image_banner($bImg, $bEyebrow, $bTitle, '', [], 'p', 'is-page');
?>
<section class="section-sm">
  <div class="container-ric">

    <div class="section-heading">
      <div>
        <span class="eyebrow">The Collection</span>
        <h2><?= htmlspecialchars($heading) ?></h2>
      </div>
      <span class="text-muted-ric small"><?= $result['total'] ?> item<?= $result['total'] === 1 ? '' : 's' ?></span>
    </div>

    <div class="filters-bar">
      <a href="<?= shop_qs(['category' => null, 'style' => null, 'size' => null, 'page' => null]) ?>" class="chip <?= empty($filters['category']) ? 'is-active' : '' ?>">All</a>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= shop_qs(['category' => $cat['slug'], 'style' => null, 'size' => null, 'page' => null]) ?>" class="chip <?= ($filters['category'] ?? '') === $cat['slug'] ? 'is-active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
      <?php endforeach; ?>

      <div class="ms-auto d-flex gap-2 align-items-center">
        <select class="form-control-ric" style="width:auto;" onchange="window.location.href=this.value">
          <?php foreach (['newest' => 'Newest', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'name' => 'Name A–Z'] as $val => $label): ?>
            <option value="<?= shop_qs(['sort' => $val]) ?>" <?= $filters['sort'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn-icon-neu" data-bs-toggle="modal" data-bs-target="#sizeGuideModal" title="Size Guide"><i class="fa-solid fa-ruler"></i></button>
      </div>
    </div>

    <?php if ($styles): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <a href="<?= shop_qs(['style' => null, 'page' => null]) ?>" class="chip <?= empty($filters['style']) ? 'is-active' : '' ?>">All <?= htmlspecialchars(MAIN_CATEGORIES[$activeCat] ?? '') ?></a>
      <?php foreach ($styles as $val => $label): ?>
        <a href="<?= shop_qs(['style' => $val, 'page' => null]) ?>" class="chip <?= ($filters['style'] ?? '') === $val ? 'is-active' : '' ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-4">
      <?php foreach ($sizes as $sz): ?>
        <a href="<?= shop_qs(['size' => ($filters['size'] ?? '') === $sz ? null : $sz]) ?>" class="size-chip <?= ($filters['size'] ?? '') === $sz ? 'is-active' : '' ?>" style="width:auto;padding:0 1rem;border-radius:var(--radius-pill);"><?= $sz ?></a>
      <?php endforeach; ?>
    </div>

    <?php if ($result['items']): ?>
      <div class="product-grid">
        <?php foreach ($result['items'] as $product): $in_wishlist = in_array($product['id'], $wishlistIds); ?>
          <?php include __DIR__ . '/includes/product-card.php'; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($result['pages'] > 1): ?>
        <div class="pagination-ric">
          <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <a href="<?= shop_qs(['page' => $p]) ?>" class="<?= $p === $result['page'] ? 'active' : '' ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="glass-panel text-center py-5 px-4" style="background:var(--cream);border-color:var(--border-light);">
        <i class="fa-solid fa-magnifying-glass fa-2x text-saddle mb-3"></i>
        <?php if ($activeCat === 'kids' && empty($filters['style']) && empty($filters['size'])): ?>
        <h3 class="h5">The kids collection is coming soon</h3>
        <p class="text-muted-ric mb-3">We're getting our kidswear ready. Join the newsletter below to hear when it lands.</p>
        <?php else: ?>
        <h3 class="h5">Nothing matches your filters</h3>
        <p class="text-muted-ric mb-3">Try clearing filters or exploring another category.</p>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/shop.php" class="btn-ric-outline">Clear Filters</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/size-guide-modal.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
