<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!rate_limit('contact_form', 5, 600)) {
        $errors[] = 'Too many messages sent. Please try again later.';
    } else {
        $name = sanitize(trim($_POST['name'] ?? ''));
        $email = safe_email($_POST['email'] ?? '');
        $message = sanitize(trim($_POST['message'] ?? ''));

        if (!$name) $errors[] = 'Please enter your name.';
        if (!$email) $errors[] = 'Please enter a valid email address.';
        if (!$message) $errors[] = 'Please write a message.';

        if (empty($errors)) {
            // Persist first so an enquiry is never lost, even if email fails.
            db_insert('contact_messages', ['name' => $name, 'email' => $email, 'message' => $message]);

            // Then email the admin inbox (SMTP, falls back to PHP mail()).
            // Reply-To is set to the sender so a simple "Reply" reaches them.
            $adminTo = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : SITE_EMAIL;
            Mailer::send(
                $adminTo,
                'New enquiry from ' . $name,
                email_wrap(
                    "<p><strong>From:</strong> " . htmlspecialchars($name) . " (" . htmlspecialchars($email) . ")</p>"
                    . "<p>" . nl2br(htmlspecialchars($message)) . "</p>"
                ),
                '',
                $email,
                $name
            );
            $sent = true;
        }
    }
}

$contactEmail = SITE_EMAIL;
$contactPhone = get_setting('contact_phone');
$whatsapp     = get_setting('contact_whatsapp');
$instagram    = get_setting('instagram_url');

$page_title = 'Contact — ' . SITE_NAME;
$meta_description = 'Get in touch with Steven Picknwear: questions about sizing, orders, payments or delivery.';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container-ric">
    <div class="text-center mb-5">
      <span class="eyebrow">Get In Touch</span>
      <h1 class="mt-2" style="font-size:clamp(1.8rem,4vw,2.6rem);">Contact Steven Picknwear</h1>
      <p class="text-muted-ric" style="max-width:560px;margin:0 auto;">Questions about sizing, an order, your payment or delivery? We'd love to hear from you.</p>
    </div>

    <div class="row g-4 justify-content-center">
      <!-- Contact details -->
      <div class="col-lg-5">
        <div class="admin-card h-100">
          <h2 class="h5 mb-4">Reach Us Directly</h2>

          <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="d-flex align-items-center gap-3 mb-4 text-dark">
            <span class="btn-icon-neu" style="width:48px;height:48px;flex-shrink:0;"><i class="fa-solid fa-envelope"></i></span>
            <span>
              <span class="d-block small text-muted-ric">Email</span>
              <span class="fw-600"><?= htmlspecialchars($contactEmail) ?></span>
            </span>
          </a>

          <?php if ($contactPhone): ?>
          <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contactPhone)) ?>" class="d-flex align-items-center gap-3 mb-4 text-dark">
            <span class="btn-icon-neu" style="width:48px;height:48px;flex-shrink:0;"><i class="fa-solid fa-phone"></i></span>
            <span>
              <span class="d-block small text-muted-ric">Phone</span>
              <span class="fw-600"><?= htmlspecialchars($contactPhone) ?></span>
            </span>
          </a>
          <?php endif; ?>

          <?php if ($whatsapp): ?>
          <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $whatsapp)) ?>" target="_blank" rel="noopener" class="d-flex align-items-center gap-3 mb-4 text-dark">
            <span class="btn-icon-neu" style="width:48px;height:48px;flex-shrink:0;"><i class="fa-brands fa-whatsapp"></i></span>
            <span>
              <span class="d-block small text-muted-ric">WhatsApp</span>
              <span class="fw-600"><?= htmlspecialchars($whatsapp) ?></span>
            </span>
          </a>
          <?php endif; ?>

          <?php if ($address = get_setting('business_address')): ?>
          <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode(str_replace("
", ', ', $address) . ', South Africa') ?>" target="_blank" rel="noopener" class="d-flex align-items-center gap-3 mb-4 text-dark">
            <span class="btn-icon-neu" style="width:48px;height:48px;flex-shrink:0;"><i class="fa-solid fa-location-dot"></i></span>
            <span>
              <span class="d-block small text-muted-ric">Visit Us</span>
              <span class="fw-600"><?= nl2br(htmlspecialchars($address)) ?></span>
            </span>
          </a>
          <?php endif; ?>

          <?php if ($instagram): ?>
          <a href="<?= htmlspecialchars($instagram) ?>" target="_blank" rel="noopener" class="d-flex align-items-center gap-3 text-dark">
            <span class="btn-icon-neu" style="width:48px;height:48px;flex-shrink:0;"><i class="fa-brands fa-instagram"></i></span>
            <span>
              <span class="d-block small text-muted-ric">Instagram</span>
              <span class="fw-600">Follow the journey</span>
            </span>
          </a>
          <?php endif; ?>

          <p class="small text-muted-ric mt-4 mb-0" style="line-height:1.7;">
            Steven Picknwear, The International Brand Outlet. We usually reply within one business day.
          </p>
        </div>
      </div>

      <!-- Message form -->
      <div class="col-lg-5">
        <div class="admin-card h-100">
          <?php if ($sent): ?>
            <div class="text-center py-4">
              <i class="fa-solid fa-circle-check fa-2x text-saddle mb-3"></i>
              <h3 class="h5">Message sent</h3>
              <p class="text-muted-ric mb-0">Thank you — we've received your message and will be in touch soon.</p>
            </div>
          <?php else: ?>
            <h2 class="h5 mb-4">Send a Message</h2>
            <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
            <form method="POST">
              <?= csrf_field() ?>
              <div class="mb-3"><label class="form-label-ric">Name</label><input type="text" name="name" class="form-control-ric" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></div>
              <div class="mb-3"><label class="form-label-ric">Email</label><input type="email" name="email" class="form-control-ric" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
              <div class="mb-3"><label class="form-label-ric">Message</label><textarea name="message" class="form-control-ric" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea></div>
              <button type="submit" class="btn-ric w-100">Send Message</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
