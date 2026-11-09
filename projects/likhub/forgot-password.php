<?php
$page_title = 'Forgot Password';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/mailer.php';

if (is_logged_in()) { header('Location: /likhub/dashboard.php'); exit; }

init_password_resets_table();

$errors  = [];
$success = false;
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!$email) {
            $errors[] = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!$errors) {
            $stmt = db()->prepare('SELECT id, name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always show success to prevent email enumeration
            if ($user) {
                // Delete any existing tokens for this user
                db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

                $token     = bin2hex(random_bytes(32));
                $expires   = time() + 3600; // 1 hour

                db()->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)')
                    ->execute([$user['id'], $token, $expires]);

                $reset_link = 'https://likhub.online/likhub/reset-password.php?token=' . $token;
                send_reset_email($user['email'], $user['name'], $reset_link);
            }

            $success = true;
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="auth-page">
  <div class="auth-box">
    <div class="auth-box__logo">Lik<span>Hub</span></div>

    <?php if ($success): ?>
      <div class="auth-box__sub" style="margin-bottom:8px;">Check your email</div>
      <p class="u-text-center" style="color:var(--text-muted,#888);margin-top:8px;line-height:1.6;">
        If an account exists for <strong><?= e($email) ?></strong>, we've sent a password reset link.<br>
        The link expires in <strong>1 hour</strong>.
      </p>
      <div style="margin-top:24px;text-align:center;">
        <a href="/likhub/login.php" class="btn btn-primary btn-full">Back to Log In</a>
      </div>
    <?php else: ?>
      <div class="auth-box__sub">Enter your email and we'll send you a reset link.</div>

      <?php if ($errors): ?>
      <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
      <?php endif; ?>

      <form method="POST" action="/likhub/forgot-password.php" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" name="email" id="email" class="form-control"
            value="<?= e($email) ?>" placeholder="you@example.com"
            required autocomplete="email" autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg">
          <i class="fa-solid fa-paper-plane"></i> Send Reset Link
        </button>
      </form>

      <div class="auth-divider">or</div>
      <p class="auth-footer-text"><a href="/likhub/login.php">&larr; Back to Log In</a></p>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
