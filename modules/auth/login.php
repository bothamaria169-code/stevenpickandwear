<?php
require_once __DIR__ . '/../../includes/functions.php';

$current_user = current_user();
if (is_logged_in()) {
    redirect($current_user['role'] === 'admin' ? 'dashboards/admin/index.php' : 'dashboards/customer/index.php');
}

$errors = [];
$redirectTo = sanitize($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if (!rate_limit('login', 8, 300)) {
        $errors[] = 'Too many attempts. Please wait a few minutes and try again.';
    } else {
        $email    = safe_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = db()->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Incorrect email or password.';
        } elseif (!$user['is_active']) {
            $errors[] = 'This account has been deactivated.';
        } else {
            JWTHandler::setToken($user);
            redirect($redirectTo && str_starts_with($redirectTo, '/') ? ltrim($redirectTo, '/') : ($user['role'] === 'admin' ? 'dashboards/admin/index.php' : 'dashboards/customer/index.php'));
        }
    }
}

$page_title = 'Sign In — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section d-flex align-items-center" style="min-height:70vh;">
  <div class="container-ric" style="max-width:440px;">
    <div class="admin-card">
      <div class="text-center mb-4">
        <span class="eyebrow">Welcome Back</span>
        <h1 class="h3 mt-1">Sign In</h1>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label-ric">Email</label><input type="email" name="email" class="form-control-ric" required autofocus></div>
        <div class="mb-3">
          <label class="form-label-ric">Password</label>
          <div class="position-relative">
            <input type="password" name="password" id="loginPassword" class="form-control-ric" required>
            <button type="button" id="toggleLoginPassword" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);"><i class="fa-regular fa-eye"></i></button>
          </div>
        </div>
        <div class="d-flex justify-content-end mb-3"><a href="<?= SITE_URL ?>/modules/auth/forgot.php" class="small text-saddle">Forgot password?</a></div>
        <button type="submit" class="btn-ric w-100">Sign In</button>
      </form>
      <p class="text-center small text-muted-ric mt-4 mb-0">New here? <a href="<?= SITE_URL ?>/modules/auth/register.php" class="text-saddle fw-600">Create an account</a></p>
    </div>
  </div>
</section>
<script>
document.getElementById('toggleLoginPassword')?.addEventListener('click', function () {
  const input = document.getElementById('loginPassword');
  const icon = this.querySelector('i');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
