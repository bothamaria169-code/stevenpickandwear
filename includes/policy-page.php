<?php
/**
 * Shared renderer for legal / policy pages.
 * The calling page requires functions.php, defines $policy, then requires this.
 *
 * $policy = [
 *   'title'         => 'Terms & Conditions',
 *   'updated'       => 'September 2026',
 *   'intro'         => 'HTML string (optional)',
 *   'sections'      => [ ['num'=>'1','title'=>'…','paras'=>[…],'list'=>[…],'paras_after'=>[…]], … ],
 *   'show_business' => true|false,
 * ];
 */
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/functions.php';
}

$page_title = $policy['title'] . ' — ' . SITE_NAME;
$meta_description = htmlspecialchars($policy['title'] . ' for ' . SITE_NAME . ' — The International Brand Outlet.');
require_once __DIR__ . '/header.php';

$policyLinks = [
    'terms.php'            => 'Terms & Conditions',
    'returns-refunds.php'  => 'Returns, Exchanges & Refunds',
    'shipping.php'         => 'Shipping & Delivery',
    'payment-policy.php'   => 'Payment Policy',
    'privacy.php'          => 'Privacy Policy',
    'cookies.php'          => 'Cookie Policy',
];
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<section class="section-sm">
  <div class="container-ric" style="max-width:1060px;">
    <div class="row g-4 g-lg-5">

      <!-- Policy navigation -->
      <aside class="col-lg-3">
        <div class="policy-nav">
          <div class="eyebrow mb-3">Legal &amp; Policies</div>
          <?php foreach ($policyLinks as $href => $label): ?>
            <a href="<?= SITE_URL ?>/<?= $href ?>" class="<?= $current === $href ? 'is-active' : '' ?>"><?= htmlspecialchars($label) ?></a>
          <?php endforeach; ?>
        </div>
      </aside>

      <!-- Policy body -->
      <div class="col-lg-9">
        <span class="eyebrow">Legal</span>
        <h1 class="mb-1" style="font-size:clamp(1.7rem,3.5vw,2.4rem);"><?= htmlspecialchars($policy['title']) ?></h1>
        <p class="small text-muted-ric mb-4">Last updated: <?= htmlspecialchars($policy['updated']) ?></p>

        <?php if (!empty($policy['intro'])): ?>
          <p class="text-muted-ric" style="line-height:1.85;"><?= $policy['intro'] ?></p>
        <?php endif; ?>

        <?php foreach ($policy['sections'] as $s): ?>
          <h2 class="h6 text-dark mt-4 mb-2"><?= isset($s['num']) ? htmlspecialchars($s['num']) . '. ' : '' ?><?= htmlspecialchars($s['title']) ?></h2>
          <?php foreach (($s['paras'] ?? []) as $p): ?>
            <p class="text-muted-ric" style="line-height:1.8;"><?= $p ?></p>
          <?php endforeach; ?>
          <?php if (!empty($s['list'])): ?>
            <ul class="policy-list text-muted-ric">
              <?php foreach ($s['list'] as $li): ?><li><?= $li ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <?php foreach (($s['paras_after'] ?? []) as $p): ?>
            <p class="text-muted-ric" style="line-height:1.8;"><?= $p ?></p>
          <?php endforeach; ?>
        <?php endforeach; ?>

        <?php
        // Business details come from Admin > Settings; the block hides until they're filled in.
        $bizReg  = get_setting('business_reg_number', '');
        $bizAddr = get_setting('business_address', '');
        ?>
        <?php if (!empty($policy['show_business']) && ($bizReg !== '' || $bizAddr !== '')): ?>
        <div class="admin-card mt-4" style="background:var(--cream);border:1px solid var(--border-light);">
          <h2 class="h6 text-dark mb-2">Business details</h2>
          <p class="text-muted-ric mb-0" style="line-height:1.9;">
            <strong class="text-dark"><?= htmlspecialchars(get_setting('business_legal_name', '') ?: SITE_NAME) ?></strong><br>
            <?php if ($bizReg !== ''): ?>Registration Number: <?= htmlspecialchars($bizReg) ?><br><?php endif; ?>
            <?php if ($bizAddr !== ''): ?><?= nl2br(htmlspecialchars($bizAddr)) ?><br><?php endif; ?>
            South Africa
          </p>
        </div>
        <?php endif; ?>

        <div class="glass-panel mt-4 p-3 p-md-4" style="background:var(--cream);border-color:var(--border-light);">
          <p class="text-muted-ric mb-0" style="line-height:1.7;">
            <i class="fa-solid fa-envelope text-saddle me-2"></i>
            Questions about this policy? Please submit them through our
            <a href="<?= SITE_URL ?>/contact.php" class="text-saddle fw-600">Contact Form</a> and our team will respond using the contact details you provide.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/footer.php'; ?>
