<?php
require_once __DIR__ . '/../../includes/functions.php';

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    json_response(['success' => false, 'message' => 'Forbidden'], 403);
}

function cart_response(string $message = '', bool $success = true): array
{
    $items = cart_items();
    $subtotal = array_sum(array_column($items, 'line_total'));

    $discount = 0.0;
    $couponCode = $_SESSION['coupon_code'] ?? null;
    if ($couponCode) {
        $check = validate_coupon($couponCode, $subtotal);
        if ($check['valid']) {
            $discount = $check['discount'];
        } else {
            unset($_SESSION['coupon_code']);
            $couponCode = null;
        }
    }

    $freeThreshold = (float) get_setting('free_shipping_threshold', '999999');
    $flatRate      = (float) get_setting('shipping_flat_rate', '0');
    $shipping      = $subtotal > 0 ? (($subtotal - $discount) >= $freeThreshold ? 0.0 : $flatRate) : 0.0;
    $total         = max(0, $subtotal - $discount + $shipping);

    return [
        'success' => $success,
        'message' => $message,
        'count'   => cart_count(),
        'totals'  => [
            'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'total' => $total,
            'coupon_code' => $couponCode,
            'formatted_subtotal' => format_price($subtotal),
            'formatted_discount' => format_price($discount),
            'formatted_shipping' => $shipping > 0 ? format_price($shipping) : 'Free',
            'formatted_total'    => format_price($total),
        ],
    ];
}

$action = $_GET['action'] ?? '';
$repo = new ProductRepository();

switch ($action) {
    case 'add':
        $productId = safe_int($_POST['product_id'] ?? 0);
        $variantId = safe_int($_POST['variant_id'] ?? 0);
        $qty       = max(1, min(10, safe_int($_POST['qty'] ?? 1)));

        $variant = $variantId ? $repo->getVariantById($variantId) : null;
        if (!$productId || !$variant || (int) $variant['product_id'] !== $productId) {
            json_response(cart_response('Please select a size first.', false));
        }
        if ((int) $variant['stock_qty'] <= 0) {
            json_response(cart_response('This piece has sold out.', false));
        }
        cart_add($productId, $variantId, $qty);
        json_response(cart_response('Added to your bag.'));
        break;

    case 'update':
        $key = sanitize($_POST['key'] ?? '');
        $qty = max(1, min(10, safe_int($_POST['qty'] ?? 1)));
        cart_update($key, $qty);
        json_response(cart_response('Cart updated.'));
        break;

    case 'remove':
        $key = sanitize($_POST['key'] ?? '');
        cart_remove($key);
        json_response(cart_response('Removed from your bag.'));
        break;

    case 'count':
        json_response(cart_response());
        break;

    case 'coupon':
        $code = sanitize(trim($_POST['code'] ?? ''));
        $subtotal = array_sum(array_column(cart_items(), 'line_total'));
        $check = validate_coupon($code, $subtotal);
        if (!$check['valid']) {
            json_response(cart_response($check['message'], false));
        }
        $_SESSION['coupon_code'] = strtoupper($code);
        json_response(cart_response('Coupon applied.'));
        break;

    case 'remove_coupon':
        unset($_SESSION['coupon_code']);
        json_response(cart_response('Coupon removed.'));
        break;

    default:
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
}
