<?php
$page_title = 'Track Your Order';
$active_nav = '';
require_once __DIR__ . '/php/db.php';

require_login('/likhub/login.php');

$viewer = current_user();
$ref    = trim($_GET['ref'] ?? '');
$order  = null;
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    
    if ($action === 'send_message_track') {
        if (!verify_csrf($_POST['csrf'] ?? '')) {
            set_flash('error', 'Invalid form token.');
            header('Location: /likhub/track-order.php?ref=' . urlencode($ref)); exit;
        }
        $mb = trim($_POST['msg_body'] ?? '');
        $thread_ref = trim($_POST['thread_ref'] ?? '');
        if ($mb && $thread_ref) {
            require_once __DIR__ . '/php/messages.php';
            post_message($thread_ref, $viewer['id'], $viewer['role'], $mb);
        }
        header('Location: /likhub/track-order.php?ref=' . urlencode($thread_ref)); exit;
    }

    
    $ref = trim($_POST['ref'] ?? '');
}

if ($ref) {
    
    try {
        $stmt = db()->prepare(
            'SELECT c.*, u_c.name AS client_name, u_a.name AS artist_name, u_a.avatar AS artist_avatar,
                    c.client_id, u_a.id AS artist_db_id
             FROM commissions c
             JOIN users u_c ON u_c.id = c.client_id
             JOIN users u_a ON u_a.id = c.artist_id
             WHERE c.commission_ref = ?'
        );
        $stmt->execute([$ref]);
        $order = $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        
    }

    
    if (!$order) {
        init_commission_requests_table();
        $stmt = db()->prepare(
            'SELECT cr.id,
                    cr.ref AS commission_ref,
                    cr.status, cr.size, cr.art_type AS art_category,
                    cr.price, cr.description, cr.created_at AS request_date,
                    cr.payment, cr.download_path,
                    u.name AS client_name,
                    cr.artist_name,
                    NULL AS artist_avatar,
                    cr.user_id AS client_id,
                    cr.artist_id AS artist_db_id,
                    cr.rating, cr.review_text
             FROM commission_requests cr
             LEFT JOIN users u ON u.id = cr.user_id
             WHERE cr.ref = ?'
        );
        $stmt->execute([$ref]);
        $order = $stmt->fetch() ?: null;
    }

    if (!$order) {
        $error = 'No commission found with reference "' . e($ref) . '". Please check the reference and try again.';
    } else {
        
        $artist_db_id = preg_replace('/^db_/', '', $order['artist_db_id'] ?? '');
        $is_client    = ((int)$viewer['id'] === (int)$order['client_id']);
        $is_artist    = ((int)$viewer['id'] === (int)$artist_db_id);
        $is_admin     = ($viewer['role'] === 'admin');

        if (!$is_client && !$is_artist && !$is_admin) {
            $order = null;
            $error = 'You do not have permission to view this commission.';
        }
    }
}

$statuses    = ['Submitted', 'Approved', 'In Progress', 'Completed'];
$statusIcons = ['fa-inbox', 'fa-check-circle', 'fa-pen-to-square', 'fa-circle-check'];
$statusIndex = $order ? array_search($order['status'], $statuses) : -1;
if ($statusIndex === false || $statusIndex === -1) {
    $legacyMap   = ['Received' => 0, 'Shipped' => 2];
    $statusIndex = $legacyMap[$order['status'] ?? ''] ?? -1;
}

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <h1>Track Your Order</h1>
    <p>Enter your commission reference number to check the status of your order.</p>
  </div>
</header>

