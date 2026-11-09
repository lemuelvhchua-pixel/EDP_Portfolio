<?php

require_once __DIR__ . '/php/db.php';
require_login('/likhub/login.php');

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /likhub/track-order.php');
    exit;
}

if (!verify_csrf($_POST['csrf'] ?? '')) {
    set_flash('error', 'Invalid form token. Please try again.');
    header('Location: /likhub/track-order.php');
    exit;
}

$commission_id = intval($_POST['commission_id'] ?? 0);
$rating        = intval($_POST['rating']        ?? 0);
$review_text   = trim($_POST['review_text']     ?? '');
$uid           = $_SESSION['user_id'];

if (!$commission_id)            $errors[] = 'Invalid commission.';
if ($rating < 1 || $rating > 5) $errors[] = 'Please select a rating between 1 and 5.';

$commission  = null;
$source      = null; 

if (!$errors) {
    
    init_commission_requests_table();
    $stmt = db()->prepare(
        'SELECT id, ref AS commission_ref, user_id AS client_id,
                artist_name, status, rating
         FROM commission_requests
         WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$commission_id]);
    $row = $stmt->fetch();
    if ($row) {
        $commission = $row;
        $source     = 'requests';
    } else {
        
        try {
            $stmt2 = db()->prepare(
                'SELECT id, commission_ref, client_id, artist_id, status, rating
                 FROM commissions WHERE id = ? LIMIT 1'
            );
            $stmt2->execute([$commission_id]);
            $commission = $stmt2->fetch() ?: null;
            if ($commission) $source = 'legacy';
        } catch (PDOException $e) {
            
        }
    }

    if (!$commission) {
        $errors[] = 'Commission not found.';
    } elseif ((int)$commission['client_id'] !== $uid) {
        $errors[] = 'You can only review your own commissions.';
    } elseif ($commission['status'] !== 'Completed') {
        $errors[] = 'You can only review a completed commission.';
    } elseif (!is_null($commission['rating'])) {
        $errors[] = 'This commission has already been reviewed.';
    }
}

if (!$errors) {
    if ($source === 'requests') {
        $upd = db()->prepare(
            'UPDATE commission_requests SET rating = ?, review_text = ? WHERE id = ?'
        );
        $upd->execute([$rating, $review_text ?: null, $commission_id]);

        
        $artist = db()->prepare('SELECT id FROM users WHERE name = ? LIMIT 1');
        $artist->execute([$commission['artist_name']]);
        $artistRow = $artist->fetch();
        if ($artistRow) {
            update_artist_stats((int)$artistRow['id']);
        }
    } else {
        
        $upd = db()->prepare(
            'UPDATE commissions SET rating = ?, review_text = ? WHERE id = ?'
        );
        $upd->execute([$rating, $review_text ?: null, $commission_id]);
        if (!empty($commission['artist_id'])) {
            update_artist_stats((int)$commission['artist_id']);
        }
    }

    set_flash('success', 'Thank you! Your review has been submitted. ⭐');
    header('Location: /likhub/track-order.php?ref=' . urlencode($commission['commission_ref']));
    exit;
}

set_flash('error', implode(' ', $errors));
$ref_param = isset($commission['commission_ref'])
    ? '?ref=' . urlencode($commission['commission_ref'])
    : ($commission_id ? '?id=' . $commission_id : '');
header('Location: /likhub/track-order.php' . $ref_param);
exit;