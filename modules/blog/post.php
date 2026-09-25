<?php
require_once __DIR__ . '/../../includes/functions.php';

$slug = sanitize($_GET['slug'] ?? '');
$post = $slug ? get_blog_by_slug($slug) : null;

if (!$post || ($post['status'] !== 'published' && !is_admin())) {
    http_response_code(404);
    $page_title = 'Article Not Found — ' . SITE_NAME;
    require_once __DIR__ . '/../../includes/header.php';
    echo '<section class="section text-center"><div class="container-ric"><h1 class="h4">Article not found</h1><a href="' . SITE_URL . '/modules/blog/index.php" class="btn-ric mt-3">Back To Journal</a></div></section>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

$stmt = db()->prepare("SELECT id, title, slug, image, created_at FROM blogs WHERE status = 'published' AND id != :id ORDER BY created_at DESC LIMIT 3");
$stmt->execute([':id' => $post['id']]);
$morePosts = $stmt->fetchAll();

$page_title = $post['title'] . ' — ' . SITE_NAME;
$meta_description = $post['excerpt'] ?: $post['title'];
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric" style="max-width:760px;">
    <nav class="small text-muted-ric mb-4">
      <a href="<?= SITE_URL ?>/modules/blog/index.php" class="text-muted-ric">Journal</a> / <span><?= htmlspecialchars($post['title']) ?></span>
    </nav>

    <span class="eyebrow">Journal</span>
    <h1 class="mt-2 mb-3" style="font-size:clamp(1.8rem,4vw,2.6rem);"><?= htmlspecialchars($post['title']) ?></h1>
    <div class="small text-muted-ric mb-4"><?= date('d F Y', strtotime($post['created_at'])) ?> &middot; By <?= htmlspecialchars($post['author_name'] ?? 'Steven Picknwear') ?></div>

    <?php if ($post['image']): ?>
      <img src="<?= product_image($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="rounded-4 mb-4" style="width:100%;aspect-ratio:16/9;object-fit:cover;">
    <?php endif; ?>

    <div class="blog-content" style="line-height:1.85;font-size:1.02rem;">
      <?= $post['content'] ?? '' ?>
    </div>
  </div>

  <?php if ($morePosts): ?>
  <div class="container-ric mt-5 pt-5" style="max-width:960px;border-top:1px solid var(--border-light);">
    <h3 class="h5 mb-4">More From The Journal</h3>
    <div class="row g-4">
      <?php foreach ($morePosts as $more_post): ?>
        <div class="col-md-4">
          <a href="<?= SITE_URL ?>/modules/blog/post.php?slug=<?= urlencode($more_post['slug']) ?>" class="d-block rounded-4 overflow-hidden hover-lift" style="background:var(--white);border:1px solid var(--border-light);">
            <div style="aspect-ratio:4/3;overflow:hidden;background:var(--cream-2);">
              <img src="<?= product_image($more_post['image']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'">
            </div>
            <div class="p-3"><h4 class="h6 mb-0"><?= htmlspecialchars($more_post['title']) ?></h4></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
