<?php
$page_title = 'Log In';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/mailer.php';

if (is_logged_in()) { header('Location: /likhub/dashboard.php'); exit; }

$errors = [];
$email  = '';

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_WINDOW_SECS',  300);
define('LOGIN_LOCKOUT_SECS', 900);

function get_rate_limit_key(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return 'rl_login_' . md5(trim(explode(',', $ip)[0]));
}

function get_rate_limit(): array {
    start_session();
    $key  = get_rate_limit_key();
    $data = $_SESSION[$key] ?? ['attempts' => 0, 'first_at' => 0, 'locked_until' => 0];
    return [$key, $data];
}

function record_failed_attempt(): void {
    [$key, $data] = get_rate_limit();
    $now = time();
    if ($now - $data['first_at'] > LOGIN_WINDOW_SECS) {
        $data = ['attempts' => 0, 'first_at' => $now, 'locked_until' => 0];
    }
    $data['attempts']++;
    if ($data['first_at'] === 0) $data['first_at'] = $now;
    if ($data['attempts'] >= LOGIN_MAX_ATTEMPTS) {
        $data['locked_until'] = $now + LOGIN_LOCKOUT_SECS;
    }
    $_SESSION[$key] = $data;
}

function clear_rate_limit(): void {
    [$key] = get_rate_limit();
    unset($_SESSION[$key]);
}

function check_rate_limit(): ?string {
    [$key, $data] = get_rate_limit();
    $now = time();
    if ($data['first_at'] && ($now - $data['first_at'] > LOGIN_WINDOW_SECS)) {
        unset($_SESSION[$key]);
        return null;
    }
    if ($data['locked_until'] && $now < $data['locked_until']) {
        $mins = ceil(($data['locked_until'] - $now) / 60);
        return "Too many failed attempts. Please try again in {$mins} minute" . ($mins === 1 ? '' : 's') . '.';
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $lockout_msg = check_rate_limit();
        if ($lockout_msg) {
            $errors[] = $lockout_msg;
        } else {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$email)    $errors[] = 'Email is required.';
            if (!$password) $errors[] = 'Password is required.';

            if (!$errors) {
                $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    clear_rate_limit();

                    start_session();
                    $_SESSION['user_id'] = $user['id'];

                    $redirect = $_GET['redirect'] ?? '/likhub/dashboard.php';
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    record_failed_attempt();
                    [$key, $data] = get_rate_limit();
                    $remaining = LOGIN_MAX_ATTEMPTS - $data['attempts'];
                    if ($remaining > 0) {
                        $errors[] = 'Invalid email or password. ' . $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' remaining.';
                    } else {
                        $errors[] = 'Too many failed attempts. Your account is locked for 15 minutes.';
                    }
                }
            }
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="auth-page">
  <div class="auth-box">
    <div class="auth-box__logo">Lik<span>Hub</span></div>
    <div class="auth-box__sub">Welcome back - log in to your account.</div>

    <?php if ($errors): ?>
    <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="/likhub/login.php" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" value="<?= e($email) ?>" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <div style="text-align:right;margin-top:-8px;margin-bottom:16px;">
        <a href="/likhub/forgot-password.php" style="font-size:13px;color:var(--text-muted,#888);">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">Log In</button>
    </form>

    <div class="auth-divider">or</div>
    <p class="auth-footer-text">Don't have an account? <a href="/likhub/register.php">Join Free</a></p>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>