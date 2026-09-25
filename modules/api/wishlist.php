<?php
require_once __DIR__ . '/../../includes/functions.php';

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    json_response(['success' => false, 'message' => 'Forbidden'], 403);
}

$user = require_auth();
$productId = safe_int($_POST['product_id'] ?? 0);

if (!$productId) {
    json_response(['success' => false, 'message' => 'Invalid product.'], 400);
}

$added = wishlist_toggle((int) $user['id'], $productId);
json_response([
    'success' => true,
    'added'   => $added,
    'message' => $added ? 'Added to your wishlist.' : 'Removed from your wishlist.',
]);
