<?php
$page_title = 'Verify Your Identity';
$active_nav = '';
require_once __DIR__ . '/php/db.php';

if (is_logged_in()) { header('Location: /likhub/dashboard.php'); exit; }

start_session();

$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'

// Guard: must have a pending session
if ($mode === 'register' && empty($_SESSION['reg_pending'])) {
    header('Location: /likhub/register.php'); exit;
}
if ($mode === 'login' && empty($_SESSION['otp_user_id'])) {
    header('Location: /likhub/login.php'); exit;
}

$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $entered = trim($_POST['otp_code'] ?? '');

        if (!$entered) {
            $errors[] = 'Please enter the verification code.';
        } elseif ($mode === 'register') {
            // --- REGISTER FLOW ---
            if (time() > ($_SESSION['reg_otp_expires'] ?? 0)) {
                unset($_SESSION['reg_otp_code'], $_SESSION['reg_otp_expires'], $_SESSION['reg_pending']);
                $errors[] = 'Your code has expired. Please register again.';
            } elseif (!hash_equals((string)$_SESSION['reg_otp_code'], $entered)) {
                $errors[] = 'Incorrect code. Please try again.';
            } else {
                $p = $_SESSION['reg_pending'];
                unset($_SESSION['reg_otp_code'], $_SESSION['reg_otp_expires'], $_SESSION['reg_pending']);

                // Create the account
                $ins = db()->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?,?,?,?,1)');
                $ins->execute([$p['name'], $p['email'], $p['hash'], $p['role']]);
                $uid = db()->lastInsertId();

                if ($p['role'] === 'artist') {
                    init_artist_profiles_table();
                    db()->prepare('INSERT IGNORE INTO artist_profiles (user_id) VALUES (?)')->execute([$uid]);
                }

                $_SESSION['user_id'] = $uid;
                session_regenerate_id(true);
                set_flash('success', 'Welcome to LikHub, ' . e($p['name']) . '! 🎉 Complete your profile to get started.');
                $redirect = $p['role'] === 'artist' ? '/likhub/edit-profile.php' : '/likhub/dashboard.php';
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            // --- LOGIN FLOW ---
            if (time() > ($_SESSION['otp_expires'] ?? 0)) {
                unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_user_id'], $_SESSION['otp_redirect']);
                $errors[] = 'Your code has expired. Please log in again.';
            } elseif (!hash_equals((string)$_SESSION['otp_code'], $entered)) {
                $errors[] = 'Incorrect code. Please try again.';
            } else {
                $uid      = $_SESSION['otp_user_id'];
                $redirect = $_SESSION['otp_redirect'] ?? '/likhub/dashboard.php';
                unset($_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_user_id'], $_SESSION['otp_redirect']);
                $_SESSION['user_id'] = $uid;
                session_regenerate_id(true);
                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

function mask_email(string $email): string {
    [$local, $domain] = explode('@', $email, 2);
    $visible = substr($local, 0, min(2, strlen($local)));
    return $visible . str_repeat('*', max(0, strlen($local) - 2)) . '@' . $domain;
}

// Get masked email to display
if ($mode === 'register') {
    $raw_email = $_SESSION['reg_pending']['email'] ?? '';
} else {
    $stmt = db()->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['otp_user_id']]);
    $row = $stmt->fetch();
    $raw_email = $row['email'] ?? '';
}
$masked = $raw_email ? mask_email($raw_email) : 'your email';

include __DIR__ . '/partials/header.php';
?>

<div class="auth-page">
  <div class="auth-box">
    <div class="auth-box__logo">Lik<span>Hub</span></div>
    <div class="u-mb-0 auth-box__sub">Check your email</div>
    <p class="u-text-center" style="color:var(--text-muted,#888);margin-top:8px">
      We sent a 6-digit code to <strong><?= e($masked) ?></strong>.<br>
      It expires in <strong>10 minutes</strong>.
    </p>

    <?php if ($errors): ?>
    <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="POST" action="/likhub/verify-otp.php?mode=<?= e($mode) ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label class="form-label" for="otp_code">Verification Code</label>
        <input
          type="text"
          name="otp_code"
          id="otp_code"
          class="u-otp-input form-control"
          placeholder="000000"
          maxlength="6"
          pattern="\d{6}"
          inputmode="numeric"
          autocomplete="one-time-code"
          autofocus
          required>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">
        <?= $mode === 'register' ? 'Verify &amp; Create Account' : 'Verify &amp; Log In' ?>
      </button>
    </form>

    <div class="auth-divider">or</div>
    <p class="auth-footer-text">
      Didn't get a code? <a href="/likhub/<?= $mode === 'register' ? 'register' : 'login' ?>.php">Go back and try again</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>