<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('customer');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name  = sanitize(trim($_POST['name'] ?? ''));
        $phone = sanitize(trim($_POST['phone'] ?? ''));
        if (!$name) {
            $errors[] = 'Name is required.';
        } else {
            db_update('users', (int) $current_user['id'], ['name' => $name, 'phone' => $phone ?: null]);
            $success = 'Profile updated.';
            $current_user['name'] = $name;
            $current_user['phone'] = $phone;
        }
    } elseif ($action === 'change_password') {
        $stmt = db()->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->execute([':id' => $current_user['id']]);
        $hash = $stmt->fetchColumn();

        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            db_update('users', (int) $current_user['id'], ['password' => password_hash($new, PASSWORD_BCRYPT, ['cost' => 12])]);
            $success = 'Password changed successfully.';
        }
    }
}

$page_title = 'Account Details — ' . SITE_NAME;
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="row g-4">
      <div class="col-lg-3"><?php include __DIR__ . '/../../includes/customer-sidebar.php'; ?></div>
      <div class="col-lg-9">
        <h2 class="h4 mb-4">Account Details</h2>

        <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

        <div class="admin-card mb-4">
          <h3 class="h6 mb-3">Profile</h3>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label-ric">Full Name</label><input type="text" name="name" class="form-control-ric" value="<?= htmlspecialchars($current_user['name']) ?>" required></div>
              <div class="col-md-6"><label class="form-label-ric">Email</label><input type="email" class="form-control-ric" value="<?= htmlspecialchars($current_user['email']) ?>" disabled></div>
              <div class="col-md-6"><label class="form-label-ric">Phone</label><input type="tel" name="phone" class="form-control-ric" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>"></div>
            </div>
            <button type="submit" class="btn-ric mt-3">Save Changes</button>
          </form>
        </div>

        <div class="admin-card">
          <h3 class="h6 mb-3">Change Password</h3>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label-ric">Current Password</label><input type="password" name="current_password" class="form-control-ric" required></div>
              <div class="col-md-4"><label class="form-label-ric">New Password</label><input type="password" name="new_password" class="form-control-ric" required minlength="8"></div>
              <div class="col-md-4"><label class="form-label-ric">Confirm New Password</label><input type="password" name="confirm_password" class="form-control-ric" required minlength="8"></div>
            </div>
            <button type="submit" class="btn-ric mt-3">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
