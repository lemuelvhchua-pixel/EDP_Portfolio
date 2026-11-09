<?php
$page_title = 'Dashboard';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/messages.php';
require_login('/likhub/login.php');

$user = current_user();
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'leave_review' && !empty($_POST['_ajax'])) {
    header('Content-Type: application/json');
    if (!verify_csrf($_POST['csrf'] ?? '')) { echo json_encode(['error' => 'Invalid token']); exit; }
    if (!$user) { echo json_encode(['error' => 'login_required']); exit; }
    $ref    = trim($_POST['ref'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $review = substr(trim($_POST['review'] ?? ''), 0, 1000);
    if ($rating < 1 || $rating > 5) { echo json_encode(['error' => 'Invalid rating']); exit; }
    init_commission_requests_table();
    $stmt = db()->prepare('SELECT id, artist_name, status, rating FROM commission_requests WHERE ref = ? AND user_id = ?');
    $stmt->execute([$ref, $user['id']]);
    $row = $stmt->fetch();
    if (!$row)                           { echo json_encode(['error' => 'Commission not found']); exit; }
    if ($row['status'] !== 'Completed')  { echo json_encode(['error' => 'Commission not completed yet']); exit; }
    if ($row['rating'])                  { echo json_encode(['error' => 'Already reviewed']); exit; }
    db()->prepare('UPDATE commission_requests SET rating = ?, review_text = ? WHERE ref = ? AND user_id = ?')
        ->execute([$rating, $review, $ref, $user['id']]);
    $ar = db()->prepare('SELECT id FROM users WHERE name = ? LIMIT 1');
    $ar->execute([$row['artist_name']]);
    $arRow = $ar->fetch();
    if ($arRow) update_artist_stats((int)$arRow['id']);
    echo json_encode(['ok' => true]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_message') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
    } else {
        $msg_ref  = trim($_POST['msg_ref'] ?? '');
        $msg_body = trim($_POST['msg_body'] ?? '');
        $file_path = null;

        // Handle optional file attachment in messages
        if (!empty($_FILES['msg_file']) && $_FILES['msg_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['msg_file'];
            $allowed_mime = ['image/jpeg','image/png','image/webp','image/gif','application/pdf'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($mime, $allowed_mime) && $file['size'] <= 10 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/images/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fname = 'msg_' . preg_replace('/[^a-z0-9]/', '', strtolower($msg_ref)) . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $uploadDir . $fname);
                $file_path = '/likhub/images/uploads/' . $fname;
            }
        }

        if ($msg_ref && ($msg_body || $file_path)) {
            post_message($msg_ref, $user['id'], $role, $msg_body ?: '📎 File attached', 'text', $file_path);
        }
        header('Location: /likhub/dashboard.php?tab=commissions&thread=' . urlencode($msg_ref)); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_delivery' && $role === 'artist') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
        header('Location: /likhub/dashboard.php'); exit;
    }
    $msg_ref  = trim($_POST['msg_ref'] ?? '');
    $art_type = trim($_POST['art_type'] ?? 'Digital');
    $note     = substr(trim($_POST['delivery_note'] ?? ''), 0, 500);
    $file     = $_FILES['delivery_file'] ?? null;

    if ($msg_ref && $file && $file['error'] === UPLOAD_ERR_OK) {
        $allowed_mime = ['image/jpeg','image/png','image/webp','image/gif','application/pdf',
                         'application/zip','application/x-zip-compressed'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed_mime) && $file['size'] <= 30 * 1024 * 1024) {
            $filename  = 'delivery_' . preg_replace('/[^a-z0-9]/', '', strtolower($msg_ref)) . '_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/images/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $filePath = '/likhub/images/uploads/' . $filename;

            $isDigital = (stripos($art_type, 'digital') !== false);
            $msgType   = $isDigital ? 'delivery_digital' : 'delivery_physical';
            $body      = $note ?: ($isDigital ? 'Here is your finished digital artwork!' : 'Here is a photo of your finished piece!');

            post_message($msg_ref, $user['id'], $role, $body, $msgType, $filePath);

            if ($isDigital) {
                init_commission_requests_table();
                db()->prepare('UPDATE commission_requests SET download_path = ? WHERE ref = ?')
                    ->execute([$filePath, $msg_ref]);
            }

            set_flash('success', 'Finished output sent to client!');
        } else {
            set_flash('error', 'Invalid file. Allowed: JPG, PNG, WEBP, GIF, PDF, ZIP (max 30 MB).');
        }
    } else {
        set_flash('error', 'Please attach a file.');
    }
    header('Location: /likhub/dashboard.php?tab=commissions&thread=' . urlencode($msg_ref)); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel' && $role === 'client') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
        header('Location: /likhub/dashboard.php'); exit;
    }
    $ref = trim($_POST['ref'] ?? '');
    if ($ref) {
        $stmt = db()->prepare('SELECT status FROM commission_requests WHERE ref = ? AND user_id = ?');
        $stmt->execute([$ref, $user['id']]);
        $row = $stmt->fetch();
        if ($row && in_array($row['status'], ['Submitted', 'In Progress'])) {
            db()->prepare("UPDATE commission_requests SET status = 'Cancelled' WHERE ref = ? AND user_id = ?")
                ->execute([$ref, $user['id']]);
            set_flash('success', 'Commission ' . $ref . ' has been cancelled.');
        } else {
            set_flash('error', 'This commission cannot be cancelled.');
        }
    }
    header('Location: /likhub/dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_commission' && in_array($role, ['client','artist'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
        header('Location: /likhub/dashboard.php'); exit;
    }
    $ref = trim($_POST['ref'] ?? '');
    if ($ref) {
        init_commission_requests_table();
        $stmt = db()->prepare('SELECT status FROM commission_requests WHERE ref = ? AND user_id = ?');
        $stmt->execute([$ref, $user['id']]);
        $row = $stmt->fetch();
        $hideable = ['Cancelled', 'Declined', 'Rejected', 'Completed'];
        if ($row && in_array($row['status'], $hideable)) {
            snapshot_artist_stats_for_ref($ref);
            db()->prepare('UPDATE commission_requests SET hidden_by_client = 1 WHERE ref = ? AND user_id = ?')
                ->execute([$ref, $user['id']]);
            set_flash('success', 'Order ' . $ref . ' removed from your dashboard.');
        } else {
            set_flash('error', 'This order cannot be removed.');
        }
    }
    header('Location: /likhub/dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'artist_archive' && $role === 'artist') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
        header('Location: /likhub/dashboard.php'); exit;
    }
    $ref = trim($_POST['ref'] ?? '');
    if ($ref) {
        init_commission_requests_table();
        $stmt = db()->prepare('SELECT status FROM commission_requests WHERE ref = ? AND artist_name = ?');
        $stmt->execute([$ref, $user['name']]);
        $row = $stmt->fetch();
        $hideable = ['Completed', 'Declined', 'Rejected', 'Cancelled'];
        if ($row && in_array($row['status'], $hideable)) {
            snapshot_artist_stats($user['name']);
            db()->prepare('UPDATE commission_requests SET hidden_by_artist = 1 WHERE ref = ? AND artist_name = ?')
                ->execute([$ref, $user['name']]);
            set_flash('success', 'Commission ' . $ref . ' removed from your dashboard.');
        } else {
            set_flash('error', 'This commission cannot be removed yet.');
        }
    }
    header('Location: /likhub/dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_delete_commission' && $role === 'admin') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
        header('Location: /likhub/dashboard.php'); exit;
    }
    $ref = trim($_POST['ref'] ?? '');
    if ($ref) {
        init_commission_requests_table();
        // Snapshot the affected artist's stats before deletion
        $row = db()->prepare('SELECT artist_name FROM commission_requests WHERE ref = ? LIMIT 1');
        $row->execute([$ref]);
        $cr = $row->fetch();
        if ($cr) snapshot_artist_stats($cr['artist_name']);
        db()->prepare('DELETE FROM commission_messages WHERE ref = ?')->execute([$ref]);
        db()->prepare('DELETE FROM commission_requests WHERE ref = ?')->execute([$ref]);
        set_flash('success', 'Commission ' . $ref . ' deleted.');
    }
    header('Location: /likhub/dashboard.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'artist') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        set_flash('error', 'Invalid form token.');
        header('Location: /likhub/dashboard.php'); exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'upload_artwork') {
        init_artist_uploads_table();
        $title  = trim($_POST['artwork_title'] ?? '');
        $medium = $_POST['artwork_medium'] ?? '';
        $file   = $_FILES['artwork_file'] ?? null;

        $mediums = ['Digital', 'Traditional', 'Painting'];
        $errors  = [];

        if (strlen($title) < 2)                  $errors[] = 'Title must be at least 2 characters.';
        if (!in_array($medium, $mediums))         $errors[] = 'Please select a valid medium.';
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Please choose a file to upload.';

        if (!$errors) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowed_types)) $errors[] = 'Only JPG, PNG, WEBP, or GIF images are allowed.';
            if ($file['size'] > 8 * 1024 * 1024)  $errors[] = 'File must be under 8 MB.';
        }

        if (!$errors) {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '_', $user['name'])));
            $filename = $safeName . '_' . uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/images/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

            db()->prepare('INSERT INTO artist_uploads (user_id, artist_name, title, medium, filename) VALUES (?,?,?,?,?)')
                ->execute([$user['id'], $user['name'], $title, $medium, $filename]);

            set_flash('success', 'Artwork "' . e($title) . '" uploaded successfully!');
        } else {
            set_flash('error', implode(' ', $errors));
        }

        header('Location: /likhub/dashboard.php'); exit;
    }

    if ($action === 'delete_artwork') {
        init_artist_uploads_table();
        $uploadId = intval($_POST['upload_id'] ?? 0);
        if ($uploadId) {
            $stmt = db()->prepare('SELECT filename FROM artist_uploads WHERE id = ? AND user_id = ?');
            $stmt->execute([$uploadId, $user['id']]);
            $row = $stmt->fetch();
            if ($row) {
                @unlink(__DIR__ . '/images/uploads/' . $row['filename']);
                db()->prepare('DELETE FROM artist_uploads WHERE id = ? AND user_id = ?')
                    ->execute([$uploadId, $user['id']]);
                set_flash('success', 'Artwork removed.');
            }
        }
        header('Location: /likhub/dashboard.php'); exit;
    }

    $ref     = trim($_POST['ref'] ?? '');
    $allowed = ['approve' => 'Approved', 'reject' => 'Declined', 'complete' => 'Payment Pending'];

    if ($ref && isset($allowed[$action])) {
        $newStatus = $allowed[$action];
        $validFrom = [
            'approve'  => ['Submitted'],
            'reject'   => ['Submitted', 'Approved', 'In Progress'],
            'complete' => ['In Progress'],
        ];
        $stmt = db()->prepare('SELECT status FROM commission_requests WHERE ref = ? AND artist_name = ?');
        $stmt->execute([$ref, $user['name']]);
        $row = $stmt->fetch();
        if ($row && in_array($row['status'], $validFrom[$action])) {
            db()->prepare('UPDATE commission_requests SET status = ? WHERE ref = ? AND artist_name = ?')
                ->execute([$newStatus, $ref, $user['name']]);
            set_flash('success', 'Commission ' . $ref . ' marked as ' . $newStatus . '.');
        }
    }

    header('Location: /likhub/dashboard.php'); exit;
}

