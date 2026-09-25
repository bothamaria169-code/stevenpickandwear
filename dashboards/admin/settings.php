<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$success = '';
$testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test_email' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $to = safe_email($_POST['test_to'] ?? '') ?: $current_user['email'];
    $ok = Mailer::send($to, 'Test email from ' . SITE_NAME, email_wrap('<p>This is a test email from your store. If you can read this, order emails are working.</p><p>Sent ' . date('d M Y, H:i') . '.</p>'));
    $testResult = $ok ? ['ok' => true, 'msg' => 'Test email sent to ' . $to . '. Check the inbox (and spam).']
                      : ['ok' => false, 'msg' => 'Sending failed: ' . (Mailer::$lastError ?: 'unknown error')];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $fields = [
        'site_name', 'site_tagline', 'currency', 'currency_symbol', 'free_shipping_threshold', 'shipping_flat_rate',
        'bank_name', 'bank_account_holder', 'bank_account_number', 'bank_branch_code', 'bank_account_type',
        'maintenance_mode',
        'contact_phone', 'contact_whatsapp', 'instagram_url', 'facebook_url', 'tiktok_url',
        'business_legal_name', 'business_reg_number', 'business_address',
    ];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            set_setting($f, sanitize(trim($_POST[$f])));
        }
    }
    set_setting('maintenance_mode', !empty($_POST['maintenance_mode']) ? '1' : '0');
    $success = 'Settings saved.';
}

$stmt = db()->query("SELECT key_name, value FROM settings");
$settings = [];
foreach ($stmt as $row) { $settings[$row['key_name']] = $row['value']; }

