<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/upload.php';
require_admin();

$tab = in_array($_GET['tab'] ?? '', ['items', 'sections', 'projects'], true) ? $_GET['tab'] : 'items';
$list = array_key_exists($_GET['list'] ?? '', LISTS) ? $_GET['list'] : 'photography';

function back(string $tab, string $list): string
{
    return 'portfolio.php?tab=' . $tab . ($tab === 'items' ? '&list=' . $list : '');
}

// Swap an item/section with its neighbour to move it up or down.
function move_row(string $table, string $keyCol, $keyVal, ?string $groupCol = null, $groupVal = null, string $dir = 'up'): void
{
    $where = $groupCol ? "WHERE $groupCol = ?" : '';
    $st = db()->prepare("SELECT $keyCol AS k, sort_order FROM $table $where ORDER BY sort_order, $keyCol");
    $st->execute($groupCol ? [$groupVal] : []);
    $rows = $st->fetchAll();
    foreach ($rows as $i => $r) {                       // renumber 10, 20, 30... first
        $rows[$i]['sort_order'] = ($i + 1) * 10;
    }
    $idx = array_search((string) $keyVal, array_map('strval', array_column($rows, 'k')), true);
    $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
    if ($idx !== false && isset($rows[$swap])) {
        [$rows[$idx]['sort_order'], $rows[$swap]['sort_order']] = [$rows[$swap]['sort_order'], $rows[$idx]['sort_order']];
    }
    $up = db()->prepare("UPDATE $table SET sort_order = ? WHERE $keyCol = ?");
    foreach ($rows as $r) $up->execute([$r['sort_order'], $r['k']]);
}

// ---------- the item list (also sent back after AJAX actions) ----------
function render_rows(string $list): string
{
    $rows = items($list, false);
    $isCert = str_starts_with($list, 'certs_');
    ob_start();
    if (!$rows): ?>
        <div class="empty-box"><p>NOTHING HERE YET.</p><p class="muted">Add the first one with the form below.</p></div>
    <?php endif;
    foreach ($rows as $n => $it): ?>
        <div class="mng-row<?= $it['is_visible'] ? '' : ' is-hidden' ?>" data-id="<?= (int) $it['id'] ?>">
            <img class="mng-thumb" src="../<?= e($it['thumb'] ?: $it['image']) ?>" alt="" loading="lazy" />
            <div class="mng-info">
                <p class="mng-title"><?= e($it['title']) ?>
                    <?php if (!$it['is_visible']): ?><span class="mng-badge">HIDDEN</span><?php endif; ?>
                    <?php if ($it['is_featured']): ?><span class="mng-badge pink">PATH CERT</span><?php endif; ?>
                    <?php if ($it['media']): ?><span class="mng-badge">VIDEO</span><?php endif; ?>
                </p>
                <p class="mng-desc"><?= e(mb_strimwidth((string) $it['description'], 0, 110, '...')) ?></p>
            </div>
            <form method="post" class="mng-actions" data-ajax="mng">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $it['id'] ?>" />
                <input type="hidden" name="list" value="<?= e($list) ?>" />
                <button class="mini" name="action" value="item_up" title="Move up" <?= $n === 0 ? 'disabled' : '' ?>>&#9650;</button>
                <button class="mini" name="action" value="item_down" title="Move down" <?= $n === count($rows) - 1 ? 'disabled' : '' ?>>&#9660;</button>
                <button class="mini" name="action" value="item_toggle" title="<?= $it['is_visible'] ? 'Hide from the site' : 'Show on the site' ?>"><?= $it['is_visible'] ? 'HIDE' : 'SHOW' ?></button>
                <?php if ($isCert && !$it['is_featured']): ?>
                    <button class="mini" name="action" value="item_feature" title="Make this the big learning path certificate">&#9733;</button>
                <?php endif; ?>
                <a class="mini" href="item.php?id=<?= (int) $it['id'] ?>">EDIT</a>
                <button class="mini danger" name="action" value="item_delete" data-confirm="Delete &quot;<?= e($it['title']) ?>&quot;? This can't be undone.">DELETE</button>
            </form>
        </div>
    <?php endforeach;
    return ob_get_clean();
}

