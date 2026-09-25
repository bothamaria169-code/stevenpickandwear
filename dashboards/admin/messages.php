<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = safe_int($_POST['id'] ?? 0);
    if ($action === 'mark_read') {
        db_update('contact_messages', $id, ['is_read' => 1]);
        $success = 'Marked as read.';
    } elseif ($action === 'mark_unread') {
        db_update('contact_messages', $id, ['is_read' => 0]);
        $success = 'Marked as unread.';
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM contact_messages WHERE id = :id")->execute([':id' => $id]);
        $success = 'Message deleted.';
    }
}

$filter = $_GET['filter'] ?? 'unread';
$where = $filter === 'unread' ? 'is_read = 0' : ($filter === 'read' ? 'is_read = 1' : '1=1');
$messages = db()->query("SELECT * FROM contact_messages WHERE $where ORDER BY created_at DESC")->fetchAll();

$page_title = 'Messages — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">People</span><h1 class="h3 mb-0">Contact Messages</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="d-flex gap-2 mb-4">
      <a href="?filter=unread" class="chip <?= $filter === 'unread' ? 'is-active' : '' ?>">Unread</a>
      <a href="?filter=read" class="chip <?= $filter === 'read' ? 'is-active' : '' ?>">Read</a>
      <a href="?filter=all" class="chip <?= $filter === 'all' ? 'is-active' : '' ?>">All</a>
    </div>

    <div class="d-flex flex-column gap-3">
      <?php foreach ($messages as $m): ?>
      <div class="admin-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div style="min-width:0;">
            <div class="fw-700" style="font-family:var(--font-serif);">
              <?= htmlspecialchars($m['name']) ?>
              <?php if (!$m['is_read']): ?><span class="badge-material ms-2" style="font-size:.6rem;">New</span><?php endif; ?>
            </div>
            <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="small text-saddle"><?= htmlspecialchars($m['email']) ?></a>
            <p class="mb-1 mt-2" style="white-space:pre-wrap;"><?= htmlspecialchars($m['message']) ?></p>
            <div class="small text-muted-ric"><?= date('d M Y, H:i', strtotime($m['created_at'])) ?></div>
          </div>
          <div class="d-flex gap-2">
            <a href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=<?= rawurlencode('Re: your enquiry — ' . SITE_NAME) ?>" class="btn-ric-sm btn-neu"><i class="fa-solid fa-reply me-1"></i>Reply</a>
            <?php if (!$m['is_read']): ?>
              <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button type="submit" class="btn-ric-sm btn-neu">Mark read</button></form>
            <?php else: ?>
              <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="mark_unread"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button type="submit" class="btn-ric-sm btn-neu">Unread</button></form>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Delete this message?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button type="submit" class="btn-icon-neu" style="width:34px;height:34px;color:var(--danger);"><i class="fa-solid fa-trash-can small"></i></button></form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$messages): ?><div class="admin-card text-center py-4 text-muted-ric">No messages here.</div><?php endif; ?>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
