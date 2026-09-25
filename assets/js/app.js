// Steven Picknwear — global site behavior (mobile nav, search overlay,
// scroll reveal, toasts, footer newsletter signup). Vanilla JS, no jQuery.
document.addEventListener('DOMContentLoaded', () => {

  // ── Mobile menu ──────────────────────────────────────────────────────
  const menuToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', () => {
      mobileMenu.style.display = mobileMenu.style.display === 'none' ? 'block' : 'none';
    });
  }

  // ── Search overlay ───────────────────────────────────────────────────
  const searchToggle = document.getElementById('searchToggle');
  const searchOverlay = document.getElementById('searchOverlay');
  const searchClose = document.getElementById('searchClose');
  if (searchToggle && searchOverlay) {
    searchToggle.addEventListener('click', () => {
      searchOverlay.style.display = 'block';
      searchOverlay.querySelector('input')?.focus();
    });
  }
  searchClose?.addEventListener('click', () => searchOverlay.style.display = 'none');

  // ── Scroll reveal (IntersectionObserver, respects prefers-reduced-motion) ─
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const revealEls = document.querySelectorAll('.reveal');
  if (revealEls.length) {
    if (prefersReduced || !('IntersectionObserver' in window)) {
      revealEls.forEach(el => el.classList.add('is-visible'));
    } else {
      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
      revealEls.forEach(el => io.observe(el));
    }
  }

  // ── Footer newsletter signup ─────────────────────────────────────────
  const footerForm = document.getElementById('footerNewsletterForm');
  footerForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = footerForm.email.value.trim();
    const msg = document.getElementById('footerNewsletterMsg');
    try {
      const res = await fetch(SITE_URL + '/modules/api/newsletter.php?action=subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams({ email }),
      });
      const data = await res.json();
      msg.style.color = data.success ? 'var(--saddle-light)' : '#e0847a';
      msg.textContent = data.message;
      if (data.success) footerForm.reset();
    } catch (err) {
      msg.style.color = '#e0847a';
      msg.textContent = 'Something went wrong. Please try again.';
    }
  });
});

// ── Toast utility (used by cart.js / wishlist.js / anywhere) ────────────
window.showToast = function (message, type = 'success') {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;
  const toast = document.createElement('div');
  toast.className = 'ric-toast ' + (type === 'error' ? 'error' : 'success');
  toast.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i><span>${message}</span>`;
  wrap.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'opacity .3s ease';
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
  }, 3200);
};
