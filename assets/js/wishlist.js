// Steven Picknwear — wishlist toggle (product cards + product detail page).
document.addEventListener('DOMContentLoaded', () => {
  document.body.addEventListener('click', async (e) => {
    const btn = e.target.closest('.wishlist-btn');
    if (!btn) return;
    e.preventDefault();

    const productId = btn.dataset.productId;
    try {
      const res = await fetch(SITE_URL + '/modules/api/wishlist.php', {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ product_id: productId }),
      });
      const data = await res.json();
      if (res.status === 401) {
        window.location.href = SITE_URL + '/modules/auth/login.php';
        return;
      }
      if (!data.success) { showToast(data.message, 'error'); return; }

      btn.classList.toggle('active', data.added);
      const icon = btn.querySelector('i');
      if (icon) icon.className = data.added ? 'fas fa-heart' : 'far fa-heart';
      icon?.classList.add('icon-bounce');
      showToast(data.message, 'success');
    } catch (err) {
      showToast('Something went wrong.', 'error');
    }
  });
});
