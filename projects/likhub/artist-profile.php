<?php
$page_title = 'Artist Profile';
$active_nav = 'commission';
require_once __DIR__ . '/php/db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /likhub/commission.php'); exit; }

$stmt = db()->prepare('SELECT u.*, ap.commission_status, ap.price_min, ap.price_max, ap.price_small, ap.price_medium, ap.price_large, ap.styles, ap.mediums, ap.avg_rating, ap.total_commissions FROM users u JOIN artist_profiles ap ON ap.user_id = u.id WHERE u.id = ? AND u.role = "artist" AND u.is_active = 1');
$stmt->execute([$id]);
$artist = $stmt->fetch();
if (!$artist) { header('Location: /likhub/commission.php'); exit; }

$page_title = e($artist['name']) . ' — Artist Profile';

$static_artwork_map = [
    'DRex'      => [
        ['image'=>'/likhub/images/DRex1_digital.jpg',     'title'=>'Knight in Muted Tones',    'medium'=>'Digital'],
        ['image'=>'/likhub/images/DRex2_digital.jpg',     'title'=>'Digital Collage Study',    'medium'=>'Digital'],
        ['image'=>'/likhub/images/DRex1_traditional.jpg', 'title'=>'Figure Composition I',     'medium'=>'Traditional'],
        ['image'=>'/likhub/images/DRex2_traditional.jpg', 'title'=>'Figure Composition II',    'medium'=>'Traditional'],
        ['image'=>'/likhub/images/DRex_painting.jpg',     'title'=>'Oil Study — Portrait',     'medium'=>'Painting'],
    ],
    'Sena'      => [
        ['image'=>'/likhub/images/Sena1_digital.jpg',     'title'=>'Chibi Character',          'medium'=>'Digital'],
        ['image'=>'/likhub/images/Sena2_digital.jpg',     'title'=>'Digital Illustration II',  'medium'=>'Digital'],
        ['image'=>'/likhub/images/Sena3_digital.jpg',     'title'=>'Digital Illustration III', 'medium'=>'Digital'],
        ['image'=>'/likhub/images/Sena1_traditional.jpg', 'title'=>'Expressive Linework',      'medium'=>'Traditional'],
        ['image'=>'/likhub/images/Sena1_painting.jpg',    'title'=>'Warm Tone Painting',       'medium'=>'Painting'],
    ],
    'Yeoj'      => [
        ['image'=>'/likhub/images/Yeoj1_digital.jpg',     'title'=>'Cyber Mech — Full Art',   'medium'=>'Digital'],
        ['image'=>'/likhub/images/Yeoj2_digital.jpg',     'title'=>'Sci-Fi Character II',      'medium'=>'Digital'],
        ['image'=>'/likhub/images/Yeoj3_digital.jpg',     'title'=>'Dynamic Pose Study',       'medium'=>'Digital'],
    ],
    'Yuhan'     => [
        ['image'=>'/likhub/images/Yuhan1_digital.jpg',    'title'=>'Pastel Character Study I', 'medium'=>'Digital'],
        ['image'=>'/likhub/images/Yuhan2_digital.jpg',    'title'=>'Soft Shading Study II',    'medium'=>'Digital'],
        ['image'=>'/likhub/images/Yuhan3_digital.jpg',    'title'=>'Digital Portrait III',     'medium'=>'Digital'],
        ['image'=>'/likhub/images/Yuhan4_digital.jpg',    'title'=>'Character Sketch IV',      'medium'=>'Digital'],
    ],
    'Keiose VB' => [
        ['image'=>'/likhub/images/Keiose VB1_traditional.jpg', 'title'=>'Fantasy Composition I',  'medium'=>'Traditional'],
        ['image'=>'/likhub/images/Keiose VB2_traditional.jpg', 'title'=>'Ink Illustration II',     'medium'=>'Traditional'],
        ['image'=>'/likhub/images/Keiose VB3_traditional.jpg', 'title'=>'Character Study III',     'medium'=>'Traditional'],
    ],
    'Andrea'    => [
        ['image'=>'/likhub/images/Andrea1_digital.jpg',   'title'=>'Digital Illustration I',   'medium'=>'Digital'],
        ['image'=>'/likhub/images/Andrea2_digital.jpg',   'title'=>'Digital Illustration II',  'medium'=>'Digital'],
        ['image'=>'/likhub/images/Andrea3_digital.jpg',   'title'=>'Digital Illustration III', 'medium'=>'Digital'],
    ],
];

