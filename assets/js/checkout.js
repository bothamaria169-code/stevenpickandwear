// Steven Picknwear — checkout page niceties: file picker feedback and a
// submit-once guard so a slow POP upload can't be double-submitted.
document.addEventListener('DOMContentLoaded', () => {
  const fileInput = document.querySelector('input[name="pop_file"]');
  fileInput?.addEventListener('change', function () {
    const label = this.nextElementSibling;
    if (this.files[0] && label && label.classList.contains('small')) {
      label.textContent = 'Selected: ' + this.files[0].name;
    }
  });

  const form = fileInput?.closest('form');
  form?.addEventListener('submit', function () {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-ric me-2"></span>Placing Order…';
    }
  });
});
