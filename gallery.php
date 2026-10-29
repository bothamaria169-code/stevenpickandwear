<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Gallery — ' . SITE_NAME;
$meta_description = 'The Steven Picknwear lookbook: outfits and styling ideas.';
require_once __DIR__ . '/includes/header.php';

$images = db()->query("SELECT * FROM gallery_images ORDER BY sort_order, id")->fetchAll();
if (!$images) {
    // Until the admin uploads lookbook photos, show the latest shoot and the workshop.
    $shoot = ['black-safari-suit', 'rust-jacquard-blazer-2', 'steel-blue-satin-shirt-trouser-set', 'charcoal-pinstripe-mandarin-suit',
              'rust-jacquard-blazer', 'steel-blue-satin-shirt-trouser-set-2', 'chocolate-satin-shirt-trouser-set', 'black-safari-suit-3',
              'girls-blue-ankara-skater-dress', 'boys-white-gold-embroidered-suit', 'girls-orange-tulle-party-dress-with-bag', 'boys-royal-blue-agbada-set-with-cap'];
    foreach ($shoot as $slug) {
        $images[] = ['image' => "assets/img/products/$slug.jpg", 'title' => '', 'caption' => ''];
    }
    foreach (['workshop-01', 'workshop-04', 'workshop-07'] as $photo) {
        $images[] = ['image' => "assets/img/office/$photo.jpg", 'title' => 'Our workshop', 'caption' => ''];
    }
}
image_banner('assets/img/banners/workshop-tailor.jpg', 'Style Inspiration', 'The Lookbook', 'Outfits from our shoots and a look inside the workshop.', [], 'p', 'is-page');
?>
<section class="section-sm">
  <div class="container-ric">
    <div class="section-heading">
      <div><span class="eyebrow">Style Inspiration</span><h2>Lookbook</h2></div>
    </div>

    <?php if ($images): ?>
      <div class="row g-3">
        <?php foreach ($images as $i => $img): ?>
          <div class="col-6 col-md-4">
            <button type="button" class="gallery-item position-relative rounded-4 overflow-hidden hover-lift"
                    style="aspect-ratio:3/4;width:100%;"
                    data-index="<?= $i ?>"
                    data-full="<?= htmlspecialchars(product_image($img['image'])) ?>"
                    data-title="<?= htmlspecialchars($img['title'] ?? '') ?>"
                    data-caption="<?= htmlspecialchars($img['caption'] ?? '') ?>">
              <span class="gallery-zoom-cue"><i class="fa-solid fa-expand"></i></span>
              <img src="<?= htmlspecialchars(product_image($img['image'])) ?>" alt="<?= htmlspecialchars($img['title'] ?? 'Steven Picknwear lookbook') ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
              <?php if ($img['title'] || $img['caption']): ?>
              <span style="position:absolute;left:0;right:0;bottom:0;padding:1rem;background:linear-gradient(transparent,rgba(58,6,8,.85));color:var(--ivory);text-align:left;display:block;">
                <?php if ($img['title']): ?><span class="fw-700 small d-block"><?= htmlspecialchars($img['title']) ?></span><?php endif; ?>
                <?php if ($img['caption']): ?><span class="small d-block" style="opacity:.85;"><?= htmlspecialchars($img['caption']) ?></span><?php endif; ?>
              </span>
              <?php endif; ?>
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="glass-panel text-center py-5 px-4" style="background:var(--cream);border-color:var(--border-light);">
        <i class="fa-solid fa-images fa-2x text-saddle mb-3"></i>
        <h3 class="h5">Our lookbook is being curated</h3>
        <p class="text-muted-ric mb-0">Check back soon for outfit ideas and new looks.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Lightbox -->
<div id="galleryLightbox" class="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
  <button type="button" class="lightbox-close" id="lbClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
  <button type="button" class="lightbox-nav prev" id="lbPrev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
  <img id="lbImage" src="" alt="">
  <button type="button" class="lightbox-nav next" id="lbNext" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
  <div class="lightbox-caption" id="lbCaption"></div>
</div>

<script>
(function () {
  const items = Array.from(document.querySelectorAll('.gallery-item'));
  if (!items.length) return;
  const lb = document.getElementById('galleryLightbox');
  const img = document.getElementById('lbImage');
  const cap = document.getElementById('lbCaption');
  let current = 0;

  function show(i) {
    current = (i + items.length) % items.length;
    const el = items[current];
    img.src = el.dataset.full;
    const title = el.dataset.title || '';
    const caption = el.dataset.caption || '';
    cap.innerHTML = (title ? '<strong>' + title + '</strong>' : '') + (title && caption ? ' — ' : '') + (caption || '');
  }
  function open(i) { show(i); lb.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
  function close() { lb.classList.remove('is-open'); document.body.style.overflow = ''; }

  items.forEach((el, i) => el.addEventListener('click', () => open(i)));
  document.getElementById('lbClose').addEventListener('click', close);
  document.getElementById('lbNext').addEventListener('click', (e) => { e.stopPropagation(); show(current + 1); });
  document.getElementById('lbPrev').addEventListener('click', (e) => { e.stopPropagation(); show(current - 1); });
  lb.addEventListener('click', (e) => { if (e.target === lb) close(); });
  document.addEventListener('keydown', (e) => {
    if (!lb.classList.contains('is-open')) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowRight') show(current + 1);
    else if (e.key === 'ArrowLeft') show(current - 1);
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