init_artist_uploads_table();
$uploadStmt = db()->prepare('SELECT * FROM artist_uploads WHERE user_id = ? ORDER BY created_at DESC');
$uploadStmt->execute([$id]);
$dbUploads = $uploadStmt->fetchAll();

$artworks = [];
foreach ($dbUploads as $u) {
    $artworks[] = ['image_path'=>'/likhub/images/uploads/'.$u['filename'], 'title'=>$u['title'], 'medium'=>$u['medium'], 'like_count'=>0, 'id'=>null];
}
foreach ($static_artwork_map[$artist['name']] ?? [] as $aw) {
    $artworks[] = ['image_path'=>$aw['image'], 'title'=>$aw['title'], 'medium'=>$aw['medium'], 'like_count'=>0, 'id'=>null];
}

$reviews = [];
init_commission_requests_table();
$rStmt = db()->prepare(
    'SELECT cr.rating, cr.review_text, cr.created_at AS completed_at, u.name AS client_name
     FROM commission_requests cr
     JOIN users u ON u.id = cr.user_id
     WHERE cr.artist_name = ? AND cr.rating IS NOT NULL
     ORDER BY cr.created_at DESC LIMIT 10'
);
$rStmt->execute([$artist['name']]);
$reviews = $rStmt->fetchAll();

try {
    $legacyStmt = db()->prepare(
        'SELECT c.rating, c.review_text, c.completed_at, u.name AS client_name
         FROM commissions c JOIN users u ON u.id = c.client_id
         WHERE c.artist_id = ? AND c.rating IS NOT NULL
         ORDER BY c.completed_at DESC LIMIT 6'
    );
    $legacyStmt->execute([$id]);
    $legacyReviews = $legacyStmt->fetchAll();
    
    $reviews = array_merge($reviews, $legacyReviews);
    usort($reviews, fn($a, $b) => strtotime($b['completed_at']) - strtotime($a['completed_at']));
    $reviews = array_slice($reviews, 0, 10);
} catch (PDOException $e) {  }

$avgStmt = db()->prepare(
    'SELECT ROUND(AVG(rating), 1) AS avg_r, COUNT(*) AS total
     FROM commission_requests WHERE artist_name = ? AND rating IS NOT NULL'
);
$avgStmt->execute([$artist['name']]);
$liveStats = $avgStmt->fetch();

if ($liveStats['total'] > 0) {
    $artist['avg_rating']       = $liveStats['avg_r'];
    $artist['total_commissions'] = max((int)$artist['total_commissions'], (int)$liveStats['total']);
}

$initials = '';
foreach (explode(' ', trim($artist['name'])) as $p) $initials .= strtoupper($p[0]);
$initials = substr($initials, 0, 2);

$tags = $artist['styles']  ? array_map('trim', explode(',', $artist['styles']))  : [];
$meds = $artist['mediums'] ? array_map('trim', explode(',', $artist['mediums'])) : [];

include __DIR__ . '/partials/header.php';
?>

