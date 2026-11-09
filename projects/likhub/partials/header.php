<?php

require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/messages.php';
start_session();
$flash = get_flash();
$user  = current_user();
$title = isset($page_title) ? e($page_title) . ' LikHub' : 'LikHub';
$nav   = $active_nav ?? '';

$unread_msgs = 0;
if ($user && in_array($user['role'], ['client', 'artist'])) {
    $unread_msgs = unread_count_for_user($user);
}

$impersonating = isset($_SESSION['admin_origin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf" content="<?= csrf_token() ?>">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="/likhub/css/style.css"> 
  <link rel="icon" type="image/x-icon" href="/likhub/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php
// LOCAL DEMO: show the code that would have been emailed
if (session_status() === PHP_SESSION_NONE) session_start();
$__demo = $_SESSION['local_demo_mail'] ?? null;
if ($__demo && time() - $__demo['at'] < 600): ?>
<div style="position:sticky;top:0;z-index:9999;background:#1a1a2e;color:#fff;font:14px/1.5 sans-serif;padding:10px 16px;text-align:center;border-bottom:3px solid #e63946">
  <strong style="color:#e63946">LOCAL DEMO</strong> &middot; no email is sent on this copy. <?= htmlspecialchars($__demo['label']) ?>:
  <?php if (str_starts_with($__demo['value'], 'http')): ?>
    <a href="<?= htmlspecialchars($__demo['value']) ?>" style="color:#ffd166">open link</a>
  <?php else: ?>
    <strong style="letter-spacing:4px;font-size:18px;color:#ffd166"><?= htmlspecialchars($__demo['value']) ?></strong>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($impersonating): ?>

<div class="impersonate-banner">
  <i class="fa-solid fa-user-secret"></i>
  You are viewing as <strong><?= e($user['name'] ?? 'Unknown') ?></strong> (<?= e($user['role'] ?? '') ?>).
  <a href="/likhub/exit-impersonate.php" class="impersonate-banner__exit">
    <i class="fa-solid fa-arrow-left"></i> Return to Admin
  </a>
</div>
<?php endif; ?>

<nav class="navbar" role="navigation" aria-label="Main navigation">
  <div class="container">
    <div class="navbar__inner">
      <a href="/likhub/index.php" class="navbar__logo">
        <img src="/likhub/images/logo-cropped.png" alt="LikHub" class="navbar__logo-img">
      </a>
      <div class="navbar__links">
        <a href="/likhub/index.php"      class="<?= $nav === 'home'       ? 'active' : '' ?>">Home</a>
        <a href="/likhub/learn.php"      class="<?= $nav === 'learn'      ? 'active' : '' ?>">Learn</a>
        <a href="/likhub/create.php"     class="<?= $nav === 'create'     ? 'active' : '' ?>">Create</a>
        <a href="/likhub/commission.php" class="<?= $nav === 'commission' ? 'active' : '' ?>">Commission</a>
        <a href="/likhub/about.php"      class="<?= $nav === 'about'      ? 'active' : '' ?>">About</a>
        <a href="/likhub/contact.php"    class="<?= $nav === 'contact'    ? 'active' : '' ?>">Contact</a>
      </div>
      <div class="navbar__actions">
        <?php if ($user): ?>
          <a href="/likhub/dashboard.php" class="u-pos-rel btn btn-primary btn-sm">
            Dashboard
            <?php if ($unread_msgs > 0): ?>
              <span class="nav-badge"><?= $unread_msgs > 9 ? '9+' : $unread_msgs ?></span>
            <?php endif; ?>
          </a>
          <?php if ($user['role'] === 'admin'): ?>
            <a href="/likhub/admin.php" class="btn btn-outline btn-sm">⚙ Admin</a>
          <?php endif; ?>
          <a href="/likhub/logout.php" class="btn btn-outline btn-sm">Log Out</a>
        <?php else: ?>
          <a href="/likhub/login.php"    class="btn btn-outline btn-sm">Log In</a>
          <a href="/likhub/register.php" class="btn btn-primary btn-sm">Sign Up</a>
        <?php endif; ?>
      </div>
      <button class="navbar__hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <div class="mobile-menu__header">
    <a href="/likhub/index.php" class="navbar__logo">
      <img src="/likhub/images/logo-cropped.png" alt="LikHub" class="navbar__logo-img">
    </a>
    <button class="mobile-menu__close" id="mobileMenuClose" aria-label="Close menu">&times;</button>
  </div>
  <a href="/likhub/index.php"      class="<?= $nav === 'home'       ? 'active' : '' ?>">Home</a>
  <a href="/likhub/learn.php"      class="<?= $nav === 'learn'      ? 'active' : '' ?>">Learn</a>
  <a href="/likhub/create.php"     class="<?= $nav === 'create'     ? 'active' : '' ?>">Create</a>
  <a href="/likhub/commission.php" class="<?= $nav === 'commission' ? 'active' : '' ?>">Commission</a>
  <a href="/likhub/about.php"      class="<?= $nav === 'about'      ? 'active' : '' ?>">About</a>
  <a href="/likhub/contact.php"    class="<?= $nav === 'contact'    ? 'active' : '' ?>">Contact</a>
  <div class="mobile-menu__actions">
    <?php if ($user): ?>
      <a href="/likhub/dashboard.php" class="btn btn-primary btn-sm">Dashboard <?php if ($unread_msgs > 0): ?><span class="nav-badge"><?= $unread_msgs > 9 ? '9+' : $unread_msgs ?></span><?php endif; ?></a>
      <?php if ($user['role'] === 'admin'): ?><a href="/likhub/admin.php" class="btn btn-outline btn-sm">⚙ Admin</a><?php endif; ?>
      <a href="/likhub/logout.php" class="btn btn-outline btn-sm">Log Out</a>
    <?php else: ?>
      <a href="/likhub/login.php"    class="btn btn-outline btn-sm">Log In</a>
      <a href="/likhub/register.php" class="btn btn-primary btn-sm">Sign Up</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="container mt-16">
  <div class="alert alert--<?= e($flash['type']) ?>">
    <?= e($flash['msg']) ?>
  </div>
</div>
<?php endif; ?>