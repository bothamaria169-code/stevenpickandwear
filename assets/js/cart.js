// Steven Picknwear — cart AJAX (add/update/remove/coupon).
// Mutations on the cart page itself reload (simplest reliable way to keep
// per-line totals + shipping/coupon math in sync); adding from a product
// page just toasts + bumps the badge so browsing isn't interrupted.
document.addEventListener('DOMContentLoaded', () => {

  function updateCartBadge(count) {
    let badge = document.getElementById('cartBadge');
    const cartIcon = document.querySelector('a[href*="cart.php"].header-icon-btn');
    if (count > 0) {
      if (!badge && cartIcon) {
        badge = document.createElement('span');
        badge.id = 'cartBadge';
        badge.className = 'header-badge';
        cartIcon.appendChild(badge);
      }
      if (badge) { badge.textContent = count; badge.classList.add('icon-bounce'); setTimeout(() => badge.classList.remove('icon-bounce'), 500); }
    } else if (badge) {
      badge.remove();
    }
  }

  document.getElementById('addToCartForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('addToCartBtn');
    const original = btn.textContent;
    btn.disabled = true; btn.textContent = 'Adding…';
    try {
      const res = await fetch(SITE_URL + '/modules/api/cart.php?action=add', {
        method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(this),
      });
      const data = await res.json();
      showToast(data.message, data.success ? 'success' : 'error');
      if (data.success) updateCartBadge(data.count);
    } catch (err) {
      showToast('Could not add to bag. Please try again.', 'error');
    } finally {
      btn.disabled = false; btn.textContent = original;
    }
  });

  // ── Cart page: qty steppers / remove / coupon ──────────────────────
  document.querySelectorAll('.cart-qty-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const line = btn.closest('.cart-line');
      const input = line.querySelector('.cart-qty-input');
      const qty = Math.max(1, Math.min(10, parseInt(input.value, 10) + parseInt(btn.dataset.step, 10)));
      await fetch(SITE_URL + '/modules/api/cart.php?action=update', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ key: line.dataset.key, qty }),
      });
      window.location.reload();
    });
  });

  document.querySelectorAll('.cart-remove-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const line = btn.closest('.cart-line');
      await fetch(SITE_URL + '/modules/api/cart.php?action=remove', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ key: line.dataset.key }),
      });
      window.location.reload();
    });
  });

  document.getElementById('couponForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const res = await fetch(SITE_URL + '/modules/api/cart.php?action=coupon', {
      method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(this),
    });
    const data = await res.json();
    const msg = document.getElementById('couponMsg');
    if (data.success) {
      window.location.reload();
    } else {
      msg.style.color = '#B0432E';
      msg.textContent = data.message;
    }
  });
});
