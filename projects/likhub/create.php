<?php
$page_title = 'Create - Artwork Gallery';
$active_nav = 'create';
require_once __DIR__ . '/php/db.php';

$medium = $_GET['medium'] ?? '';
$search = $_GET['search'] ?? '';
$mediums = ['Digital','Traditional','Painting'];

$all_artworks = [
  
  ['id'=>1,  'title'=>'Knight in Muted Tones',   'artist'=>'DRex',     'medium'=>'Digital',     'image'=>'/likhub/images/DRex1_digital.jpg',      'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>2,  'title'=>'Digital Collage Study',    'artist'=>'DRex',     'medium'=>'Digital',     'image'=>'/likhub/images/DRex2_digital.jpg',      'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>3,  'title'=>'Figure Composition I',     'artist'=>'DRex',     'medium'=>'Traditional', 'image'=>'/likhub/images/DRex1_traditional.jpg',  'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>4,  'title'=>'Figure Composition II',    'artist'=>'DRex',     'medium'=>'Traditional', 'image'=>'/likhub/images/DRex2_traditional.jpg',  'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>5,  'title'=>'Linework Study III',       'artist'=>'DRex',     'medium'=>'Traditional', 'image'=>'/likhub/images/DRex3_traditional.jpg',  'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>6,  'title'=>'Warrior Sketch IV',        'artist'=>'DRex',     'medium'=>'Traditional', 'image'=>'/likhub/images/DRex4_traditional.jpg',  'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>7,  'title'=>'Oil Study — Portrait',     'artist'=>'DRex',     'medium'=>'Painting',    'image'=>'/likhub/images/DRex_painting.jpg',      'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  ['id'=>8,  'title'=>'Abstract Painting',        'artist'=>'DRex',     'medium'=>'Painting',    'image'=>'/likhub/images/DRex2_painting.jpg',     'pfp'=>'/likhub/images/DRex_pfp.jpg'],
  
  ['id'=>9,  'title'=>'Chibi Character',          'artist'=>'Sena',     'medium'=>'Digital',     'image'=>'/likhub/images/Sena1_digital.jpg',      'pfp'=>'/likhub/images/Sena_pfp.jpg'],
  ['id'=>28, 'title'=>'Digital Illustration II',  'artist'=>'Sena',     'medium'=>'Digital',     'image'=>'/likhub/images/Sena2_digital.jpg',      'pfp'=>'/likhub/images/Sena_pfp.jpg'],
  ['id'=>29, 'title'=>'Digital Illustration III', 'artist'=>'Sena',     'medium'=>'Digital',     'image'=>'/likhub/images/Sena3_digital.jpg',      'pfp'=>'/likhub/images/Sena_pfp.jpg'],
  ['id'=>10, 'title'=>'Expressive Linework',      'artist'=>'Sena',     'medium'=>'Traditional', 'image'=>'/likhub/images/Sena1_traditional.jpg',  'pfp'=>'/likhub/images/Sena_pfp.jpg'],
  ['id'=>11, 'title'=>'Seated Figure Study',      'artist'=>'Sena',     'medium'=>'Traditional', 'image'=>'/likhub/images/Sena2_traditional.jpg',  'pfp'=>'/likhub/images/Sena_pfp.jpg'],
  ['id'=>12, 'title'=>'Warm Tone Painting',       'artist'=>'Sena',     'medium'=>'Painting',    'image'=>'/likhub/images/Sena1_painting.jpg',     'pfp'=>'/likhub/images/Sena_pfp.jpg'],
  
  ['id'=>13, 'title'=>'Cyber Mech — Full Art',    'artist'=>'Yeoj',     'medium'=>'Digital',     'image'=>'/likhub/images/Yeoj1_digital.jpg',      'pfp'=>'/likhub/images/Yeoj_pfp.jpg'],
  ['id'=>14, 'title'=>'Sci-Fi Character II',      'artist'=>'Yeoj',     'medium'=>'Digital',     'image'=>'/likhub/images/Yeoj2_digital.jpg',      'pfp'=>'/likhub/images/Yeoj_pfp.jpg'],
  ['id'=>15, 'title'=>'Dynamic Pose Study',       'artist'=>'Yeoj',     'medium'=>'Digital',     'image'=>'/likhub/images/Yeoj3_digital.jpg',      'pfp'=>'/likhub/images/Yeoj_pfp.jpg'],
  
  ['id'=>16, 'title'=>'Pastel Character Study I', 'artist'=>'Yuhan',    'medium'=>'Digital',     'image'=>'/likhub/images/Yuhan1_digital.jpg',     'pfp'=>null],
  ['id'=>17, 'title'=>'Soft Shading Study II',    'artist'=>'Yuhan',    'medium'=>'Digital',     'image'=>'/likhub/images/Yuhan2_digital.jpg',     'pfp'=>null],
  ['id'=>18, 'title'=>'Digital Portrait III',     'artist'=>'Yuhan',    'medium'=>'Digital',     'image'=>'/likhub/images/Yuhan3_digital.jpg',     'pfp'=>null],
  ['id'=>19, 'title'=>'Character Sketch IV',      'artist'=>'Yuhan',    'medium'=>'Digital',     'image'=>'/likhub/images/Yuhan4_digital.jpg',     'pfp'=>null],
  
  ['id'=>20, 'title'=>'Fantasy Composition I',    'artist'=>'Keiose VB', 'medium'=>'Traditional', 'image'=>'/likhub/images/Keiose VB1_traditional.jpg', 'pfp'=>'/likhub/images/Keiose VB_pfp.jpg'],
  ['id'=>21, 'title'=>'Ink Illustration II',      'artist'=>'Keiose VB', 'medium'=>'Traditional', 'image'=>'/likhub/images/Keiose VB2_traditional.jpg', 'pfp'=>'/likhub/images/Keiose VB_pfp.jpg'],
  ['id'=>22, 'title'=>'Character Study III',      'artist'=>'Keiose VB', 'medium'=>'Traditional', 'image'=>'/likhub/images/Keiose VB3_traditional.jpg', 'pfp'=>'/likhub/images/Keiose VB_pfp.jpg'],
  
  ['id'=>23, 'title'=>'Digital Illustration I',   'artist'=>'Andrea',   'medium'=>'Digital',     'image'=>'/likhub/images/Andrea1_digital.jpg',    'pfp'=>null],
  ['id'=>24, 'title'=>'Digital Illustration II',  'artist'=>'Andrea',   'medium'=>'Digital',     'image'=>'/likhub/images/Andrea2_digital.jpg',    'pfp'=>null],
  ['id'=>25, 'title'=>'Digital Illustration III', 'artist'=>'Andrea',   'medium'=>'Digital',     'image'=>'/likhub/images/Andrea3_digital.jpg',    'pfp'=>null],
  ['id'=>26, 'title'=>'Digital Illustration IV',  'artist'=>'Andrea',   'medium'=>'Digital',     'image'=>'/likhub/images/Andrea4_digital.jpg',    'pfp'=>null],
  ['id'=>27, 'title'=>'Digital Illustration V',   'artist'=>'Andrea',   'medium'=>'Digital',     'image'=>'/likhub/images/Andrea5_digital.jpg',    'pfp'=>null],
];

$artworks = array_filter($all_artworks, function($aw) use ($medium, $search) {
  if ($medium && $aw['medium'] !== $medium) return false;
  if ($search && stripos($aw['title'].$aw['artist'], $search) === false) return false;
  return true;
});
$artworks = array_values($artworks);

try {
    init_artist_uploads_table();
    $uploadQuery = 'SELECT au.*, u.id AS artist_user_id, u.name AS artist_display_name, u.avatar AS artist_pfp
                    FROM artist_uploads au
                    LEFT JOIN users u ON u.id = au.user_id AND u.role = "artist"' .
                   ($medium ? ' WHERE au.medium = ?' : '') .
                   ' ORDER BY au.created_at DESC';
    $uploadStmt = db()->prepare($uploadQuery);
    $uploadStmt->execute($medium ? [$medium] : []);
    $dbUploads = $uploadStmt->fetchAll();
    foreach ($dbUploads as $u) {
        if ($search && stripos($u['title'] . $u['artist_name'], $search) === false) continue;
        $artworks[] = [
            'id'             => 'u' . $u['id'],
            'title'          => $u['title'],
            'artist'         => $u['artist_name'],
            'artist_user_id' => $u['artist_user_id'],
            'medium'         => $u['medium'],
            'image'          => '/likhub/images/uploads/' . $u['filename'],
            'pfp'            => $u['artist_pfp'],
        ];
    }
} catch (Exception $e) {
    error_log('create.php DB error: ' . $e->getMessage());
}

$total = count($artworks);

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <h1>Artwork Gallery</h1>
    <p>Browse original work by student artists. Click any piece to view it in full. Like, leave feedback, and discover new talent.</p>
  </div>
</header>

<section class="section">
  <div class="container">

    
    <div class="gallery-request-banner">
      <div>
        <strong>Are you a student artist?</strong>
        <p>Submit your artwork to be featured in the LikHub gallery and start receiving commissions.</p>
      </div>
      <button class="btn btn-cta btn-sm" id="btnRequestPost"
        data-login-required="<?= !current_user() ? '/likhub/login.php?redirect=' . urlencode('/likhub/create.php') : '' ?>"
        data-util-class="u-nowrap">
        <i class="fa-solid fa-upload"></i> Request to Post
      </button>
    </div>

    <form class="filter-bar" method="GET" action="/likhub/create.php" aria-label="Filter artworks">
      <span class="filter-bar__label">Medium:</span>
      <button type="submit" name="medium" value="" class="filter-chip<?= !$medium ? ' active' : '' ?>">All</button>
      <?php foreach ($mediums as $m): ?>
        <button type="submit" name="medium" value="<?= $m ?>" class="filter-chip<?= $medium === $m ? ' active' : '' ?>"><?= $m ?></button>
      <?php endforeach; ?>
      <div class="search-input-wrap">
        <input type="text" name="search" placeholder="Search artworks or artists…" value="<?= e($search) ?>" aria-label="Search artworks">
        <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
      </div>
    </form>

    <?php if ($artworks): ?>
    <p class="body-text mb-16">Showing <?= $total ?> artwork<?= $total !== 1 ? 's' : '' ?> &nbsp;·&nbsp; <small>Click any image to view full size</small></p>
    <div class="grid-4">
      <?php foreach ($artworks as $aw): ?>
      <article class="card fade-up">
        <div class="card__img js-lightbox-trigger"
             data-src="<?= e($aw['image'] ?? '') ?>"
             data-caption="<?= e($aw['title']) ?> by <?= e($aw['artist']) ?> · <?= e($aw['medium']) ?>">
          <?php if ($aw['image']): ?>
            <div class="card__img-blur-bg" style="background-image:url('<?= e($aw['image']) ?>')"></div>
            <img src="<?= e($aw['image']) ?>" alt="<?= e($aw['title']) ?>" loading="lazy">
          <?php else: ?>
            <div class="u-flex-center-full">
              <i class="u-fs-28 u-opacity-4 fa-solid fa-image"></i>
              <span class="u-fs-11 u-opacity-6">No Image</span>
            </div>
          <?php endif; ?>
        </div>
        <div class="card__body">
          <div class="card__artist-row">
            <?php if ($aw['pfp']): ?>
              <img src="<?= e($aw['pfp']) ?>" alt="<?= e($aw['artist']) ?>" class="card__artist-pfp">
            <?php else: ?>
              <div class="card__artist-initials"><?= e(strtoupper(substr($aw['artist'], 0, 2))) ?></div>
            <?php endif; ?>
            <?php if (!empty($aw['artist_user_id'])): ?>
              <a href="/likhub/artist-profile.php?id=<?= $aw['artist_user_id'] ?>" class="card__artist-name"><?= e($aw['artist']) ?></a>
            <?php else: ?>
              <span class="card__artist-name"><?= e($aw['artist']) ?></span>
            <?php endif; ?>
          </div>
          <div class="card__title"><?= e($aw['title']) ?></div>
          <div class="card__meta"><?= e($aw['medium']) ?></div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="no-results text-center">
      <i class="fa-solid fa-palette"></i>
      <h3>No artworks found</h3>
      <p><a href="/likhub/create.php">Clear filters</a> or check back soon.</p>
    </div>
    <?php endif; ?>

  </div>
</section>

<div class="lightbox-overlay" id="lightbox" role="dialog" aria-modal="true" aria-label="Artwork viewer">
  <button class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
  <img src="" alt="" id="lightboxImg">
  <div class="lightbox-caption" id="lightboxCaption"></div>
  <div class="lightbox-nav">
    <button id="lightboxPrev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
    <button id="lightboxNext" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
</div>

<div class="modal-overlay" id="requestPostModal" role="dialog" aria-modal="true" aria-labelledby="rptTitle">
  <div class="modal modal--wide">
    <button class="modal__close" id="rptClose" aria-label="Close">&times;</button>
    <h3 id="rptTitle">Request to Post Your Artwork</h3>
    <p class="u-mt-6 body-text mb-24">Fill in the details below and our team will review your submission within 2–3 business days.</p>

    <form id="requestPostForm">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="rpt_name">Your Name *</label>
          <input type="text" id="rpt_name" class="form-control" placeholder="Full name" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="rpt_medium">Art Medium *</label>
          <select id="rpt_medium" class="form-control" required>
            <option value="">— Select —</option>
            <option>Digital</option>
            <option>Traditional</option>
            <option>Painting</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="rpt_title">Artwork Title *</label>
        <input type="text" id="rpt_title" class="form-control" placeholder="Give your piece a title" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="rpt_desc">Description</label>
        <textarea id="rpt_desc" class="form-control" rows="3" placeholder="Tell us about this artwork — inspiration, technique, story…"></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Upload Artwork Image *</label>
        <div class="file-upload-area" id="rptUploadArea">
          <input type="file" id="rpt_image" accept="image/*">
          <i class="fa-solid fa-cloud-arrow-up"></i>
          <div class="upload-label"><strong>Click to upload</strong> or drag & drop</div>
          <div class="upload-label">PNG, JPG, WEBP — max 10MB</div>
        </div>
        <div class="file-preview-row" id="rptPreview"></div>
      </div>
      <div class="terms-notice">
        By submitting, you agree to LikHub's <a href="/likhub/terms.php" target="_blank">Terms of Service</a> and confirm this is your original work. Submissions are reviewed before going live.
      </div>
      <button type="submit" class="btn btn-primary btn-full">Submit for Review</button>
    </form>

    <div id="rptSuccess" class="u-d-none u-text-center u-pt-0">
      <i class="fa-solid fa-circle-check" class="u-icon-check-lg" style="color:
      <h4>Submission Received!</h4>
      <p>We'll review your artwork and get back to you within 2–3 business days.</p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script>
(function(){
  
  const lb      = document.getElementById('lightbox');
  const lbImg   = document.getElementById('lightboxImg');
  const lbCap   = document.getElementById('lightboxCaption');
  const lbClose = document.getElementById('lightboxClose');
  const lbPrev  = document.getElementById('lightboxPrev');
  const lbNext  = document.getElementById('lightboxNext');
  const triggers = Array.from(document.querySelectorAll('.js-lightbox-trigger[data-src]'));
  let currentIdx = 0;

  function openLightbox(idx) {
    const el = triggers[idx];
    if (!el || !el.dataset.src) return;
    currentIdx = idx;
    lbImg.src = el.dataset.src;
    lbCap.textContent = el.dataset.caption || '';
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeLightbox() {
    lb.classList.remove('open');
    document.body.style.overflow = '';
  }

  triggers.forEach((el, i) => {
    if (el.dataset.src) el.addEventListener('click', () => openLightbox(i));
  });
  lbClose.addEventListener('click', closeLightbox);
  lb.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });
  lbPrev.addEventListener('click', () => {
    openLightbox((currentIdx - 1 + triggers.length) % triggers.length);
  });
  lbNext.addEventListener('click', () => {
    openLightbox((currentIdx + 1) % triggers.length);
  });
  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') lbPrev.click();
    if (e.key === 'ArrowRight') lbNext.click();
  });

  
  const rptModal = document.getElementById('requestPostModal');
  const rptClose = document.getElementById('rptClose');
  const rptForm  = document.getElementById('requestPostForm');
  const rptSucc  = document.getElementById('rptSuccess');

  document.getElementById('btnRequestPost').addEventListener('click', function() {
    const loginUrl = this.dataset.loginRequired;
    if (loginUrl) { window.location.href = loginUrl; return; }
    rptModal.classList.add('open');
    document.body.style.overflow = 'hidden';
  });
  rptClose.addEventListener('click', () => { rptModal.classList.remove('open'); document.body.style.overflow = ''; });
  rptModal.addEventListener('click', e => { if (e.target === rptModal) { rptModal.classList.remove('open'); document.body.style.overflow = ''; } });

  
  document.getElementById('rpt_image').addEventListener('change', function() {
    const prev = document.getElementById('rptPreview');
    prev.innerHTML = '';
    Array.from(this.files).forEach(f => {
      const r = new FileReader();
      r.onload = ev => {
        const img = document.createElement('img');
        img.src = ev.target.result;
        img.className = 'file-preview-thumb';
        prev.appendChild(img);
      };
      r.readAsDataURL(f);
    });
  });

  rptForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = rptForm.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Submitting…';

    const fd = new FormData(rptForm);
    
    fd.set('name',   document.getElementById('rpt_name').value.trim());
    fd.set('medium', document.getElementById('rpt_medium').value);
    fd.set('title',  document.getElementById('rpt_title').value.trim());
    fd.set('desc',   document.getElementById('rpt_desc').value.trim());
    const imgFile = document.getElementById('rpt_image').files[0];
    if (imgFile) fd.set('image', imgFile);
    fd.set('csrf', document.querySelector('meta[name="csrf"]')?.content || '');

    fetch('/likhub/request-post.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.ok) {
          rptForm.style.display = 'none';
          rptSucc.style.display = 'block';
        } else {
          btn.disabled = false;
          btn.textContent = 'Submit for Review';
          alert(data.error || 'Something went wrong. Please try again.');
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Submit for Review';
        alert('Network error. Please check your connection and try again.');
      });
  });
})();
</script>