<section class="section">
  <div class="container track-order-wrap">

    <div class="form-section">
      <div class="form-section__title">Commission Reference Lookup</div>
      <form method="POST" action="/likhub/track-order.php">
        <div class="form-group">
          <label class="form-label" for="ref">Commission Reference Number</label>
          <div class="track-form-row">
            <input type="text" name="ref" id="ref" class="form-control track-input" placeholder="e.g. LH-00042" value="<?= e($ref) ?>" required>
            <button type="submit" class="btn btn-primary track-btn"><i class="fa-solid fa-magnifying-glass"></i> Track</button>
          </div>
          <div class="form-hint">Your reference is visible in your dashboard or was shown when you submitted the commission.</div>
        </div>
      </form>
    </div>

    <?php if ($error): ?>
    <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= $error ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
    <div class="form-section">
      <div class="flex-between mb-16 order-header">
        <div>
          <div class="section-label">Commission</div>
          <h3 class="order-ref-heading"><?= e($order['commission_ref']) ?></h3>
        </div>
        <span class="status-badge status-badge--<?= strtolower(str_replace(' ', '', $order['status'])) ?>">
          <?= e($order['status']) ?>
        </span>
      </div>

      <div class="order-timeline mb-24" role="list" aria-label="Order status timeline">
        <?php foreach ($statuses as $i => $s):
          $cls = $i < $statusIndex ? 'done' : ($i === $statusIndex ? 'active' : '');
        ?>
        <div class="timeline-step <?= $cls ?>" role="listitem" aria-current="<?= $cls === 'active' ? 'step' : 'false' ?>">
          <div class="timeline-step__dot"><i class="fa-solid <?= $statusIcons[$i] ?>"></i></div>
          <div class="timeline-step__label"><?= $s ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="divider"></div>

      <?php if ($order['status'] === 'Approved' && $is_client): ?>
      <div class="payment-prompt-banner">
        <div class="payment-prompt-banner__icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="payment-prompt-banner__body">
          <div class="payment-prompt-banner__title">🎉 Your request was approved!</div>
          <div class="payment-prompt-banner__detail">
            Please send <strong>₱<?= number_format($order['price'], 2) ?></strong>
            to <strong><?= e($order['artist_name']) ?></strong>
            via <strong><?= e($order['payment'] ?: 'your agreed method') ?></strong>.
          </div>
          <div class="payment-prompt-banner__note">Once you've paid, click "Confirm Payment" below to start the commission.</div>
          <form method="POST" action="/likhub/commission.php" id="payConfirmForm" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
            <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="client_accept">
            <input type="hidden" name="ref"    value="<?= e($order['commission_ref']) ?>">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm payment of ₱<?= number_format($order['price'],2) ?> to <?= e($order['artist_name']) ?>?')">
              <i class="fa-solid fa-money-bill-wave"></i> Confirm Payment
            </button>
            <a href="/likhub/commission.php" class="btn btn-outline">Back to Commission Page</a>
          </form>
        </div>
      </div>
      <div class="divider"></div>
      <?php endif; ?>

      <div class="grid-2">
        <div>
          <div class="body-text">Client</div>
          <p class="order-field-value"><?= e($order['client_name']) ?></p>
        </div>
        <div>
          <div class="body-text">Artist</div>
          <p class="order-field-value"><?= e($order['artist_name']) ?></p>
        </div>
        <div>
          <div class="body-text">Art Type</div>
          <p class="order-field-value--plain"><?= e($order['art_category']) ?></p>
        </div>
        <div>
          <div class="body-text">Size</div>
          <p class="order-field-value--plain"><?= e($order['size']) ?></p>
        </div>
        <div>
          <div class="body-text">Price</div>
          <p class="order-field-value--price">₱<?= number_format($order['price'], 2) ?></p>
        </div>
        <div>
          <div class="body-text">Date Requested</div>
          <p class="order-field-value--plain"><?= date('F j, Y', strtotime($order['request_date'])) ?></p>
        </div>
      </div>

      <div class="divider"></div>

      <div class="body-text">Description</div>
      <p class="order-desc"><?= e($order['description']) ?></p>

      <?php if ($order['rating']): ?>
      <div class="divider"></div>
      <div class="body-text">Your Review</div>
      <div class="order-stars"><?= str_repeat('★', intval($order['rating'])) ?></div>
      <?php if ($order['review_text']): ?>
        <div class="blockquote"><?= e($order['review_text']) ?></div>
      <?php endif; ?>
      <?php endif; ?>

      <?php if ($order['status'] === 'Completed' && !$order['rating'] && $is_client): ?>
      <div class="divider" id="leave-review"></div>
      <div class="u-mb-12 alert alert--info">
        <i class="fa-solid fa-star" style="color:#f59e0b;margin-right:6px;"></i>
        <span><strong>Your commission is complete!</strong> Share your experience to help other clients.</span>
      </div>
      <h4>Leave a Review</h4>
      <form method="POST" action="/likhub/review.php" class="review-form">
        <input type="hidden" name="commission_id" value="<?= $order['id'] ?>">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-group">
          <label class="form-label">Rating</label>
          <div class="star-picker" id="starPicker" role="radiogroup" aria-label="Star rating">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" class="star-btn" data-val="<?= $s ?>" aria-label="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">★</button>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingInput" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="review_text">Your Review (optional)</label>
          <textarea name="review_text" id="review_text" class="form-control" rows="3" placeholder="Tell us about your experience…"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Review</button>
      </form>
      <?php endif; ?>

      <?php
      
      require_once __DIR__ . '/php/messages.php';
      $thread_ref = $order['commission_ref'];
      mark_read($thread_ref, $viewer['id']);
      $thread_messages = get_messages($thread_ref);
      ?>
      <div class="divider"></div>
      <h4 class="u-mb-12" id="msg-thread">
        <i class="fa-solid fa-comments"></i>
        Messages with <?= $is_client ? e($order['artist_name']) : e($order['client_name']) ?>
      </h4>

      <div class="msg-track-thread">
        <?php if (empty($thread_messages)): ?>
          <div class="msg-empty"><i class="fa-regular fa-comment-dots"></i> No messages yet.</div>
        <?php endif; ?>
        <?php foreach ($thread_messages as $m):
            $is_mine = ($m['sender_id'] == $viewer['id']);
        ?>
        <div class="msg-bubble <?= $is_mine ? 'msg-bubble--mine' : 'msg-bubble--theirs' ?>">
          <div class="msg-bubble__meta">
            <span class="msg-bubble__name"><?= e($m['sender_name'] ?? 'Unknown') ?></span>
            <span class="msg-bubble__time"><?= date('M j, g:i a', strtotime($m['created_at'])) ?></span>
          </div>
          <div class="msg-bubble__body"><?= nl2br(e($m['body'])) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <form method="POST" class="u-mt-12 msg-compose" action="/likhub/track-order.php?ref=<?= urlencode($thread_ref) ?>">
        <input type="hidden" name="csrf"       value="<?= csrf_token() ?>">
        <input type="hidden" name="action"     value="send_message_track">
        <input type="hidden" name="thread_ref" value="<?= e($thread_ref) ?>">
        <textarea name="msg_body" class="msg-compose__input" placeholder="Type a message…" rows="2" required maxlength="2000"></textarea>
        <button type="submit" class="btn btn-primary msg-compose__btn">
          <i class="fa-solid fa-paper-plane"></i> Send
        </button>
      </form>

    </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>