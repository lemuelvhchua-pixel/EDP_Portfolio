<?php
require __DIR__ . '/../includes/init.php';
require_admin();

function inbox_counts(): array
{
    $c = db()->query('SELECT COUNT(*) AS total, COALESCE(SUM(is_read = 0), 0) AS unread FROM messages')->fetch();
    return [
        'total'  => (int) $c['total'],
        'unread' => (int) $c['unread'],
        'users'  => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    ];
}

function inbox_messages(string $filter): array
{
    $sql = 'SELECT m.*, u.name AS account_name FROM messages m LEFT JOIN users u ON u.id = m.user_id'
         . ($filter === 'unread' ? ' WHERE m.is_read = 0' : '') . ' ORDER BY m.created_at DESC, m.id DESC';
    return db()->query($sql)->fetchAll();
}

// One message row. Used for the first page load and for live updates.
function render_message(array $m, string $filter): string
{
    $subjectLine = $m['subject'] !== '' ? $m['subject'] : mb_strimwidth($m['message'], 0, 60, '...');
    $reply = 'mailto:' . e($m['email']) . '?subject=' . rawurlencode('Re: ' . ($m['subject'] ?: 'Your message'));
    ob_start(); ?>
                        <details class="msg<?= $m['is_read'] ? '' : ' unread' ?>" data-id="<?= (int) $m['id'] ?>">
                            <summary>
                                <span class="msg-dot" aria-label="<?= $m['is_read'] ? 'Read' : 'Unread' ?>"></span>
                                <span class="msg-from"><?= e($m['name']) ?></span>
                                <span class="msg-subject"><?= e($subjectLine) ?></span>
                                <time class="msg-date" datetime="<?= e($m['created_at']) ?>"><?= e(date('M j, Y g:i A', strtotime($m['created_at']))) ?></time>
                            </summary>
                            <div class="msg-body">
                                <p class="msg-meta">
                                    FROM <a href="<?= $reply ?>"><?= e($m['email']) ?></a>
                                    <?= $m['account_name'] ? ' · ACCOUNT: ' . e($m['account_name']) : ' · GUEST' ?>
                                </p>
                                <p class="msg-text"><?= nl2br(e($m['message'])) ?></p>
                                <div class="msg-actions">
                                    <a class="btn" href="<?= $reply ?>">REPLY &#9658;</a>
                                    <form method="post" action="messages.php" data-ajax="inbox">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>" />
                                        <input type="hidden" name="filter" value="<?= e($filter) ?>" />
                                        <button type="submit" class="btn" name="action" value="<?= $m['is_read'] ? 'unread' : 'read' ?>"><?= $m['is_read'] ? 'MARK UNREAD' : 'MARK READ' ?></button>
                                        <button type="submit" class="btn btn-danger" name="action" value="delete" data-confirm="Delete this message from <?= e($m['name']) ?>? This can't be undone.">DELETE</button>
                                    </form>
                                </div>
                            </div>
                        </details>
<?php
    return ob_get_clean();
}

// ----- live feed for the widget: new messages since the last one the page has -----
if (isset($_GET['feed'])) {
    $filter = ($_GET['filter'] ?? '') === 'unread' ? 'unread' : 'all';
    $since = (int) ($_GET['since'] ?? 0);
    $st = db()->prepare('SELECT m.*, u.name AS account_name FROM messages m LEFT JOIN users u ON u.id = m.user_id
                         WHERE m.id > ?' . ($filter === 'unread' ? ' AND m.is_read = 0' : '') . ' ORDER BY m.id ASC');
    $st->execute([$since]);
    $new = $st->fetchAll();
    json_out([
        'ok'     => true,
        'counts' => inbox_counts(),
        'new'    => array_map(fn($m) => ['id' => (int) $m['id'], 'html' => render_message($m, $filter)], $new),
    ]);
}

// ----- actions (POST only, with CSRF) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        if (is_ajax()) json_out(['ok' => false, 'errors' => ['Session expired, reload the page.']], 419);
        flash('notice', 'Session expired, try again.');
        redirect('messages.php');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notice = null;
    switch ($action) {
        case 'read':
            db()->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
            break;
        case 'unread':
            db()->prepare('UPDATE messages SET is_read = 0 WHERE id = ?')->execute([$id]);
            break;
        case 'delete':
            db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            $notice = 'Message deleted.';
            break;
        case 'read_all':
            db()->exec('UPDATE messages SET is_read = 1');
            $notice = 'All messages marked as read.';
            break;
    }
    if (is_ajax()) {
        json_out(['ok' => true, 'action' => $action, 'id' => $id, 'counts' => inbox_counts(), 'notice' => $notice]);
    }
    if ($notice) flash('notice', $notice);
    redirect('messages.php' . (($_POST['filter'] ?? '') === 'unread' ? '?filter=unread' : ''));
}

