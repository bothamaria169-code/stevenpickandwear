<?php
require_once __DIR__ . '/../../includes/functions.php';

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    json_response(['success' => false, 'message' => 'Forbidden'], 403);
}

$action = $_GET['action'] ?? '';

if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!rate_limit('newsletter_subscribe', 10, 600)) {
        json_response(['success' => false, 'message' => 'Please try again shortly.'], 429);
    }
    $service = new NewsletterService();
    $result = $service->subscribe(trim($_POST['email'] ?? ''), sanitize(trim($_POST['name'] ?? '')) ?: null);
    json_response($result);
}

json_response(['success' => false, 'message' => 'Unknown action.'], 400);
