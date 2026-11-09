<?php

require_once __DIR__ . '/php/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (!verify_csrf($_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid form token. Please reload and try again.']);
    exit;
}

require_login(); 

$caller = current_user();
if (!$caller || $caller['role'] !== 'artist') {
    http_response_code(403);
    echo json_encode(['error' => 'Only artists can upload artwork.']);
    exit;
}

$uid    = $caller['id'];
$name   = trim($_POST['name']   ?? '');
$medium = trim($_POST['medium'] ?? '');
$title  = trim($_POST['title']  ?? '');
$desc   = trim($_POST['desc']   ?? '');

$errors = [];

if (strlen($name)  < 2) $errors[] = 'Name must be at least 2 characters.';
if (!$medium)           $errors[] = 'Please select a medium.';
if (strlen($title) < 2) $errors[] = 'Title must be at least 2 characters.';

$allowed_mediums = ['Digital', 'Traditional', 'Painting'];
if ($medium && !in_array($medium, $allowed_mediums, true)) {
    $errors[] = 'Invalid medium selected.';
}

$file     = $_FILES['image'] ?? null;
$filename = null;

if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = 'Please upload an artwork image.';
} elseif ($file['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'File upload failed (error ' . $file['error'] . '). Please try again.';
} else {
    $max_bytes = 10 * 1024 * 1024; 
    if ($file['size'] > $max_bytes) {
        $errors[] = 'Image must be 10 MB or smaller.';
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_types, true)) {
        $errors[] = 'Only JPG, PNG, WEBP, or GIF images are accepted.';
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

$upload_dir = __DIR__ . '/images/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
$filename = uniqid('upload_', true) . '.' . strtolower($ext);
$dest     = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save the uploaded file. Please try again.']);
    exit;
}

init_artist_uploads_table();
$stmt = db()->prepare(
    'INSERT INTO artist_uploads (user_id, artist_name, title, medium, filename)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$uid, $name, $title, $medium, $filename]);

echo json_encode(['ok' => true]);
exit;
