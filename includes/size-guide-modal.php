<!-- ── Size Guide Modal (adult jacket / top measurements) ─────────────── -->
<div class="modal fade" id="sizeGuideModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content glass-modal">

      <div class="modal-header">
        <h5 class="modal-title font-serif"><i class="fa-solid fa-ruler text-saddle me-2"></i>Size Guide</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="ooak-note mb-4">
          <i class="fa-solid fa-circle-info"></i>
          <div>Fit can vary a little between styles and brands. Use this as a guide to choose your size. If you're between sizes, <a href="<?= SITE_URL ?>/contact.php" class="text-saddle fw-600">contact us</a> and we'll help.</div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div class="d-flex gap-2 flex-wrap" id="sgSizeSelector">
            <?php foreach (JACKET_SIZES as $sz): ?>
              <button type="button" class="size-chip sg-size-btn <?= $sz === 'M' ? 'is-active' : '' ?>" data-size="<?= $sz ?>"><?= $sz ?></button>
            <?php endforeach; ?>
          </div>
          <div class="d-flex" style="border-radius:var(--radius-pill);overflow:hidden;background:var(--cream);">
            <button class="sg-unit-btn btn-neu is-active" data-unit="cm" style="border-radius:var(--radius-pill) 0 0 var(--radius-pill);">CM</button>
            <button class="sg-unit-btn btn-neu" data-unit="in" style="border-radius:0 var(--radius-pill) var(--radius-pill) 0;">IN</button>
          </div>
        </div>

        <div class="table-responsive mb-4">
          <table class="admin-table" id="sgTable">
            <thead><tr><th>Size</th><th>Chest</th><th>Shoulder</th><th>Sleeve</th><th>Body Length</th></tr></thead>
            <tbody>
              <?php foreach (JACKET_SIZES as $sz): ?>
              <tr data-size="<?= $sz ?>" class="<?= $sz === 'M' ? 'table-active' : '' ?>">
                <td><strong><?= $sz ?></strong></td><td class="sg-chest"></td><td class="sg-shoulder"></td><td class="sg-sleeve"></td><td class="sg-length"></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <h6 class="eyebrow mb-3">How To Measure</h6>
        <div class="row g-2">
          <div class="col-12"><p class="small mb-1 text-muted-ric"><strong class="text-dark">Chest:</strong> Measure straight across under the arms, around the fullest part of the chest.</p></div>
          <div class="col-12"><p class="small mb-1 text-muted-ric"><strong class="text-dark">Shoulder:</strong> Seam to seam, straight across the back.</p></div>
          <div class="col-12"><p class="small mb-1 text-muted-ric"><strong class="text-dark">Sleeve:</strong> Shoulder seam to cuff.</p></div>
          <div class="col-12"><p class="small mb-1 text-muted-ric"><strong class="text-dark">Body Length:</strong> Base of collar straight down to the hem.</p></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const DATA = {
    XS:  { chest:[96,100],  shoulder:[42,44], sleeve:[58,60], length:[60,62] },
    S:   { chest:[101,105], shoulder:[44,46], sleeve:[60,62], length:[62,64] },
    M:   { chest:[106,110], shoulder:[46,48], sleeve:[62,64], length:[64,66] },
    L:   { chest:[111,116], shoulder:[48,50], sleeve:[64,66], length:[66,68] },
    XL:  { chest:[117,122], shoulder:[50,52], sleeve:[66,68], length:[68,70] },
    XXL: { chest:[123,128], shoulder:[52,54], sleeve:[68,70], length:[70,72] },
  };
  let unit = 'cm';

  function fmt(range) {
    if (unit === 'cm') return range[0] + '–' + range[1] + ' cm';
    return (range[0]/2.54).toFixed(1) + '–' + (range[1]/2.54).toFixed(1) + '"';
  }

  function render() {
    document.querySelectorAll('#sgTable tbody tr').forEach(tr => {
      const d = DATA[tr.dataset.size];
      if (!d) return;
      tr.querySelector('.sg-chest').textContent = fmt(d.chest);
      tr.querySelector('.sg-shoulder').textContent = fmt(d.shoulder);
      tr.querySelector('.sg-sleeve').textContent = fmt(d.sleeve);
      tr.querySelector('.sg-length').textContent = fmt(d.length);
    });
  }

  document.querySelectorAll('.sg-size-btn').forEach(b => b.addEventListener('click', () => {
    document.querySelectorAll('.sg-size-btn').forEach(x => x.classList.remove('is-active'));
    b.classList.add('is-active');
    document.querySelectorAll('#sgTable tbody tr').forEach(tr => tr.classList.toggle('table-active', tr.dataset.size === b.dataset.size));
  }));

  document.querySelectorAll('.sg-unit-btn').forEach(b => b.addEventListener('click', () => {
    unit = b.dataset.unit;
    document.querySelectorAll('.sg-unit-btn').forEach(x => x.classList.remove('is-active'));
    b.classList.add('is-active');
    render();
  }));

  document.addEventListener('DOMContentLoaded', render);
  document.getElementById('sizeGuideModal')?.addEventListener('shown.bs.modal', render);
})();
</script>
