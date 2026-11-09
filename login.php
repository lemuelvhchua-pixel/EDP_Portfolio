<?php
define('PUBLIC_PAGE', true);
require __DIR__ . '/includes/init.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);
if (current_user()) {
    redirect($next);
}

$error = '';
$email = '';
$lockedFor = max(0, ($_SESSION['login_lock'] ?? 0) - time());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($lockedFor > 0) {
        $error = "Too many tries. Wait {$lockedFor} seconds.";
    } elseif ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
            login_user($user);
            flash('notice', 'Welcome back, ' . strtok($user['name'], ' ') . '!');
            redirect($next);
        }

        // same message for wrong email or wrong password
        $error = 'Wrong email or password.';
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) {
            $_SESSION['login_lock'] = time() + 60;
            $_SESSION['login_fails'] = 0;
            $error = 'Too many tries. Wait 60 seconds.';
        }
    }
}

if (is_ajax() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    json_out(['ok' => false, 'errors' => [$error], 'csrf' => csrf_token()], 422);
}

$title = 'Login | Lemuel D. Chua';
$page = 'login';
$bodyClass = 'contact-body-bg';
$crumb = 'LOGIN';
require __DIR__ . '/includes/header.php';
?>
    <main id="main">

        <section class="contact-hero auth-hero page-hero-anim">
            <h1 class="hero-name">Login</h1>
            <p class="hero-role">Welcome back</p>
        </section>

        <section class="auth-wrap">
            <div class="form-terminal auth-card">
                <div class="terminal-bar">
                    <div class="t-dot red"></div>
                    <div class="t-dot yellow"></div>
                    <div class="t-dot green"></div>
                    <span class="terminal-title">login.exe</span>
                </div>

                <div class="form-body">
                    <?php if ($n = flash('notice')): ?>
                        <p class="alert ok" role="status"><?= e($n) ?></p>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <p class="alert err" role="alert"><?= e($error) ?></p>
                    <?php endif; ?>

                    <form method="post" action="login.php" class="php-form" data-kind="login" data-busy="CHECKING..." novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="next" value="<?= e($next) ?>" />

                        <div class="form-group">
                            <label class="form-label" for="email">EMAIL <span>*</span></label>
                            <input class="form-input" type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="you@email.com" autocomplete="email" required autofocus />
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">PASSWORD <span>*</span></label>
                            <div class="pw-wrap">
                                <input class="form-input" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required />
                                <button type="button" class="pw-toggle" aria-label="Show password" data-pw="password">SHOW</button>
                            </div>
                        </div>

                        <button type="submit" class="form-submit">&#9654; LOG IN</button>
                        <?php if (ALLOW_SIGNUP): ?>
                        <p class="auth-switch">No account yet? <a href="register.php<?= $next !== 'index.php' ? '?next=' . urlencode($next) : '' ?>">Create one &#9658;</a></p>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </section>

    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
