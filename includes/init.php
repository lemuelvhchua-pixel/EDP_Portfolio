<?php
// Loaded at the top of every page: settings, database, session and helpers.
require_once __DIR__ . '/../config.php';

// ---------- session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_name('lemuel_portfolio');
    session_start();
}

// ---------- database ----------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = DEBUG ? $e->getMessage() : 'Database connection failed.';
            exit('<p style="font-family:monospace;color:#ff0055;padding:20px">[ DATABASE ERROR ] ' . e($msg)
                . '<br><br>Start MySQL in XAMPP and import database.sql in phpMyAdmin (see README.md).</p>');
        }
    }
    return $pdo;
}

// ---------- helpers ----------
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): void
{
    if (is_ajax()) {
        json_out(['ok' => true, 'redirect' => $to]);
    }
    header('Location: ' . $to);
    exit;
}

// Requests made by our JavaScript (fetch) send this header and get JSON back.
function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '" />';
}

function csrf_check(): bool
{
    return isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
}

// ---------- auth ----------
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $st = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
            $st->execute([$_SESSION['user_id']]);
            $user = $st->fetch() ?: null;
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

// The admin password that database.sql creates. It is written in README.md,
// so the site nags the admin to change it (see admin/account.php).
const DEFAULT_ADMIN_PASSWORD = 'LemuelAdmin2026';

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['login_fails'], $_SESSION['login_lock'], $_SESSION['default_pw']);
    if (($user['role'] ?? '') === 'admin' && password_verify(DEFAULT_ADMIN_PASSWORD, (string) ($user['password_hash'] ?? ''))) {
        $_SESSION['default_pw'] = true;
    }
}

function require_login(): void
{
    if (!current_user()) {
        $back = basename($_SERVER['SCRIPT_NAME']);
        if (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin') {
            $back = 'admin/' . $back;
        }
        if (is_ajax()) {
            json_out(['ok' => false, 'errors' => ['Please log in again.'], 'redirect' => url('login.php') . '?next=' . urlencode($back)], 401);
        }
        redirect(url('login.php') . '?next=' . urlencode($back));
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        if (is_ajax()) {
            json_out(['ok' => false, 'errors' => ['Admins only.']], 403);
        }
        http_response_code(403);
        exit('<p style="font-family:monospace;color:#ff0055;padding:20px">[ 403 ] Admins only. <a style="color:#fff" href="' . url('index.php') . '">Go home</a></p>');
    }
}

// Pages inside /admin need "../" in front of links.
function url(string $path): string
{
    $inAdmin = basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin';
    return ($inAdmin ? '../' : '') . $path;
}

// True when the site is opened on this computer (XAMPP / localhost).
function is_local(): bool
{
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true) || str_ends_with($host, '.localhost');
}

// "VIEW CODE" button: opens the project's folder in this repo on GitHub.
function code_button(string $project): string
{
    // '.' means the whole repo (this portfolio site itself)
    $href = $project === '.' ? GITHUB_REPO : GITHUB_REPO . '/tree/main/projects/' . $project;
    return '<a href="' . e($href) . '" target="_blank" rel="noopener" class="btn btn-local">&#9654; VIEW CODE</a>';
}

// "DOWNLOAD RESUME" button, shown only when the PDF from config.php exists.
function resume_button(string $extraClass = ''): string
{
    if (!RESUME_FILE || !is_file(__DIR__ . '/../' . RESUME_FILE)) {
        return '';
    }
    return '<a href="' . e(url(RESUME_FILE)) . '" class="btn btn-resume ' . e($extraClass) . '" download>&#11015; DOWNLOAD RESUME</a>';
}

// Only allow redirects back to our own pages.
function safe_next(?string $next): string
{
    return ($next && preg_match('/^(admin\/)?[a-z0-9_\-]+\.php(#[a-z0-9\-]+)?$/i', $next)) ? $next : 'index.php';
}

// Gate every page when REQUIRE_LOGIN is on (login/register/logout stay open).
if (REQUIRE_LOGIN && !defined('PUBLIC_PAGE')) {
    require_login();
}
