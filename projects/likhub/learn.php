<?php
$page_title = 'Learn - Art Tutorials';
$active_nav = 'learn';
require_once __DIR__ . '/php/db.php';

$level  = $_GET['level']  ?? '';
$topic  = $_GET['topic']  ?? '';
$search = $_GET['search'] ?? '';

$where  = ['t.is_published = 1'];
$params = [];

if ($level && in_array($level, ['Beginner','Intermediate','Advanced'])) {
    $where[]  = 't.skill_level = ?';
    $params[] = $level;
}
if ($topic && in_array($topic, ['Drawing','Digital','Painting'])) {
    $where[]  = 't.topic = ?';
    $params[] = $topic;
}
if ($search) {
    $where[]  = '(t.title LIKE ? OR u.name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM tutorials t JOIN users u ON u.id = t.instructor_id $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$stmt = db()->prepare("SELECT t.*, u.name AS instructor_name FROM tutorials t JOIN users u ON u.id = t.instructor_id $whereSQL ORDER BY t.created_at DESC");
$stmt->execute($params);
$tutorials = $stmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <div class="section-label">Learning Center</div>
    <h1>Tutorial Library</h1>
    <p>Structured, beginner-friendly lessons from fellow student artists.</p>
  </div>
</header>

<section class="section">
  <div class="container">

    <form method="GET" action="/likhub/learn.php" aria-label="Filter tutorials">
      <div class="filter-bar">
        <span class="filter-bar__label">Level:</span>
        <?php foreach ([''=>'All','Beginner'=>'Beginner','Intermediate'=>'Intermediate','Advanced'=>'Advanced'] as $v => $l): ?>
          <button type="submit" name="level" value="<?= $v ?>" class="filter-chip<?= $level === $v ? ' active' : '' ?>"><?= $l ?></button>
        <?php endforeach; ?>
        <span class="filter-bar__label filter-label--spaced">Topic:</span>
        <?php foreach ([''=>'All','Drawing'=>'Traditional','Digital'=>'Digital','Painting'=>'Painting'] as $v => $l): ?>
          <button type="submit" name="topic" value="<?= $v ?>" class="filter-chip<?= $topic === $v ? ' active' : '' ?>"><?= $l ?></button>
        <?php endforeach; ?>
        <div class="search-input-wrap">
          <input type="text" name="search" placeholder="Search tutorials..." value="<?= e($search) ?>">
          <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
      </div>
    </form>

    <p class="body-text mb-24"><?= $total ?> tutorials found</p>
    
    <?php if ($tutorials): ?>
    <div class="grid-3">
      <?php $loop = 1; foreach ($tutorials as $t): ?>
      <article class="tcard fade-up">
        <div style="position:relative;width:100%;aspect-ratio:16/9;overflow:hidden;background:linear-gradient(135deg,#b8c6db 0%,#9aafc9 50%,#c5cfe8 100%);">
          <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:cursive;font-size:36px;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.15);">Tutorial <?= $loop ?></div>
        </div>
        <div class="tcard__body">
          <div class="tutorial-badge-row">
            <?php
              $badgeMap = [
                'Beginner'     => '/likhub/images/Website1_adges.jpg',
                'Intermediate' => '/likhub/images/Website2_badges.jpg',
                'Advanced'     => '/likhub/images/Website3_badges.jpg',
              ];
              $badgeImg = $badgeMap[$t['skill_level']] ?? null;
            ?>
            <?php if ($badgeImg): ?>
              <img src="<?= e($badgeImg) ?>" alt="<?= e($t['skill_level']) ?>" class="skill-badge-img" title="<?= e($t['skill_level']) ?>">
            <?php else: ?>
              <span class="tcard__badge tcard__badge--<?= strtolower($t['skill_level']) ?>"><?= e($t['skill_level']) ?></span>
            <?php endif; ?>
            <?php
              $topicMap = ['Drawing'=>'Traditional','Digital'=>'Digital','Painting'=>'Painting'];
              $topicLabel = $topicMap[$t['topic']] ?? $t['topic'];
            ?>
            <span class="tcard__topic"><?= e($topicLabel) ?></span>
          </div>
          <div class="tcard__title"><?= e($t['title']) ?></div>
          <div class="tcard__meta">
            <span>by <?= e($t['instructor_name']) ?></span>
            <span><i class="fa-regular fa-clock"></i> <?= e($t['duration_mins']) ?> min</span>
          </div>
          <a href="/likhub/tutorial.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-full btn-sm tutorial-watch-btn">Watch Tutorial</a>
        </div>
      <?php $loop++; ?>
      </article>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="text-center no-tutorials-section">
      <i class="fa-solid fa-book-open"></i>
      <h3>No tutorials found</h3>
      <p>Try adjusting your filters or <a href="/likhub/learn.php">clear all filters</a>.</p>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>