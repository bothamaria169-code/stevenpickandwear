<?php
require_once __DIR__ . '/../../includes/functions.php';

$token = sanitize($_GET['token'] ?? '');
$service = new NewsletterService();
$done = $token ? $service->unsubscribe($token) : false;

$page_title = 'Unsubscribe — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section text-center">
  <div class="container-ric" style="max-width:480px;">
    <?php if ($done): ?>
      <i class="fa-solid fa-circle-check fa-2x text-saddle mb-3"></i>
      <h1 class="h4 mb-2">You've been unsubscribed</h1>
      <p class="text-muted-ric">You won't receive further emails from us. You're always welcome back.</p>
    <?php else: ?>
      <i class="fa-solid fa-circle-exclamation fa-2x text-saddle mb-3"></i>
      <h1 class="h4 mb-2">Link not recognised</h1>
      <p class="text-muted-ric">This unsubscribe link is invalid or has already been used.</p>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/index.php" class="btn-ric mt-3">Back To Home</a>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
