<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/jwt_handler.php';
require_once __DIR__ . '/../classes/ImageUploader.php';
require_once __DIR__ . '/../classes/ProductRepository.php';
require_once __DIR__ . '/../classes/OrderRepository.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../classes/NewsletterService.php';

// ── Formatting ───────────────────────────────────────────────────────────
function format_price(float $amount): string
{
    return CURRENCY . number_format($amount, 2);
}

/** Absolute URLs pass through; relative paths (assets/..., uploads/...) get SITE_URL prefixed. */
function product_image(?string $url): string
{
    if (!$url) {
        return SITE_URL . '/assets/img/placeholder.jpg';
    }
    return str_starts_with($url, 'http') ? $url : SITE_URL . '/' . ltrim($url, '/');
}

/**
 * Editorial imagery map (config/editorial-images.php) used by the homepage
 * CTA cards and story sections. Returns a full URL for $key, or null.
 */
function editorial_image(string $key): ?string
{
    static $map = null;
    if ($map === null) {
        $file = __DIR__ . '/../config/editorial-images.php';
        $map = is_file($file) ? (require $file) : [];
    }
    return !empty($map[$key]) ? product_image($map[$key]) : null;
}

/**
 * Full-width photo banner with a red veil. $image is relative to the site root.
 * $buttons = [[label, url, 'primary'|'ghost'], ...]. $tag = heading tag (h1 on page tops).
 */
function image_banner(string $image, string $eyebrow, string $title, string $text = '', array $buttons = [], string $tag = 'h2', string $class = ''): void
{
    ?>
    <section class="img-banner <?= htmlspecialchars($class) ?>">
      <img src="<?= htmlspecialchars(product_image($image)) ?>" alt="" loading="<?= str_contains($class, 'is-page') ? 'eager' : 'lazy' ?>">
      <div class="img-banner-veil"></div>
      <div class="container-ric img-banner-body">
        <span class="eyebrow"><?= htmlspecialchars($eyebrow) ?></span>
        <<?= $tag ?> class="img-banner-title"><?= htmlspecialchars($title) ?></<?= $tag ?>>
        <?php if ($text): ?><p class="img-banner-text"><?= htmlspecialchars($text) ?></p><?php endif; ?>
        <?php if ($buttons): ?>
        <div class="d-flex gap-2 flex-wrap">
          <?php foreach ($buttons as [$label, $url, $style]): ?>
            <a href="<?= htmlspecialchars($url) ?>" class="<?= $style === 'ghost' ? 'btn-ghost-light' : 'btn-ric' ?>"><?= htmlspecialchars($label) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php
}

// ── Upload helpers ─────────────────────────────────────────────────────
/** Plain-English message for a PHP upload error code (null when the upload is fine). */
function upload_error_message(int $code): ?string
{
    return match ($code) {
        UPLOAD_ERR_OK         => null,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE  => 'is too large (max ' . ini_get('upload_max_filesize') . 'B per photo).',
        UPLOAD_ERR_PARTIAL    => 'only partly uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'was not received.',
        default               => 'could not be uploaded (server error ' . $code . ').',
    };
}

/** True when the whole request was bigger than post_max_size, so PHP dropped every field. */
function post_too_large(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && empty($_FILES)
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

// ── EFT / proof of payment ──────────────────────────────────────────────
/**
 * Validates and stores an uploaded proof of payment in uploads/proof-of-payments/
 * (that folder is closed to the web; pop.php serves files after an access check).
 * @return array{ok:bool,path?:string,error?:string}
 */
function store_proof_of_payment(array $file): array
{
    $check = validate_upload($file, ALLOWED_POP_TYPES);
    if (!$check['ok']) {
        return $check;
    }
    $dir = UPLOAD_PATH . 'proof-of-payments/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $filename = secure_filename($file['name'] ?? '', $check['mime']);
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return ['ok' => false, 'error' => 'Could not save your proof of payment. Please try again.'];
    }
    return ['ok' => true, 'path' => $filename];
}

/** Remembers orders placed in this browser session, so guests can see their order and upload proof later. */
function remember_order(string $orderNumber): void
{
    $_SESSION['my_orders'][$orderNumber] = true;
}

