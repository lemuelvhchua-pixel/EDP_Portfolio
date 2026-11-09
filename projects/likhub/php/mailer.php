<?php
// LOCAL COPY: no real emails are sent here. The code or link is shown on screen
// in a "LOCAL DEMO" banner so sign up, login and password reset still work offline.
define('MAIL_FROM', 'LikHub <noreply@likhub.local>');

function local_demo_note(string $label, string $value): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['local_demo_mail'] = ['label' => $label, 'value' => $value, 'at' => time()];
}

function send_otp_email(string $to_email, string $to_name, string $otp, string $purpose = 'login'): bool {
    local_demo_note($purpose === 'register' ? 'Your sign up code' : 'Your login code', $otp);
    return true;
}

function send_reset_email(string $to_email, string $to_name, string $reset_link): bool {
    local_demo_note('Your password reset link', $reset_link);
    return true;
}