// ---------- actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postList = array_key_exists($_POST['list'] ?? '', LISTS) ? $_POST['list'] : $list;
    $postTab = $_POST['tab'] ?? $tab;
    $notice = null;
    try {
        if (!csrf_check()) throw new RuntimeException('Session expired. Reload the page and try again.');
        $id = (int) ($_POST['id'] ?? 0);

        switch ($action) {
            case 'item_add':
                $t = trim($_POST['title'] ?? '');
                if ($t === '' || mb_strlen($t) > 150) throw new RuntimeException('Give it a title (up to 150 characters).');
                $img = save_upload('image', 'image');
                if (!$img) throw new RuntimeException('Choose an image to upload.');
                $media = null;
                if ($postList === 'video') {
                    $ytId = youtube_id($_POST['media_url'] ?? '');
                    if (!$ytId) { delete_upload($img); throw new RuntimeException('Paste a valid YouTube link (for example https://youtu.be/VvwvFlpmBM4).'); }
                    $media = 'https://www.youtube.com/embed/' . $ytId;
                }
                $thumb = $postList === 'photography' ? make_thumb($img) : null;
                $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM items WHERE list_key = ' . db()->quote($postList))->fetchColumn();
                $featured = str_starts_with($postList, 'certs_') && !empty($_POST['is_featured']) ? 1 : 0;
                if ($featured) db()->prepare('UPDATE items SET is_featured = 0 WHERE list_key = ?')->execute([$postList]);
                db()->prepare('INSERT INTO items (list_key, title, description, image, thumb, media, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$postList, $t, trim($_POST['description'] ?? ''), $img, $thumb, $media, $featured, $max + 10]);
                $notice = 'Added "' . $t . '".';
                break;
            case 'item_up':
            case 'item_down':
                move_row('items', 'id', $id, 'list_key', $postList, $action === 'item_up' ? 'up' : 'down');
                break;
            case 'item_toggle':
                db()->prepare('UPDATE items SET is_visible = 1 - is_visible WHERE id = ?')->execute([$id]);
                break;
            case 'item_feature':
                db()->prepare('UPDATE items SET is_featured = (id = ?) WHERE list_key = ?')->execute([$id, $postList]);
                break;
            case 'item_delete':
                $st = db()->prepare('SELECT image, thumb, media FROM items WHERE id = ?');
                $st->execute([$id]);
                if ($row = $st->fetch()) {
                    db()->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
                    array_map('delete_upload', [$row['image'], $row['thumb'], $row['media']]);
                    $notice = 'Deleted.';
                }
                break;
            case 'section_save':
                $key = $_POST['key'] ?? '';
                $title = trim($_POST['title'] ?? '');
                $nav = trim($_POST['nav_label'] ?? '');
                if ($title === '' || $nav === '') throw new RuntimeException('Sections need a title and a menu label.');
                db()->prepare('UPDATE sections SET title = ?, nav_label = ?, intro = ?, is_visible = ? WHERE section_key = ?')
                    ->execute([mb_substr($title, 0, 100), mb_substr($nav, 0, 40), trim($_POST['intro'] ?? '') ?: null, empty($_POST['is_visible']) ? 0 : 1, $key]);
                $notice = 'Saved "' . $title . '".';
                break;
            case 'section_up':
            case 'section_down':
                move_row('sections', 'section_key', $_POST['key'] ?? '', null, null, $action === 'section_up' ? 'up' : 'down');
                break;
            default:
                throw new RuntimeException('Unknown action.');
        }
        if (is_ajax()) json_out(['ok' => true, 'html' => str_starts_with($action, 'item_') ? render_rows($postList) : null, 'notice' => $notice]);
        if ($notice) flash('notice', $notice);
    } catch (RuntimeException $ex) {
        if (is_ajax()) json_out(['ok' => false, 'errors' => [$ex->getMessage()]], 422);
        flash('error', $ex->getMessage());
    }
    redirect(back($postTab, $postList));
}

$title = 'Manage Portfolio | Lemuel D. Chua';
$page = 'manage';
$bodyClass = 'contact-body-bg';
$crumb = 'MANAGE PORTFOLIO';
require __DIR__ . '/../includes/header.php';
$counts = db()->query('SELECT list_key, COUNT(*) n FROM items GROUP BY list_key')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
    <main id="main">
        <section class="contact-hero page-hero-anim">
            <h1 class="hero-name">Manage</h1>
            <p class="hero-role">Everything on the portfolio page comes from here</p>
        </section>

        <section class="contact-wrapper">
            <?php if ($n = flash('notice')): ?><p class="alert ok" role="status"><?= e($n) ?></p><?php endif; ?>
            <?php if ($er = flash('error')): ?><p class="alert err" role="alert"><?= e($er) ?></p><?php endif; ?>
            <p class="alert ok" id="mngNotice" role="status" hidden></p>

            <div class="cert-tabs" role="navigation" aria-label="Manage">
                <a class="cert-tab" href="portfolio.php?tab=items" aria-selected="<?= $tab === 'items' ? 'true' : 'false' ?>">ITEMS</a>
                <a class="cert-tab" href="portfolio.php?tab=sections" aria-selected="<?= $tab === 'sections' ? 'true' : 'false' ?>">SECTIONS</a>
                <a class="cert-tab" href="portfolio.php?tab=projects" aria-selected="<?= $tab === 'projects' ? 'true' : 'false' ?>">PROJECTS</a>
                <a class="cert-tab" href="account.php">PASSWORD</a>
                <a class="cert-tab" href="../portfolio.php" target="_blank">VIEW SITE &#8599;</a>
            </div>

