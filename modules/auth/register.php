<?php
require_once __DIR__ . '/../../includes/functions.php';

if (is_logged_in()) {
    redirect('dashboards/customer/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $name     = sanitize(trim($_POST['name'] ?? ''));
    $email    = safe_email($_POST['email'] ?? '');
    $phone    = sanitize(trim($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name) $errors[] = 'Please enter your name.';
    if (!$email) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = db()->prepare("SELECT id FROM users WHERE email = :e");
        $stmt->execute([':e' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $userId = db_insert('users', [
            'name' => $name, 'email' => $email, 'phone' => $phone ?: null,
            'password' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => 'customer',
        ]);
        $stmt = db()->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        JWTHandler::setToken($stmt->fetch());

        Mailer::send($email, 'Welcome to Steven Picknwear', email_wrap("<p>Hi {$name},</p><p>Welcome to Steven Picknwear, and thank you for joining us. You can now track your orders, upload proof of payment and save favourites to your wishlist.</p>"));

        redirect('dashboards/customer/index.php');
    }
}

$page_title = 'Create Account — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section d-flex align-items-center" style="min-height:70vh;">
  <div class="container-ric" style="max-width:460px;">
    <div class="admin-card">
      <div class="text-center mb-4">
        <span class="eyebrow">Join Us</span>
        <h1 class="h3 mt-1">Create Your Account</h1>
      </div>

      <?php foreach ($errors as $err): ?>
        <div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label-ric">Full Name</label><input type="text" name="name" class="form-control-ric" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label-ric">Email</label><input type="email" name="email" class="form-control-ric" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
        <div class="mb-3"><label class="form-label-ric">Phone (optional)</label><input type="tel" name="phone" class="form-control-ric" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
        <div class="mb-3"><label class="form-label-ric">Password</label><input type="password" name="password" class="form-control-ric" required minlength="8"></div>
        <div class="mb-3"><label class="form-label-ric">Confirm Password</label><input type="password" name="confirm_password" class="form-control-ric" required minlength="8"></div>
        <button type="submit" class="btn-ric w-100">Create Account</button>
      </form>
      <p class="text-center small text-muted-ric mt-4 mb-0">Already have an account? <a href="<?= SITE_URL ?>/modules/auth/login.php" class="text-saddle fw-600">Sign in</a></p>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
