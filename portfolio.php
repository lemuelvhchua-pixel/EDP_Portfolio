<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/content.php';
require __DIR__ . '/includes/render.php';

// Build every visible section from the database (empty sections are skipped).
$built = [];
foreach (sections() as $s) {
    $html = r_section($s, count($built) + 1);
    if ($html !== '') $built[] = [$s, $html];
}

$title = 'Portfolio | Lemuel D. Chua';
$page = 'portfolio';
$bodyClass = 'portfolio-body-bg';
$description = 'Photography, web projects, UI design, graphic design, networking and certificates by Lemuel D. Chua.';
$crumb = 'PORTFOLIO';
require __DIR__ . '/includes/header.php';
?>
    <main id="main">

        <!-- PAGE HERO -->
        <section class="portfolio-hero page-hero-anim">
            <h1 class="hero-name">My Work</h1>
            <p class="hero-role">Projects &amp; Creations</p>
        </section>

        <!-- JUMP BAR -->
        <div class="filter-bar">
            <p class="filter-label">JUMP TO &#9658;</p>
<?php foreach ($built as $i => [$s]): ?>
            <button type="button" class="filter-btn<?= $i === 0 ? ' active' : '' ?>" data-target="sec-<?= e($s['section_key']) ?>"><?= e($s['nav_label']) ?></button>
<?php endforeach; ?>
        </div>
<?= implode("\n\n        <div class=\"px-divider\"></div>\n", array_column($built, 1)) ?>

<?php if (is_admin()): ?>
        <a class="admin-fab" href="admin/portfolio.php" title="Edit portfolio content">&#9998; MANAGE</a>
<?php endif; ?>

    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
