<?php
// Serves a proof of payment to an admin or to the order's own buyer.
// uploads/proof-of-payments/ is closed to the web (see its .htaccess).
require_once __DIR__ . '/includes/functions.php';

$order = (new OrderRepository())->getByOrderNumber(sanitize($_GET['order'] ?? ''));
if (!$order || empty($order['pop_path']) || !can_view_order($order)) {
    http_response_code(404);
    exit('Not found');
}

$path = UPLOAD_PATH . 'proof-of-payments/' . basename($order['pop_path']);
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="pop-' . $order['order_number'] . '.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
