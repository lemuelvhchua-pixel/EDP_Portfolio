<?php
// LOCAL COPY: connects to XAMPP's MySQL. The live server settings were removed.

define('DB_HOST',    'localhost');
define('DB_NAME',    'likhub_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn     = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('DB connect failed: ' . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function is_logged_in(): bool {
    start_session();
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_login(string $redirect = '/likhub/login.php'): void {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Convenience wrapper — looks up the artist name from a ref, then snapshots.
 */
function snapshot_artist_stats_for_ref(string $ref): void {
    $row = db()->prepare('SELECT artist_name FROM commission_requests WHERE ref = ? LIMIT 1');
    $row->execute([$ref]);
    $r = $row->fetch();
    if ($r) snapshot_artist_stats($r['artist_name']);
}

/**
 * Snapshot an artist's stats from live commission_requests rows INTO artist_profiles
 * BEFORE those rows get deleted. This permanently preserves their count and rating
 * so deleting old records never resets the displayed stats.
 */
function snapshot_artist_stats(string $artistName): void {
    init_commission_requests_table();

    // Get current completed stats from live rows
    $req = db()->prepare('
        SELECT COUNT(*) AS total, AVG(NULLIF(rating, 0)) AS avg_r
        FROM commission_requests
        WHERE artist_name = ? AND status = "Completed"
    ');
    $req->execute([$artistName]);
    $row   = $req->fetch();
    $total = (int)$row['total'];
    $avg_r = $row['avg_r'] ? round((float)$row['avg_r'], 2) : null;

    if ($total === 0) return; // nothing to snapshot

    // Fetch artist user id
    $uRow = db()->prepare('SELECT id FROM users WHERE name = ? LIMIT 1');
    $uRow->execute([$artistName]);
    $u = $uRow->fetch();
    if (!$u) return;

    init_artist_profiles_table();

    // Read the currently stored snapshot values
    $cur = db()->prepare('SELECT total_commissions, avg_rating FROM artist_profiles WHERE user_id = ?');
    $cur->execute([$u['id']]);
    $stored = $cur->fetch();

    $storedTotal = (int)($stored['total_commissions'] ?? 0);
    $storedAvg   = $stored['avg_rating'] ? (float)$stored['avg_rating'] : null;

    // Merge: live rows are a subset of what's already stored — use whichever total is higher,
    // and do a weighted average so ratings are never lost
    if ($total > $storedTotal) {
        // There are more live rows than stored — recalc with full weight
        $newTotal = $total;
        $newAvg   = $avg_r;
    } elseif ($storedTotal > 0 && $avg_r !== null && $storedAvg !== null) {
        // Keep stored total; blend stored avg with any new ratings in live rows
        $newTotal = $storedTotal;
        $newAvg   = round(
            ($storedAvg * $storedTotal + $avg_r * $total) / ($storedTotal + $total),
            2
        );
    } else {
        $newTotal = max($storedTotal, $total);
        $newAvg   = $storedAvg ?? $avg_r;
    }

    db()->prepare('
        UPDATE artist_profiles
        SET total_commissions = ?,
            avg_rating        = ?
        WHERE user_id = ?
    ')->execute([$newTotal, $newAvg, $u['id']]);
}

function update_artist_stats(int $artist_id): void {

    $artistNameRow = db()->prepare('SELECT name FROM users WHERE id = ?');
    $artistNameRow->execute([$artist_id]);
    $nameRow    = $artistNameRow->fetch();
    $artistName = $nameRow['name'] ?? '';

    $total = 0; $avg_r = null;

    if ($artistName) {
        init_commission_requests_table();
        $req = db()->prepare('
            SELECT COUNT(*) AS total, AVG(NULLIF(rating, 0)) AS avg_r
            FROM commission_requests
            WHERE artist_name = ? AND status = "Completed"
        ');
        $req->execute([$artistName]);
        $row   = $req->fetch();
        $total = (int)$row['total'];
        $avg_r = $row['avg_r'] ? round((float)$row['avg_r'], 2) : null;
    }

    // Legacy commissions table
    try {
        $leg = db()->prepare('
            SELECT COUNT(*) AS total, AVG(NULLIF(rating, 0)) AS avg_r
            FROM commissions WHERE artist_id = ? AND status = "Completed"
        ');
        $leg->execute([$artist_id]);
        $legRow = $leg->fetch();
        if ($legRow['total'] > 0) {
            $legTotal = (int)$legRow['total'];
            $legAvg   = $legRow['avg_r'] ? (float)$legRow['avg_r'] : 0;
            $curAvg   = $avg_r ? (float)$avg_r : 0;
            $combined = ($total + $legTotal) > 0
                ? round(($curAvg * $total + $legAvg * $legTotal) / ($total + $legTotal), 2)
                : null;
            $total += $legTotal;
            $avg_r  = $combined ?: null;
        }
    } catch (PDOException $e) {}

    // Read stored snapshot — never let a recalc go BELOW what was previously snapshotted
    init_artist_profiles_table();
    $cur = db()->prepare('SELECT total_commissions, avg_rating FROM artist_profiles WHERE user_id = ?');
    $cur->execute([$artist_id]);
    $stored = $cur->fetch();
    $storedTotal = (int)($stored['total_commissions'] ?? 0);
    $storedAvg   = $stored['avg_rating'] !== null ? (float)$stored['avg_rating'] : null;

    // Use the higher total so deleting rows never shrinks the count
    $finalTotal = max($total, $storedTotal);

    // Weighted avg: prefer live data but never drop below stored
    if ($total > 0 && $avg_r !== null && $storedAvg !== null && $storedTotal > $total) {
        $finalAvg = round(
            ($storedAvg * $storedTotal + $avg_r * $total) / ($storedTotal + $total),
            2
        );
    } elseif ($avg_r !== null) {
        $finalAvg = $avg_r;
    } else {
        $finalAvg = $storedAvg;
    }

    db()->prepare('
        UPDATE artist_profiles
        SET total_commissions = ?,
            avg_rating        = ?
        WHERE user_id = ?
    ')->execute([$finalTotal, $finalAvg, $artist_id]);
}


function init_password_resets_table(): void {
    db()->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            token      VARCHAR(64) NOT NULL UNIQUE,
            expires_at INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_user  (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function render_stars(int $rating, bool $show_empty = true): string {
    $rating  = max(0, min(5, $rating));
    $filled  = str_repeat('<span class="star star--filled" aria-hidden="true">★</span>', $rating);
    $empty   = $show_empty
        ? str_repeat('<span class="star star--empty" aria-hidden="true">☆</span>', 5 - $rating)
        : '';
    return '<span class="star-display" aria-label="' . $rating . ' out of 5 stars">' . $filled . $empty . '</span>';
}

function set_flash(string $type, string $msg): void {
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    start_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    start_session();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function init_artist_uploads_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec("
        CREATE TABLE IF NOT EXISTS artist_uploads (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT           NOT NULL,
            artist_name VARCHAR(100)  NOT NULL,
            title       VARCHAR(200)  NOT NULL,
            medium      VARCHAR(50)   NOT NULL,
            filename    VARCHAR(255)  NOT NULL,
            created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_au_user (user_id),
            INDEX idx_au_artist (artist_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function init_commission_requests_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec("
        CREATE TABLE IF NOT EXISTS commission_requests (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            ref         VARCHAR(20)   NOT NULL UNIQUE,
            user_id     INT           NOT NULL,
            artist_id   VARCHAR(50)   NOT NULL,
            artist_name VARCHAR(100)  NOT NULL,
            size        VARCHAR(20)   NOT NULL,
            art_type    VARCHAR(50)   NOT NULL,
            description TEXT,
            payment     VARCHAR(50),
            price       INT           DEFAULT 0,
            status      VARCHAR(50)   DEFAULT 'Submitted',
            rating      INT           DEFAULT NULL,
            review_text TEXT          DEFAULT NULL,
            created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cr_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    foreach (['rating INT DEFAULT NULL', 'review_text TEXT DEFAULT NULL',
              'shipping_fee INT DEFAULT 0', 'download_path VARCHAR(255) DEFAULT NULL',
              'ref_images TEXT DEFAULT NULL',
              'payment_note TEXT DEFAULT NULL',
              'hidden_by_client TINYINT(1) DEFAULT 0',
              'hidden_by_artist TINYINT(1) DEFAULT 0'] as $col) {
        try { db()->exec("ALTER TABLE commission_requests ADD COLUMN $col"); }
        catch (PDOException $e) {  }
    }
    
    try { db()->exec("UPDATE commission_requests SET status = 'Submitted' WHERE status = 'Pending Approval'"); }
    catch (PDOException $e) {  }
}

function init_artworks_tables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec("
        CREATE TABLE IF NOT EXISTS artworks (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            artist_id    INT           NOT NULL,
            title        VARCHAR(200)  NOT NULL,
            medium       VARCHAR(50),
            description  TEXT,
            image_path   VARCHAR(255),
            like_count   INT           DEFAULT 0,
            is_published TINYINT(1)    DEFAULT 1,
            created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_aw_artist (artist_id),
            INDEX idx_aw_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    db()->exec("
        CREATE TABLE IF NOT EXISTS artwork_likes (
            artwork_id INT NOT NULL,
            user_id    INT NOT NULL,
            PRIMARY KEY (artwork_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    db()->exec("
        CREATE TABLE IF NOT EXISTS artwork_comments (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            artwork_id INT      NOT NULL,
            user_id    INT      NOT NULL,
            comment    TEXT     NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ac_artwork (artwork_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function get_user_cart(int $uid): array {
    init_commission_requests_table();
    $stmt = db()->prepare(
        "SELECT * FROM commission_requests
         WHERE user_id = ? AND hidden_by_client = 0
           AND status NOT IN ('Completed', 'Declined', 'Rejected', 'Cancelled')
         ORDER BY created_at DESC"
    );
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    // Also fetch completed/declined for history (last 10)
    $histStmt = db()->prepare(
        "SELECT * FROM commission_requests
         WHERE user_id = ? AND hidden_by_client = 0
           AND status IN ('Completed', 'Declined', 'Rejected', 'Cancelled')
         ORDER BY created_at DESC LIMIT 10"
    );
    $histStmt->execute([$uid]);
    $histRows = $histStmt->fetchAll();
    $allRows = array_merge($rows, $histRows);
    return array_map(fn($r) => [
        'id'          => $r['ref'],
        'ref'         => $r['ref'],
        'artistId'    => $r['artist_id'],
        'artistName'  => $r['artist_name'],
        'size'        => $r['size'],
        'type'        => $r['art_type'],
        'desc'        => $r['description'] ?? '',
        'payment'     => $r['payment'] ?? '',
        'price'       => (int) $r['price'],
        'shipping_fee'=> (int) ($r['shipping_fee'] ?? 0),
        'status'      => $r['status'],
        'rating'      => $r['rating'] ? (int)$r['rating'] : 0,
        'review_text' => $r['review_text'] ?? '',
        'ref_images'  => $r['ref_images'] ? json_decode($r['ref_images'], true) : [],
        'download_path' => $r['download_path'] ?? null,
        'payment_note'  => $r['payment_note'] ?? null,
        'addedAt'     => $r['created_at'],
    ], $allRows);
}

function init_artist_profiles_table(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    db()->exec("
        CREATE TABLE IF NOT EXISTS artist_profiles (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            user_id           INT           NOT NULL UNIQUE,
            bio               TEXT,
            commission_status VARCHAR(20)   DEFAULT 'open',
            price_min         INT           DEFAULT 0,
            price_max         INT           DEFAULT 0,
            price_small       INT           DEFAULT 0,
            price_medium      INT           DEFAULT 0,
            price_large       INT           DEFAULT 0,
            shipping_fee      INT           DEFAULT 0,
            mediums           VARCHAR(255)  DEFAULT '',
            styles            VARCHAR(255)  DEFAULT '',
            avg_rating        DECIMAL(3,2)  DEFAULT NULL,
            total_commissions INT           DEFAULT 0,
            INDEX idx_ap_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    
    $cols = ['bio TEXT', 'commission_status VARCHAR(20) DEFAULT \'open\'',
             'price_min INT DEFAULT 0', 'price_max INT DEFAULT 0',
             'price_small INT DEFAULT 0', 'price_medium INT DEFAULT 0',
             'price_large INT DEFAULT 0', 'shipping_fee INT DEFAULT 0',
             'mediums VARCHAR(255) DEFAULT \'\'', 'styles VARCHAR(255) DEFAULT \'\''];
    foreach ($cols as $col) {
        try { db()->exec("ALTER TABLE artist_profiles ADD COLUMN $col"); }
        catch (PDOException $e) {  }
    }
}

function get_all_db_artists(?string $medium = null, ?string $search = null): array {
    init_artist_profiles_table();
    init_artist_uploads_table();

    $where = 'u.role = "artist" AND u.is_active = 1';
    $params = [];
    if ($search) { $where .= ' AND u.name LIKE ?'; $params[] = "%$search%"; }

    $stmt = db()->prepare(
        "SELECT u.id, u.name, u.avatar,
                ap.commission_status, ap.price_min, ap.price_max,
                ap.price_small, ap.price_medium, ap.price_large, ap.shipping_fee,
                ap.mediums, ap.styles, ap.bio,
                ap.avg_rating, ap.total_commissions
         FROM users u
         LEFT JOIN artist_profiles ap ON ap.user_id = u.id
         WHERE $where
         ORDER BY ap.avg_rating DESC, u.name ASC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    
    static $static_map = [
        'DRex'      => ['/likhub/images/DRex1_digital.jpg','/likhub/images/DRex1_traditional.jpg','/likhub/images/DRex_painting.jpg','/likhub/images/DRex2_digital.jpg'],
        'Sena'      => ['/likhub/images/Sena1_digital.jpg','/likhub/images/Sena2_digital.jpg','/likhub/images/Sena3_digital.jpg','/likhub/images/Sena1_traditional.jpg'],
        'Yeoj'      => ['/likhub/images/Yeoj1_digital.jpg','/likhub/images/Yeoj2_digital.jpg','/likhub/images/Yeoj3_digital.jpg'],
        'Yuhan'     => ['/likhub/images/Yuhan1_digital.jpg','/likhub/images/Yuhan2_digital.jpg','/likhub/images/Yuhan3_digital.jpg','/likhub/images/Yuhan4_digital.jpg'],
        'Keiose VB' => ['/likhub/images/Keiose VB1_traditional.jpg','/likhub/images/Keiose VB2_traditional.jpg','/likhub/images/Keiose VB3_traditional.jpg'],
        'Andrea'    => ['/likhub/images/Andrea1_digital.jpg','/likhub/images/Andrea2_digital.jpg','/likhub/images/Andrea3_digital.jpg'],
    ];

    
    $upRows = db()->query('SELECT user_id, filename FROM artist_uploads ORDER BY created_at DESC')->fetchAll();
    $uploadsByUser = [];
    foreach ($upRows as $u) {
        $uploadsByUser[$u['user_id']][] = '/likhub/images/uploads/' . $u['filename'];
    }

    $artists = [];
    foreach ($rows as $r) {
        $meds_arr = $r['mediums'] ? array_filter(array_map('trim', explode(',', $r['mediums']))) : [];
        
        if ($medium && $meds_arr && !in_array($medium, $meds_arr)) continue;

        $artworks = array_merge(
            $uploadsByUser[$r['id']] ?? [],
            $static_map[$r['name']] ?? []
        );

        
        $p_small  = (int)($r['price_small']  ?? 0) ?: 300;
        $p_medium = (int)($r['price_medium'] ?? 0) ?: 900;
        $p_large  = (int)($r['price_large']  ?? 0) ?: 3000;

        
        $p_min = (int)($r['price_min'] ?? 0);
        $p_max = (int)($r['price_max'] ?? 0);
        if (!$p_min && !$p_max) {
            $p_min = $p_small;
            $p_max = $p_large;
        }

        $artists[] = [
            'id'           => 'db_' . $r['id'],
            'db_id'        => $r['id'],
            'name'         => $r['name'],
            'pfp'          => $r['avatar'],
            'mediums'      => $meds_arr,
            'status'       => $r['commission_status'] ?? 'open',
            'price_min'    => $p_min,
            'price_max'    => $p_max,
            'price_small'  => $p_small,
            'price_medium' => $p_medium,
            'price_large'  => $p_large,
            'shipping_fee' => (int)($r['shipping_fee'] ?? 0),
            'bio'          => $r['bio'] ?? '',
            'avg_rating'   => $r['avg_rating'],
            'artworks'     => $artworks,
        ];
    }
    return $artists;
}