<?php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$nav_groups = [
    'Overview' => [
        ['href' => 'index.php', 'icon' => 'fa-gauge', 'label' => 'Dashboard', 'page' => 'index'],
    ],
    'Store' => [
        ['href' => 'products.php', 'icon' => 'fa-shirt', 'label' => 'Products', 'page' => 'products'],
        ['href' => 'categories.php', 'icon' => 'fa-tags', 'label' => 'Categories', 'page' => 'categories'],
        ['href' => 'orders.php', 'icon' => 'fa-bag-shopping', 'label' => 'Orders', 'page' => 'orders'],
        ['href' => 'coupons.php', 'icon' => 'fa-ticket', 'label' => 'Coupons', 'page' => 'coupons'],
        ['href' => 'reviews.php', 'icon' => 'fa-star', 'label' => 'Reviews', 'page' => 'reviews'],
    ],
    'Content' => [
        ['href' => 'blog.php', 'icon' => 'fa-feather', 'label' => 'Journal', 'page' => 'blog'],
        ['href' => 'newsletter.php', 'icon' => 'fa-envelope-open-text', 'label' => 'Newsletter', 'page' => 'newsletter'],
        ['href' => 'gallery.php', 'icon' => 'fa-images', 'label' => 'Gallery', 'page' => 'gallery'],
    ],
    'People' => [
        ['href' => 'users.php', 'icon' => 'fa-users', 'label' => 'Users', 'page' => 'users'],
        ['href' => 'messages.php', 'icon' => 'fa-inbox', 'label' => 'Messages', 'page' => 'messages'],
    ],
    'System' => [
        ['href' => 'settings.php', 'icon' => 'fa-gear', 'label' => 'Settings', 'page' => 'settings'],
    ],
];

function render_admin_nav(array $groups, string $current): void {
    foreach ($groups as $label => $links) {
        echo '<div class="admin-nav-group"><div class="group-label">' . htmlspecialchars($label) . '</div>';
        foreach ($links as $link) {
            $active = $link['page'] === $current ? 'active' : '';
            echo '<a href="' . SITE_URL . '/dashboards/admin/' . $link['href'] . '" class="' . $active . '"><i class="fa-solid ' . $link['icon'] . '"></i>' . htmlspecialchars($link['label']) . '</a>';
        }
        echo '</div>';
    }
}
?>
<aside class="admin-sidebar d-none d-md-block">
  <div class="brand">
    <img src="<?= SITE_URL ?>/assets/img/logo/logo-white.png" alt="<?= SITE_NAME ?>" style="height:34px;width:auto;">
    <div>
      <div style="font-family:var(--font-serif);font-size:.95rem;color:var(--ivory);line-height:1.1;">Steven Picknwear</div>
      <div style="font-size:.65rem;color:var(--text-on-dark-muted);letter-spacing:.08em;text-transform:uppercase;">Admin</div>
    </div>
  </div>
  <?php render_admin_nav($nav_groups, $current_page); ?>
  <div class="admin-nav-group">
    <a href="<?= SITE_URL ?>/index.php"><i class="fa-solid fa-arrow-left"></i>Back to Site</a>
    <a href="<?= SITE_URL ?>/modules/auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign Out</a>
  </div>
</aside>

<!-- Mobile admin drawer (plain CSS/JS — avoids Bootstrap offcanvas conflicts) -->
<button id="adminDrawerToggle" class="admin-drawer-toggle btn-icon-neu" style="position:fixed;bottom:1.2rem;right:1.2rem;z-index:1200;width:52px;height:52px;background:var(--espresso);color:var(--ivory);box-shadow:var(--shadow-lg);">
  <i class="fa-solid fa-bars"></i>
</button>
<div id="adminDrawerBackdrop" style="display:none;position:fixed;inset:0;background:rgba(43,27,18,.5);z-index:1300;"></div>
<div id="adminDrawer" class="admin-sidebar" style="display:none;position:fixed;top:0;left:0;bottom:0;z-index:1400;width:280px;">
  <div class="brand">
    <img src="<?= SITE_URL ?>/assets/img/logo/logo-white.png" alt="<?= SITE_NAME ?>" style="height:34px;width:auto;">
    <div>
      <div style="font-family:var(--font-serif);font-size:.95rem;color:var(--ivory);">Steven Picknwear</div>
      <div style="font-size:.65rem;color:var(--text-on-dark-muted);letter-spacing:.08em;text-transform:uppercase;">Admin</div>
    </div>
  </div>
  <?php render_admin_nav($nav_groups, $current_page); ?>
  <div class="admin-nav-group">
    <a href="<?= SITE_URL ?>/index.php"><i class="fa-solid fa-arrow-left"></i>Back to Site</a>
    <a href="<?= SITE_URL ?>/modules/auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign Out</a>
  </div>
</div>
<script>
(function(){
  const toggle = document.getElementById('adminDrawerToggle');
  const drawer = document.getElementById('adminDrawer');
  const backdrop = document.getElementById('adminDrawerBackdrop');
  if (!toggle) return;
  function open(){ drawer.style.display='block'; backdrop.style.display='block'; }
  function close(){ drawer.style.display='none'; backdrop.style.display='none'; }
  toggle.addEventListener('click', open);
  backdrop.addEventListener('click', close);
})();
</script>
