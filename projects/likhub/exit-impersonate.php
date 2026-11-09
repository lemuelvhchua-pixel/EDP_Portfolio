<?php
require_once __DIR__ . '/php/db.php';
start_session();

if (isset($_SESSION['admin_origin_id'])) {
    $admin_id = (int) $_SESSION['admin_origin_id'];
    unset($_SESSION['admin_origin_id']);
    $_SESSION['user_id'] = $admin_id;
    session_regenerate_id(true);
    header('Location: /likhub/admin.php'); exit;
}

header('Location: /likhub/dashboard.php'); exit;
