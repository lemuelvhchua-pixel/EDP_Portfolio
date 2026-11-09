<?php
ob_start();
$page_title = 'Where Art Finds Its Home';
$active_nav = 'home';
require_once __DIR__ . '/php/db.php';

try {
    $stmt = db()->query('SELECT t.*, u.name AS instructor_name FROM tutorials t JOIN users u ON u.id = t.instructor_id WHERE t.is_published = 1 ORDER BY t.created_at DESC LIMIT 3');
    $tutorials = $stmt->fetchAll();
} catch (PDOException $e) { $tutorials = []; }

try {
    $stmt = db()->query('SELECT u.*, ap.avg_rating, ap.commission_status, ap.price_min, ap.price_max FROM users u JOIN artist_profiles ap ON ap.user_id = u.id WHERE u.role = "artist" AND u.is_active = 1 ORDER BY ap.avg_rating DESC LIMIT 3');
    $artists = $stmt->fetchAll();
} catch (PDOException $e) { $artists = []; }

try {
    $upRows = db()->query('SELECT user_id, filename FROM artist_uploads ORDER BY created_at DESC')->fetchAll();
} catch (PDOException $e) { $upRows = []; }

$uploadsByUser = [];
foreach ($upRows as $u) {
  $uploadsByUser[$u['user_id']][] = '/likhub/images/uploads/' . $u['filename'];
}
$static_map = [
  'DRex'      => '/likhub/images/DRex1_digital.jpg',
  'Sena'      => '/likhub/images/Sena1_digital.jpg',
  'Yeoj'      => '/likhub/images/Yeoj1_digital.jpg',
  'Yuhan'     => '/likhub/images/Yuhan1_digital.jpg',
  'Keiose VB' => '/likhub/images/Keiose VB1_traditional.jpg',
  'Andrea'    => '/likhub/images/Andrea1_digital.jpg',
];
foreach ($artists as &$a) {
  $uploads = $uploadsByUser[$a['id']] ?? [];
  $a['cover_image'] = $uploads[0] ?? ($static_map[$a['name']] ?? null);
}
unset($a);

try {
    $stmt = db()->query('SELECT c.rating, c.review_text, u.name AS client_name FROM commissions c JOIN users u ON u.id = c.client_id WHERE c.rating IS NOT NULL AND c.review_text IS NOT NULL ORDER BY c.rating DESC, c.completed_at DESC LIMIT 3');
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) { $reviews = []; }

try { $stat_tutorials = (int) db()->query('SELECT COUNT(*) FROM tutorials WHERE is_published = 1')->fetchColumn(); }
catch (PDOException $e) { $stat_tutorials = 0; }

