<?php

function init_messages_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec("
        CREATE TABLE IF NOT EXISTS commission_messages (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            ref         VARCHAR(20)  NOT NULL,
            sender_id   INT          NOT NULL,
            sender_role ENUM('client','artist','admin') NOT NULL DEFAULT 'client',
            body        TEXT         NOT NULL,
            msg_type    VARCHAR(20)  NOT NULL DEFAULT 'text',
            file_path   VARCHAR(255) DEFAULT NULL,
            is_read     TINYINT(1)   DEFAULT 0,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cm_ref      (ref),
            INDEX idx_cm_sender   (sender_id),
            INDEX idx_cm_unread   (ref, is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    foreach (['msg_type VARCHAR(20) NOT NULL DEFAULT \'text\'',
              'file_path VARCHAR(255) DEFAULT NULL'] as $col) {
        try { db()->exec("ALTER TABLE commission_messages ADD COLUMN $col"); }
        catch (PDOException $e) {  }
    }
}

function get_messages(string $ref): array {
    init_messages_table();
    $stmt = db()->prepare(
        'SELECT m.*, u.name AS sender_name, u.role AS sender_real_role
         FROM commission_messages m
         LEFT JOIN users u ON u.id = m.sender_id
         WHERE m.ref = ?
         ORDER BY m.created_at ASC'
    );
    $stmt->execute([$ref]);
    return $stmt->fetchAll();
}

function post_message(string $ref, int $sender_id, string $role, string $body, string $msg_type = 'text', ?string $file_path = null): void {
    init_messages_table();
    $body = trim($body);
    if ($body === '' && !$file_path) return;
    db()->prepare(
        'INSERT INTO commission_messages (ref, sender_id, sender_role, body, msg_type, file_path) VALUES (?,?,?,?,?,?)'
    )->execute([$ref, $sender_id, $role, substr($body, 0, 2000), $msg_type, $file_path]);
}

function mark_read(string $ref, int $viewer_id): void {
    init_messages_table();
    db()->prepare(
        'UPDATE commission_messages SET is_read = 1 WHERE ref = ? AND sender_id != ?'
    )->execute([$ref, $viewer_id]);
}

function unread_count_for_user(array $user): int {
    init_messages_table();
    init_commission_requests_table();

    if ($user['role'] === 'artist') {
        
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM commission_messages m
             JOIN commission_requests cr ON cr.ref = m.ref
             WHERE cr.artist_name = ? AND m.sender_id != ? AND m.is_read = 0'
        );
        $stmt->execute([$user['name'], $user['id']]);
    } else {
        
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM commission_messages m
             JOIN commission_requests cr ON cr.ref = m.ref
             WHERE cr.user_id = ? AND m.sender_id != ? AND m.is_read = 0'
        );
        $stmt->execute([$user['id'], $user['id']]);
    }
    return (int) $stmt->fetchColumn();
}