<div class="profile-header">
  <div class="container">
    <div class="profile-header__inner">
      <div class="profile-avatar">
        <?php if ($artist['avatar']): ?><img src="<?= e($artist['avatar']) ?>" alt="<?= e($artist['name']) ?>"><?php else: ?><?= e($initials) ?><?php endif; ?>
      </div>
      <div class="profile-header__info">
        <h2><?= e($artist['name']) ?></h2>
        <div class="profile-header__meta">
          <?php if ($artist['avg_rating']): ?>
          <span><i class="fa-solid fa-star fa-star--gold"></i> <?= number_format($artist['avg_rating'], 1) ?> avg rating</span>
          <?php endif; ?>
          <span><i class="fa-solid fa-check-circle fa-check-circle--green"></i> <?= $artist['total_commissions'] ?> commissions</span>
          <?php if ($artist['price_min'] && $artist['price_max']): ?>
          <span><i class="fa-solid fa-peso-sign"></i> ₱<?= number_format($artist['price_min'],0) ?>–₱<?= number_format($artist['price_max'],0) ?></span>
          <?php endif; ?>
        </div>
        <div class="artist-action-row">
          <span class="status-badge status-badge--<?= $artist['commission_status'] === 'open' ? 'open' : 'closed' ?>">
            <?= $artist['commission_status'] === 'open' ? 'Open for Commissions' : 'Commissions Closed' ?>
          </span>
          <?php if ($artist['commission_status'] === 'open'): ?>
          <a href="/likhub/commission.php" class="btn btn-primary btn-sm">Inquire Now</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="artist-profile-layout">

      
      <div>
        <?php if ($artist['bio']): ?>
        <div class="form-section form-section--mb">
          <div class="form-section__title">About</div>
          <p class="artist-bio"><?= nl2br(e($artist['bio'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($meds): ?>
        <div class="form-section form-section--mb">
          <div class="form-section__title">Mediums</div>
          <div class="artist-card__tags"><?php foreach ($meds as $m): ?><span class="tag"><?= e($m) ?></span><?php endforeach; ?></div>
        </div>
        <?php endif; ?>

        <?php if ($tags): ?>
        <div class="form-section form-section--mb">
          <div class="form-section__title">Styles</div>
          <div class="artist-card__tags"><?php foreach ($tags as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div>
        </div>
        <?php endif; ?>

        <?php
          
          $default_small  = 300;  $default_medium = 900;  $default_large = 3000;
          $p_small  = !empty($artist['price_small'])  ? (int)$artist['price_small']  : $default_small;
          $p_medium = !empty($artist['price_medium']) ? (int)$artist['price_medium'] : $default_medium;
          $p_large  = !empty($artist['price_large'])  ? (int)$artist['price_large']  : $default_large;
          $using_defaults = empty($artist['price_small']) && empty($artist['price_medium']) && empty($artist['price_large']);
        ?>
        <div class="form-section">
          <div class="form-section__title">Pricing</div>
          <?php if ($using_defaults): ?>
          <div class="u-alert-sm alert alert--info">
            <i class="fa-solid fa-circle-info"></i> Standard rates apply — artist hasn't set custom pricing yet.
          </div>
          <?php endif; ?>
          <div class="body-text">Small (500px)</div>
          <p class="artist-pricing__price">₱<?= number_format($p_small) ?></p>
          <div class="body-text artist-pricing__label">Medium (1200px)</div>
          <p class="artist-pricing__price">₱<?= number_format($p_medium) ?></p>
          <div class="body-text artist-pricing__label">Large (2400px)</div>
          <p class="artist-pricing__price">₱<?= number_format($p_large) ?></p>
          <a href="/likhub/size-guide.php" class="card__link artist-pricing__link">Size Guide <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </div>

      
      <div>
        <h3 class="portfolio-heading">Portfolio</h3>
        <?php if ($artworks): ?>
        <div class="grid-3">
          <?php foreach ($artworks as $aw): ?>
          <div class="card fade-up">
            <div class="card__img">
              <?php if ($aw['image_path']): ?><img src="<?= e($aw['image_path']) ?>" alt="<?= e($aw['title']) ?>" loading="lazy" class="card__img-fill"><?php else: ?>ARTWORK<?php endif; ?>
            </div>
            <div class="card__body">
              <div class="card__title"><?= e($aw['title']) ?></div>
              <div class="card__meta"><span class="tag"><?= e($aw['medium']) ?></span></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center portfolio-empty">
          <i class="fa-solid fa-palette"></i>
          <p>No artworks published yet.</p>
        </div>
        <?php endif; ?>

        
        <?php if ($reviews): ?>
        <h3 class="reviews-heading">Client Reviews</h3>
        <div class="grid-2">
          <?php foreach ($reviews as $r):
            $wi = '';
            foreach (explode(' ', trim($r['client_name'])) as $p) $wi .= strtoupper($p[0]);
            $wi = substr($wi, 0, 2);
          ?>
          <div class="review-card fade-up">
            <div class="review-card__stars"><?= render_stars((int)$r['rating']) ?></div>
            <?php if ($r['review_text']): ?><div class="blockquote"><?= e($r['review_text']) ?></div><?php endif; ?>
            <div class="review-card__author">
              <div class="review-card__avatar"><?= e($wi) ?></div>
              <div>
                <div class="review-card__name"><?= e($r['client_name']) ?></div>
                <div class="review-card__role">Verified Client</div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>