$artist_artwork_map = [
    'DRex'      => ['/likhub/images/DRex1_digital.jpg','/likhub/images/DRex1_traditional.jpg','/likhub/images/DRex_painting.jpg','/likhub/images/DRex2_digital.jpg'],
    'Sena'      => ['/likhub/images/Sena1_digital.jpg','/likhub/images/Sena2_digital.jpg','/likhub/images/Sena3_digital.jpg','/likhub/images/Sena1_traditional.jpg','/likhub/images/Sena1_painting.jpg'],
    'Yeoj'      => ['/likhub/images/Yeoj1_digital.jpg','/likhub/images/Yeoj2_digital.jpg','/likhub/images/Yeoj3_digital.jpg'],
    'Yuhan'     => ['/likhub/images/Yuhan1_digital.jpg','/likhub/images/Yuhan2_digital.jpg','/likhub/images/Yuhan3_digital.jpg','/likhub/images/Yuhan4_digital.jpg'],
    'Keiose VB' => ['/likhub/images/Keiose VB1_traditional.jpg','/likhub/images/Keiose VB2_traditional.jpg','/likhub/images/Keiose VB3_traditional.jpg'],
    'Andrea'    => ['/likhub/images/Andrea1_digital.jpg','/likhub/images/Andrea2_digital.jpg','/likhub/images/Andrea3_digital.jpg'],
];

