<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([current_user()['id']]);
    $hash = (string) $st->fetchColumn();

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        if (!password_verify($current, $hash)) $errors[] = 'Your current password is wrong.';
        if (strlen($new) < 10) $errors[] = 'Use at least 10 characters for the new password.';
        if ($new !== $confirm) $errors[] = 'The new passwords don\'t match.';
        if ($new !== '' && $new === $current) $errors[] = 'Pick a password different from the current one.';
        if ($new === DEFAULT_ADMIN_PASSWORD) $errors[] = 'That is the public default password. Pick a new one.';
    }

    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), current_user()['id']]);
        unset($_SESSION['default_pw']);
        session_regenerate_id(true);
        $done = true;
    }
}

$title = 'Change Password | Lemuel D. Chua';
$page = 'manage';
$bodyClass = 'contact-body-bg';
$crumb = 'CHANGE PASSWORD';
require __DIR__ . '/../includes/header.php';
?>
    <main id="main">
        <section class="auth-wrap">
            <div class="form-terminal auth-card">
                <div class="terminal-bar"><div class="t-dot red"></div><div class="t-dot yellow"></div><div class="t-dot green"></div><span class="terminal-title">password.exe</span></div>
                <div class="form-body">
                    <h2 class="about-section-title">CHANGE PASSWORD</h2>
                    <div class="section-line"></div>

                    <?php if ($done): ?>
                        <p class="alert ok" role="status">Password changed. Use the new one next time you log in.</p>
                        <p class="auth-switch"><a href="portfolio.php">&#9664; Back to Manage</a></p>
                    <?php else: ?>
                        <?php if ($errors): ?>
                            <div class="alert err" role="alert">
                                <?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="account.php" novalidate>
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label class="form-label" for="current">CURRENT PASSWORD <span>*</span></label>
                                <input class="form-input" type="password" id="current" name="current" autocomplete="current-password" required autofocus />
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="password">NEW PASSWORD <span>*</span></label>
                                    <input class="form-input" type="password" id="password" name="password" autocomplete="new-password" minlength="10" placeholder="10+ characters" required />
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="confirm">CONFIRM <span>*</span></label>
                                    <input class="form-input" type="password" id="confirm" name="confirm" autocomplete="new-password" placeholder="Type it again" required />
                                </div>
                            </div>
                            <button type="submit" class="form-submit">&#9654; SAVE PASSWORD</button>
                            <p class="auth-switch"><a href="portfolio.php">&#9664; Back without saving</a></p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