$page_title = 'Settings — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">System</span><h1 class="h3 mb-0">Settings</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Site</h3>
            <div class="mb-3"><label class="form-label-ric">Site Name</label><input type="text" name="site_name" class="form-control-ric" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label-ric">Tagline</label><input type="text" name="site_tagline" class="form-control-ric" value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>"></div>
            <div class="row g-2">
              <div class="col-6"><label class="form-label-ric">Currency Code</label><input type="text" name="currency" class="form-control-ric" value="<?= htmlspecialchars($settings['currency'] ?? '') ?>"></div>
              <div class="col-6"><label class="form-label-ric">Currency Symbol</label><input type="text" name="currency_symbol" class="form-control-ric" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '') ?>"></div>
            </div>
          </div>

          <div class="admin-card">
            <h3 class="h6 mb-3">Shipping</h3>
            <div class="row g-2">
              <div class="col-6"><label class="form-label-ric">Flat Rate (R)</label><input type="number" step="0.01" name="shipping_flat_rate" class="form-control-ric" value="<?= htmlspecialchars($settings['shipping_flat_rate'] ?? '0') ?>"></div>
              <div class="col-6"><label class="form-label-ric">Free Shipping Over (R)</label><input type="number" step="0.01" name="free_shipping_threshold" class="form-control-ric" value="<?= htmlspecialchars($settings['free_shipping_threshold'] ?? '0') ?>"></div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Banking Details <span class="small text-muted-ric fw-400">(shown at checkout)</span></h3>
            <div class="mb-3"><label class="form-label-ric">Bank Name</label><input type="text" name="bank_name" class="form-control-ric" value="<?= htmlspecialchars($settings['bank_name'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label-ric">Account Holder</label><input type="text" name="bank_account_holder" class="form-control-ric" value="<?= htmlspecialchars($settings['bank_account_holder'] ?? '') ?>"></div>
            <div class="row g-2 mb-3">
              <div class="col-6"><label class="form-label-ric">Account Number</label><input type="text" name="bank_account_number" class="form-control-ric" value="<?= htmlspecialchars($settings['bank_account_number'] ?? '') ?>"></div>
              <div class="col-6"><label class="form-label-ric">Branch Code</label><input type="text" name="bank_branch_code" class="form-control-ric" value="<?= htmlspecialchars($settings['bank_branch_code'] ?? '') ?>"></div>
            </div>
            <div class="mb-0"><label class="form-label-ric">Account Type</label><input type="text" name="bank_account_type" class="form-control-ric" value="<?= htmlspecialchars($settings['bank_account_type'] ?? '') ?>"></div>
          </div>

          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Business Details <span class="small text-muted-ric fw-400">(shown on the Terms and Privacy pages once filled in)</span></h3>
            <div class="mb-3"><label class="form-label-ric">Registered Business Name</label><input type="text" name="business_legal_name" class="form-control-ric" value="<?= htmlspecialchars($settings['business_legal_name'] ?? '') ?>" placeholder="e.g. Steven Picknwear (Pty) Ltd"></div>
            <div class="mb-3"><label class="form-label-ric">Registration Number</label><input type="text" name="business_reg_number" class="form-control-ric" value="<?= htmlspecialchars($settings['business_reg_number'] ?? '') ?>" placeholder="e.g. 2026/123456/07"></div>
            <div class="mb-0"><label class="form-label-ric">Business Address</label><textarea name="business_address" class="form-control-ric" rows="3"><?= htmlspecialchars($settings['business_address'] ?? '') ?></textarea></div>
          </div>

          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Email (Brevo SMTP)</h3>
            <?php $smtpOn = SMTP_USER !== '' && SMTP_PASS !== ''; ?>
            <p class="small mb-2">Status:
              <span class="status-pill <?= $smtpOn ? 'active' : 'pending' ?>"><?= $smtpOn ? 'Connected' : 'Not set up' ?></span>
            </p>
            <p class="small text-muted-ric mb-3">
              Server: <strong class="text-dark"><?= htmlspecialchars(SMTP_HOST ?: '—') ?></strong>
              &middot; Sends from: <strong class="text-dark"><?= htmlspecialchars(SMTP_FROM_EMAIL) ?></strong><br>
              The login is kept on the server in <code>config/config.local.php</code> for security.
            </p>
            <?php if ($testResult): ?>
              <div class="small mb-2 p-2 rounded-3" style="background:<?= $testResult['ok'] ? 'rgba(76,122,74,.1)' : 'rgba(176,67,46,.1)' ?>;color:var(<?= $testResult['ok'] ? '--success' : '--danger' ?>);"><?= htmlspecialchars($testResult['msg']) ?></div>
            <?php endif; ?>
            <div class="d-flex gap-2 flex-wrap">
              <input type="email" name="test_to" form="testEmailForm" class="form-control-ric" style="flex:1;min-width:200px;" value="<?= htmlspecialchars($current_user['email']) ?>">
              <button type="submit" form="testEmailForm" class="btn-neu">Send Test Email</button>
            </div>
          </div>

          <div class="admin-card mb-3">
            <h3 class="h6 mb-3">Contact &amp; Social <span class="small text-muted-ric fw-400">(shown on the Contact page &amp; footer)</span></h3>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label-ric">Phone</label><input type="text" name="contact_phone" class="form-control-ric" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" placeholder="+27 ..."></div>
              <div class="col-6"><label class="form-label-ric">WhatsApp</label><input type="text" name="contact_whatsapp" class="form-control-ric" value="<?= htmlspecialchars($settings['contact_whatsapp'] ?? '') ?>" placeholder="27821234567"></div>
            </div>
            <div class="mb-2"><label class="form-label-ric">Instagram URL</label><input type="url" name="instagram_url" class="form-control-ric" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/..."></div>
            <div class="mb-2"><label class="form-label-ric">Facebook URL</label><input type="url" name="facebook_url" class="form-control-ric" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/..."></div>
            <div class="mb-0"><label class="form-label-ric">TikTok URL</label><input type="url" name="tiktok_url" class="form-control-ric" value="<?= htmlspecialchars($settings['tiktok_url'] ?? '') ?>" placeholder="https://tiktok.com/@..."></div>
          </div>

          <div class="admin-card">
            <label class="d-flex align-items-center gap-2"><input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>> <span>Maintenance Mode</span></label>
          </div>
        </div>
      </div>
      <button type="submit" class="btn-ric mt-3">Save Settings</button>
    </form>
    <form method="POST" id="testEmailForm"><?= csrf_field() ?><input type="hidden" name="action" value="test_email"></form>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
