<?php
$active_nav = 'learn';
require_once __DIR__ . '/php/db.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /likhub/learn.php'); exit; }

$stmt = db()->prepare('SELECT t.*, u.name AS instructor_name, u.avatar AS instructor_avatar, u.bio AS instructor_bio FROM tutorials t JOIN users u ON u.id = t.instructor_id WHERE t.id = ? AND t.is_published = 1 LIMIT 1');
$stmt->execute([$id]);
$tutorial = $stmt->fetch();
if (!$tutorial) { header('Location: /likhub/learn.php'); exit; }

$page_title = e($tutorial['title']) . ' — LikHub Tutorials';

$initials = '';
foreach (explode(' ', trim($tutorial['instructor_name'])) as $p) $initials .= strtoupper($p[0]);
$initials = substr($initials, 0, 2);

$relStmt = db()->prepare('SELECT t.*, u.name AS instructor_name FROM tutorials t JOIN users u ON u.id = t.instructor_id WHERE t.id != ? AND t.is_published = 1 AND (t.topic = ? OR t.skill_level = ?) ORDER BY RAND() LIMIT 3');
$relStmt->execute([$id, $tutorial['topic'], $tutorial['skill_level']]);
$related = $relStmt->fetchAll();

$badgeClass = [
    'Beginner'     => 'beginner',
    'Intermediate' => 'intermediate',
    'Advanced'     => 'advanced',
];

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <nav class="breadcrumb">
      <a href="/likhub/learn.php" class="breadcrumb__link">Tutorials</a>
      <span class="breadcrumb__sep">›</span>
      <a href="/likhub/learn.php?topic=<?= urlencode($tutorial['topic'] ?? '') ?>" class="breadcrumb__link"><?= e($tutorial['topic'] ?? 'General') ?></a>
      <span class="breadcrumb__sep">›</span>
      <span class="breadcrumb__current"><?= e($tutorial['title']) ?></span>
    </nav>
    <span class="tcard__badge tcard__badge--<?= e(strtolower($badgeClass[$tutorial['skill_level']] ?? 'beginner')) ?> tutorial-header-meta__badge"><?= e($tutorial['skill_level']) ?></span>
    <h1><?= e($tutorial['title']) ?></h1>
    <div class="tutorial-header-meta">
      <span class="tutorial-meta-item"><i class="fa-solid fa-clock"></i><?= intval($tutorial['duration_mins']) ?> min</span>
      <span class="tutorial-meta-item"><i class="fa-solid fa-tag"></i><?= e($tutorial['topic'] ?? 'General') ?></span>
      <span class="tutorial-meta-item"><i class="fa-solid fa-user"></i><?= e($tutorial['instructor_name']) ?></span>
      <span class="tutorial-meta-item"><i class="fa-regular fa-calendar"></i><?= date('M j, Y', strtotime($tutorial['created_at'])) ?></span>
    </div>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="tutorial-layout">

      <article>

        <?php if ($tutorial['thumbnail']): ?>
        <div class="tutorial-thumb-wrap">
          <img src="<?= e($tutorial['thumbnail']) ?>" alt="<?= e($tutorial['title']) ?>">
        </div>
        <?php else: ?>
        <div class="tutorial-thumb-placeholder">
          <i class="fa-solid fa-book-open"></i>
        </div>
        <?php endif; ?>

        <div class="progress-wrap">
          <div class="progress-label">
            <span>Lesson Progress</span>
            <span id="progressLabel">0%</span>
          </div>
          <div class="progress-track">
            <div id="progressBar" class="progress-bar"></div>
          </div>
        </div>

        <?php if ($tutorial['description']): ?>
        <div class="form-section form-section--mb">
          <div class="form-section__title">Overview</div>
          <p class="tutorial-desc"><?= nl2br(e($tutorial['description'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="tutorial-body tutorial-body--mb" id="tutorialBody">
          <?php if ($tutorial['content']): ?>
            <?= $tutorial['content'] ?>
          <?php else: ?>
          <div class="lesson-placeholder">
            <i class="fa-solid fa-book-open"></i>
            <p class="lesson-placeholder__title">Full lesson content coming soon.</p>
            <p>The instructor is still writing this lesson. Check back shortly or <a href="/likhub/learn.php">browse other tutorials</a>.</p>
          </div>

          <div class="lesson-content">
            <h3>What You'll Learn in This Lesson</h3>
            <?php
            $topics_map = [
              'Anatomy'      => ['Understanding basic body proportions', 'Drawing the skeletal framework', 'Muscle placement and movement', 'Common beginner mistakes to avoid'],
              'Color Theory' => ['The color wheel and relationships', 'Warm vs. cool tones', 'Mixing and blending techniques', 'Creating mood through color'],
              'Composition'  => ['The rule of thirds', 'Leading lines and focal points', 'Negative space usage', 'Balance and visual weight'],
              'Digital Tools'=> ['Setting up your canvas in Clip Studio / Procreate', 'Layer management', 'Using brushes and textures', 'Exporting for print and web'],
              'Shading'      => ['Understanding light sources', 'Rendering forms with value', 'Soft vs. hard shadows', 'Blending modes in digital art'],
            ];
            $bullets = $topics_map[$tutorial['topic']] ?? [
              'Core concepts and vocabulary',
              'Step-by-step technique demonstrations',
              'Practice exercises with reference images',
              'Common pitfalls and how to fix them',
            ];
            ?>
            <ul class="lesson-objectives">
              <?php foreach ($bullets as $b): ?>
              <li class="lesson-objective">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= e($b) ?></span>
              </li>
              <?php endforeach; ?>
            </ul>

            <div class="lesson-tip">
              <div class="lesson-tip__label">Pro Tip</div>
              <p class="lesson-tip__text">Practice deliberately. Pick one concept from this lesson and spend 30 minutes drawing it from five different angles before moving on. Repetition builds muscle memory faster than anything else.</p>
            </div>

            <h3 class="lesson-exercise-h3">Suggested Practice Exercise</h3>
            <p>After watching the lesson, complete the following exercise to solidify your understanding:</p>
            <div class="lesson-exercise">
              <strong>Exercise:</strong>
              <p>Choose a reference photo and apply the <?= e(strtolower($tutorial['topic'] ?? 'concept')) ?> principles you learned today. Share your result in the LikHub gallery and tag it with the topic — the community would love to see your progress!</p>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="lesson-footer">
          <a href="/likhub/learn.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Tutorials</a>
          <?php if ($related): ?>
          <a href="/likhub/tutorial.php?id=<?= $related[0]['id'] ?>" class="btn btn-primary">Next Lesson <i class="fa-solid fa-arrow-right"></i></a>
          <?php endif; ?>
        </div>
      </article>

      <aside>

        <div class="form-section form-section--mb">
          <div class="form-section__title">Your Instructor</div>
          <div class="instructor-header">
            <div class="instructor-avatar">
              <?php if ($tutorial['instructor_avatar']): ?>
              <img src="<?= e($tutorial['instructor_avatar']) ?>" alt="<?= e($tutorial['instructor_name']) ?>">
              <?php else: ?>
              <?= e($initials) ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="instructor-name"><?= e($tutorial['instructor_name']) ?></div>
              <div class="instructor-role">Student Artist &amp; Instructor</div>
            </div>
          </div>
          <?php if ($tutorial['instructor_bio']): ?>
          <p class="instructor-bio"><?= e(mb_strimwidth($tutorial['instructor_bio'], 0, 160, '…')) ?></p>
          <?php endif; ?>
          <a href="/likhub/artist-profile.php?id=<?= $tutorial['instructor_id'] ?>" class="card__link instructor-link">View Artist Profile <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="form-section form-section--mb">
          <div class="form-section__title">Lesson Info</div>
          <?php
          $meta_items = [
            ['fa-solid fa-signal', 'Skill Level', $tutorial['skill_level']],
            ['fa-solid fa-tag',    'Topic',       $tutorial['topic'] ?? 'General'],
            ['fa-solid fa-clock',  'Duration',    $tutorial['duration_mins'] . ' minutes'],
          ];
          foreach ($meta_items as [$icon, $label, $val]):
          ?>
          <div class="tutorial-detail-row">
            <div class="tutorial-detail-icon">
              <i class="<?= $icon ?>"></i>
            </div>
            <div>
              <div class="tutorial-detail-label"><?= $label ?></div>
              <div class="tutorial-detail-value"><?= e($val) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="form-section form-section--mb">
          <div class="form-section__title">What You'll Need</div>
          <?php
          $needs = match(strtolower($tutorial['topic'] ?? '')) {
            'digital tools' => ['A drawing tablet (optional but helpful)', 'Clip Studio Paint, Procreate, or Krita', 'Stylus or mouse'],
            'anatomy'       => ['Sketchbook or drawing paper', 'Pencil (HB and 2B)', 'Reference anatomy images or books'],
            'color theory'  => ['Watercolor or colored pencils', 'Color wheel reference', 'White paper or canvas'],
            default         => ['Sketchbook or drawing paper', 'Pencils (HB, 2B)', 'Eraser and ruler'],
          };
          ?>
          <ul class="prereq-list">
            <?php foreach ($needs as $n): ?>
            <li class="prereq-item">
              <i class="fa-solid fa-circle-dot"></i>
              <?= e($n) ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="cta-box">
          <div class="cta-box__title">Want custom art lessons?</div>
          <p class="cta-box__desc">Commission a student artist for 1-on-1 guidance or personalized artwork.</p>
          <a href="/likhub/commission.php" class="btn btn-cta btn-full btn-sm">Find an Artist</a>
        </div>

      </aside>
    </div>

    <?php if ($related): ?>
    <div class="tutorial-more-section">
      <div class="section__header flex-between">
        <h3>Related Lessons</h3>
        <a href="/likhub/learn.php" class="btn btn-outline btn-sm">All Tutorials <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="grid-3">
        <?php foreach ($related as $r): ?>
        <article class="card fade-up">
          <div class="card__img" style="position:relative;aspect-ratio:16/9;background:linear-gradient(135deg,#b8c6db 0%,#9aafc9 50%,#c5cfe8 100%);overflow:hidden;">
            <?php if ($r['thumbnail'] && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $r['thumbnail'])): ?>
              <img src="<?= e($r['thumbnail']) ?>" alt="<?= e($r['title']) ?>" loading="lazy" class="card__img-fill" onerror="this.style.display='none'">
            <?php else: ?>
              <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:cursive;font-size:28px;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.15);"><?= e($r['title']) ?></div>
            <?php endif; ?>
          </div>
          <div class="card__body">
            <span class="tcard__badge tcard__badge--<?= e(strtolower($badgeClass[$r['skill_level']] ?? 'beginner')) ?>"><?= e($r['skill_level']) ?></span>
            <div class="card__title"><?= e($r['title']) ?></div>
            <div class="card__meta">
              <i class="fa-solid fa-tag"></i> <?= e($r['topic'] ?? '') ?> &nbsp;·&nbsp;
              <i class="fa-solid fa-clock"></i> <?= intval($r['duration_mins']) ?> min
            </div>
            <a href="/likhub/tutorial.php?id=<?= $r['id'] ?>" class="card__link">Start lesson <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>