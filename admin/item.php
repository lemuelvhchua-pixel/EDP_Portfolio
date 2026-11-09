<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/upload.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM items WHERE id = ?');
$st->execute([$id]);
$item = $st->fetch();
if (!$item) {
    flash('error', "That item doesn't exist anymore.");
    redirect('portfolio.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_check()) throw new RuntimeException('Session expired. Try again.');
        $t = trim($_POST['title'] ?? '');
        if ($t === '' || mb_strlen($t) > 150) throw new RuntimeException('Give it a title (up to 150 characters).');
        $list = array_key_exists($_POST['list_key'] ?? '', LISTS) ? $_POST['list_key'] : $item['list_key'];

        $image = $item['image'];
        $thumb = $item['thumb'];
        $media = $item['media'];
        if ($new = save_upload('image', 'image')) {
            delete_upload($item['image']);
            delete_upload($item['thumb']);
            $image = $new;
            $thumb = $list === 'photography' ? make_thumb($new) : null;
        }
        if ($list === 'video' && ($newVid = save_upload('media', 'video'))) {
            delete_upload($item['media']);
            $media = $newVid;
        }
        $featured = str_starts_with($list, 'certs_') && !empty($_POST['is_featured']) ? 1 : 0;
        if ($featured) db()->prepare('UPDATE items SET is_featured = 0 WHERE list_key = ? AND id <> ?')->execute([$list, $id]);
        if ($list !== $item['list_key']) { // moving to another list: put it at the end
            $item['sort_order'] = 10 + (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM items WHERE list_key = ' . db()->quote($list))->fetchColumn();
        }
        db()->prepare('UPDATE items SET list_key = ?, title = ?, description = ?, image = ?, thumb = ?, media = ?, is_featured = ?, is_visible = ?, sort_order = ? WHERE id = ?')
            ->execute([$list, $t, trim($_POST['description'] ?? ''), $image, $thumb, $media, $featured, empty($_POST['is_visible']) ? 0 : 1, $item['sort_order'], $id]);
        flash('notice', 'Saved "' . $t . '".');
        redirect('portfolio.php?tab=items&list=' . $list);
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
        $item = array_merge($item, ['title' => $_POST['title'] ?? $item['title'], 'description' => $_POST['description'] ?? $item['description']]);
    }
}

$title = 'Edit Item | Lemuel D. Chua';
$page = 'manage';
$bodyClass = 'contact-body-bg';
$crumb = 'EDIT ITEM';
require __DIR__ . '/../includes/header.php';
$isVideo = $item['list_key'] === 'video';
?>
    <main id="main">
        <section class="auth-wrap">
            <div class="form-terminal auth-card setup-card">
                <div class="terminal-bar"><div class="t-dot red"></div><div class="t-dot yellow"></div><div class="t-dot green"></div><span class="terminal-title">edit_item.exe</span></div>
                <div class="form-body">
                    <?php if ($error): ?><p class="alert err" role="alert"><?= e($error) ?></p><?php endif; ?>
                    <form method="post" enctype="multipart/form-data" action="item.php?id=<?= $id ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>" />
                        <img class="mng-preview" id="aPreview" src="../<?= e($item['image']) ?>" alt="Current image" />

                        <div class="form-group">
                            <label class="form-label" for="ftitle">TITLE <span>*</span></label>
                            <input class="form-input" id="ftitle" name="title" value="<?= e($item['title']) ?>" maxlength="150" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="fdesc"><?= $isVideo ? 'TAG (small label)' : 'DESCRIPTION' ?></label>
                            <textarea class="form-textarea" id="fdesc" name="description" rows="4"><?= e($item['description']) ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="aimg">REPLACE <?= $isVideo ? 'POSTER' : 'IMAGE' ?></label>
                                <input class="form-input" type="file" id="aimg" name="image" accept="image/jpeg,image/png,image/webp,image/gif" />
                                <p class="field-hint">Leave empty to keep the current one</p>
                            </div>
                            <?php if ($isVideo): ?>
                            <div class="form-group">
                                <label class="form-label" for="amedia">REPLACE VIDEO</label>
                                <input class="form-input" type="file" id="amedia" name="media" accept="video/mp4" />
                                <p class="field-hint">Now: <?= e(basename((string) $item['media'])) ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label class="form-label" for="flist">LIST</label>
                                <select class="form-input" id="flist" name="list_key">
                                    <?php foreach (LISTS as $k => $l): ?>
                                        <option value="<?= $k ?>" <?= $k === $item['list_key'] ? 'selected' : '' ?>><?= e($l['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <label class="check"><input type="checkbox" name="is_visible" value="1" <?= $item['is_visible'] ? 'checked' : '' ?> /> Show on the site</label>
                        <?php if (str_starts_with($item['list_key'], 'certs_')): ?>
                            <label class="check"><input type="checkbox" name="is_featured" value="1" <?= $item['is_featured'] ? 'checked' : '' ?> /> Learning path certificate (shown big)</label>
                        <?php endif; ?>
                        <button type="submit" class="form-submit">&#9654; SAVE</button>
                        <p class="auth-switch"><a href="portfolio.php?tab=items&amp;list=<?= e($item['list_key']) ?>">&#9664; Back without saving</a></p>
                    </form>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
