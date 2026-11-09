<?php
$page_title = 'Admin Panel';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/messages.php';
require_login('/likhub/login.php');

$user = current_user();

if ($user['role'] !== 'admin') {
    header('Location: /likhub/dashboard.php'); exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['impersonate_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $target_id = intval($_POST['impersonate_id']);
        $stmt = db()->prepare('SELECT id, name, role FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$target_id]);
        $target = $stmt->fetch();
        if ($target) {
            start_session();
            $_SESSION['admin_origin_id'] = $user['id'];
            $_SESSION['user_id'] = $target['id'];
            session_regenerate_id(true);
            header('Location: /likhub/dashboard.php'); exit;
        } else {
            $errors[] = 'User not found or inactive.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_artist_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $aid = intval($_POST['approve_artist_id']);
        db()->prepare("UPDATE users SET is_active = 1 WHERE id = ? AND role = 'artist'")->execute([$aid]);
        try {
            db()->prepare('INSERT IGNORE INTO artist_profiles (user_id) VALUES (?)')->execute([$aid]);
        } catch (PDOException $e) {}
        $success = 'Artist account approved.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deactivate_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $did = intval($_POST['deactivate_id']);
        if ($did !== $user['id']) {
            db()->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$did]);
            $success = 'User deactivated.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivate_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $rid = intval($_POST['reactivate_id']);
        db()->prepare('UPDATE users SET is_active = 1 WHERE id = ?')->execute([$rid]);
        $success = 'User reactivated.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $crid    = intval($_POST['change_role_id']);
        $newrole = in_array($_POST['new_role'] ?? '', ['client','artist','learner','admin']) ? $_POST['new_role'] : null;
        if ($newrole && $crid !== $user['id']) {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newrole, $crid]);
            if ($newrole === 'artist') {
                try { db()->prepare('INSERT IGNORE INTO artist_profiles (user_id) VALUES (?)')->execute([$crid]); } catch (PDOException $e) {}
            }
            $success = 'Role updated.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rating_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $dr_id = intval($_POST['delete_rating_id']);
        $stmt  = db()->prepare('SELECT artist_name FROM commission_requests WHERE id = ? LIMIT 1');
        $stmt->execute([$dr_id]);
        $cr = $stmt->fetch();
        db()->prepare('UPDATE commission_requests SET rating = NULL, review_text = NULL WHERE id = ?')->execute([$dr_id]);
        if ($cr) {
            $artistRow = db()->prepare('SELECT id FROM users WHERE name = ? LIMIT 1');
            $artistRow->execute([$cr['artist_name']]);
            $ar = $artistRow->fetch();
            if ($ar) update_artist_stats((int)$ar['id']);
        }
        $success = 'Rating removed.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_test_data'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid token.';
    } else {
        $target = $_POST['clear_test_data'];
        if ($target === 'commissions') {
            init_commission_requests_table();
            // Snapshot every affected artist before wiping rows
            $artists = db()->query('SELECT DISTINCT artist_name FROM commission_requests')->fetchAll();
            foreach ($artists as $a) {
                snapshot_artist_stats($a['artist_name']);
            }
            db()->exec('DELETE FROM commission_requests');
            try { db()->exec('DELETE FROM commission_messages'); } catch (PDOException $e) {}
            $success = 'All commission requests and messages have been cleared.';
        } elseif ($target === 'messages') {
            try { db()->exec('DELETE FROM commission_messages'); } catch (PDOException $e) {}
            $success = 'All commission messages have been cleared.';
        }
    }
}

$search_q    = trim($_GET['q'] ?? '');
$filter_role = $_GET['role'] ?? '';

