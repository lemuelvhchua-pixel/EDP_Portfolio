<?php
$active_nav = 'create';
require_once __DIR__ . '/php/db.php';
init_artworks_tables(); 

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /likhub/create.php'); exit; }

$stmt = db()->prepare('SELECT a.*, u.name AS artist_name, u.avatar AS artist_avatar, u.bio AS artist_bio FROM artworks a JOIN users u ON u.id = a.artist_id WHERE a.id = ? AND a.is_published = 1 LIMIT 1');
$stmt->execute([$id]);
$artwork = $stmt->fetch();
if (!$artwork) { header('Location: /likhub/create.php'); exit; }

$page_title = e($artwork['title']) . ' by ' . e($artwork['artist_name']);

$initials = '';
foreach (explode(' ', trim($artwork['artist_name'])) as $p) $initials .= strtoupper($p[0]);
$initials = substr($initials, 0, 2);

$currentUser = current_user();
$liked = false;
if ($currentUser) {
    $ls = db()->prepare('SELECT 1 FROM artwork_likes WHERE artwork_id = ? AND user_id = ?');
    $ls->execute([$id, $currentUser['id']]);
    $liked = (bool)$ls->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_like') {
    require_login();
    if (!verify_csrf($_POST['csrf'] ?? '')) { http_response_code(403); echo json_encode(['error'=>'Invalid token']); exit; }
    $uid = $_SESSION['user_id'];
    $check = db()->prepare('SELECT 1 FROM artwork_likes WHERE artwork_id=? AND user_id=?');
    $check->execute([$id, $uid]);
    if ($check->fetch()) {
        db()->prepare('DELETE FROM artwork_likes WHERE artwork_id=? AND user_id=?')->execute([$id, $uid]);
        db()->prepare('UPDATE artworks SET like_count = like_count - 1 WHERE id=?')->execute([$id]);
        echo json_encode(['liked'=>false]);
    } else {
        db()->prepare('INSERT INTO artwork_likes (artwork_id,user_id) VALUES (?,?)')->execute([$id, $uid]);
        db()->prepare('UPDATE artworks SET like_count = like_count + 1 WHERE id=?')->execute([$id]);
        echo json_encode(['liked'=>true]);
    }
    exit;
}

$commentError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'post_comment') {
    if (!$currentUser) { header('Location: /likhub/login.php'); exit; }
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $commentError = 'Invalid form token.';
    } else {
        $comment = trim($_POST['comment'] ?? '');
        if (strlen($comment) < 2) {
            $commentError = 'Comment must be at least 2 characters.';
        } else {
            db()->prepare('INSERT INTO artwork_comments (artwork_id, user_id, comment) VALUES (?,?,?)')->execute([$id, $currentUser['id'], $comment]);
            header('Location: /likhub/artwork.php?id=' . $id . '
            exit;
        }
    }
}

$comStmt = db()->prepare('SELECT ac.*, u.name AS author_name FROM artwork_comments ac JOIN users u ON u.id = ac.user_id WHERE ac.artwork_id = ? ORDER BY ac.created_at DESC LIMIT 30');
$comStmt->execute([$id]);
$comments = $comStmt->fetchAll();

$artwork['like_count'] = db()->prepare('SELECT like_count FROM artworks WHERE id=?')->execute([$id]) ? db()->query("SELECT like_count FROM artworks WHERE id=$id")->fetchColumn() : $artwork['like_count'];

