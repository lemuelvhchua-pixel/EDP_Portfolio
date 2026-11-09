<?php
$page_title = 'Join LikHub — Create Account';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/mailer.php';

if (is_logged_in()) { header('Location: /likhub/dashboard.php'); exit; }

$errors = [];
$vals   = ['name'=>'','email'=>'','role'=>'learner'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token.';
    } else {
        $vals['name']  = trim($_POST['name']  ?? '');
        $vals['email'] = trim($_POST['email'] ?? '');
        $vals['role']  = in_array($_POST['role'] ?? '', ['learner','artist','client']) ? $_POST['role'] : 'learner';
        $pass          = $_POST['password']  ?? '';
        $pass2         = $_POST['password2'] ?? '';

        if (strlen($vals['name'])  < 2)      $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if (strlen($pass) < 8)               $errors[] = 'Password must be at least 8 characters.';
        if ($pass !== $pass2)                $errors[] = 'Passwords do not match.';
        if (empty($_POST['agree_terms']))    $errors[] = 'You must agree to the Terms of Service to continue.';

        if (!$errors) {
            $check = db()->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$vals['email']]);
            if ($check->fetch()) {
                $errors[] = 'An account with that email already exists.';
            }
        }

        if (!$errors) {
            // Store pending registration in session, send OTP
            $otp  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $hash = password_hash($pass, PASSWORD_BCRYPT);

            start_session();
            $_SESSION['reg_otp_code']    = $otp;
            $_SESSION['reg_otp_expires'] = time() + 600;
            $_SESSION['reg_pending']     = [
                'name'  => $vals['name'],
                'email' => $vals['email'],
                'role'  => $vals['role'],
                'hash'  => $hash,
            ];

            $sent = send_otp_email($vals['email'], $vals['name'], $otp, 'register');

            if ($sent) {
                header('Location: /likhub/verify-otp.php?mode=register');
                exit;
            } else {
                $errors[] = 'Could not send verification email. Please try again.';
            }
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<div class="auth-page auth-page--padded">
  <div class="auth-box auth-box--wide">
    <div class="auth-box__logo">Lik<span>Hub</span></div>
    <div class="auth-box__sub">Create your free account and join the community.</div>

    <?php if ($errors): ?>
    <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="/likhub/register.php" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" value="<?= e($vals['name']) ?>" placeholder="Your full name" required autocomplete="name">
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" value="<?= e($vals['email']) ?>" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">I am joining as a…</label>
        <div class="role-picker">
          <?php foreach (['learner'=>'Learner','artist'=>'Artist','client'=>'Client'] as $v=>$l): ?>
          <label class="role-label">
            <input type="radio" name="role" value="<?= $v ?>" <?= $vals['role']===$v?'checked':'' ?> class="role-radio">
            <?= $l ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input type="password" name="password" id="password" class="form-control" placeholder="Min 8 characters" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label" for="password2">Confirm Password</label>
          <input type="password" name="password2" id="password2" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
        </div>
      </div>
      <div class="form-group">
        <label class="u-terms-label form-label terms-check-label">
          <input class="u-terms-checkbox" type="checkbox" name="agree_terms" id="agree_terms" required>
          <span>I have read and agree to the <a class="u-color-navy u-fw-600" href="/likhub/terms.php" target="_blank">Terms of Service</a>. Artwork submitted must be original work, payments are arranged directly with artists, and LikHub is not liable for commission disputes.</span>
        </label>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">Create Account</button>
    </form>

    <div class="auth-divider">or</div>
    <p class="auth-footer-text">Already have an account? <a href="/likhub/login.php">Log In</a></p>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>