$stats = [];
if ($role === 'artist') {
    init_commission_requests_table();

    $artistName = $user['name'];

    $stmt = db()->prepare('SELECT COUNT(*) FROM commission_requests WHERE artist_name = ?');
    $stmt->execute([$artistName]);
    $stats['total_commissions'] = $stmt->fetchColumn();

    $stmt = db()->prepare('SELECT COUNT(*) FROM commission_requests WHERE artist_name = ? AND status IN ("Completed", "Payment Pending")');
    $stmt->execute([$artistName]);
    $stats['completed'] = $stmt->fetchColumn();

    $rStmt = db()->prepare(
        'SELECT ROUND(AVG(NULLIF(rating, 0)), 1) AS avg_r,
                SUM(CASE WHEN status IN ("Completed", "Payment Pending") THEN price ELSE 0 END) AS earned,
                SUM(CASE WHEN status = "In Progress" THEN price ELSE 0 END) AS pending
         FROM commission_requests WHERE artist_name = ?'
    );
    $rStmt->execute([$artistName]);
    $rRow = $rStmt->fetch();
    $stats['avg_rating'] = $rRow['avg_r'] ?: null;
    $stats['earned']     = (int)($rRow['earned'] ?? 0);
    $stats['pending']    = (int)($rRow['pending'] ?? 0);

    $artistArtworks = $artist_artwork_map[$artistName] ?? [];

    init_artist_uploads_table();
    $stmt = db()->prepare('SELECT * FROM artist_uploads WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $uploadedArtworks = $stmt->fetchAll();

    $stats['artworks'] = count($artistArtworks) + count($uploadedArtworks);

    $stmt = db()->prepare(
        'SELECT cr.*, u.name AS client_name
         FROM commission_requests cr
         LEFT JOIN users u ON u.id = cr.user_id
         WHERE cr.artist_name = ? AND cr.hidden_by_artist = 0
         ORDER BY cr.created_at DESC LIMIT 8'
    );
    $stmt->execute([$artistName]);
    $commissions_received = $stmt->fetchAll();

    // Also fetch commissions this artist placed themselves (as a client/requester)
    $stmt2 = db()->prepare(
        'SELECT cr.*, u.name AS client_name
         FROM commission_requests cr
         LEFT JOIN users u ON u.id = cr.user_id
         WHERE cr.user_id = ? AND cr.artist_name != ? AND cr.hidden_by_client = 0
         ORDER BY cr.created_at DESC LIMIT 8'
    );
    $stmt2->execute([$user['id'], $artistName]);
    $commissions_placed = $stmt2->fetchAll();

    // Merge and deduplicate by ref
    $seen_refs = [];
    $merged = [];
    foreach (array_merge($commissions_received, $commissions_placed) as $row) {
        if (!isset($seen_refs[$row['ref']])) {
            $seen_refs[$row['ref']] = true;
            $merged[] = $row;
        }
    }
    usort($merged, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $commissions_raw = array_slice($merged, 0, 16);

    $commissions = array_map(function($c) use ($artistName) {
        $is_received = ($c['artist_name'] === $artistName);
        return [
            'commission_ref' => $c['ref'],
            'client_name'    => $c['client_name'] ?? 'Unknown',
            'artist_name'    => $c['artist_name'],
            'size'           => $c['size'],
            'art_category'   => $c['art_type'],
            'price'          => $c['price'],
            'status'         => $c['status'],
            'request_date'   => $c['created_at'],
            'description'    => $c['description'] ?? '',
            'rating'         => $c['rating'] ?? null,
            'payment'        => $c['payment'] ?? '',
            'is_received'    => $is_received,
        ];
    }, $commissions_raw);

} elseif ($role === 'client') {
    init_commission_requests_table();

    $stmt = db()->prepare('SELECT COUNT(*) FROM commission_requests WHERE user_id = ? AND hidden_by_client = 0');
    $stmt->execute([$user['id']]);
    $stats['total_commissions'] = $stmt->fetchColumn();

    $stmt = db()->prepare('SELECT COUNT(*) FROM commission_requests WHERE user_id = ? AND hidden_by_client = 0 AND status = "Completed"');
    $stmt->execute([$user['id']]);
    $stats['completed'] = $stmt->fetchColumn();

    $stmt = db()->prepare('SELECT * FROM commission_requests WHERE user_id = ? AND hidden_by_client = 0 ORDER BY created_at DESC LIMIT 8');
    $stmt->execute([$user['id']]);
    $raw = $stmt->fetchAll();

    $commissions = array_map(function($c) {
        return [
            'commission_ref' => $c['ref'],
            'artist_name'    => $c['artist_name'],
            'size'           => $c['size'],
            'art_category'   => $c['art_type'],
            'price'          => $c['price'],
            'status'         => $c['status'],
            'request_date'   => $c['created_at'],
            'rating'         => $c['rating'],
            'db_id'          => $c['id'],
        ];
    }, $raw);

} elseif ($role === 'admin') {
    $stats['users']       = db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['commissions'] = db()->query('SELECT COUNT(*) FROM commission_requests')->fetchColumn();
    $stats['artworks']    = db()->query('SELECT COUNT(*) FROM artworks')->fetchColumn();
    $stats['tutorials']   = db()->query('SELECT COUNT(*) FROM tutorials')->fetchColumn();
    $commissions = db()->query('SELECT cr.*, cr.ref AS commission_ref, cr.art_type AS art_category, u.name AS client_name, cr.artist_name, cr.created_at AS request_date FROM commission_requests cr JOIN users u ON u.id = cr.user_id ORDER BY cr.created_at DESC LIMIT 10')->fetchAll();
    $contacts    = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5')->fetchAll();
}

$active_tab = trim($_GET['tab'] ?? '');
// Normalise: 'messages' tab is just the commissions page scrolled to the panel
if ($active_tab === 'messages') $active_tab = 'messages'; // keep separate for admin
if (!$active_tab) $active_tab = 'overview';

include __DIR__ . '/partials/header.php';
?>
<style>
  /* On dashboard, suppress the navbar hamburger — we use our own */
  .mobile-menu { display: none !important; }

  @media (max-width: 1024px) {
    .navbar__hamburger   { display: none !important; }  /* hide the navbar one */
    .navbar__actions     { display: none !important; }
    .navbar__links       { display: none !important; }
    .dashboard-hamburger { display: flex !important; }  /* show our dedicated one */
  }
  @media (min-width: 1025px) {
    .navbar__hamburger     { display: none !important; }
    .dashboard-hamburger   { display: none !important; }
  }

  /* Dedicated mobile hamburger button */
  .dashboard-hamburger {
    display: none;
    position: fixed;
    top: 10px;
    right: 12px;
    z-index: 1200;
    background: var(--color-primary, #1e3a5f);
    border: none;
    border-radius: 8px;
    padding: 8px 10px;
    cursor: pointer;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.25);
  }
  .dashboard-hamburger span {
    display: block;
    width: 22px;
    height: 2px;
    background: #fff;
    border-radius: 2px;
    transition: transform 0.25s ease, opacity 0.25s ease;
  }
  .dashboard-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
  .dashboard-hamburger.open span:nth-child(2) { opacity: 0; }
  .dashboard-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
</style>

<?php
$initials = '';
foreach (explode(' ', trim($user['name'])) as $p) $initials .= strtoupper($p[0]);
$initials = substr($initials, 0, 2);
?>

<!-- Dedicated dashboard hamburger — fully independent of main.js -->
<button class="dashboard-hamburger" id="dashHamburger" aria-label="Open navigation menu">
  <span></span>
  <span></span>
  <span></span>
</button>

<div class="sidebar-drawer-backdrop" id="sidebarBackdrop"></div>

<div class="dashboard-layout">

  <aside class="sidebar" id="dashSidebar" aria-label="Dashboard navigation">
    <div class="sidebar-profile">
      <div class="sidebar-profile__row">
        <div class="sidebar-avatar">
          <?php if ($user['avatar']): ?><img src="<?= e($user['avatar']) ?>" alt=""><?php else: ?><?= e($initials) ?><?php endif; ?>
        </div>
        <div>
          <div class="sidebar-name"><?= e($user['name']) ?></div>
          <div class="sidebar-role"><?= e($role) ?></div>
        </div>
      </div>
    </div>

    <div class="sidebar__section-label">Menu</div>
    <a href="/likhub/dashboard.php" class="sidebar__link active"><span><i class="fa-solid fa-gauge"></i></span> Overview</a>

    <?php if ($role === 'artist'): ?>
    <a href="/likhub/dashboard.php?tab=commissions" class="sidebar__link"><span><i class="fa-solid fa-handshake"></i></span> Commissions</a>
    <a href="/likhub/dashboard.php?tab=artworks" class="sidebar__link"><span><i class="fa-solid fa-palette"></i></span> My Artworks</a>
    <a href="/likhub/edit-profile.php" class="sidebar__link"><span><i class="fa-solid fa-pen-to-square"></i></span> Edit Profile</a>
    <a href="/likhub/artist-profile.php?id=<?= $user['id'] ?>" class="sidebar__link"><span><i class="fa-solid fa-user"></i></span> Public Profile</a>
    <?php elseif ($role === 'client'): ?>
    <a href="/likhub/dashboard.php?tab=commissions" class="sidebar__link"><span><i class="fa-solid fa-list"></i></span> My Orders</a>
    <?php elseif ($role === 'admin'): ?>
    <a href="/likhub/dashboard.php?tab=users" class="sidebar__link"><span><i class="fa-solid fa-users"></i></span> Users</a>
    <a href="/likhub/dashboard.php?tab=messages" class="sidebar__link"><span><i class="fa-solid fa-envelope"></i></span> Messages</a>
    <?php endif; ?>

    <div class="sidebar__section-label">Explore</div>
    <a href="/likhub/learn.php" class="sidebar__link"><span><i class="fa-solid fa-book-open"></i></span> Tutorials</a>
    <a href="/likhub/create.php" class="sidebar__link"><span><i class="fa-solid fa-image"></i></span> Gallery</a>
    <a href="/likhub/commission.php" class="sidebar__link"><span><i class="fa-solid fa-star"></i></span> Find Artists</a>
    <a href="/likhub/logout.php" class="sidebar__link sidebar__link--logout"><span><i class="fa-solid fa-right-from-bracket"></i></span> Log Out</a>
  </aside>

  <main class="dashboard-main">
    <h2>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h2>

    <div class="stat-grid">
      <?php if ($role === 'artist'): ?>
        <div class="stat-card"><div class="stat-card__label">Total Commissions</div><div class="stat-card__value"><?= $stats['total_commissions'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Completed</div><div class="stat-card__value"><?= $stats['completed'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Avg Rating</div><div class="stat-card__value"><?= $stats['avg_rating'] ? '⭐ ' . $stats['avg_rating'] : '—' ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Artworks</div><div class="stat-card__value"><?= $stats['artworks'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Total Earned</div><div class="stat-card__value">₱<?= number_format($stats['earned'] ?? 0) ?></div></div>
        <div class="stat-card"><div class="stat-card__label">In Progress</div><div class="stat-card__value">₱<?= number_format($stats['pending'] ?? 0) ?></div></div>
      <?php elseif ($role === 'client'): ?>
        <div class="stat-card"><div class="stat-card__label">Total Orders</div><div class="stat-card__value"><?= $stats['total_commissions'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Completed</div><div class="stat-card__value"><?= $stats['completed'] ?></div></div>
      <?php elseif ($role === 'admin'): ?>
        <div class="stat-card"><div class="stat-card__label">Total Users</div><div class="stat-card__value"><?= $stats['users'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Commissions</div><div class="stat-card__value"><?= $stats['commissions'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Artworks</div><div class="stat-card__value"><?= $stats['artworks'] ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Tutorials</div><div class="stat-card__value"><?= $stats['tutorials'] ?></div></div>
      <?php else: ?>
        <div class="stat-card"><div class="stat-card__label">Tutorials Available</div><div class="stat-card__value"><?= db()->query('SELECT COUNT(*) FROM tutorials WHERE is_published=1')->fetchColumn() ?></div></div>
        <div class="stat-card"><div class="stat-card__label">Artists</div><div class="stat-card__value"><?= db()->query('SELECT COUNT(*) FROM users WHERE role="artist"')->fetchColumn() ?></div></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($commissions)): ?>
    <h3 class="section-heading"><?= $role === 'client' ? 'My Orders' : 'Recent Commissions' ?></h3>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Ref</th>
            <?= $role === 'artist' ? '<th>Client</th>' : ($role === 'client' ? '<th>Artist</th>' : '<th>Client</th><th>Artist</th>') ?>
            <th>Size</th>
            <th>Category</th>
            <th>Price</th>
            <th>Status</th>
            <th>Date</th>
            <?php if ($role === 'artist' || $role === 'client' || $role === 'admin'): ?><th>Actions</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($commissions as $c): ?>
          <tr>
            <td><a href="/likhub/track-order.php?ref=<?= urlencode($c['commission_ref']) ?>" class="commission-ref-link"><?= e($c['commission_ref']) ?></a></td>
            <?php if ($role === 'artist'): ?>
              <td>
                <?php if (!empty($c['is_received'])): ?>
                  <?= e($c['client_name']) ?>
                  <?php if (!empty($c['description'])): ?>
                    <div class="commission-desc-preview"><?= e(mb_strimwidth($c['description'], 0, 60, '…')) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <?= e($c['artist_name']) ?>
                  <div class="commission-desc-preview" style="color:#6b7280;font-size:11px;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Your request</div>
                <?php endif; ?>
              </td>
            <?php elseif ($role === 'client'): ?>
              <td><?= e($c['artist_name']) ?></td>
            <?php else: ?>
              <td><?= e($c['client_name']) ?></td><td><?= e($c['artist_name']) ?></td>
            <?php endif; ?>
            <td><?= e($c['size']) ?></td>
            <td><?= e($c['art_category']) ?></td>
            <td>₱<?= number_format($c['price'], 2) ?></td>
            <td><span class="status-badge status-badge--<?= strtolower(str_replace(' ', '', $c['status'])) ?>"><?= e($c['status']) ?></span></td>
            <td><?= date('M j, Y', strtotime($c['request_date'])) ?></td>
            <?php if ($role === 'artist'): ?>
            <td class="commission-actions">
              <?php $s = $c['status']; ?>
              <?php if (!empty($c['is_received'])): ?>
                <?php /* === Actions when this artist is the RECEIVING artist === */ ?>
                <?php if ($s === 'Approved'): ?>
                  <span class="commission-actions__done" style="color:#16a34a;font-size:12px;"><i class="fa-solid fa-clock"></i> Awaiting client</span>
                  <form class="u-d-inline" method="POST">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="ref" value="<?= e($c['commission_ref']) ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-action btn-action--reject" title="Cancel"
                      onclick="return confirm('Cancel this commission?')">
                      <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                  </form>
                <?php elseif ($s === 'Submitted'): ?>
                  <form class="u-d-inline" method="POST">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="ref" value="<?= e($c['commission_ref']) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn-action btn-action--approve" title="Approve">
                      <i class="fa-solid fa-check"></i> Approve
                    </button>
                  </form>
                  <form class="u-d-inline" method="POST">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="ref" value="<?= e($c['commission_ref']) ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-action btn-action--reject" title="Reject"
                      onclick="return confirm('Reject this commission?')">
                      <i class="fa-solid fa-xmark"></i> Reject
                    </button>
                  </form>
                <?php elseif ($s === 'In Progress'): ?>
                <form class="u-d-inline" method="POST">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="ref" value="<?= e($c['commission_ref']) ?>">
                  <input type="hidden" name="action" value="complete">
                  <button type="submit" class="btn-action btn-action--complete" title="Mark Complete — client will then pay">
                    <i class="fa-solid fa-flag-checkered"></i> Mark Complete
                  </button>
                </form>
                <form class="u-d-inline" method="POST">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="ref" value="<?= e($c['commission_ref']) ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn-action btn-action--reject" title="Reject"
                    onclick="return confirm('Reject this commission?')">
                    <i class="fa-solid fa-xmark"></i> Reject
                  </button>
                </form>
              <?php elseif ($s === 'Payment Pending'): ?>
                <span class="commission-actions__done" style="color:#d97706;font-size:12px;"><i class="fa-solid fa-clock"></i> Awaiting client payment</span>
              <?php elseif (in_array($s, ['Completed', 'Declined', 'Rejected', 'Cancelled'])): ?>
                <form class="u-d-inline" method="POST">
                  <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                  <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                  <input type="hidden" name="action" value="artist_archive">
                  <button type="submit" class="btn-action btn-action--reject" title="Remove from dashboard"
                    onclick="return confirm('Remove this from your dashboard?')">
                    <i class="fa-solid fa-trash"></i> Remove
                  </button>
                </form>
              <?php else: ?>
                <span class="commission-actions__done">—</span>
              <?php endif; ?>
              <?php else: ?>
                <?php /* === Actions when this artist is the REQUESTER (placed the commission) === */ ?>
                <?php if ($s === 'Approved'): ?>
                  <button class="btn-action btn-action--approve js-commission-action"
                    data-action="client_accept"
                    data-ref="<?= e($c['commission_ref']) ?>"
                    data-confirm="Accept this commission from <?= e($c['artist_name'] ?? '') ?>? Work begins — you pay once they&apos;re done."
                    title="Accept commission">
                    <i class="fa-solid fa-check"></i> Accept
                  </button>
                <?php elseif ($s === 'Payment Pending'): ?>
                  <button class="btn-action btn-action--complete js-commission-action"
                    data-action="client_pay_confirm"
                    data-ref="<?= e($c['commission_ref']) ?>"
                    data-price="<?= (int)($c['price'] ?? 0) ?>"
                    data-payment="<?= e($c['payment'] ?? '') ?>"
                    data-artist="<?= e($c['artist_name'] ?? '') ?>"
                    data-confirm="Confirm you have sent ₱<?= number_format((int)($c['price'] ?? 0)) ?> to <?= e($c['artist_name'] ?? 'the artist') ?> via <?= e($c['payment'] ?? 'your agreed method') ?>?\n\nOnly confirm once payment has been sent."
                    title="Confirm payment">
                    <i class="fa-solid fa-money-bill-wave"></i> Pay Now
                  </button>
                <?php elseif (in_array($s, ['Submitted', 'In Progress'])): ?>
                  <form class="u-d-inline" method="POST">
                    <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                    <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn-action btn-action--reject" title="Cancel"
                      onclick="return confirm('Cancel this commission? This cannot be undone.')">
                      <i class="fa-solid fa-ban"></i> Cancel
                    </button>
                  </form>
                <?php elseif ($s === 'Completed' && empty($c['rating'])): ?>
                  <button type="button"
                    class="btn-action btn-action--approve dash-review-btn"
                    data-ref="<?= e($c['commission_ref']) ?>"
                    data-artist="<?= e($c['artist_name']) ?>"
                    title="Leave a Review">
                    <i class="fa-solid fa-star"></i> Review
                  </button>
                <?php elseif (!empty($c['rating'])): ?>
                  <span class="commission-actions__done" title="You rated this <?= (int)$c['rating'] ?>/5">
                    <?= str_repeat('★', (int)$c['rating']) ?><?= str_repeat('☆', 5 - (int)$c['rating']) ?>
                  </span>
                <?php else: ?>
                  <span class="commission-actions__done">—</span>
                <?php endif; ?>
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if ($role === 'client'): ?>
            <td class="commission-actions">
              <?php if ($c['status'] === 'Approved'): ?>
                <button class="btn-action btn-action--approve js-commission-action"
                  data-action="client_accept"
                  data-ref="<?= e($c['commission_ref']) ?>"
                  data-confirm="Accept this commission from <?= e($c['artist_name'] ?? '') ?>? Work begins — you pay once they're done."
                  title="Accept commission">
                  <i class="fa-solid fa-check"></i> Accept
                </button>
              <?php elseif ($c['status'] === 'Payment Pending'): ?>
                <button class="btn-action btn-action--complete js-commission-action"
                  data-action="client_pay_confirm"
                  data-ref="<?= e($c['commission_ref']) ?>"
                  data-price="<?= (int)$c['price'] ?>"
                  data-payment="<?= e($c['payment'] ?? '') ?>"
                  data-artist="<?= e($c['artist_name'] ?? '') ?>"
                  data-confirm="Confirm you have sent ₱<?= number_format((int)$c['price']) ?> to <?= e($c['artist_name'] ?? 'the artist') ?> via <?= e($c['payment'] ?? 'your agreed method') ?>?\n\nOnly confirm once payment has been sent."
                  title="Confirm payment">
                  <i class="fa-solid fa-money-bill-wave"></i> Pay Now
                </button>
              <?php elseif (in_array($c['status'], ['Submitted', 'In Progress'])): ?>
                <form class="u-d-inline" method="POST">
                  <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                  <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                  <input type="hidden" name="action" value="cancel">
                  <button type="submit" class="btn-action btn-action--reject" title="Cancel"
                    onclick="return confirm('Cancel this commission? This cannot be undone.')">
                    <i class="fa-solid fa-ban"></i> Cancel
                  </button>
                </form>
              <?php elseif ($c['status'] === 'Completed' && empty($c['rating'])): ?>
                <button type="button"
                   class="btn-action btn-action--approve dash-review-btn"
                   data-ref="<?= e($c['commission_ref']) ?>"
                   data-artist="<?= e($c['artist_name']) ?>"
                   title="Leave a Review">
                  <i class="fa-solid fa-star"></i> Review
                </button>
                <form class="u-d-inline" method="POST">
                  <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                  <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                  <input type="hidden" name="action" value="delete_commission">
                  <button type="submit" class="btn-action btn-action--reject" title="Remove from dashboard"
                    onclick="return confirm('Remove this completed order from your dashboard?')">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              <?php elseif ($c['status'] === 'Completed' && !empty($c['rating'])): ?>
                <span class="commission-actions__done" title="You rated this <?= (int)$c['rating'] ?>/5">
                  <?= str_repeat('★', (int)$c['rating']) ?><?= str_repeat('☆', 5 - (int)$c['rating']) ?>
                </span>
                <form class="u-d-inline" method="POST">
                  <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                  <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                  <input type="hidden" name="action" value="delete_commission">
                  <button type="submit" class="btn-action btn-action--reject" title="Remove from dashboard"
                    onclick="return confirm('Remove this completed order from your dashboard?')">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              <?php elseif (in_array($c['status'], ['Cancelled','Declined','Rejected'])): ?>
                  <form class="u-d-inline" method="POST">
                    <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                    <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                    <input type="hidden" name="action" value="delete_commission">
                    <button type="submit" class="btn-action btn-action--reject" title="Remove from list"
                      onclick="return confirm('Remove this order from your list?')">
                      <i class="fa-solid fa-trash"></i> Delete
                    </button>
                  </form>
              <?php else: ?>
                  <span class="commission-actions__done">—</span>
              <?php endif; ?>
            </td>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
            <td class="commission-actions">
              <form class="u-d-inline" method="POST"
                onsubmit="return confirm('Permanently delete commission <?= e($c['commission_ref']) ?> and all its messages?')">
                <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                <input type="hidden" name="ref"    value="<?= e($c['commission_ref']) ?>">
                <input type="hidden" name="action" value="admin_delete_commission">
                <button type="submit" class="btn-action btn-action--reject" title="Delete permanently">
                  <i class="fa-solid fa-trash"></i> Delete
                </button>
              </form>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php
    $active_thread_ref = trim($_GET['thread'] ?? '');
    // For admin: load all commission threads to browse/monitor all conversations
    $msg_commissions = $commissions;
    if ($role === 'admin') {
        $all_threads = db()->query(
            'SELECT cr.ref AS commission_ref, u.name AS client_name, cr.artist_name
             FROM commission_requests cr
             LEFT JOIN users u ON u.id = cr.user_id
             ORDER BY cr.created_at DESC LIMIT 50'
        )->fetchAll();
        $msg_commissions = array_map(function($r) {
            return [
                'commission_ref' => $r['commission_ref'],
                'client_name'    => $r['client_name'] ?? 'Client',
                'artist_name'    => $r['artist_name'] ?? 'Artist',
            ];
        }, $all_threads);
    }
    if (in_array($role, ['artist','client','admin']) && !empty($msg_commissions)):
        if ($active_thread_ref) {
            mark_read($active_thread_ref, $user['id']);
        }
    ?>
    <h3 class="u-mt-32 section-heading--spaced" id="msg-thread">
      <i class="fa-solid fa-comments"></i> Messages
    </h3>
    <div class="msg-panel">
      <div class="msg-panel__sidebar">
        <?php foreach ($msg_commissions as $c):
            $thread_ref = $c['commission_ref'];
            $unread_in_thread = 0;
            try {
                $sq = db()->prepare(
                    'SELECT COUNT(*) FROM commission_messages WHERE ref = ? AND sender_id != ? AND is_read = 0'
                );
                $sq->execute([$thread_ref, $user['id']]);
                $unread_in_thread = (int) $sq->fetchColumn();
            } catch (Exception $e) {}
            $is_active = ($active_thread_ref === $thread_ref);
        ?>
        <a href="/likhub/dashboard.php?tab=commissions&thread=<?= urlencode($thread_ref) ?>" class="msg-thread-link <?= $is_active ? 'msg-thread-link--active' : '' ?>">
          <span class="msg-thread-link__ref"><?= e($thread_ref) ?></span>
          <span class="msg-thread-link__who">
            <?php if ($role === 'admin'): ?>
              <?= e($c['client_name'] ?? 'Client') ?> → <?= e($c['artist_name'] ?? 'Artist') ?>
            <?php elseif ($role === 'artist'): ?>
              <?= e($c['client_name'] ?? 'Client') ?>
            <?php else: ?>
              <?= e($c['artist_name'] ?? 'Artist') ?>
            <?php endif; ?>
          </span>
          <?php if ($unread_in_thread > 0): ?>
            <span class="msg-thread-link__badge"><?= $unread_in_thread ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="msg-panel__main">
        <?php if ($active_thread_ref):
            $thread_messages = get_messages($active_thread_ref);
        ?>
        <div class="msg-messages" id="msgMessages">
          <?php if (empty($thread_messages)): ?>
            <div class="msg-empty"><i class="fa-regular fa-comment-dots"></i><br>No messages yet — start the conversation!</div>
          <?php endif; ?>
          <?php foreach ($thread_messages as $m):
              $is_mine  = ($m['sender_id'] == $user['id']);
              $mtype    = $m['msg_type'] ?? 'text';
              $is_delivery = in_array($mtype, ['delivery_digital','delivery_physical']);
          ?>
          <div class="msg-bubble <?= $is_mine ? 'msg-bubble--mine' : 'msg-bubble--theirs' ?> <?= $is_delivery ? 'msg-bubble--delivery' : '' ?>">
            <div class="msg-bubble__meta">
              <span class="msg-bubble__name"><?= e($m['sender_name'] ?? 'Unknown') ?></span>
              <span class="msg-bubble__time"><?= date('M j, g:i a', strtotime($m['created_at'])) ?></span>
            </div>
            <?php if ($is_delivery && $m['file_path']): ?>
              <div class="msg-delivery-card">
                <?php if ($mtype === 'delivery_digital'): ?>
                  <div class="msg-delivery-card__icon"><i class="fa-solid fa-file-image"></i></div>
                  <div class="msg-delivery-card__info">
                    <div class="msg-delivery-card__label">✅ Finished Digital Artwork</div>
                    <div class="u-m-4-0 msg-bubble__body"><?= nl2br(e($m['body'])) ?></div>
                    <?php
                      $ext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION));
                      $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                    ?>
                    <?php if ($isImg): ?>
                      <img src="<?= e($m['file_path']) ?>" alt="Finished artwork" data-util-class="u-img-artwork">
                    <?php endif; ?>
                    <a href="<?= e($m['file_path']) ?>" download class="btn btn-primary btn-sm" data-util-class="u-dl-btn">
                      <i class="fa-solid fa-download"></i> Download File
                    </a>
                  </div>
                <?php else: ?>
                  <div class="msg-delivery-card__icon"><i class="fa-solid fa-truck"></i></div>
                  <div class="msg-delivery-card__info">
                    <div class="msg-delivery-card__label">📦 Finished Physical Artwork</div>
                    <div class="u-m-4-0 msg-bubble__body"><?= nl2br(e($m['body'])) ?></div>
                    <?php
                      $ext2 = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION));
                      $isImg2 = in_array($ext2, ['jpg','jpeg','png','gif','webp']);
                    ?>
                    <?php if ($isImg2): ?>
                      <img src="<?= e($m['file_path']) ?>" alt="Finished artwork photo" data-util-class="u-img-artwork">
                    <?php endif; ?>
                    <p class="u-ship-note"><i class="fa-solid fa-info-circle"></i> The physical piece will be shipped to you. Please coordinate shipping details with the artist.</p>
                  </div>
                <?php endif; ?>
              </div>
            <?php else: ?>
            <?php if ($m['body'] && $m['body'] !== '📎 File attached'): ?>
            <div class="msg-bubble__body"><?= nl2br(e($m['body'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($m['file_path'])): ?>
              <?php $mext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION)); $mIsImg = in_array($mext, ['jpg','jpeg','png','gif','webp']); ?>
              <?php if ($mIsImg): ?>
                <img src="<?= e($m['file_path']) ?>" alt="Attached image" class="msg-attachment-img">
              <?php else: ?>
                <a href="<?= e($m['file_path']) ?>" download class="msg-attachment-link"><i class="fa-solid fa-file-arrow-down"></i> Download attachment</a>
              <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <form method="POST" class="msg-compose" action="/likhub/dashboard.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf"    value="<?= csrf_token() ?>">
          <input type="hidden" name="action"  value="send_message">
          <input type="hidden" name="msg_ref" value="<?= e($active_thread_ref) ?>">
          <div class="msg-compose__inner">
            <div class="msg-compose__attach-row">
              <label class="msg-compose__attach-btn" title="Attach image or file">
                <i class="fa-solid fa-paperclip"></i>
                <input type="file" name="msg_file" accept="image/*,.pdf" style="display:none;" id="msgFileInput">
              </label>
              <span class="msg-compose__file-name" id="msgFileName"></span>
            </div>
            <textarea name="msg_body" class="msg-compose__input" placeholder="Type a message…" rows="2" maxlength="2000" id="msgBodyInput"></textarea>
          </div>
          <button type="submit" class="btn btn-primary msg-compose__btn">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </form>
        <script>
        (function(){
          var fi = document.getElementById('msgFileInput');
          var fn = document.getElementById('msgFileName');
          if(fi) fi.addEventListener('change', function(){ fn.textContent = fi.files[0] ? fi.files[0].name : ''; });
        })();
        </script>

        <?php if ($role === 'artist'):
          $thr_stmt = db()->prepare('SELECT art_type FROM commission_requests WHERE ref = ? LIMIT 1');
          $thr_stmt->execute([$active_thread_ref]);
          $thr_commission = $thr_stmt->fetch();
          $thr_type = $thr_commission['art_type'] ?? 'Digital';
        ?>
        <details class="msg-delivery-form" id="deliveryFormWrap">
          <summary class="u-cursor-pointer u-d-inline-flex u-align-center u-gap-6 u-mt-10 u-list-none btn btn-outline btn-sm">
            <i class="fa-solid fa-paper-plane"></i> Send Finished Output
          </summary>
          <form method="POST" enctype="multipart/form-data" class="msg-delivery-form__body" action="/likhub/dashboard.php">
            <input type="hidden" name="csrf"     value="<?= csrf_token() ?>">
            <input type="hidden" name="action"   value="send_delivery">
            <input type="hidden" name="msg_ref"  value="<?= e($active_thread_ref) ?>">
            <input type="hidden" name="art_type" value="<?= e($thr_type) ?>">
            <div class="u-flex-col-gap10">
              <div>
                <label class="u-fs-12 form-label">
                  <?= $thr_type === 'Digital'
                    ? '<i class="fa-solid fa-file-image"></i> Attach finished file (JPG, PNG, PDF, ZIP — max 30 MB)'
                    : '<i class="fa-solid fa-camera"></i> Attach photo of finished piece (JPG, PNG — max 30 MB)' ?>
                </label>
                <input type="file" name="delivery_file" class="u-mt-4 form-control"
                  accept="<?= $thr_type === 'Digital' ? 'image/*,.pdf,.zip' : 'image/*' ?>" required>
              </div>
              <div>
                <label class="u-fs-12 form-label">Note to client <span class="u-fw-400 u-color-muted">(optional)</span></label>
                <input type="text" name="delivery_note" class="form-control" maxlength="500"
                  placeholder="<?= $thr_type === 'Digital' ? 'e.g. Here\'s your file! Full-res PNG attached.' : 'e.g. Your piece is ready — I\'ll ship tomorrow!' ?>">
              </div>
              <button type="submit" class="u-align-self-start btn btn-primary btn-sm">
                <i class="fa-solid fa-check-circle"></i> Send to Client
              </button>
            </div>
          </form>
        </details>
        <?php endif; ?>
        <?php else: ?>
          <div class="u-m-auto msg-empty">
            <i class="fa-solid fa-hand-pointer"></i><br>
            Select a commission on the left to view the conversation.
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($role === 'admin' && !empty($contacts)): ?>
    <h3 class="section-heading--spaced">Unread Contact Messages</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Subject</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($contacts as $msg): ?>
          <tr>
            <td><?= e($msg['name']) ?></td>
            <td><?= e($msg['email']) ?></td>
            <td><?= e($msg['subject'] ?: '(no subject)') ?></td>
            <td><?= date('M j, Y', strtotime($msg['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if ($role === 'artist'): ?>
    <div class="section-heading-row">
      <h3 class="section-heading--spaced">My Artworks</h3>
      <button class="btn btn-primary btn-sm" id="btnShowUpload">
        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Artwork
      </button>
    </div>

    <div class="u-d-none upload-panel" id="uploadPanel">
      <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="upload_artwork">
        <div class="upload-form__fields">
          <div class="form-group">
            <label class="form-label" for="artwork_title">Title *</label>
            <input type="text" name="artwork_title" id="artwork_title" class="form-control"
              placeholder="e.g. Character Study I" required maxlength="200">
          </div>
          <div class="form-group">
            <label class="form-label" for="artwork_medium">Medium *</label>
            <select name="artwork_medium" id="artwork_medium" class="form-control" required>
              <option value="">— Select —</option>
              <option value="Digital">Digital</option>
              <option value="Traditional">Traditional</option>
              <option value="Painting">Painting</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="artwork_file">Image * <span class="u-d-inline form-hint">(JPG, PNG, WEBP · max 8 MB)</span></label>
            <input type="file" name="artwork_file" id="artwork_file" class="form-control"
              accept="image/jpeg,image/png,image/webp,image/gif" required>
          </div>
        </div>
        <div class="u-d-none upload-form__preview" id="artworkPreviewWrap">
          <img id="artworkPreview" src="" alt="Preview">
        </div>
        <div class="upload-form__actions">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload</button>
          <button type="button" class="btn btn-outline" id="btnCancelUpload">Cancel</button>
        </div>
      </form>
    </div>

    <?php if (!empty($uploadedArtworks)): ?>
    <h4 class="artwork-section-label">Uploaded by You</h4>
    <div class="grid-4">
      <?php foreach ($uploadedArtworks as $aw): ?>
      <div class="card artwork-card">
        <div class="card__img js-lb-trigger"
             data-src="/likhub/images/uploads/<?= e($aw['filename']) ?>"
             data-caption="<?= e($aw['title']) ?> · <?= e($aw['medium']) ?>"
             title="Click to view full size" data-util-class="u-cursor-zoom">
          <img src="/likhub/images/uploads/<?= e($aw['filename']) ?>" alt="<?= e($aw['title']) ?>" loading="lazy" class="card__img-fill">
        </div>
        <div class="card__body">
          <div class="artwork-card__title"><?= e($aw['title']) ?></div>
          <div class="artwork-card__meta"><span class="tag"><?= e($aw['medium']) ?></span></div>
          <form class="u-mt-8" method="POST">
            <input type="hidden" name="csrf"      value="<?= csrf_token() ?>">
            <input type="hidden" name="action"    value="delete_artwork">
            <input type="hidden" name="upload_id" value="<?= $aw['id'] ?>">
            <button type="submit" class="btn-action btn-action--reject btn-full-sm"
              onclick="return confirm('Remove this artwork?')">
              <i class="fa-solid fa-trash"></i> Remove
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($artistArtworks)): ?>
    <h4 class="artwork-section-label" class="<?= empty($uploadedArtworks) ? '' : 'u-mt-24' ?>">Portfolio</h4>
    <div class="grid-4">
      <?php foreach ($artistArtworks as $imgPath): ?>
      <div class="card">
        <div class="card__img js-lb-trigger"
             data-src="<?= e($imgPath) ?>"
             data-caption="Portfolio artwork"
             title="Click to view full size" data-util-class="u-cursor-zoom">
          <img src="<?= e($imgPath) ?>" alt="Artwork" loading="lazy" class="card__img-fill">
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($artistArtworks) && empty($uploadedArtworks)): ?>
    <p class="u-mt-12 text-muted">No artworks yet — upload your first one!</p>
    <?php endif; ?>

    <script>
    (function(){
      const btn    = document.getElementById('btnShowUpload');
      const panel  = document.getElementById('uploadPanel');
      const cancel = document.getElementById('btnCancelUpload');
      const fileIn = document.getElementById('artwork_file');
      const prev   = document.getElementById('artworkPreview');
      const prevW  = document.getElementById('artworkPreviewWrap');

      btn.addEventListener('click', () => { panel.style.display = panel.style.display === 'none' ? 'block' : 'none'; });
      cancel.addEventListener('click', () => { panel.style.display = 'none'; });

      fileIn.addEventListener('change', () => {
        const f = fileIn.files[0];
        if (!f) { prevW.style.display = 'none'; return; }
        const reader = new FileReader();
        reader.onload = e => { prev.src = e.target.result; prevW.style.display = 'block'; };
        reader.readAsDataURL(f);
      });
    })();
    </script>

    <div id="dashLightbox" class="u-lightbox-overlay">
      <button class="u-lightbox-wrap" id="dashLbClose" aria-label="Close" style="position:absolute;top:16px;right:20px;background:none;border:none;color:
      <div>
        <img class="u-img-contain" id="dashLbImg" src="" alt="">
        <p id="dashLbCaption" style="color:
      </div>
    </div>
    <script>
    (function(){
      const lb      = document.getElementById('dashLightbox');
      const lbImg   = document.getElementById('dashLbImg');
      const lbCap   = document.getElementById('dashLbCaption');
      const lbClose = document.getElementById('dashLbClose');

      function openLb(src, caption) {
        lbImg.src = src;
        lbCap.textContent = caption || '';
        lb.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }
      function closeLb() {
        lb.style.display = 'none';
        document.body.style.overflow = '';
        lbImg.src = '';
      }

      document.querySelectorAll('.js-lb-trigger').forEach(el => {
        el.addEventListener('click', () => openLb(el.dataset.src, el.dataset.caption));
      });
      lbClose.addEventListener('click', closeLb);
      lb.addEventListener('click', e => { if (e.target === lb) closeLb(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && lb.style.display === 'flex') closeLb(); });
    })();
    </script>
    <?php endif; ?>

    <?php if ($role === 'learner'): ?>
    <div class="grid-3 quick-links">
      <a href="/likhub/learn.php" class="size-card quick-link-card">
        <div class="size-card__icon lg quick-link-icon"><i class="fa-solid fa-book-open"></i></div>
        <div class="size-card__name">Browse Tutorials</div>
        <div class="size-card__dim">Find lessons for your skill level</div>
      </a>
      <a href="/likhub/create.php" class="size-card quick-link-card">
        <div class="size-card__icon lg quick-link-icon"><i class="fa-solid fa-palette"></i></div>
        <div class="size-card__name">Explore Gallery</div>
        <div class="size-card__dim">See what other artists are creating</div>
      </a>
      <a href="/likhub/commission.php" class="size-card quick-link-card">
        <div class="size-card__icon lg quick-link-icon"><i class="fa-solid fa-handshake"></i></div>
        <div class="size-card__name">Commission Art</div>
        <div class="size-card__dim">Request custom artwork from artists</div>
      </a>
    </div>
    <?php endif; ?>
  </main>
</div>

<!-- Review Modal (dashboard) -->
<div class="modal-overlay" id="dashReviewModal" role="dialog" aria-modal="true" aria-labelledby="dashReviewModalTitle" style="display:none;">
  <div class="u-max-w-480 modal">
    <button class="modal__close" id="dashReviewModalClose">&times;</button>
    <h3 id="dashReviewModalTitle"><i class="fa-solid fa-star" style="color:#f59e0b;margin-right:6px;"></i> Leave a Review</h3>
    <p class="u-m-6-0-16 body-text">How was your experience with <strong id="dashReviewArtistName"></strong>?</p>
    <input type="hidden" id="dashReviewRef" value="">
    <div class="form-group" style="margin-bottom:16px;">
      <label class="form-label">Your Rating *</label>
      <div class="star-picker" id="dashStarPicker" style="display:flex;gap:8px;margin-top:8px;">
        <?php for($i=1;$i<=5;$i++): ?>
        <button type="button" class="dash-star-pick-btn" data-val="<?= $i ?>" style="font-size:28px;background:none;border:none;cursor:pointer;color:#d1d5db;transition:color .15s;" aria-label="<?= $i ?> star">★</button>
        <?php endfor; ?>
      </div>
      <input type="hidden" id="dashReviewRating" value="0">
    </div>
    <div class="form-group">
      <label class="form-label" for="dashReviewText">Comments <span class="u-fw-400 u-color-muted">(optional)</span></label>
      <textarea id="dashReviewText" class="form-control" rows="3" maxlength="1000" placeholder="Share your experience…"></textarea>
    </div>
    <div class="modal-btn-row" style="margin-top:16px;">
      <button type="button" class="btn btn-outline" id="dashReviewModalCancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="dashReviewSubmitBtn"><i class="fa-solid fa-paper-plane"></i> Submit Review</button>
    </div>
  </div>
</div>
<div class="cart-added-toast" id="dashReviewToast"></div>

<script>
(function(){
'use strict';
const CSRF = <?= json_encode(csrf_token()) ?>;

// Auto-scroll to messages panel if tab=messages
(function() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('tab') === 'messages') {
    const el = document.getElementById('msg-thread');
    if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
  }
})();

function showDashToast(msg, ok=true) {
  const t = document.getElementById('dashReviewToast');
  t.textContent = msg;
  t.style.background = ok ? '#1e3a5f' : '#dc2626';
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'), 3000);
}

const modal = document.getElementById('dashReviewModal');
let reviewRef = '', selectedRating = 0;

function openModal(ref, artist) {
  reviewRef = ref;
  selectedRating = 0;
  document.getElementById('dashReviewRef').value = ref;
  document.getElementById('dashReviewRating').value = 0;
  document.getElementById('dashReviewText').value = '';
  document.getElementById('dashReviewArtistName').textContent = artist || 'the artist';
  document.querySelectorAll('.dash-star-pick-btn').forEach(b => b.style.color = '#d1d5db');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  modal.style.display = 'none';
  document.body.style.overflow = '';
}

document.getElementById('dashReviewModalClose').addEventListener('click', closeModal);
document.getElementById('dashReviewModalCancel').addEventListener('click', closeModal);
modal.addEventListener('click', e => { if(e.target === modal) closeModal(); });

document.querySelectorAll('.dash-star-pick-btn').forEach((btn, i) => {
  btn.addEventListener('mouseenter', () => {
    document.querySelectorAll('.dash-star-pick-btn').forEach((b,j) => b.style.color = j <= i ? '#f59e0b' : '#d1d5db');
  });
  btn.addEventListener('mouseleave', () => {
    document.querySelectorAll('.dash-star-pick-btn').forEach((b,j) => b.style.color = j < selectedRating ? '#f59e0b' : '#d1d5db');
  });
  btn.addEventListener('click', () => {
    selectedRating = i + 1;
    document.getElementById('dashReviewRating').value = selectedRating;
    document.querySelectorAll('.dash-star-pick-btn').forEach((b,j) => b.style.color = j < selectedRating ? '#f59e0b' : '#d1d5db');
  });
});

document.getElementById('dashReviewSubmitBtn').addEventListener('click', async () => { console.log('[Review] CSRF:', CSRF, 'ref:', reviewRef);
  const rating = parseInt(document.getElementById('dashReviewRating').value, 10);
  if (rating < 1) { showDashToast('Please select a star rating.', false); return; }
  const review = document.getElementById('dashReviewText').value.trim();
  try {
    const res = await fetch('/likhub/dashboard.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({csrf: CSRF, action: 'leave_review', ref: reviewRef, rating, review, _ajax: 1})
    });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); } catch(pe) {
      showDashToast('Server error. Please try again.', false);
      console.error('[Review] Raw response (HTTP '+res.status+'):', text.substring(0, 800));
      return;
    }
    console.log('[Review] Parsed JSON:', data); if (data.error) { showDashToast('Error: ' + data.error, false); return; }
    closeModal();
    showDashToast('Review submitted! Thank you ⭐');
    // Update the button to show stars inline
    const btn = document.querySelector('.dash-review-btn[data-ref="'+reviewRef+'"]');
    if (btn) {
      const stars = '★'.repeat(rating) + '☆'.repeat(5-rating);
      const span = document.createElement('span');
      span.className = 'commission-actions__done';
      span.title = 'You rated this ' + rating + '/5';
      span.textContent = stars;
      btn.replaceWith(span);
    }
  } catch(e) { showDashToast('Network error. Please try again.', false); }
});

document.querySelectorAll('.dash-review-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    openModal(btn.dataset.ref, btn.dataset.artist);
  });
});
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
/* ── Dedicated dashboard sidebar drawer — independent of main.js ── */
(function () {
  var hamburger = document.getElementById('dashHamburger');
  var sidebar   = document.getElementById('dashSidebar');
  var backdrop  = document.getElementById('sidebarBackdrop');

  if (!hamburger || !sidebar || !backdrop) return;

  function openSidebar() {
    sidebar.classList.add('open');
    backdrop.classList.add('open');
    hamburger.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    backdrop.classList.remove('open');
    hamburger.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', function (e) {
    e.stopPropagation();
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });

  backdrop.addEventListener('click', closeSidebar);

  sidebar.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', closeSidebar);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
  });
})();
</script>

