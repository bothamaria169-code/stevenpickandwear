<?php
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$links = [
    ['href' => 'index.php', 'icon' => 'fa-gauge', 'label' => 'Overview', 'page' => 'index'],
    ['href' => 'orders.php', 'icon' => 'fa-bag-shopping', 'label' => 'My Orders', 'page' => 'orders'],
    ['href' => 'wishlist.php', 'icon' => 'fa-heart', 'label' => 'Wishlist', 'page' => 'wishlist'],
    ['href' => 'account.php', 'icon' => 'fa-user', 'label' => 'Account Details', 'page' => 'account'],
];
?>
<div class="admin-card mb-4">
  <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--border-light);">
    <div class="btn-icon-neu" style="width:52px;height:52px;"><i class="fa-solid fa-user fa-lg"></i></div>
    <div>
      <div class="fw-700"><?= htmlspecialchars($current_user['name'] ?? 'Guest') ?></div>
      <div class="small text-muted-ric"><?= htmlspecialchars($current_user['email'] ?? '') ?></div>
    </div>
  </div>
  <nav class="d-flex flex-column gap-1">
    <?php foreach ($links as $link): $active = $link['page'] === $current_page; ?>
      <a href="<?= SITE_URL ?>/dashboards/customer/<?= $link['href'] ?>"
         class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
         style="font-size:.88rem;font-weight:600;color:<?= $active ? 'var(--white)' : 'var(--text-dark)' ?>;background:<?= $active ? 'var(--saddle)' : 'transparent' ?>;">
        <i class="fa-solid <?= $link['icon'] ?>"></i><?= $link['label'] ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= SITE_URL ?>/modules/auth/logout.php" class="d-flex align-items-center gap-2 px-3 py-2 rounded-3" style="font-size:.88rem;font-weight:600;color:var(--danger);">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>Sign Out
    </a>
  </nav>
</div>
