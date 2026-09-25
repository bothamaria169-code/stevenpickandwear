<?php
require_once __DIR__ . '/../../includes/functions.php';

$token = sanitize($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$done = false;

$stmt = db()->prepare("SELECT id, name FROM users WHERE reset_token = :t AND reset_expires > NOW() LIMIT 1");
$stmt->execute([':t' => $token]);
$user = $token ? $stmt->fetch() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!$user) {
        $errors[] = 'This reset link is invalid or has expired.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            db_update('users', (int) $user['id'], [
                'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'reset_token' => null, 'reset_expires' => null,
            ]);
            $done = true;
        }
    }
}

$page_title = 'Reset Password — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section d-flex align-items-center" style="min-height:70vh;">
  <div class="container-ric" style="max-width:440px;">
    <div class="admin-card">
      <div class="text-center mb-4"><span class="eyebrow">Account Recovery</span><h1 class="h3 mt-1">Set New Password</h1></div>

      <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

      <?php if ($done): ?>
        <div class="small p-3 rounded-3 text-center mb-3" style="background:rgba(76,122,74,.1);color:var(--success);">Your password has been updated.</div>
        <a href="<?= SITE_URL ?>/modules/auth/login.php" class="btn-ric w-100">Sign In</a>
      <?php elseif (!$user): ?>
        <p class="text-muted-ric small">This link is invalid or has expired.</p>
        <a href="<?= SITE_URL ?>/modules/auth/forgot.php" class="btn-ric w-100">Request A New Link</a>
      <?php else: ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
          <div class="mb-3"><label class="form-label-ric">New Password</label><input type="password" name="password" class="form-control-ric" required minlength="8"></div>
          <div class="mb-3"><label class="form-label-ric">Confirm Password</label><input type="password" name="confirm_password" class="form-control-ric" required minlength="8"></div>
          <button type="submit" class="btn-ric w-100">Update Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