$where  = '1=1';
$params = [];
if ($search_q) {
    $where   .= ' AND (name LIKE ? OR email LIKE ?)';
    $params[] = "%$search_q%";
    $params[] = "%$search_q%";
}
if ($filter_role && in_array($filter_role, ['client','artist','learner','admin'])) {
    $where   .= ' AND role = ?';
    $params[] = $filter_role;
}
$stmt = db()->prepare("SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pending = db()->query("SELECT * FROM users WHERE role='artist' AND is_active=0 ORDER BY created_at DESC")->fetchAll();

$total_users       = db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_artists     = db()->query("SELECT COUNT(*) FROM users WHERE role='artist'")->fetchColumn();
$total_commissions = db()->query('SELECT COUNT(*) FROM commission_requests')->fetchColumn();
$pending_count     = count($pending);

$rated_commissions = db()->query(
    "SELECT cr.id, cr.ref, cr.artist_name, cr.rating, cr.review_text, cr.status,
            u.name AS client_name, u.email AS client_email, cr.created_at
     FROM commission_requests cr
     JOIN users u ON u.id = cr.user_id
     WHERE cr.rating IS NOT NULL
     ORDER BY cr.created_at DESC
     LIMIT 100"
)->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<style>
.admin-actions-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
}

@media (max-width: 768px) {

  .admin-users-table th:nth-child(3),
  .admin-users-table td:nth-child(3),
  .admin-users-table th:nth-child(5),
  .admin-users-table td:nth-child(5),
  .admin-users-table th:nth-child(6),
  .admin-users-table td:nth-child(6) { display: none; }

  .admin-ratings-table th:nth-child(5),
  .admin-ratings-table td:nth-child(5),
  .admin-ratings-table th:nth-child(6),
  .admin-ratings-table td:nth-child(6) { display: none; }

  .admin-role-cell select { width: 80px; font-size: 11px; padding: 3px 4px; }
  .admin-role-cell .btn-action { font-size: 11px; padding: 4px 8px; }

  .btn-action { font-size: 11px; padding: 5px 9px; }

  .table-wrap table.admin-users-table,
  .table-wrap table.admin-ratings-table { min-width: unset; width: 100%; }
}

@media (max-width: 480px) {
  .admin-users-table th:nth-child(1),
  .admin-users-table td:nth-child(1) { display: none; }

  .btn-action { font-size: 10px; padding: 4px 7px; }
  .admin-role-cell select { width: 70px; }
}
</style>

