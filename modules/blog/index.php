<?php
require_once __DIR__ . '/../../includes/functions.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');
$filters = ['status' => 'published'];
if ($search) $filters['search'] = $search;
$result = get_blogs($filters, $page, 9);

$page_title = 'Journal — ' . SITE_NAME;
$meta_description = 'Style notes, outfit ideas and news from Steven Picknwear.';
require_once __DIR__ . '/../../includes/header.php';
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="section-heading">
      <div><span class="eyebrow">Style Notes</span><h2>The Journal</h2></div>
    </div>

    <form method="GET" class="mb-4" style="max-width:340px;">
      <input type="text" name="search" class="form-control-ric" placeholder="Search articles…" value="<?= htmlspecialchars($search) ?>">
    </form>

    <?php if ($result['items']): ?>
      <div class="row g-4">
        <?php foreach ($result['items'] as $post): ?>
          <div class="col-md-4">
            <a href="<?= SITE_URL ?>/modules/blog/post.php?slug=<?= urlencode($post['slug']) ?>" class="d-block rounded-4 overflow-hidden hover-lift h-100" style="background:var(--white);border:1px solid var(--border-light);">
              <div style="aspect-ratio:4/3;overflow:hidden;background:var(--cream-2);">
                <img src="<?= product_image($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='<?= SITE_URL ?>/assets/img/placeholder.jpg'">
              </div>
              <div class="p-3">
                <div class="small text-muted-ric mb-1"><?= date('d M Y', strtotime($post['created_at'])) ?> &middot; <?= htmlspecialchars($post['author_name'] ?? '') ?></div>
                <h3 class="h6 mb-2"><?= htmlspecialchars($post['title']) ?></h3>
                <?php if ($post['excerpt']): ?><p class="small text-muted-ric mb-0"><?= htmlspecialchars(mb_strimwidth($post['excerpt'], 0, 110, '…')) ?></p><?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($result['pages'] > 1): ?>
        <div class="pagination-ric">
          <?php for ($p = 1; $p <= $result['pages']; $p++): ?><a href="?page=<?= $p ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="<?= $p === $result['page'] ? 'active' : '' ?>"><?= $p ?></a><?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="glass-panel text-center py-5 px-4" style="background:var(--cream);border-color:var(--border-light);">
        <i class="fa-solid fa-feather fa-2x text-saddle mb-3"></i>
        <h3 class="h5">New stories are on the way</h3>
        <p class="text-muted-ric mb-0">Check back soon for cultural notes, craft insight and styling ideas.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
