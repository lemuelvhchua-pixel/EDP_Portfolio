<?php
define('PUBLIC_PAGE', true);
require __DIR__ . '/includes/init.php';

// Live widget: is this email free? (called while typing on the sign up form)
if (isset($_GET['check_email'])) {
    $em = trim((string) $_GET['check_email']);
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        json_out(['valid' => false, 'available' => false]);
    }
    $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
    $st->execute([$em]);
    json_out(['valid' => true, 'available' => !$st->fetchColumn()]);
}

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);
if (current_user()) {
    redirect($next);
}

// Sign-ups are closed (see ALLOW_SIGNUP in config.php).
if (!ALLOW_SIGNUP) {
    if (is_ajax()) {
        json_out(['ok' => false, 'errors' => ['Sign-ups are closed.']], 403);
    }
    flash('notice', 'Sign-ups are closed. Use the contact form to reach me.');
    redirect('login.php');
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Enter your name (up to 80 characters).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'Enter a valid email.';
        if (strlen($password) < 8) $errors[] = 'Password needs at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords don\'t match.';

        if (!$errors) {
            $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetchColumn()) {
                $errors[] = 'That email already has an account. Try logging in.';
            }
        }

        if (!$errors) {
            // Sign-ups are always normal accounts. The admin comes from database.sql.
            db()->prepare('INSERT INTO users (name, email, password_hash, role, last_login) VALUES (?, ?, ?, ?, NOW())')
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'user']);
            login_user(['id' => db()->lastInsertId()]);
            flash('notice', 'Account created. Welcome, ' . strtok($name, ' ') . '!');
            redirect($next);
        }
    }
}

if (is_ajax() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    json_out(['ok' => false, 'errors' => $errors], 422);
}

$title = 'Create Account | Lemuel D. Chua';
$page = 'register';
$bodyClass = 'contact-body-bg';
$crumb = 'CREATE ACCOUNT';
require __DIR__ . '/includes/header.php';
?>
    <main id="main">

        <section class="contact-hero auth-hero page-hero-anim">
            <h1 class="hero-name">Sign Up</h1>
            <p class="hero-role">Create an account</p>
        </section>

        <section class="auth-wrap">
            <div class="form-terminal auth-card">
                <div class="terminal-bar">
                    <div class="t-dot red"></div>
                    <div class="t-dot yellow"></div>
                    <div class="t-dot green"></div>
                    <span class="terminal-title">register.exe</span>
                </div>

                <div class="form-body">
                    <?php if ($errors): ?>
                        <div class="alert err" role="alert">
                            <?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="register.php" class="php-form" data-kind="register" data-busy="CREATING..." novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="next" value="<?= e($next) ?>" />

                        <div class="form-group">
                            <label class="form-label" for="name">NAME <span>*</span></label>
                            <input class="form-input" type="text" id="name" name="name" value="<?= e($name) ?>" placeholder="Your name" autocomplete="name" maxlength="80" required autofocus />
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">EMAIL <span>*</span></label>
                            <input class="form-input" type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="you@email.com" autocomplete="email" maxlength="190" required />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="password">PASSWORD <span>*</span></label>
                                <div class="pw-wrap">
                                    <input class="form-input" type="password" id="password" name="password" placeholder="8+ characters" autocomplete="new-password" minlength="8" required />
                                    <button type="button" class="pw-toggle" aria-label="Show password" data-pw="password">SHOW</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="confirm">CONFIRM <span>*</span></label>
                                <input class="form-input" type="password" id="confirm" name="confirm" placeholder="Type it again" autocomplete="new-password" required />
                            </div>
                        </div>
                        <div class="pw-meter" aria-hidden="true"><span></span></div>

                        <button type="submit" class="form-submit">&#9654; CREATE ACCOUNT</button>
                        <p class="auth-switch">Already have one? <a href="login.php<?= $next !== 'index.php' ? '?next=' . urlencode($next) : '' ?>">Log in &#9658;</a></p>
                    </form>
                </div>
            </div>
        </section>

    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
