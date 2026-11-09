<?php
require_once __DIR__ . '/php/db.php';
start_session();
session_destroy();
header('Location: /likhub/index.php');
exit;