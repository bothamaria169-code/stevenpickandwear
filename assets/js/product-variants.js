// Steven Picknwear — product detail: size selection.
// Pieces are grouped by material and made to order in each size; selecting a
// size updates price/availability and shows the size's note.
document.addEventListener('DOMContentLoaded', () => {
  const variants = window.productVariants || [];
  const basePrice = window.productBasePrice || 0;
  if (!variants.length) return;

  const priceEl   = document.getElementById('productCurrentPrice');
  const imageEl   = document.getElementById('productMainImage');
  const noteEl    = document.getElementById('productPatternNote');
  const stockEl   = document.getElementById('productStockNote');
  const hiddenId  = document.getElementById('selectedVariantId');
  const addBtn    = document.getElementById('addToCartBtn');
  const chips     = document.querySelectorAll('.variant-size-chip');

  function formatPrice(v) {
    return window.CURRENCY_SYMBOL ? window.CURRENCY_SYMBOL + v.toFixed(2) : 'R' + v.toFixed(2);
  }

  function selectVariant(variantId) {
    const variant = variants.find(v => String(v.id) === String(variantId));
    if (!variant) return;

    hiddenId.value = variant.id;
    chips.forEach(c => c.classList.toggle('is-active', String(c.dataset.variantId) === String(variantId)));

    priceEl.textContent = formatPrice(basePrice + parseFloat(variant.additional_price || 0));

    if (variant.image && imageEl) {
      imageEl.src = variant.image;
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('is-active'));
    }

    if (noteEl) {
      noteEl.textContent = variant.pattern_note || '';
      noteEl.style.display = variant.pattern_note ? 'block' : 'none';
    }

    const stock = parseInt(variant.stock_qty, 10);
    if (stockEl) {
      stockEl.style.display = 'block';
      if (stock <= 0) {
        stockEl.textContent = 'Currently made to order in this size — allow a little extra time, or contact us.';
        stockEl.className = 'small text-saddle fw-600';
      } else {
        stockEl.textContent = 'Available in ' + (variant.size_sa || 'this size') + ' · handcrafted to order.';
        stockEl.className = 'small text-muted-ric';
      }
    }

    if (addBtn) {
      addBtn.disabled = false;
      addBtn.textContent = 'Add To Cart';
    }
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      if (chip.disabled) return;
      selectVariant(chip.dataset.variantId);
    });
  });

  // Gallery thumbnail swap (lifestyle shots, independent of variant selection)
  document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      imageEl.src = thumb.dataset.fullImage;
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('is-active'));
      thumb.classList.add('is-active');
    });
  });

  // Auto-select first in-stock variant on load
  const firstInStock = variants.find(v => parseInt(v.stock_qty, 10) > 0);
  if (firstInStock) selectVariant(firstInStock.id);
  else if (variants[0]) selectVariant(variants[0].id);
});