<div class="dashboard-layout">

  <aside class="sidebar" aria-label="Admin navigation">
    <div class="sidebar-profile">
      <div class="sidebar-profile__row">
        <div class="sidebar-avatar">⚙</div>
        <div>
          <div class="sidebar-name"><?= e($user['name']) ?></div>
          <div class="sidebar-role">Admin</div>
        </div>
      </div>
    </div>
    <div class="sidebar__section-label">Admin</div>
    <a href="/likhub/admin.php" class="sidebar__link active"><span><i class="fa-solid fa-users-cog"></i></span> User Management</a>
    <a href="/likhub/dashboard.php" class="sidebar__link"><span><i class="fa-solid fa-gauge"></i></span> Dashboard</a>
    <div class="sidebar__section-label">Explore</div>
    <a href="/likhub/commission.php" class="sidebar__link"><span><i class="fa-solid fa-star"></i></span> Find Artists</a>
    <a href="/likhub/logout.php" class="sidebar__link sidebar__link--logout"><span><i class="fa-solid fa-right-from-bracket"></i></span> Log Out</a>
  </aside>

  <main class="dashboard-main">
    <h2>Admin Panel ⚙️</h2>

    <?php if ($errors): ?>
      <div class="u-mb-16 alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="u-mb-16 alert alert--success"><i class="fa-solid fa-circle-check"></i> <?= e($success) ?></div>
    <?php endif; ?>

    <div class="u-mb-32 stat-grid">
      <div class="stat-card"><div class="stat-card__label">Total Users</div><div class="stat-card__value"><?= $total_users ?></div></div>
      <div class="stat-card"><div class="stat-card__label">Artists</div><div class="stat-card__value"><?= $total_artists ?></div></div>
      <div class="stat-card"><div class="stat-card__label">Commissions</div><div class="stat-card__value"><?= $total_commissions ?></div></div>
      <div class="stat-card"><div class="stat-card__label">Pending Approvals</div><div class="stat-card__value" style="color:<?= $pending_count > 0 ? '#ef4444' : 'var(--navy-dark)' ?>"><?= $pending_count ?></div></div>
    </div>

    <?php if ($pending): ?>
    <h3 class="u-mb-12 section-heading">⏳ Pending Artist Approvals</h3>
    <div class="u-mb-32 table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($pending as $p): ?>
          <tr>
            <td><strong><?= e($p['name']) ?></strong></td>
            <td><?= e($p['email']) ?></td>
            <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
            <td>
              <form class="u-d-inline" method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="approve_artist_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn-action btn-action--approve">
                  <i class="fa-solid fa-check"></i> Approve
                </button>
              </form>
              <form method="POST" class="u-d-inline u-ml-6">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="deactivate_id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn-action btn-action--reject"
                  onclick="return confirm('Reject and deactivate this account?')">
                  <i class="fa-solid fa-xmark"></i> Reject
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="u-d-flex u-align-center u-flex-wrap u-gap-12 u-mb-12">
      <h3 class="u-mb-0 section-heading">All Users</h3>
      <form method="GET" action="/likhub/admin.php" class="u-d-flex u-gap-8 u-flex-wrap">
        <input type="text" name="q" value="<?= e($search_q) ?>" placeholder="Search name or email…" class="form-control" style="width:220px;padding:7px 12px;font-size:13px;">
        <select name="role" class="form-control u-select-sm">
          <option value="">All roles</option>
          <?php foreach (['client','artist','learner','admin'] as $r): ?>
          <option value="<?= $r ?>" <?= $filter_role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        <?php if ($search_q || $filter_role): ?>
          <a href="/likhub/admin.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="table-wrap">
      <table class="admin-users-table">
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" class="u-text-center-muted">No users found.</td></tr>
          <?php endif; ?>
          <?php foreach ($users as $u):
            $uname_js = e(addslashes($u['name']));
          ?>
          <tr>
            <td style="color:var(--text-muted);font-size:12px;"><?= $u['id'] ?></td>
            <td>
              <strong><?= e($u['name']) ?></strong>
              <?php if ($u['id'] === $user['id']): ?>
                <span style="font-size:10px;background:#e0f2fe;color:#0369a1;padding:2px 6px;margin-left:4px;">You</span>
              <?php endif; ?>
            </td>
            <td class="u-td-email"><?= e($u['email']) ?></td>
            <td class="admin-role-cell">
              <form method="POST" class="u-d-flex u-gap-6 u-align-center">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="change_role_id" value="<?= $u['id'] ?>">
                <select name="new_role" class="form-control u-select-xs" <?= $u['id'] === $user['id'] ? 'disabled' : '' ?>>
                  <?php foreach (['client','artist','learner','admin'] as $r): ?>
                  <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($u['id'] !== $user['id']): ?>
                <button type="submit" class="btn-action" style="background:#e0f2fe;color:#0369a1;border-color:#bae6fd;">Set</button>
                <?php endif; ?>
              </form>
            </td>
            <td>
              <span class="status-badge <?= $u['is_active'] ? 'status-badge--inprogress' : 'status-badge--rejected' ?>">
                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td class="u-td-date"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td class="admin-actions-cell">
              <?php if ($u['id'] !== $user['id']): ?>
              <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="impersonate_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-action" style="background:#f3e8ff;color:#7c3aed;border-color:#e9d5ff;white-space:nowrap;"
                  title="Log in as this user"
                  onclick="return confirm('Log in as <?= $uname_js ?>?')">
                  <i class="fa-solid fa-right-to-bracket"></i> Log in as
                </button>
              </form>
              <?php if ($u['is_active']): ?>
              <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="deactivate_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-action btn-action--reject"
                  onclick="return confirm('Deactivate <?= $uname_js ?>?')">
                  <i class="fa-solid fa-ban"></i> Deactivate
                </button>
              </form>
              <?php else: ?>
              <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="reactivate_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-action btn-action--approve"
                  onclick="return confirm('Reactivate <?= $uname_js ?>?')">
                  <i class="fa-solid fa-rotate-left"></i> Reactivate
                </button>
              </form>
              <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p style="color:var(--text-muted);font-size:12px;margin-top:12px;">Showing <?= count($users) ?> user(s). Max 200 per page.</p>

    <div class="u-mt-40">
      <div class="section-heading-row u-mb-16">
        <h3 class="u-mb-0 section-heading">⭐ Commission Ratings</h3>
        <span style="font-size:12px;color:var(--text-muted);"><?= count($rated_commissions) ?> rated commission(s)</span>
      </div>
      <?php if ($rated_commissions): ?>
      <div class="table-wrap">
        <table class="admin-ratings-table">
          <thead>
            <tr><th>Ref</th><th>Client</th><th>Artist</th><th>Rating</th><th>Review</th><th>Date</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($rated_commissions as $rc): ?>
            <tr>
              <td><span style="font-size:12px;font-weight:700;color:var(--navy-mid);"><?= e($rc['ref']) ?></span></td>
              <td>
                <div style="font-weight:600;font-size:13px;"><?= e($rc['client_name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?= e($rc['client_email']) ?></div>
              </td>
              <td style="font-size:13px;"><?= e($rc['artist_name']) ?></td>
              <td>
                <span style="color:#f59e0b;letter-spacing:1px;font-size:15px;"><?= str_repeat('★', (int)$rc['rating']) ?><?= str_repeat('☆', 5 - (int)$rc['rating']) ?></span>
                <span style="font-size:11px;color:var(--text-muted);margin-left:4px;"><?= (int)$rc['rating'] ?>/5</span>
              </td>
              <td style="max-width:220px;font-size:12px;color:#444;">
                <?= $rc['review_text'] ? e(mb_strimwidth($rc['review_text'], 0, 100, '…')) : '<span style="color:var(--text-muted);font-style:italic;">No text</span>' ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted);"><?= date('M j, Y', strtotime($rc['created_at'])) ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('Remove this rating? This cannot be undone.')">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="delete_rating_id" value="<?= (int)$rc['id'] ?>">
                  <button type="submit" class="btn-action btn-action--reject" style="white-space:nowrap;">
                    <i class="fa-solid fa-trash"></i> Remove
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="lesson-placeholder">
        <i class="fa-solid fa-star" style="color:var(--grey-pale);font-size:32px;margin-bottom:10px;display:block;"></i>
        <p class="lesson-placeholder__title">No ratings yet.</p>
        <p>Ratings will appear here once clients review completed commissions.</p>
      </div>
      <?php endif; ?>
    </div>

    <div class="u-mt-40">
      <div class="section-heading-row u-mb-16">
        <h3 class="u-mb-0 section-heading">🧹 Clear Data</h3>
      </div>
      <div style="background:var(--white,#fff);border:1px solid var(--border-color,#e5e7eb);border-radius:10px;padding:24px;display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;">
        <div style="flex:1;min-width:240px;">
          <div style="font-weight:700;font-size:14px;margin-bottom:4px;">Commission Requests &amp; Messages</div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Deletes all rows in <code>commission_requests</code> and <code>commission_messages</code>. Use this to wipe test data.</div>
          <form method="POST" onsubmit="return confirm('This will permanently delete ALL commission requests and messages. Are you sure?')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="clear_test_data" value="commissions">
            <button type="submit" class="btn-action btn-action--reject" style="padding:8px 18px;font-size:13px;">
              <i class="fa-solid fa-trash"></i> Clear All Commissions &amp; Messages
            </button>
          </form>
        </div>
        <div style="flex:1;min-width:240px;">
          <div style="font-weight:700;font-size:14px;margin-bottom:4px;">Messages Only</div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Deletes only the chat messages inside commissions, keeping the commission records intact.</div>
          <form method="POST" onsubmit="return confirm('Delete all commission messages? Commission records will be kept.')">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="clear_test_data" value="messages">
            <button type="submit" class="btn-action btn-action--reject" style="padding:8px 18px;font-size:13px;">
              <i class="fa-solid fa-comments-slash"></i> Clear Messages Only
            </button>
          </form>
        </div>
      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>