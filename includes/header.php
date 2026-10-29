<?php
require_once __DIR__ . '/functions.php';

$current_user   = current_user();
$cart_count     = cart_count();
$wishlist_count = is_logged_in() ? count(wishlist_ids((int) $current_user['id'])) : 0;
$nav_categories = get_categories();

$page_title       = $page_title ?? SITE_NAME . ' — ' . get_setting('site_tagline', SITE_TAGLINE);
$meta_description = $meta_description ?? 'Steven Picknwear — The International Brand Outlet. Suits, denim and occasion wear for women, men and kids, delivered across South Africa.';
$body_class       = $body_class ?? '';
$extra_css        = $extra_css ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
<meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_description) ?>">
<meta property="og:type" content="website">
<link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/img/logo/favicon.png">
<link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/img/logo/favicon.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/theme.css?v=<?= filemtime(__DIR__ . "/../assets/css/theme.css") ?>">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/custom-style.css?v=<?= filemtime(__DIR__ . "/../assets/css/custom-style.css") ?>">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/animations.css?v=<?= filemtime(__DIR__ . "/../assets/css/animations.css") ?>">
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css?v=<?= filemtime(__DIR__ . "/../assets/css/responsive.css") ?>">
<?php foreach ($extra_css as $href): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
<?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($body_class) ?> has-bottom-nav">

<?php include __DIR__ . '/navbar.php'; ?>

<main id="main-content">