$filter = ($_GET['filter'] ?? '') === 'unread' ? 'unread' : 'all';
$messages = inbox_messages($filter);
$counts = inbox_counts();
$latest = (int) db()->query('SELECT COALESCE(MAX(id), 0) FROM messages')->fetchColumn();

$title = 'Inbox | Lemuel D. Chua';
$page = 'admin';
$bodyClass = 'contact-body-bg';
$crumb = 'INBOX';
require __DIR__ . '/../includes/header.php';
?>
    <main id="main">

        <section class="contact-hero page-hero-anim">
            <h1 class="hero-name">Inbox</h1>
            <p class="hero-role">Messages from the contact form</p>
        </section>

        <section class="contact-wrapper" id="inbox" data-filter="<?= e($filter) ?>" data-latest="<?= $latest ?>">
            <p class="alert ok" role="status" id="inboxNotice"<?= ($n = flash('notice')) ? '' : ' hidden' ?>><?= e($n ?? '') ?></p>

            <div class="stats-strip admin-stats">
                <div class="stat-item"><p class="stat-num" data-stat="total"><?= $counts['total'] ?></p><p class="stat-label">MESSAGES</p></div>
                <div class="stat-item"><p class="stat-num" data-stat="unread"><?= $counts['unread'] ?></p><p class="stat-label">UNREAD</p></div>
                <div class="stat-item"><p class="stat-num" data-stat="users"><?= $counts['users'] ?></p><p class="stat-label">ACCOUNTS</p></div>
            </div>

            <div class="inbox-bar">
                <div class="cert-tabs" role="navigation" aria-label="Filter messages">
                    <a class="cert-tab" href="messages.php" aria-selected="<?= $filter === 'all' ? 'true' : 'false' ?>">ALL<span class="n" data-stat="total"><?= $counts['total'] ?></span></a>
                    <a class="cert-tab" href="messages.php?filter=unread" aria-selected="<?= $filter === 'unread' ? 'true' : 'false' ?>">UNREAD<span class="n" data-stat="unread"><?= $counts['unread'] ?></span></a>
                </div>
                <div class="inbox-tools">
                    <span class="live-pill" title="New messages show up here on their own"><span class="live-dot"></span>LIVE</span>
                    <form method="post" action="messages.php" data-ajax="inbox" id="readAllForm"<?= $counts['unread'] ? '' : ' hidden' ?>>
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="read_all" />
                        <button type="submit" class="btn">MARK ALL READ</button>
                    </form>
                </div>
            </div>

            <div class="empty-box" id="inboxEmpty"<?= $messages ? ' hidden' : '' ?>>
                <p class="success-icon">[ ]</p>
                <p><?= $filter === 'unread' ? 'NO UNREAD MESSAGES.' : 'NO MESSAGES YET.' ?></p>
                <p class="muted">When someone uses the contact form, it shows up here. No need to refresh.</p>
            </div>

            <div class="inbox" id="inboxList">
<?php foreach ($messages as $m) echo render_message($m, $filter); ?>
            </div>
        </section>

    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
