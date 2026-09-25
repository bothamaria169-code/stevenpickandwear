<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$tab = in_array($_GET['tab'] ?? '', ['subscribers', 'templates', 'campaigns']) ? $_GET['tab'] : 'subscribers';
$errors = [];
$success = '';
$newsletterService = new NewsletterService();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_subscriber') {
        db()->prepare("DELETE FROM newsletter_subscribers WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Subscriber removed.'; $tab = 'subscribers';

    } elseif ($action === 'save_template') {
        $id = safe_int($_POST['id'] ?? 0);
        $name = sanitize(trim($_POST['name'] ?? ''));
        $desc = sanitize(trim($_POST['description'] ?? ''));
        $html = trim($_POST['body_html'] ?? '');
        if (!$name || !$html) {
            $errors[] = 'Template name and content are required.';
        } elseif ($id) {
            db_update('newsletter_templates', $id, ['name' => $name, 'description' => $desc, 'body_html' => $html]);
            $success = 'Template updated.';
        } else {
            db_insert('newsletter_templates', ['name' => $name, 'description' => $desc, 'body_html' => $html]);
            $success = 'Template created.';
        }
        $tab = 'templates';

    } elseif ($action === 'delete_template') {
        db()->prepare("DELETE FROM newsletter_templates WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Template deleted.'; $tab = 'templates';

    } elseif ($action === 'save_campaign') {
        $id = safe_int($_POST['id'] ?? 0);
        $subject = sanitize(trim($_POST['subject'] ?? ''));
        $html = trim($_POST['body_html'] ?? '');
        $templateId = safe_int($_POST['template_id'] ?? 0) ?: null;
        if (!$subject || !$html) {
            $errors[] = 'Subject and content are required.';
        } elseif ($id) {
            db_update('newsletter_campaigns', $id, ['subject' => $subject, 'body_html' => $html, 'template_id' => $templateId]);
            $success = 'Campaign saved.';
        } else {
            db_insert('newsletter_campaigns', ['subject' => $subject, 'body_html' => $html, 'template_id' => $templateId, 'created_by' => $current_user['id']]);
            $success = 'Campaign saved as draft.';
        }
        $tab = 'campaigns';

    } elseif ($action === 'send_campaign') {
        $id = safe_int($_POST['id'] ?? 0);
        $result = $newsletterService->sendCampaign($id);
        $success = "Campaign sent to {$result['sent']} subscriber(s).";
        $tab = 'campaigns';

    } elseif ($action === 'delete_campaign') {
        db()->prepare("DELETE FROM newsletter_campaigns WHERE id = :id")->execute([':id' => safe_int($_POST['id'] ?? 0)]);
        $success = 'Campaign deleted.'; $tab = 'campaigns';
    }
}

$subscribers = db()->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();
$templates = db()->query("SELECT * FROM newsletter_templates ORDER BY created_at DESC")->fetchAll();
$campaigns = db()->query("SELECT c.*, u.name AS author_name FROM newsletter_campaigns c LEFT JOIN users u ON u.id = c.created_by ORDER BY c.created_at DESC")->fetchAll();

$editTemplateId = safe_int($_GET['edit_template'] ?? 0);
$editTemplate = $editTemplateId ? array_values(array_filter($templates, fn($t) => (int) $t['id'] === $editTemplateId))[0] ?? null : null;
$editCampaignId = safe_int($_GET['edit_campaign'] ?? 0);
$editCampaign = $editCampaignId ? array_values(array_filter($campaigns, fn($c) => (int) $c['id'] === $editCampaignId))[0] ?? null : null;

$activeCount = count(array_filter($subscribers, fn($s) => $s['status'] === 'active'));

