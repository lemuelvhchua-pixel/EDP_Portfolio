<?php
$page_title = 'Reset Password';
$active_nav = '';
require_once __DIR__ . '/php/db.php';

if (is_logged_in()) { header('Location: /likhub/dashboard.php'); exit; }

init_password_resets_table();

$token   = trim($_GET['token'] ?? '');
$errors  = [];
$success = false;
$user    = null;

// Validate token on every load
if (!$token) {
    set_flash('error', 'Invalid or missing reset link. Please request a new one.');
    header('Location: /likhub/forgot-password.php'); exit;
}

$stmt = db()->prepare('SELECT pr.*, u.name, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id WHERE pr.token = ? LIMIT 1');
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    set_flash('error', 'This reset link is invalid or has already been used.');
    header('Location: /likhub/forgot-password.php'); exit;
}

if (time() > (int)$reset['expires_at']) {
    db()->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
    set_flash('error', 'This reset link has expired. Please request a new one.');
    header('Location: /likhub/forgot-password.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([$hash, $reset['user_id']]);
            db()->prepare('DELETE FROM password_resets WHERE user_id = ?')
                ->execute([$reset['user_id']]);

            set_flash('success', 'Password reset! You can now log in with your new password.');
            header('Location: /likhub/login.php'); exit;
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="auth-page">
  <div class="auth-box">
    <div class="auth-box__logo">Lik<span>Hub</span></div>
    <div class="auth-box__sub">Choose a new password for <strong><?= e($reset['email']) ?></strong></div>

    <?php if ($errors): ?>
    <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="/likhub/reset-password.php?token=<?= urlencode($token) ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label" for="password">New Password</label>
        <input type="password" name="password" id="password" class="form-control"
          placeholder="Minimum 8 characters" required minlength="8"
          autocomplete="new-password" autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password2">Confirm New Password</label>
        <input type="password" name="password2" id="password2" class="form-control"
          placeholder="Repeat your password" required minlength="8"
          autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">
        <i class="fa-solid fa-lock"></i> Reset Password
      </button>
    </form>

    <div class="auth-divider">or</div>
    <p class="auth-footer-text"><a href="/likhub/login.php">&larr; Back to Log In</a></p>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