$moreStmt = db()->prepare('SELECT * FROM artworks WHERE artist_id = ? AND id != ? AND is_published = 1 ORDER BY created_at DESC LIMIT 4');
$moreStmt->execute([$artwork['artist_id'], $id]);
$moreArtworks = $moreStmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <nav class="breadcrumb">
      <a href="/create.php" class="breadcrumb__link">Gallery</a>
      <span class="breadcrumb__sep">›</span>
      <span class="breadcrumb__current"><?= e($artwork['title']) ?></span>
    </nav>
    <h1><?= e($artwork['title']) ?></h1>
    <p>by <?= e($artwork['artist_name']) ?> &nbsp;·&nbsp; <?= e($artwork['medium'] ?? 'Mixed Media') ?></p>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="artwork-layout">

      <div>
        <div class="artwork-image-wrap">
          <?php if ($artwork['image_path']): ?>
          <img src="<?= e($artwork['image_path']) ?>" alt="<?= e($artwork['title']) ?>">
          <?php else: ?>
          <div class="artwork-image-placeholder">
            <i class="fa-solid fa-image"></i>
            IMAGE NOT AVAILABLE
          </div>
          <?php endif; ?>
        </div>

        <div class="artwork-meta-bar">
          <button class="like-btn <?= $liked ? 'like-btn--liked' : 'like-btn--default' ?>" id="likeBtn"
            data-id="<?= $id ?>"
            data-liked="<?= $liked ? '1' : '0' ?>"
            data-csrf="<?= csrf_token() ?>"
            data-endpoint="/likhub/artwork.php?id=<?= $id ?>">
            <i id="likeIcon" class="fa-<?= $liked ? 'solid' : 'regular' ?> fa-heart"></i>
            <span class="like-count" id="likeCount"><?= $artwork['like_count'] ?></span> Likes
          </button>
          <span class="artwork-meta-bar__date">
            <i class="fa-regular fa-calendar"></i>
            Posted <?= date('F j, Y', strtotime($artwork['created_at'])) ?>
          </span>
          <div class="artwork-meta-bar__actions">
            <a href="/likhub/commission.php?search=<?= urlencode($artwork['artist_name']) ?>" class="btn btn-primary btn-sm">
              <i class="fa-solid fa-handshake"></i> Commission Artist
            </a>
            <a href="/likhub/artist-profile.php?id=<?= $artwork['artist_id'] ?>" class="btn btn-outline btn-sm">
              View Profile
            </a>
          </div>
        </div>

        <div id="comments">
          <h3 class="comments-heading">Comments <span class="comments-heading__count">(<?= count($comments) ?>)</span></h3>

          <?php if ($currentUser): ?>
          <?php if ($commentError): ?>
          <div class="alert alert--error comment-alert"><i class="fa-solid fa-circle-xmark"></i> <?= e($commentError) ?></div>
          <?php endif; ?>
          <form method="POST" action="/likhub/artwork.php?id=<?= $id ?>
            <input type="hidden" name="action" value="post_comment">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="comment-form__row">
              <div class="comment-form__avatar">
                <?php
                $myInit = '';
                foreach (explode(' ', trim($currentUser['name'])) as $p) $myInit .= strtoupper($p[0]);
                echo e(substr($myInit, 0, 2));
                ?>
              </div>
              <div class="comment-form__body">
                <textarea name="comment" class="form-control comment-form__textarea" rows="2" placeholder="Leave a comment…" required><?= isset($_POST['comment']) ? e($_POST['comment']) : '' ?></textarea>
                <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
              </div>
            </div>
          </form>
          <?php else: ?>
          <div class="alert alert--info comment-login-alert"><i class="fa-solid fa-circle-info"></i> <a href="/likhub/login.php">Log in</a> to leave a comment.</div>
          <?php endif; ?>

          <?php if ($comments): ?>
          <div class="comment-list">
            <?php foreach ($comments as $com):
              $ci = '';
              foreach (explode(' ', trim($com['author_name'])) as $p) $ci .= strtoupper($p[0]);
              $ci = substr($ci, 0, 2);
            ?>
            <div class="comment-item">
              <div class="comment-item__avatar"><?= e($ci) ?></div>
              <div>
                <div class="comment-item__author"><?= e($com['author_name']) ?></div>
                <div class="comment-item__date"><?= date('M j, Y · g:i A', strtotime($com['created_at'])) ?></div>
                <p class="comment-item__text"><?= nl2br(e($com['comment'])) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div class="comment-empty">
            <i class="fa-regular fa-comment"></i>
            <p>No comments yet. Be the first!</p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="form-section form-section--mb">
          <div class="form-section__title">About the Artist</div>
          <div class="artist-sidebar-header">
            <div class="artist-avatar-lg">
              <?php if ($artwork['artist_avatar']): ?>
              <img src="<?= e($artwork['artist_avatar']) ?>" alt="<?= e($artwork['artist_name']) ?>">
              <?php else: ?>
              <?= e($initials) ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="artist-sidebar-name"><?= e($artwork['artist_name']) ?></div>
              <a href="/likhub/artist-profile.php?id=<?= $artwork['artist_id'] ?>" class="card__link artist-sidebar-link">View Profile <i class="fa-solid fa-arrow-right"></i></a>
            </div>
          </div>
          <?php if ($artwork['artist_bio']): ?>
          <p class="artist-bio"><?= e(mb_strimwidth($artwork['artist_bio'], 0, 180, '…')) ?></p>
          <?php endif; ?>
          <a href="/likhub/commission.php?search=<?= urlencode($artwork['artist_name']) ?>" class="btn btn-primary btn-full btn-sm">
            <i class="fa-solid fa-handshake"></i> Commission This Artist
          </a>
        </div>

        <div class="form-section form-section--mb">
          <div class="form-section__title">Artwork Details</div>
          <?php if ($artwork['medium']): ?>
          <div class="artwork-detail-row">
            <div class="body-text">Medium</div>
            <div class="artwork-detail-value"><?= e($artwork['medium']) ?></div>
          </div>
          <?php endif; ?>
          <div class="artwork-detail-row">
            <div class="body-text">Uploaded</div>
            <div class="artwork-detail-value"><?= date('F j, Y', strtotime($artwork['created_at'])) ?></div>
          </div>
          <div>
            <div class="body-text">Engagement</div>
            <div class="engagement-row">
              <span class="engagement-stat">
                <i class="fa-solid fa-heart"></i> <span id="sidebarLikeCount"><?= $artwork['like_count'] ?></span>
              </span>
              <span class="engagement-stat">
                <i class="fa-regular fa-comment"></i> <?= count($comments) ?>
              </span>
            </div>
          </div>
        </div>

        <?php if ($artwork['description']): ?>
        <div class="form-section form-section--mb">
          <div class="form-section__title">Description</div>
          <p class="artwork-desc"><?= nl2br(e($artwork['description'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="form-section">
          <div class="form-section__title">Share This Artwork</div>
          <div class="artwork-share-row">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://likhub.ph/artwork.php?id=' . $id) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
              <i class="fa-brands fa-facebook-f"></i> Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?text=<?= urlencode('Check out this artwork on LikHub: ' . $artwork['title']) ?>&url=<?= urlencode('https://likhub.ph/artwork.php?id=' . $id) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
              <i class="fa-brands fa-x-twitter"></i> X
            </a>
          </div>
        </div>
      </div>
    </div>

    <?php if ($moreArtworks): ?>
    <div class="artwork-more-section">
      <div class="section__header flex-between">
        <h3>More by <?= e(explode(' ', $artwork['artist_name'])[0]) ?></h3>
        <a href="/likhub/artist-profile.php?id=<?= $artwork['artist_id'] ?>" class="btn btn-outline btn-sm">All Works <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="grid-4">
        <?php foreach ($moreArtworks as $aw): ?>
        <article class="card fade-up">
          <div class="card__img">
            <?php if ($aw['image_path']): ?>
            <img src="<?= e($aw['image_path']) ?>" alt="<?= e($aw['title']) ?>" loading="lazy">
            <?php else: ?>ARTWORK<?php endif; ?>
          </div>
          <div class="card__body">
            <div class="card__title"><?= e($aw['title']) ?></div>
            <div class="card__meta"><?= e($aw['medium'] ?? '') ?> · <?= $aw['like_count'] ?> <i class="fa-solid fa-heart card__meta-heart"></i></div>
            <a href="/likhub/artwork.php?id=<?= $aw['id'] ?>" class="card__link">View <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