$page_title = 'Newsletter — Admin';
$body_class = 'admin-body';
$extra_css = ['https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css'];
$extra_js = ['https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js', SITE_URL . '/assets/js/wysiwyg.js'];
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">Content</span><h1 class="h3 mb-0">Newsletter</h1></div>
      <span class="small text-muted-ric"><?= $activeCount ?> active subscriber(s)</span>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="d-flex gap-2 mb-4">
      <a href="?tab=subscribers" class="chip <?= $tab === 'subscribers' ? 'is-active' : '' ?>">Subscribers</a>
      <a href="?tab=templates" class="chip <?= $tab === 'templates' ? 'is-active' : '' ?>">Templates</a>
      <a href="?tab=campaigns" class="chip <?= $tab === 'campaigns' ? 'is-active' : '' ?>">Campaigns</a>
    </div>

    <?php if ($tab === 'subscribers'): ?>
      <div class="admin-card">
        <div class="table-responsive">
          <table class="admin-table">
            <thead><tr><th>Email</th><th>Name</th><th>Source</th><th>Status</th><th>Joined</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($subscribers as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td class="small text-muted-ric"><?= htmlspecialchars($s['name'] ?? '—') ?></td>
                <td class="small text-muted-ric"><?= htmlspecialchars($s['source']) ?></td>
                <td><span class="status-pill <?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                <td class="small text-muted-ric"><?= date('d M Y', strtotime($s['subscribed_at'])) ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Remove this subscriber?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_subscriber"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn-icon-neu" style="width:30px;height:30px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$subscribers): ?><tr><td colspan="6" class="text-center text-muted-ric py-4">No subscribers yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'templates'): ?>
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="admin-card">
            <h3 class="h6 mb-3">Saved Templates</h3>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($templates as $t): ?>
                <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:<?= (int) $t['id'] === $editTemplateId ? 'var(--cream)' : 'transparent' ?>;">
                  <div>
                    <a href="?tab=templates&edit_template=<?= $t['id'] ?>" class="fw-600 small"><?= htmlspecialchars($t['name']) ?></a>
                    <div class="small text-muted-ric"><?= htmlspecialchars($t['description'] ?? '') ?></div>
                  </div>
                  <form method="POST" onsubmit="return confirm('Delete this template?');">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_template"><input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn-icon-neu" style="width:28px;height:28px;color:var(--danger);"><i class="fa-solid fa-trash-can" style="font-size:.65rem;"></i></button>
                  </form>
                </div>
              <?php endforeach; ?>
              <?php if (!$templates): ?><p class="small text-muted-ric mb-0">No templates yet — build your first one.</p><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="admin-card">
            <h3 class="h6 mb-3"><?= $editTemplate ? 'Edit Template' : 'New Template' ?></h3>
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_template">
              <input type="hidden" name="id" value="<?= $editTemplate['id'] ?? '' ?>">
              <div class="mb-3"><label class="form-label-ric">Name</label><input type="text" name="name" class="form-control-ric" value="<?= htmlspecialchars($editTemplate['name'] ?? '') ?>" required></div>
              <div class="mb-3"><label class="form-label-ric">Description</label><input type="text" name="description" class="form-control-ric" value="<?= htmlspecialchars($editTemplate['description'] ?? '') ?>"></div>
              <div class="mb-3">
                <label class="form-label-ric">Content</label>
                <div id="templateEditor" style="background:#fff;border-radius:var(--radius);min-height:220px;"></div>
                <textarea name="body_html" id="templateHidden" style="display:none;"><?= htmlspecialchars($editTemplate['body_html'] ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn-ric w-100">Save Template</button>
            </form>
          </div>
        </div>
      </div>

    <?php else: /* campaigns */ ?>
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="admin-card">
            <h3 class="h6 mb-3">Campaigns</h3>
            <div class="table-responsive">
              <table class="admin-table">
                <thead><tr><th>Subject</th><th>Status</th><th>Sent</th><th></th></tr></thead>
                <tbody>
                  <?php foreach ($campaigns as $c): ?>
                  <tr>
                    <td><a href="?tab=campaigns&edit_campaign=<?= $c['id'] ?>" class="fw-600 small"><?= htmlspecialchars($c['subject']) ?></a></td>
                    <td><span class="status-pill <?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td class="small text-muted-ric"><?= $c['sent_count'] ?></td>
                    <td class="text-nowrap">
                      <?php if ($c['status'] === 'draft'): ?>
                      <form method="POST" class="d-inline" onsubmit="return confirm('Send this campaign to all active subscribers now?');">
                        <?= csrf_field() ?><input type="hidden" name="action" value="send_campaign"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn-ric-sm btn-neu">Send</button>
                      </form>
                      <?php endif; ?>
                      <form method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete_campaign"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn-icon-neu" style="width:28px;height:28px;color:var(--danger);"><i class="fa-solid fa-trash-can" style="font-size:.65rem;"></i></button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$campaigns): ?><tr><td colspan="4" class="text-center text-muted-ric py-4">No campaigns yet.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="admin-card">
            <h3 class="h6 mb-3"><?= $editCampaign ? 'Edit Campaign' : 'New Campaign' ?></h3>
            <form method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_campaign">
              <input type="hidden" name="id" value="<?= $editCampaign['id'] ?? '' ?>">
              <div class="mb-3"><label class="form-label-ric">Subject</label><input type="text" name="subject" class="form-control-ric" value="<?= htmlspecialchars($editCampaign['subject'] ?? '') ?>" required></div>
              <div class="mb-3">
                <label class="form-label-ric">Start From Template</label>
                <select id="templatePicker" class="form-control-ric">
                  <option value="">— Blank —</option>
                  <?php foreach ($templates as $t): ?>
                    <option value="<?= $t['id'] ?>" data-html="<?= htmlspecialchars($t['body_html']) ?>" <?= (int) ($editCampaign['template_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="template_id" id="templateIdInput" value="<?= $editCampaign['template_id'] ?? '' ?>">
              </div>
              <div class="mb-3">
                <label class="form-label-ric">Content <span class="text-muted-ric">— use {{name}} to personalise</span></label>
                <div id="campaignEditor" style="background:#fff;border-radius:var(--radius);min-height:220px;"></div>
                <textarea name="body_html" id="campaignHidden" style="display:none;"><?= htmlspecialchars($editCampaign['body_html'] ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn-ric w-100">Save Draft</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('templateEditor')) {
    initRicEditor('templateEditor', 'templateHidden', document.getElementById('templateHidden').value);
  }
  const campaignQuill = document.getElementById('campaignEditor')
    ? initRicEditor('campaignEditor', 'campaignHidden', document.getElementById('campaignHidden').value)
    : null;

  document.getElementById('templatePicker')?.addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    document.getElementById('templateIdInput').value = this.value;
    if (this.value && campaignQuill && confirm('Load this template into the editor? This replaces current content.')) {
      campaignQuill.root.innerHTML = opt.dataset.html;
    }
  });
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