<?php if ($tab === 'items'): ?>
            <div class="mng-lists">
                <?php foreach (LISTS as $k => $l): ?>
                    <a href="portfolio.php?tab=items&amp;list=<?= $k ?>" class="chip<?= $k === $list ? ' on' : '' ?>"><?= e(strtoupper($l['label'])) ?> <span><?= (int) ($counts[$k] ?? 0) ?></span></a>
                <?php endforeach; ?>
            </div>

            <h2 class="about-section-title"><?= e(strtoupper(LISTS[$list]['label'])) ?></h2>
            <div class="section-line"></div>
            <div class="mng-list" id="mngList"><?= render_rows($list) ?></div>

            <div class="form-terminal mng-add">
                <div class="terminal-bar"><div class="t-dot red"></div><div class="t-dot yellow"></div><div class="t-dot green"></div><span class="terminal-title">add_item.exe</span></div>
                <div class="form-body">
                    <form method="post" enctype="multipart/form-data" action="portfolio.php?tab=items&amp;list=<?= e($list) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="item_add" />
                        <input type="hidden" name="list" value="<?= e($list) ?>" />
                        <div class="form-group">
                            <label class="form-label" for="atitle">TITLE <span>*</span></label>
                            <input class="form-input" id="atitle" name="title" maxlength="150" required placeholder="<?= $list === 'video' ? 'Group Video / Estranghero' : 'e.g. SUNSET' ?>" />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="adesc"><?= $list === 'video' ? 'TAG (small label)' : 'DESCRIPTION' ?></label>
                            <textarea class="form-textarea" id="adesc" name="description" rows="3" placeholder="<?= $list === 'video' ? 'FEATURED' : "It's a picture I took while..." ?>"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="aimg"><?= $list === 'video' ? 'POSTER IMAGE' : 'IMAGE' ?> <span>*</span></label>
                                <input class="form-input" type="file" id="aimg" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required />
                                <p class="field-hint">JPG, PNG, WEBP or GIF, up to 8 MB</p>
                            </div>
                            <?php if ($list === 'video'): ?>
                            <div class="form-group">
                                <label class="form-label" for="amedia">YOUTUBE LINK <span>*</span></label>
                                <input class="form-input" type="url" id="amedia" name="media_url" placeholder="https://youtu.be/VvwvFlpmBM4" required />
                                <p class="field-hint">Upload the video to YouTube, then paste its link here</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (str_starts_with($list, 'certs_')): ?>
                            <label class="check"><input type="checkbox" name="is_featured" value="1" /> This is the learning path certificate (shown big)</label>
                        <?php endif; ?>
                        <img class="mng-preview" id="aPreview" alt="" hidden />
                        <button type="submit" class="form-submit">&#9654; ADD TO <?= e(strtoupper(LISTS[$list]['label'])) ?></button>
                    </form>
                </div>
            </div>

<?php elseif ($tab === 'sections'): ?>
            <p class="section-intro">[ The order here is the order on the portfolio page. Hidden sections don't show up at all. ]</p>
            <div class="mng-sections">
            <?php $all = sections(false); foreach ($all as $n => $s): ?>
                <form method="post" class="mng-section<?= $s['is_visible'] ? '' : ' is-hidden' ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tab" value="sections" />
                    <input type="hidden" name="key" value="<?= e($s['section_key']) ?>" />
                    <div class="mng-sec-head">
                        <span class="section-num"><?= sprintf('%02d', $n + 1) ?></span>
                        <span class="mng-kind"><?= e(strtoupper($s['kind'])) ?></span>
                        <span class="mng-sec-move">
                            <button class="mini" name="action" value="section_up" <?= $n === 0 ? 'disabled' : '' ?> formnovalidate>&#9650;</button>
                            <button class="mini" name="action" value="section_down" <?= $n === count($all) - 1 ? 'disabled' : '' ?> formnovalidate>&#9660;</button>
                        </span>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">TITLE</label><input class="form-input" name="title" value="<?= e($s['title']) ?>" maxlength="100" required /></div>
                        <div class="form-group"><label class="form-label">MENU LABEL</label><input class="form-input" name="nav_label" value="<?= e($s['nav_label']) ?>" maxlength="40" required /></div>
                    </div>
                    <div class="form-group"><label class="form-label">INTRO TEXT</label><textarea class="form-textarea" name="intro" rows="2"><?= e($s['intro']) ?></textarea></div>
                    <div class="mng-sec-foot">
                        <label class="check"><input type="checkbox" name="is_visible" value="1" <?= $s['is_visible'] ? 'checked' : '' ?> /> Show on the site</label>
                        <button class="btn" name="action" value="section_save">SAVE</button>
                    </div>
                </form>
            <?php endforeach; ?>
            </div>

<?php else: ?>
            <p class="section-intro">[ Write-ups for the web projects, the Figma projects and the certificate tabs. ]</p>
            <div class="services">
            <?php foreach (PROJECT_LABELS as $slug => $label): $p = project($slug); ?>
                <div class="service">
                    <span class="service-icon" aria-hidden="true"><?= str_starts_with($slug, 'certs') ? '&#9733;' : (in_array($slug, ['likhub', 'kabakal'], true) ? '&lt;/&gt;' : '&#9647;') ?></span>
                    <h3><?= e(strtoupper($label)) ?></h3>
                    <p><?= e(mb_strimwidth((string) ($p['description'] ?: $p['title']), 0, 90, '...')) ?></p>
                    <a href="project.php?slug=<?= e($slug) ?>">EDIT &#9658;</a>
                </div>
            <?php endforeach; ?>
            </div>
<?php endif; ?>
        </section>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
