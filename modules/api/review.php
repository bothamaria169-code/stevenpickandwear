<?php
require_once __DIR__ . '/../../includes/functions.php';

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    json_response(['success' => false, 'message' => 'Forbidden'], 403);
}

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request'], 405);
}

$productId = safe_int($_POST['product_id'] ?? 0);
$rating    = max(1, min(5, safe_int($_POST['rating'] ?? 5)));
$comment   = sanitize(trim($_POST['comment'] ?? ''));

if (!$productId || $comment === '') {
    json_response(['success' => false, 'message' => 'Please write a comment before submitting.']);
}

$repo = new ProductRepository();
if (!$repo->getById($productId)) {
    json_response(['success' => false, 'message' => 'Product not found.'], 404);
}

$stmt = db()->prepare("SELECT id FROM reviews WHERE product_id = :p AND user_id = :u");
$stmt->execute([':p' => $productId, ':u' => $user['id']]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'You\'ve already reviewed this piece.']);
}

db_insert('reviews', [
    'product_id'  => $productId,
    'user_id'     => $user['id'],
    'rating'      => $rating,
    'comment'     => $comment,
    'is_approved' => 0,
]);

json_response(['success' => true, 'message' => 'Thank you — your review is awaiting approval.']);
