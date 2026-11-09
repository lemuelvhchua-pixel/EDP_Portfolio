<?php
require __DIR__ . '/../includes/init.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/upload.php';
require_admin();

$slug = array_key_exists($_GET['slug'] ?? $_POST['slug'] ?? '', PROJECT_LABELS) ? ($_GET['slug'] ?? $_POST['slug']) : null;
if (!$slug) redirect('portfolio.php?tab=projects');
$p = project($slug);
$isWeb = in_array($slug, ['likhub', 'kabakal'], true);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!csrf_check()) throw new RuntimeException('Session expired. Try again.');
        $t = trim($_POST['title'] ?? '');
        if ($t === '') throw new RuntimeException('A title is needed.');
        $logo = $p['logo'];
        if ($new = save_upload('logo', 'image')) { delete_upload($p['logo']); $logo = $new; }
        // site_url is no longer shown on the site; keep whatever is stored.
        db()->prepare('INSERT INTO projects (slug, title, description, logo, logo_light, meta, features, tech, site_url, code_path, case_study)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), logo = VALUES(logo), logo_light = VALUES(logo_light),
                       meta = VALUES(meta), features = VALUES(features), tech = VALUES(tech), code_path = VALUES(code_path), case_study = VALUES(case_study)')
            ->execute([$slug, mb_substr($t, 0, 100), trim($_POST['description'] ?? ''), $logo, empty($_POST['logo_light']) ? 0 : 1,
                trim($_POST['meta'] ?? ''), trim($_POST['features'] ?? ''), trim($_POST['tech'] ?? ''), $p['site_url'] ?? null,
                preg_replace('/[^a-z0-9_\-]/i', '', $_POST['code_path'] ?? '') ?: null, trim($_POST['case_study'] ?? '')]);
        flash('notice', 'Saved "' . $t . '".');
        redirect('portfolio.php?tab=projects');
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
        $p = array_merge($p, array_intersect_key($_POST, $p));
    }
}

$title = 'Edit ' . PROJECT_LABELS[$slug] . ' | Lemuel D. Chua';
$page = 'manage';
$bodyClass = 'contact-body-bg';
$crumb = 'EDIT PROJECT';
require __DIR__ . '/../includes/header.php';
?>
    <main id="main">
        <section class="auth-wrap">
            <div class="form-terminal auth-card setup-card">
                <div class="terminal-bar"><div class="t-dot red"></div><div class="t-dot yellow"></div><div class="t-dot green"></div><span class="terminal-title"><?= e($slug) ?>.exe</span></div>
                <div class="form-body">
                    <h2 class="about-section-title"><?= e(strtoupper(PROJECT_LABELS[$slug])) ?></h2>
                    <div class="section-line"></div>
                    <?php if ($error): ?><p class="alert err" role="alert"><?= e($error) ?></p><?php endif; ?>
                    <form method="post" enctype="multipart/form-data" action="project.php?slug=<?= e($slug) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="slug" value="<?= e($slug) ?>" />
                        <div class="form-group">
                            <label class="form-label" for="ptitle"><?= str_starts_with($slug, 'certs') ? 'TAB NAME' : 'TITLE' ?> <span>*</span></label>
                            <input class="form-input" id="ptitle" name="title" value="<?= e($p['title']) ?>" maxlength="100" required />
                        </div>
                        <?php if (!str_starts_with($slug, 'certs')): ?>
                        <div class="form-group">
                            <label class="form-label" for="pdesc">DESCRIPTION</label>
                            <textarea class="form-textarea" id="pdesc" name="description" rows="4"><?= e($p['description']) ?></textarea>
                        </div>
                        <?php endif; ?>
                        <?php if ($isWeb): ?>
                        <div class="form-group">
                            <label class="form-label" for="pmeta">DETAILS <span>one per line, like ROLE: Developer</span></label>
                            <textarea class="form-textarea" id="pmeta" name="meta" rows="3"><?= e($p['meta']) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pcase">CASE STUDY <span>one per line: PROBLEM: / APPROACH: / HARDEST PART: / RESULT:</span></label>
                            <textarea class="form-textarea" id="pcase" name="case_study" rows="8"><?= e($p['case_study'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pfeat">FEATURES <span>one per line</span></label>
                            <textarea class="form-textarea" id="pfeat" name="features" rows="5"><?= e($p['features']) ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="ptech">TECH <span>comma separated</span></label>
                                <input class="form-input" id="ptech" name="tech" value="<?= e($p['tech']) ?>" />
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="pcode">CODE FOLDER <span>in projects/</span></label>
                                <input class="form-input" id="pcode" name="code_path" value="<?= e($p['code_path']) ?>" placeholder="likhub" />
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="aimg">REPLACE LOGO</label>
                                <input class="form-input" type="file" id="aimg" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" />
                            </div>
                            <div class="form-group">
                                <?php if ($p['logo']): ?><img class="mng-preview logo-prev<?= $p['logo_light'] ? ' light' : '' ?>" id="aPreview" src="../<?= e($p['logo']) ?>" alt="Current logo" /><?php endif; ?>
                                <label class="check"><input type="checkbox" name="logo_light" value="1" <?= $p['logo_light'] ? 'checked' : '' ?> /> Put the logo on a white box</label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="form-submit">&#9654; SAVE</button>
                        <p class="auth-switch"><a href="portfolio.php?tab=projects">&#9664; Back without saving</a></p>
                    </form>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
