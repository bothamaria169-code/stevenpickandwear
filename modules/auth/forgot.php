<?php
require_once __DIR__ . '/../../includes/functions.php';

$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!rate_limit('forgot_password', 5, 600)) {
        $errors[] = 'Too many attempts. Please try again later.';
    } else {
        $email = safe_email($_POST['email'] ?? '');
        if ($email) {
            $stmt = db()->prepare("SELECT id, name FROM users WHERE email = :e");
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                db_update('users', (int) $user['id'], ['reset_token' => $token, 'reset_expires' => date('Y-m-d H:i:s', time() + 3600)]);
                $resetUrl = SITE_URL . '/modules/auth/reset.php?token=' . $token;
                Mailer::send($email, 'Reset your password', email_wrap("<p>Hi {$user['name']},</p><p>Click below to reset your password. This link expires in 1 hour.</p><p><a href=\"{$resetUrl}\" style=\"color:#A9713D;\">Reset Password</a></p><p>If you didn't request this, you can safely ignore this email.</p>"));
            }
        }
        $sent = true; // Same response whether or not the email exists (no account enumeration)
    }
}

$page_title = 'Reset Password — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section d-flex align-items-center" style="min-height:70vh;">
  <div class="container-ric" style="max-width:440px;">
    <div class="admin-card">
      <div class="text-center mb-4"><span class="eyebrow">Account Recovery</span><h1 class="h3 mt-1">Forgot Password</h1></div>

      <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

      <?php if ($sent): ?>
        <div class="small p-3 rounded-3 text-center" style="background:rgba(76,122,74,.1);color:var(--success);">
          <i class="fa-solid fa-envelope-circle-check fa-lg mb-2 d-block"></i>
          If that email is registered, a reset link is on its way.
        </div>
      <?php else: ?>
        <p class="text-muted-ric small mb-3">Enter your email and we'll send you a link to reset your password.</p>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label-ric">Email</label><input type="email" name="email" class="form-control-ric" required autofocus></div>
          <button type="submit" class="btn-ric w-100">Send Reset Link</button>
        </form>
      <?php endif; ?>
      <p class="text-center small text-muted-ric mt-4 mb-0"><a href="<?= SITE_URL ?>/modules/auth/login.php" class="text-saddle fw-600">Back to sign in</a></p>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
