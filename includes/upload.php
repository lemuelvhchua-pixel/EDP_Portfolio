<?php
// Safe file uploads for the admin pages (images and MP4 videos only).

const UPLOAD_TYPES = [
    'image' => ['max' => 8 * 1024 * 1024,  'dir' => 'images/uploads', 'mimes' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif']],
    'video' => ['max' => 40 * 1024 * 1024, 'dir' => 'video/uploads',  'mimes' => ['video/mp4' => 'mp4']],
];

/**
 * Saves an uploaded file. Returns the site-relative path, null if no file was chosen.
 * Throws RuntimeException with a friendly message when the file isn't allowed.
 */
function save_upload(string $field, string $type = 'image'): ?string
{
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
    $rule = UPLOAD_TYPES[$type];
    $mb = round($rule['max'] / 1048576);
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > $rule['max']) {
        throw new RuntimeException("That file is too big (max {$mb} MB).");
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('The upload failed. Try again.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($rule['mimes'][$mime])) {
        throw new RuntimeException($type === 'image' ? 'Only JPG, PNG, WEBP or GIF images are allowed.' : 'Only MP4 videos are allowed.');
    }
    if ($type === 'image' && @getimagesize($f['tmp_name']) === false) {
        throw new RuntimeException("That file isn't a real image.");
    }
    $root = dirname(__DIR__);
    $dir = $root . '/' . $rule['dir'];
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $rule['mimes'][$mime];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) {
        throw new RuntimeException("Couldn't save the file. Check that the {$rule['dir']} folder can be written to.");
    }
    return $rule['dir'] . '/' . $name;
}

// Small preview for slideshow thumbnails. Needs PHP's GD extension; without it the full image is used.
function make_thumb(string $path, int $max = 360): ?string
{
    if (!function_exists('imagecreatefromstring')) return null;
    $root = dirname(__DIR__);
    $src = @imagecreatefromstring((string) file_get_contents("$root/$path"));
    if (!$src) return null;
    [$w, $h] = [imagesx($src), imagesy($src)];
    $k = min(1, $max / max($w, $h));
    $tw = max(1, (int) round($w * $k));
    $th = max(1, (int) round($h * $k));
    $dst = imagecreatetruecolor($tw, $th);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
    $out = preg_replace('/\.\w+$/', '-thumb.jpg', $path);
    imagejpeg($dst, "$root/$out", 80);
    imagedestroy($src);
    imagedestroy($dst);
    return $out;
}

// Only files that were uploaded through the admin get deleted (never the original images).
function delete_upload(?string $path): void
{
    if (!$path || !preg_match('#^(images|video)/uploads/[\w\-]+\.\w+$#', $path)) return;
    $file = dirname(__DIR__) . '/' . $path;
    if (is_file($file)) @unlink($file);
}