<script>
(function () {
  const CSRF    = document.querySelector('meta[name="csrf"]')?.content || '';
  const API     = '/likhub/commission.php';

  async function commissionApi(payload) {
    payload.csrf  = CSRF;
    payload._ajax = 1;
    const fd = new FormData();
    Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
    try {
      const r = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
      return r.json();
    } catch (e) {
      return { error: String(e) };
    }
  }

  function flashMsg(msg, ok = true) {
    const div = document.createElement('div');
    div.className = 'alert alert--' + (ok ? 'success' : 'danger');
    div.style.cssText = 'position:fixed;top:70px;left:50%;transform:translateX(-50%);z-index:9999;min-width:280px;text-align:center;padding:12px 20px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.15);';
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3500);
  }

  document.querySelectorAll('.js-commission-action').forEach(btn => {
    btn.addEventListener('click', async function () {
      const confirmMsg = this.dataset.confirm || 'Are you sure?';
      if (!confirm(confirmMsg)) return;

      this.disabled = true;
      const originalHtml = this.innerHTML;
      this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

      const res = await commissionApi({ action: this.dataset.action, ref: this.dataset.ref });

      if (res.ok) {
        flashMsg(
          this.dataset.action === 'client_accept'
            ? 'Commission accepted — the artist will get started! 🎨'
            : 'Payment confirmed — commission complete! 🎉'
        );
        setTimeout(() => location.reload(), 1200);
      } else {
        flashMsg('Error: ' + (res.error || 'Unknown'), false);
        this.disabled = false;
        this.innerHTML = originalHtml;
      }
    });
  });
})();
</script>