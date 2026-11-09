<?php
define('PUBLIC_PAGE', true);
require __DIR__ . '/includes/init.php';

// Only log out on a real POST from our own form (stops logout links from other sites).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    flash('notice', 'You are logged out.');
    redirect('login.php');
}
redirect('index.php');