/** Admins see every order, customers their own, guests the orders placed in this browser session. */
function can_view_order(array $order): bool
{
    $user = current_user();
    if ($user && $user['role'] === 'admin') {
        return true;
    }
    if ($user && $order['user_id'] !== null && (int) $order['user_id'] === (int) $user['id']) {
        return true;
    }
    return !empty($_SESSION['my_orders'][$order['order_number']]);
}

/** Customer-facing payment state as [css class, label]. */
function payment_label(array $order): array
{
    return match (true) {
        $order['payment_status'] === 'paid'   => ['delivered', 'Paid'],
        $order['payment_status'] === 'failed' => ['cancelled', 'Proof rejected'],
        !empty($order['pop_path'])            => ['processing', 'Proof being checked'],
        default                               => ['pending', 'Awaiting payment'],
    };
}

/** Bank details for EFT as label => value (edited in Admin > Settings). */
function bank_details(): array
{
    return [
        'Bank'           => get_setting('bank_name', ''),
        'Account Holder' => get_setting('bank_account_holder', ''),
        'Account Number' => get_setting('bank_account_number', ''),
        'Branch Code'    => get_setting('bank_branch_code', ''),
        'Account Type'   => get_setting('bank_account_type', ''),
    ];
}

// ── Settings (key/value store) ──────────────────────────────────────────
function get_setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query("SELECT key_name, value FROM settings") as $row) {
            $cache[$row['key_name']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    db()->prepare("INSERT INTO settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2")
        ->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
}

// ── Categories ───────────────────────────────────────────────────────────
function get_categories(bool $activeOnly = true): array
{
    $sql = "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status='published') AS product_count
            FROM categories c" . ($activeOnly ? " WHERE c.status = 'active'" : "") . " ORDER BY c.sort_order, c.name";
    return db()->query($sql)->fetchAll();
}

function get_category_by_slug(string $slug): ?array
{
    $stmt = db()->prepare("SELECT * FROM categories WHERE slug = :s LIMIT 1");
    $stmt->execute([':s' => $slug]);
    return $stmt->fetch() ?: null;
}

// ── Session cart (keyed "productId_variantId") ──────────────────────────
function cart_add(int $productId, int $variantId, int $qty = 1): void
{
    $key = $productId . '_' . $variantId;
    $_SESSION['cart'][$key] = ['product_id' => $productId, 'variant_id' => $variantId, 'qty' => ($_SESSION['cart'][$key]['qty'] ?? 0) + $qty];
}

function cart_update(string $key, int $qty): void
{
    if ($qty <= 0) {
        cart_remove($key);
        return;
    }
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] = $qty;
    }
}