try { $stat_artists = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "artist" AND is_active = 1')->fetchColumn(); }
catch (PDOException $e) { $stat_artists = 0; }

try { $stat_learners = (int) db()->query('SELECT COUNT(*) FROM users WHERE role IN ("learner","client") AND is_active = 1')->fetchColumn(); }
catch (PDOException $e) { $stat_learners = 0; }

try { $stat_commissions = (int) db()->query('SELECT COUNT(*) FROM commission_requests WHERE status = "Completed"')->fetchColumn(); }
catch (PDOException $e) { $stat_commissions = 0; }

try { $stat_commissions += (int) db()->query('SELECT COUNT(*) FROM commissions WHERE status = "Completed"')->fetchColumn(); }
catch (PDOException $e) { }

function fmt_stat(int $n): string {
    if ($n === 0) return '0';
    if ($n < 10)  return (string)$n;
    $magnitude = pow(10, floor(log10($n)));
    $rounded   = (int)(floor($n / $magnitude) * $magnitude);
    return number_format($rounded) . '+';
}

include __DIR__ . '/partials/header.php';
?>

<section class="hero" aria-label="Welcome to LikHub">
  <div class="hero__bg" aria-hidden="true"></div>
  <img src="/likhub/images/Website1_asset.jpg" alt="" class="hero__asset" aria-hidden="true">
  <div class="container">
    <div class="fade-up">
      <div class="section-label">A Student-Driven Art Platform</div>
      <h1>Where Art Finds<br><em>Its Home</em></h1>
      <p class="hero__sub">Learn drawing skills, showcase your artwork, and commission talented student artists, all in one place.</p>
      <div class="hero__actions">
        <a href="/likhub/register.php" class="btn btn-primary btn-lg">Start Learning Free</a>
        <a href="/likhub/commission.php" class="btn btn-outline btn-lg btn--white-outline">Browse Artists</a>
      </div>
    </div>
  </div>
</section>

<div class="stat-bar">
  <div class="container">
    <div class="stat-bar__grid">
      <div class="stat-bar__item fade-up">
        <div class="stat-bar__num"><?= fmt_stat($stat_tutorials) ?></div>
        <div class="stat-bar__label">Tutorials</div>
      </div>
      <div class="stat-bar__item fade-up fade-up-delay-1">
        <div class="stat-bar__num"><?= fmt_stat($stat_artists) ?></div>
        <div class="stat-bar__label">Student Artists</div>
      </div>
      <div class="stat-bar__item fade-up fade-up-delay-2">
        <div class="stat-bar__num"><?= fmt_stat($stat_learners) ?></div>
        <div class="stat-bar__label">Learners</div>
      </div>
      <div class="stat-bar__item fade-up fade-up-delay-3">
        <div class="stat-bar__num"><?= fmt_stat($stat_commissions) ?></div>
        <div class="stat-bar__label">Commissions Done</div>
      </div>
    </div>
  </div>
</div>

<section class="section demo-reel" aria-label="LikHub demo reel">
  <div class="container">
    <div class="section__header section__header--center">
      <div class="section-label">See It In Action</div>
      <h2>Watch the Demo Reel</h2>
      <p>Get a feel for everything LikHub has to offer — from tutorials and artist profiles to the full commission experience.</p>
    </div>
    <div class="demo-reel__frame fade-up">
      <div class="demo-reel__ratio">
        <iframe
          src="https://www.youtube.com/embed/Gnzah1FPheE"
          title="LikHub Demo Reel"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          referrerpolicy="strict-origin-when-cross-origin"
          allowfullscreen>
        </iframe>
      </div>
    </div>
  </div>
</section>

<section class="section" aria-label="Featured tutorials">
  <div class="container">
    <div class="section__header flex-between">
      <div>
        <div class="section-label">Featured Tutorials</div>
        <h2>Start Your Art Journey</h2>
      </div>
      <a href="/likhub/learn.php" class="btn btn-outline btn-sm">View All <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <?php if ($tutorials): ?>
    <div class="grid-3">
      <?php foreach ($tutorials as $t): ?>
      <article class="tcard fade-up">
        <div class="tcard__img-wrap"><?= $t['thumbnail'] ? '<img src="' . e($t['thumbnail']) . '" alt="' . e($t['title']) . '">' : '' ?></div>
        <div class="tcard__body">
          <span class="tcard__badge tcard__badge--<?= strtolower($t['skill_level']) ?>"><?= e($t['skill_level']) ?></span>
          <div class="tcard__title"><?= e($t['title']) ?></div>
          <div class="tcard__meta">
            <span>by <?= e($t['instructor_name']) ?></span>
            <span><i class="fa-regular fa-clock"></i> <?= e($t['duration_mins']) ?> min</span>
          </div>
          <a href="/likhub/tutorial.php?id=<?= $t['id'] ?>" class="card__link">Learn more <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--pale" aria-label="How LikHub works">
  <div class="container">
    <div class="section__header section__header--center">
      <div class="section-label">How It Works</div>
      <h2>Three Pillars of LikHub</h2>
    </div>
    <div class="pillars-grid">
      <div class="pillar fade-up">
        <div class="pillar__num">01</div>
        <div class="pillar__title">Learn</div>
        <p>Access structured tutorials by student artists. Filter by skill level, medium, or topic and grow at your own pace.</p>
        <a href="/likhub/learn.php" class="pillar__link">Explore Learn <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="pillar fade-up fade-up-delay-1">
        <div class="pillar__num">02</div>
        <div class="pillar__title">Create</div>
        <p>Upload your artwork, build a portfolio, and receive constructive feedback from a supportive creative community.</p>
        <a href="/likhub/create.php" class="pillar__link">Explore Create <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="pillar fade-up fade-up-delay-2">
        <div class="pillar__num">03</div>
        <div class="pillar__title">Commission</div>
        <p>Connect with talented student artists for affordable custom illustrations, portraits, and more.</p>
        <a href="/likhub/commission.php" class="pillar__link">Explore Commission <i class="fa-solid fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</section>

<section class="section" aria-label="Artist spotlights">
  <div class="container">
    <div class="section__header flex-between">
      <div>
        <div class="section-label">Artist Spotlight</div>
        <h2>Meet Our Creators</h2>
      </div>
      <a href="/likhub/commission.php" class="btn btn-outline btn-sm">All Artists <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <?php if ($artists): ?>
    <div class="grid-3">
      <?php foreach ($artists as $a):
        $initials = substr(implode('', array_map(fn($p) => strtoupper($p[0]), explode(' ', trim($a['name'])))), 0, 2);
      ?>
      <article class="artist-card fade-up">
        <div class="artist-card__cover" style="background-image:url(<?= $a['cover_image'] ? e($a['cover_image']) : '/likhub/images/website_asset.jpg' ?>);background-size:contain;background-position:center;background-repeat:no-repeat;background-color:#f5f5f5;">
        </div>
        <div class="artist-card__body">
          <div class="artist-card__row">
            <div class="artist-card__avatar"><?php if ($a['avatar']): ?><img src="<?= e($a['avatar']) ?>" alt=""><?php else: ?><?= e($initials) ?><?php endif; ?></div>
            <div class="status-badge status-badge--<?= $a['commission_status'] === 'open' ? 'open' : 'closed' ?>">
              <?= $a['commission_status'] === 'open' ? 'Open' : 'Closed' ?>
            </div>
          </div>
          <div class="artist-card__name"><?= e($a['name']) ?></div>
          <?php if ($a['avg_rating'] > 0): ?>
          <div class="artist-card__rating">
            <span class="stars"><?= str_repeat('★', round($a['avg_rating'])) ?></span> <?= number_format($a['avg_rating'], 1) ?>
          </div>
          <?php endif; ?>
          <?php
            $p_min = (int)($a['price_min'] ?: 0) ?: 300;
            $p_max = (int)($a['price_max'] ?: 0) ?: 3000;
          ?>
          <div class="artist-card__price">Starting from <strong>₱<?= number_format($p_min) ?> – ₱<?= number_format($p_max) ?></strong></div>
          <a href="/likhub/artist-profile.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm btn-full mt-16">View Profile</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section aria-label="Join LikHub promotion">
  <div class="container">
    <div class="promo">
      <div>
        <div class="promo__badge">Featured</div>
        <div class="promo__title">Join LikHub Today</div>
        <div class="promo__sub">Your creative journey starts here. Free to learn, free to share.</div>
      </div>
      <a href="/likhub/register.php" class="btn btn-cta btn-lg">Get Started</a>
    </div>
  </div>
</section>

<?php if ($reviews): ?>
<section class="section section--pale" aria-label="Client reviews">
  <div class="container">
    <div class="section__header section__header--center">
      <div class="section-label">Testimonials</div>
      <h2>What People Are Saying</h2>
    </div>
    <div class="grid-3">
      <?php foreach ($reviews as $r):
        $init = substr(implode('', array_map(fn($w) => strtoupper($w[0]), explode(' ', trim($r['client_name'])))), 0, 2);
      ?>
      <div class="review-card fade-up">
        <div class="review-card__stars"><?= str_repeat('★', intval($r['rating'])) ?></div>
        <p class="review-text">"<?= e($r['review_text']) ?>"</p>
        <div class="review-card__author">
          <div class="review-card__avatar"><?= e($init) ?></div>
          <div>
            <div class="review-card__name"><?= e($r['client_name']) ?></div>
            <div class="review-card__role">Verified Client</div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>