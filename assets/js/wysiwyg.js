// Steven Picknwear — shared Quill WYSIWYG initializer, used by the
// blog editor and the newsletter template/campaign editor. Kept as one
// small helper so every rich-text field in the admin behaves the same way.
function initRicEditor(containerId, hiddenInputId, initialHtml) {
  const container = document.getElementById(containerId);
  const hidden = document.getElementById(hiddenInputId);
  if (!container || typeof Quill === 'undefined') return null;

  const quill = new Quill('#' + containerId, {
    theme: 'snow',
    placeholder: 'Start writing…',
    modules: {
      toolbar: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ color: [] }, { background: [] }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ align: [] }],
        ['blockquote', 'link', 'image'],
        ['clean'],
      ],
    },
  });

  if (initialHtml) {
    quill.root.innerHTML = initialHtml;
  }

  const form = hidden?.closest('form');
  form?.addEventListener('submit', () => {
    hidden.value = quill.root.innerHTML;
  });

  return quill;
}