function cart_remove(string $key): void
{
    unset($_SESSION['cart'][$key]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

/** Resolves session cart lines against live product/variant data (fresh price+stock). */
function cart_items(): array
{
    $repo  = new ProductRepository();
    $items = [];
    foreach ($_SESSION['cart'] ?? [] as $key => $line) {
        $product = $repo->getById((int) $line['product_id']);
        $variant = $repo->getVariantById((int) $line['variant_id']);
        if (!$product || !$variant) {
            continue;
        }
        $price = $repo->getPrice($product) + (float) $variant['additional_price'];
        $items[] = [
            'key'        => $key,
            'product'    => $product,
            'variant'    => $variant,
            'qty'        => (int) $line['qty'],
            'price'      => $price,
            'line_total' => $price * (int) $line['qty'],
            'image'      => $variant['image'] ?: $product['featured_image'],
        ];
    }
    return $items;
}

function cart_count(): int
{
    return array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
}

function cart_subtotal(): float
{
    return array_sum(array_column(cart_items(), 'line_total'));
}

// ── Wishlist (DB-backed, requires login) ────────────────────────────────
function wishlist_toggle(int $userId, int $productId): bool
{
    $stmt = db()->prepare("SELECT id FROM wishlist WHERE user_id = :u AND product_id = :p");
    $stmt->execute([':u' => $userId, ':p' => $productId]);
    $row = $stmt->fetch();
    if ($row) {
        db()->prepare("DELETE FROM wishlist WHERE id = :id")->execute([':id' => $row['id']]);
        return false;
    }
    db_insert('wishlist', ['user_id' => $userId, 'product_id' => $productId]);
    return true;
}

function wishlist_ids(int $userId): array
{
    $stmt = db()->prepare("SELECT product_id FROM wishlist WHERE user_id = :u");
    $stmt->execute([':u' => $userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

// ── Blog ─────────────────────────────────────────────────────────────────
function get_blogs(array $filters = [], int $page = 1, int $perPage = 9): array
{
    $where  = ['1=1'];
    $params = [];
    $status = $filters['status'] ?? 'published';
    if ($status !== 'all') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }
    if (!empty($filters['search'])) {
        $where[] = '(title LIKE :s1 OR excerpt LIKE :s2)';
        $params[':s1'] = $params[':s2'] = '%' . $filters['search'] . '%';
    }
    $whereSql = implode(' AND ', $where);

    $count = db()->prepare("SELECT COUNT(*) FROM blogs WHERE $whereSql");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $pages = (int) max(1, ceil($total / $perPage));
    $page  = max(1, min($page, $pages));

    $stmt = db()->prepare("SELECT b.*, u.name AS author_name FROM blogs b LEFT JOIN users u ON u.id = b.author_id WHERE $whereSql ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
    $stmt->execute();

    return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function get_blog_by_slug(string $slug): ?array
{
    $stmt = db()->prepare("SELECT b.*, u.name AS author_name FROM blogs b LEFT JOIN users u ON u.id = b.author_id WHERE b.slug = :s LIMIT 1");
    $stmt->execute([':s' => $slug]);
    return $stmt->fetch() ?: null;
}

// ── Coupons ──────────────────────────────────────────────────────────────
function validate_coupon(string $code, float $subtotal): array
{
    $stmt = db()->prepare("SELECT * FROM coupons WHERE code = :c AND status = 'active' LIMIT 1");
    $stmt->execute([':c' => strtoupper($code)]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid coupon code.'];
    }
    if ($coupon['expiry_date'] && strtotime($coupon['expiry_date']) < time()) {
        return ['valid' => false, 'message' => 'This coupon has expired.'];
    }
    if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
        return ['valid' => false, 'message' => 'This coupon has reached its usage limit.'];
    }
    if ($subtotal < (float) $coupon['min_order']) {
        return ['valid' => false, 'message' => 'Minimum order of ' . format_price((float) $coupon['min_order']) . ' required.'];
    }

    $discount = $coupon['discount_type'] === 'percentage'
        ? $subtotal * ((float) $coupon['discount_value'] / 100)
        : (float) $coupon['discount_value'];

    return ['valid' => true, 'coupon' => $coupon, 'discount' => min($discount, $subtotal)];
}

// ── Transactional email shell (marketing shell lives in NewsletterService) ─
function email_wrap(string $innerHtml): string
{
    $siteUrl = SITE_URL;
    $year    = date('Y');
    return <<<HTML
    <!doctype html><html><body style="margin:0;padding:0;background:#F1E7DA;font-family:'Manrope',Helvetica,Arial,sans-serif;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1E7DA;padding:32px 0;">
        <tr><td align="center">
          <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#FAF6F0;border-radius:12px;overflow:hidden;max-width:600px;width:92%;">
            <tr><td style="background:#4A0507;padding:28px 32px;text-align:center;">
              <span style="color:#FAF6F0;font-size:22px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;">Steven Picknwear</span>
            </td></tr>
            <tr><td style="padding:32px;color:#1F0A0B;font-size:15px;line-height:1.6;">{$innerHtml}</td></tr>
            <tr><td style="background:#F1E7DA;padding:20px 32px;text-align:center;font-size:12px;color:#6E5452;">
              &copy; {$year} Steven Picknwear &middot; <a href="{$siteUrl}" style="color:#A50E14;">{$siteUrl}</a>
            </td></tr>
          </table>
        </td></tr>
      </table>
    </body></html>
    HTML;
}

function notify_order_confirmation(array $order, array $items): void
{
    $email = $order['guest_email'] ?? $order['user_email'] ?? null;
    if (!$email) {
        return;
    }
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:8px 0;">' . htmlspecialchars($item['name']) . ($item['size_sa'] ? ' (' . htmlspecialchars($item['size_sa']) . ')' : '') . '</td><td style="padding:8px 0;text-align:right;">' . format_price((float) $item['price'] * (int) $item['qty']) . '</td></tr>';
    }
    $html = "<p>Thank you for your order, {$order['guest_name']}.</p>
        <p><strong>Order " . htmlspecialchars($order['order_number']) . "</strong></p>
        <table role=\"presentation\" width=\"100%\">{$rows}</table>
        <p style=\"margin-top:16px;\"><strong>Total: " . format_price((float) $order['total_amount']) . "</strong></p>
        " . order_payment_email_block($order);
    Mailer::send($email, 'Your Steven Picknwear order — ' . $order['order_number'], email_wrap($html));

    $adminHtml = '<p>New order <strong>' . htmlspecialchars($order['order_number']) . '</strong> from '
        . htmlspecialchars($order['guest_name']) . ', ' . format_price((float) $order['total_amount']) . '.</p>'
        . '<p>Proof of payment: ' . (!empty($order['pop_path']) ? 'uploaded, ready to check' : 'not uploaded yet') . '.</p>';
    Mailer::send(ADMIN_EMAIL, 'New order ' . $order['order_number'], email_wrap($adminHtml));
}

function order_link(string $orderNumber): string
{
    return SITE_URL . '/order-confirmation.php?order=' . urlencode($orderNumber);
}

function order_payment_email_block(array $order): string
{
    if (!empty($order['pop_path'])) {
        return "<p>We've received your proof of payment and will confirm your order as soon as the payment reflects.</p>";
    }
    $rows = '';
    foreach (bank_details() as $label => $value) {
        $rows .= '<tr><td style="padding:4px 12px 4px 0;color:#6E5452;">' . $label . '</td><td style="padding:4px 0;font-weight:600;">' . htmlspecialchars($value) . '</td></tr>';
    }
    $rows .= '<tr><td style="padding:4px 12px 4px 0;color:#6E5452;">Reference</td><td style="padding:4px 0;font-weight:600;">' . htmlspecialchars($order['order_number']) . '</td></tr>';
    return '<p><strong>Next step:</strong> pay by EFT using your order number as the reference:</p>'
        . '<table role="presentation" style="margin:8px 0 16px;">' . $rows . '</table>'
        . '<p>Then upload your proof of payment: <a href="' . order_link($order['order_number']) . '" style="color:#A50E14;">upload proof of payment</a>. '
        . 'You can also upload it from <em>My Orders</em> if you have an account.</p>';
}

function notify_pop_uploaded(array $order): void
{
    $html = '<p>A proof of payment was uploaded for order <strong>' . htmlspecialchars($order['order_number']) . '</strong> ('
        . format_price((float) $order['total_amount']) . ').</p>'
        . '<p><a href="' . SITE_URL . '/dashboards/admin/order-detail.php?id=' . (int) $order['id'] . '" style="color:#A50E14;">Check it in the admin</a>.</p>';
    Mailer::send(ADMIN_EMAIL, 'Proof of payment: ' . $order['order_number'], email_wrap($html));
}

function notify_payment_decision(array $order, bool $approved, string $reason = ''): void
{
    $email = $order['guest_email'] ?? $order['user_email'] ?? null;
    if (!$email) {
        return;
    }
    $name = htmlspecialchars($order['guest_name'] ?? $order['user_name'] ?? '');
    $num  = htmlspecialchars($order['order_number']);
    $html = $approved
        ? "<p>Hi {$name},</p><p>Your payment for order <strong>{$num}</strong> is confirmed. We're getting your order ready.</p>"
        : "<p>Hi {$name},</p><p>We couldn't match your proof of payment for order <strong>{$num}</strong>."
            . ($reason !== '' ? ' Reason: ' . htmlspecialchars($reason) . '.' : '') . '</p>'
            . '<p>Please <a href="' . order_link($order['order_number']) . '" style="color:#A50E14;">upload a new proof of payment</a>, or reply to this email.</p>';
    Mailer::send($email, ($approved ? 'Payment confirmed: ' : 'Action needed on your payment: ') . $order['order_number'], email_wrap($html));
}

function notify_order_status_change(array $order): void
{
    $email = $order['guest_email'] ?? $order['user_email'] ?? null;
    if (!$email) {
        return;
    }
    $html = "<p>Hi {$order['guest_name']},</p><p>Your order <strong>" . htmlspecialchars($order['order_number']) . "</strong> is now <strong>" . htmlspecialchars(ucfirst($order['status'])) . "</strong>.</p>";
    Mailer::send($email, 'Order update — ' . $order['order_number'], email_wrap($html));
}
