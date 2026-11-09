<?php
// ---------------------------------------------------------------
//  Site settings - the only file you need to edit.
// ---------------------------------------------------------------

// Database (XAMPP default: user "root", no password)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'lemuel_portfolio');
define('DB_USER', 'root');
define('DB_PASS', '');

// true  = visitors must log in before they can see the site
// false = the site is public; logging in only unlocks the admin inbox
define('REQUIRE_LOGIN', false);

// My resume. Put the PDF at this path and a DOWNLOAD RESUME button appears
// on the About and Contact pages. No file = no button.
define('RESUME_FILE', 'files/Lemuel_Chua_Resume.pdf');

// Code for my web projects lives in this repo, in the projects/ folder.
define('GITHUB_REPO', 'https://github.com/lemuelvhchua-pixel/EDP_Portfolio');

// Is the site running on this computer (XAMPP)? Used for the two settings below.
define('IS_LOCAL', in_array(
    strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost')),
    ['localhost', '127.0.0.1', '[::1]'],
    true
));

// Show PHP errors. Automatically on in XAMPP and off once the site is online,
// so visitors never see file paths or database details.
define('DEBUG', IS_LOCAL);

// Who can create an account on the Sign Up page.
// IS_LOCAL = only while testing in XAMPP (recommended), true = always, false = never.
// Sign-ups are always normal accounts; only the admin from database.sql can open Inbox / Manage.
define('ALLOW_SIGNUP', IS_LOCAL);

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
