<?php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$is_active = fn(string $page) => $current_page === $page ? 'active' : '';
$nav_cat = $current_page === 'shop' ? (string) ($_GET['category'] ?? '') : '';
?>
<header class="site-header">
  <div class="container-ric d-flex align-items-center justify-content-between" style="height:74px;">

    <a href="<?= SITE_URL ?>/index.php" class="brand-lockup d-flex align-items-center gap-2">
      <img src="<?= SITE_URL ?>/assets/img/logo/logo.png" alt="<?= SITE_NAME ?>" class="logo-mark">
    </a>

    <nav class="desktop-nav d-none d-lg-flex align-items-center gap-4">
      <a href="<?= SITE_URL ?>/index.php" class="<?= $is_active('index') ?>">Home</a>
      <a href="<?= SITE_URL ?>/shop.php" class="<?= $current_page === 'shop' && $nav_cat === '' ? 'active' : '' ?>">Shop All</a>
      <?php foreach (MAIN_CATEGORIES as $slug => $label): ?>
        <a href="<?= SITE_URL ?>/shop.php?category=<?= $slug ?>" class="<?= $nav_cat === $slug ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <a href="<?= SITE_URL ?>/about.php" class="<?= $is_active('about') ?>">About</a>
      <a href="<?= SITE_URL ?>/contact.php" class="<?= $is_active('contact') ?>">Contact</a>
    </nav>

    <div class="d-flex align-items-center gap-1">
      <button type="button" class="header-icon-btn d-none d-md-inline-flex" id="searchToggle" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>

      <?php if ($current_user): ?>
        <div class="dropdown">
          <button class="header-icon-btn" data-bs-toggle="dropdown" aria-label="Account">
            <i class="fa-solid fa-user"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 p-2 mt-2">
            <li><a class="dropdown-item rounded-3" href="<?= SITE_URL ?>/dashboards/<?= $current_user['role'] === 'admin' ? 'admin/index.php' : 'customer/index.php' ?>"><i class="fa-solid fa-gauge me-2"></i>Dashboard</a></li>
            <li><a class="dropdown-item rounded-3" href="<?= SITE_URL ?>/dashboards/customer/orders.php"><i class="fa-solid fa-bag-shopping me-2"></i>My Orders</a></li>
            <li><a class="dropdown-item rounded-3" href="<?= SITE_URL ?>/dashboards/customer/wishlist.php"><i class="fa-solid fa-heart me-2"></i>Wishlist</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item rounded-3" href="<?= SITE_URL ?>/modules/auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Sign out</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/modules/auth/login.php" class="header-icon-btn" aria-label="Sign in"><i class="fa-solid fa-user"></i></a>
      <?php endif; ?>

      <a href="<?= SITE_URL ?>/dashboards/customer/wishlist.php" class="header-icon-btn d-none d-md-inline-flex" aria-label="Wishlist">
        <i class="fa-regular fa-heart"></i>
        <?php if ($wishlist_count > 0): ?><span class="header-badge"><?= $wishlist_count ?></span><?php endif; ?>
      </a>

      <a href="<?= SITE_URL ?>/cart.php" class="header-icon-btn" aria-label="Cart">
        <i class="fa-solid fa-bag-shopping"></i>
        <?php if ($cart_count > 0): ?><span class="header-badge" id="cartBadge"><?= $cart_count ?></span><?php endif; ?>
      </a>

      <button type="button" class="header-icon-btn d-lg-none" id="mobileMenuToggle" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>

  <!-- Mobile nav drawer -->
  <div id="mobileMenu" class="d-lg-none" style="display:none;background:var(--ivory);border-top:1px solid var(--border-light);">
    <nav class="d-flex flex-column p-3 gap-1">
      <a href="<?= SITE_URL ?>/index.php" class="py-2">Home</a>
      <a href="<?= SITE_URL ?>/shop.php" class="py-2">Shop All</a>
      <?php foreach (MAIN_CATEGORIES as $slug => $label): ?>
        <a href="<?= SITE_URL ?>/shop.php?category=<?= $slug ?>" class="py-2"><?= $label ?></a>
      <?php endforeach; ?>
      <a href="<?= SITE_URL ?>/about.php" class="py-2">About</a>
      <a href="<?= SITE_URL ?>/gallery.php" class="py-2">Lookbook</a>
      <a href="<?= SITE_URL ?>/contact.php" class="py-2">Contact</a>
    </nav>
  </div>

  <!-- Search overlay -->
  <div id="searchOverlay" class="glass-panel-dark" style="display:none;position:fixed;inset:0;z-index:2000;">
    <div class="container-ric d-flex flex-column" style="padding-top:8rem;">
      <button id="searchClose" class="btn-icon-neu align-self-end" aria-label="Close search"><i class="fa-solid fa-xmark"></i></button>
      <form action="<?= SITE_URL ?>/modules/shop/search.php" method="GET" class="mt-4">
        <input type="text" name="q" placeholder="Search suits, jeans, dresses…" autocomplete="off"
               style="width:100%;background:transparent;border:none;border-bottom:2px solid rgba(255,255,255,.3);color:var(--ivory);font-family:var(--font-serif);font-size:clamp(1.6rem,5vw,3rem);padding:.6rem 0;outline:none;">
      </form>
    </div>
  </div>
</header>

<!-- Mobile bottom tab bar -->
<nav class="mobile-bottom-nav">
  <a href="<?= SITE_URL ?>/index.php" class="<?= $is_active('index') ?>"><i class="fa-solid fa-house"></i>Home</a>
  <a href="<?= SITE_URL ?>/shop.php" class="<?= $is_active('shop') ?>"><i class="fa-solid fa-shirt"></i>Shop</a>
  <a href="<?= SITE_URL ?>/cart.php" class="<?= $is_active('cart') ?>"><i class="fa-solid fa-bag-shopping"></i>Cart</a>
  <a href="<?= SITE_URL ?>/dashboards/customer/wishlist.php" class="<?= $is_active('wishlist') ?>"><i class="fa-regular fa-heart"></i>Saved</a>
  <a href="<?= $current_user ? SITE_URL . '/dashboards/customer/index.php' : SITE_URL . '/modules/auth/login.php' ?>" class="<?= $is_active('account') ?>"><i class="fa-solid fa-user"></i>Account</a>
</nav>
