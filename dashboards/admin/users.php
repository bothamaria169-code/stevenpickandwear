<?php
require_once __DIR__ . '/../../includes/functions.php';
$current_user = require_auth('admin');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = safe_int($_POST['id'] ?? 0);

    if ($id === (int) $current_user['id'] && in_array($action, ['toggle_active', 'delete', 'change_role'])) {
        $errors[] = 'You cannot modify your own account here.';
    } elseif ($action === 'change_role') {
        $role = in_array($_POST['role'] ?? '', USER_ROLES) ? $_POST['role'] : 'customer';
        db_update('users', $id, ['role' => $role]);
        $success = 'Role updated.';
    } elseif ($action === 'toggle_active') {
        $stmt = db()->prepare("SELECT is_active FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $active = $stmt->fetchColumn();
        db_update('users', $id, ['is_active' => $active ? 0 : 1]);
        $success = 'User updated.';
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $id]);
        $success = 'User deleted.';
    }
}

$search = sanitize($_GET['search'] ?? '');
$roleFilter = sanitize($_GET['role'] ?? '');
$where = ['1=1'];
$params = [];
if ($search) { $where[] = '(name LIKE :s OR email LIKE :s)'; $params[':s'] = "%$search%"; }
if ($roleFilter) { $where[] = 'role = :r'; $params[':r'] = $roleFilter; }
$stmt = db()->prepare("SELECT * FROM users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Users — Admin';
$body_class = 'admin-body';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-topbar">
      <div><span class="eyebrow">People</span><h1 class="h3 mb-0">Users</h1></div>
    </div>

    <?php if ($success): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(76,122,74,.1);color:var(--success);"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $err): ?><div class="small mb-3 p-2 rounded-3" style="background:rgba(176,67,46,.1);color:var(--danger);"><?= htmlspecialchars($err) ?></div><?php endforeach; ?>

    <div class="admin-card mb-3">
      <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="text" name="search" class="form-control-ric" style="max-width:240px;" placeholder="Search name or email…" value="<?= htmlspecialchars($search) ?>">
        <select name="role" class="form-control-ric" style="max-width:180px;">
          <option value="">All Roles</option>
          <?php foreach (USER_ROLES as $r): ?><option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn-neu">Filter</button>
      </form>
    </div>

    <div class="admin-card">
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): $isSelf = (int) $u['id'] === (int) $current_user['id']; ?>
            <tr>
              <td class="fw-600"><?= htmlspecialchars($u['name']) ?> <?= $isSelf ? '<span class="small text-muted-ric">(you)</span>' : '' ?></td>
              <td class="small text-muted-ric"><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <form method="POST" onsubmit="return !this.disabled;">
                  <?= csrf_field() ?><input type="hidden" name="action" value="change_role"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <select name="role" class="form-control-ric" style="padding:.4rem .6rem;" onchange="this.form.submit()" <?= $isSelf ? 'disabled' : '' ?>>
                    <?php foreach (USER_ROLES as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><span class="status-pill <?= $u['is_active'] ? 'active' : 'inactive' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
              <td class="small text-muted-ric"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td class="text-nowrap">
                <form method="POST" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-icon-neu" style="width:32px;height:32px;" <?= $isSelf ? 'disabled' : '' ?> title="Toggle active"><i class="fa-solid fa-power-off small"></i></button>
                </form>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-icon-neu" style="width:32px;height:32px;color:var(--danger);" <?= $isSelf ? 'disabled' : '' ?>><i class="fa-solid fa-trash-can small"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
