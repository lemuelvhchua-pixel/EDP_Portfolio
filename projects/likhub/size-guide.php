<?php
$page_title = 'Size Guide';
$active_nav = 'commission';
require_once __DIR__ . '/php/db.php';
include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <h1>Commission Size Guide</h1>
    <p>Everything you need to know before choosing your canvas size. All sizes are delivered as digital files.</p>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="section__header section__header--center">
      <div class="section-label">Canvas Dimensions</div>
      <h2>Choose the Right Size for Your Project</h2>
      <p>Resolution determines print quality and file size. Digital files are delivered as PNG or JPEG unless otherwise requested.</p>
    </div>

    <div class="grid-3">

      
      <div class="size-card fade-up">
        <div class="size-card__icon sm size-card__icon--sm">SM</div>
        <div class="size-card__name">Small</div>
        <div class="size-card__dim">500 × 500 px &nbsp;·&nbsp; 72 DPI</div>
        <div class="size-card__price">₱300 – ₱800</div>
        <div class="size-divider"></div>
        <ul class="size-list">
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Profile pictures &amp; avatars</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Sticker &amp; badge designs</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Social media icons</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-xmark"></i> Not suitable for printing above 4"</li>
        </ul>
        <div class="size-turnaround">
          <span class="size-turnaround__label">Typical turnaround</span>
          <div class="size-turnaround__value">3 – 5 days</div>
        </div>
        <a href="/likhub/commission.php" class="btn btn-primary btn-sm btn-full size-card__cta">Commission Small <i class="fa-solid fa-arrow-right"></i></a>
      </div>

      
      <div class="size-card fade-up fade-up-delay-1 size-card--popular">
        <div class="size-popular-badge">Most Popular</div>
        <div class="size-card__icon md size-card__icon--md">MD</div>
        <div class="size-card__name">Medium</div>
        <div class="size-card__dim">1200 × 1200 px &nbsp;·&nbsp; 150 DPI</div>
        <div class="size-card__price">₱900 – ₱2,500</div>
        <div class="size-divider"></div>
        <ul class="size-list">
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Character illustrations</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Blog &amp; banner headers</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Prints up to 8 × 8 inches at 150 DPI</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Fan art &amp; portraits</li>
        </ul>
        <div class="size-turnaround">
          <span class="size-turnaround__label">Typical turnaround</span>
          <div class="size-turnaround__value">5 – 10 days</div>
        </div>
        <a href="/likhub/commission.php" class="btn btn-primary btn-sm btn-full size-card__cta">Commission Medium <i class="fa-solid fa-arrow-right"></i></a>
      </div>

      
      <div class="size-card fade-up fade-up-delay-2">
        <div class="size-card__icon lg size-card__icon--lg-text">LG</div>
        <div class="size-card__name">Large</div>
        <div class="size-card__dim">2400 × 2400 px &nbsp;·&nbsp; 300 DPI</div>
        <div class="size-card__price">₱3,000 – ₱8,500</div>
        <div class="size-divider"></div>
        <ul class="size-list">
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Book covers &amp; poster art</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Detailed scene illustrations</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Print-ready at 8 × 8 inches at 300 DPI</li>
          <li class="size-list__item"><i class="fa-solid fa-circle-check"></i> Commercial &amp; merch use</li>
        </ul>
        <div class="size-turnaround">
          <span class="size-turnaround__label">Typical turnaround</span>
          <div class="size-turnaround__value">10 – 21 days</div>
        </div>
        <a href="/likhub/commission.php" class="btn btn-primary btn-sm btn-full size-card__cta">Commission Large <i class="fa-solid fa-arrow-right"></i></a>
      </div>

    </div>
  </div>
</section>

<section class="section section--pale">
  <div class="container">
    <div class="section__header section__header--center">
      <div class="section-label">Comparison</div>
      <h2>Side-by-Side Overview</h2>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Feature</th>
            <th>Small (500px)</th>
            <th>Medium (1200px)</th>
            <th>Large (2400px)</th>
          </tr>
        </thead>
        <tbody>
          <tr><td class="table-th-label">Canvas Size</td><td>500 × 500 px</td><td>1200 × 1200 px</td><td>2400 × 2400 px</td></tr>
          <tr><td class="table-th-label">Resolution</td><td>72 DPI (screen)</td><td>150 DPI (mid-print)</td><td>300 DPI (print-ready)</td></tr>
          <tr><td class="table-th-label">File Format</td><td>PNG / JPEG</td><td>PNG / JPEG / PSD</td><td>PNG / JPEG / PSD / TIFF</td></tr>
          <tr><td class="table-th-label">Price Range</td><td>₱300 – ₱800</td><td>₱900 – ₱2,500</td><td>₱3,000 – ₱8,500</td></tr>
          <tr><td class="table-th-label">Turnaround</td><td>3 – 5 days</td><td>5 – 10 days</td><td>10 – 21 days</td></tr>
          <tr><td class="table-th-label">Revisions</td><td>1 free revision</td><td>2 free revisions</td><td>3 free revisions</td></tr>
          <tr><td class="table-th-label">Best For</td><td>Icons, avatars, stickers</td><td>Portraits, illustrations</td><td>Prints, covers, merch</td></tr>
          <tr><td class="table-th-label">Commercial Use</td><td><i class="fa-solid fa-minus fa-minus--muted"></i> Add-on fee</td><td><i class="fa-solid fa-minus fa-minus--muted"></i> Add-on fee</td><td><i class="fa-solid fa-check fa-check--green"></i> Included</td></tr>
        </tbody>
      </table>
    </div>
    <p class="table-note">* Prices are starting ranges. Final price depends on complexity and the individual artist's rate.</p>
  </div>
</section>

<section class="section">
  <div class="container size-guide-narrow">
    <div class="section__header section__header--center">
      <div class="section-label">FAQ</div>
      <h2>Common Questions</h2>
    </div>
    <?php
    $faqs = [
      ['Can I request a custom canvas ratio?', 'Yes! During your commission request, mention your preferred ratio (e.g. 16:9 for YouTube thumbnails, 9:16 for mobile wallpapers) in the description. Artists accommodate custom ratios within the same size tier.'],
      ['What file formats will I receive?', 'Small commissions are delivered as PNG or JPEG. Medium and Large commissions may include layered PSD files on request. Always confirm with your artist before submitting.'],
      ['Is commercial use included?', 'Standard commissions are licensed for personal use only. Commercial licenses (resale, merchandise, marketing) are available as add-ons — typically +50% of the base price. Large commissions include commercial rights by default.'],
      ['Can I upgrade my size after submitting?', 'Size upgrades are possible if the artist has not yet started work. Message your artist directly via the order reference page. Any price difference is settled between you and the artist.'],
      ['Do prices include revisions?', 'Yes — Small commissions include 1 free revision; Medium includes 2; Large includes 3. Additional revisions beyond the included count are subject to the artist\'s revision fee.'],
    ];
    foreach ($faqs as [$q, $a]):
    ?>
    <div class="faq-item">
      <button class="faq-question"><?= e($q) ?> <i class="fa-solid fa-chevron-down"></i></button>
      <div class="faq-answer"><?= e($a) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section aria-label="Ready to commission">
  <div class="container">
    <div class="promo">
      <div>
        <div class="promo__badge">Ready?</div>
        <div class="promo__title">Find Your Artist Today</div>
        <div class="promo__sub">Browse student artists and submit your commission in minutes.</div>
      </div>
      <a href="/likhub/commission.php" class="btn btn-cta btn-lg">Browse Artists <i class="fa-solid fa-arrow-right"></i></a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
