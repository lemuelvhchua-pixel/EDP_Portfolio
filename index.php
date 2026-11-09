<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/content.php';
$stats = portfolio_stats();
$title = 'Lemuel D. Chua | Portfolio';
$page = 'home';
$bodyClass = 'index-body-bg';
$description = 'Lemuel D. Chua - IT student, photographer, designer and web developer from Valenzuela City.';
require __DIR__ . '/includes/header.php';
?>
    <main id="main">

        <!-- HERO -->
        <section class="index-hero">
            <div class="hero-corner tl"></div>
            <div class="hero-corner tr"></div>
            <div class="hero-corner bl"></div>
            <div class="hero-corner br"></div>

            <div class="hero-badge">&#9632; AVAILABLE FOR WORK &#9632;</div>
            <div class="hero-avatar">
                <img src="images/me.jpg" alt="Lemuel D. Chua" />
            </div>
            <h1 class="index-hero-name">Lemuel D. Chua</h1>
            <p class="index-hero-role">// Photographer &middot; Editor &middot; Designer &middot; Developer</p>
            <p class="hero-desc">
                [ I'm an IT student who mixes photography, design, and code. From capturing moments around the city to building websites with my team, I like turning ideas into something people can actually see and use. ]
            </p>
            <div class="hero-divider"></div>
            <div>
                <a href="portfolio.php" class="btn">VIEW WORK</a>
                <a href="about.php" class="btn">ABOUT ME</a>
            </div>
        </section>

        <!-- SKILLS MARQUEE -->
        <div class="skills-strip">
            <div class="skills-row">
                <p class="skill-tag accent">&#9658; PHOTOGRAPHY</p><p class="skill-tag">//</p>
                <p class="skill-tag">PHOTOSHOP</p><p class="skill-tag">//</p>
                <p class="skill-tag accent">&#9658; PREMIERE PRO</p><p class="skill-tag">//</p>
                <p class="skill-tag">ILLUSTRATOR</p><p class="skill-tag">//</p>
                <p class="skill-tag accent">&#9658; FIGMA</p><p class="skill-tag">//</p>
                <p class="skill-tag">HTML / CSS / JS</p><p class="skill-tag">//</p>
                <p class="skill-tag accent">&#9658; PHP + MYSQL</p><p class="skill-tag">//</p>
                <p class="skill-tag">SQL</p><p class="skill-tag">//</p>
                <p class="skill-tag accent">&#9658; PYTHON</p><p class="skill-tag">//</p>
                <p class="skill-tag">CISCO PACKET TRACER</p><p class="skill-tag">//</p>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-strip">
            <div class="stat-item">
                <p class="stat-num" data-count="<?= $stats['projects'] ?>"><?= $stats['projects'] ?></p>
                <p class="stat-label">PROJECTS</p>
            </div>
            <div class="stat-item">
                <p class="stat-num" data-count="<?= $stats['certs'] ?>"><?= $stats['certs'] ?></p>
                <p class="stat-label">CERTIFICATES</p>
            </div>
            <div class="stat-item">
                <p class="stat-num" data-count="<?= $stats['websites'] ?>" data-pad="2"><?= sprintf('%02d', $stats['websites']) ?></p>
                <p class="stat-label">WEBSITES BUILT</p>
            </div>
            <div class="stat-item">
                <p class="stat-num">&#8734;</p>
                <p class="stat-label">CREATIVITY</p>
            </div>
        </div>

        <!-- WHAT I DO -->
        <section class="index-section">
            <div class="section-header">
                <div class="section-num">01</div>
                <h2 class="section-title">WHAT I DO</h2>
            </div>
            <div class="section-line"></div>
            <div class="services">
                <div class="service">
                    <span class="service-icon" aria-hidden="true">&#9673;</span>
                    <h3>PHOTOGRAPHY</h3>
                    <p>Night shots, city lights and candid moments. Mostly around Intramuros and the Pasig River.</p>
                    <a href="portfolio.php#sec-photography">SEE PHOTOS &#9658;</a>
                </div>
                <div class="service">
                    <span class="service-icon" aria-hidden="true">&#9638;</span>
                    <h3>GRAPHIC DESIGN</h3>
                    <p>Banners, posters and profiles made in Photoshop, Illustrator and Canva.</p>
                    <a href="portfolio.php#sec-graphics">SEE DESIGNS &#9658;</a>
                </div>
                <div class="service">
                    <span class="service-icon" aria-hidden="true">&#9647;</span>
                    <h3>UI DESIGN</h3>
                    <p>App screens and flows in Figma, from login pages to a full business app.</p>
                    <a href="portfolio.php#sec-ui">SEE SCREENS &#9658;</a>
                </div>
                <div class="service">
                    <span class="service-icon" aria-hidden="true">&lt;/&gt;</span>
                    <h3>WEB DEVELOPMENT</h3>
                    <p>Full websites with PHP and MySQL, like LikHub and the Kabakal shop system.</p>
                    <a href="portfolio.php#sec-likhub">SEE PROJECTS &#9658;</a>
                </div>
                <div class="service">
                    <span class="service-icon" aria-hidden="true">&#8801;</span>
                    <h3>DATA &amp; NETWORKS</h3>
                    <p>SQL and Python for data analysis, plus network setups in Cisco Packet Tracer.</p>
                    <a href="portfolio.php#sec-certs">SEE CERTS &#9658;</a>
                </div>
            </div>
        </section>

        <div class="px-divider"></div>

        <!-- DEMO REEL -->
        <section class="index-section">
            <div class="section-header">
                <div class="section-num">02</div>
                <h2 class="section-title">DEMO REEL</h2>
            </div>
            <div class="section-line"></div>
            <div class="video-wrap">
                <video controls preload="metadata" poster="images/intro-poster.jpg">
                    <source src="video/intro.mp4" type="video/mp4" />
                </video>
                <div class="video-meta">
                    <span class="video-title">[ Lemuel Chua / Portfolio Intro ]</span>
                    <span class="video-tag">DEMO REEL</span>
                </div>
            </div>
        </section>

        <div class="px-divider"></div>

        <!-- FEATURED WORK -->
        <section class="index-section">
            <div class="section-header">
                <div class="section-num">03</div>
                <h2 class="section-title">FEATURED WORK</h2>
            </div>
            <div class="section-line"></div>

            <div class="cards">
                <article class="card">
                    <a class="card-img" href="portfolio.php#sec-likhub" aria-label="LikHub project">
                        <div class="card-img-overlay"></div>
                        <img src="images/likhub/home.jpg" alt="LikHub home page with art lessons, a gallery and commissions" loading="lazy" />
                    </a>
                    <div class="card-hover-line"></div>
                    <div class="card-body">
                        <p class="card-tag">WEB</p>
                        <h3 class="card-title">LIKHUB</h3>
                        <p class="card-desc">[ Our site for student artists. Lessons, a gallery and commissions. I co-founded it and did the development. ]</p>
                    </div>
                    <div class="card-footer">
                        <a href="portfolio.php#sec-likhub" class="card-link">VIEW PROJECT &#9658;</a>
                        <div class="card-dot"></div>
                    </div>
                </article>

                <article class="card">
                    <a class="card-img" href="portfolio.php#sec-kabakal" aria-label="Kabakal project">
                        <div class="card-img-overlay"></div>
                        <img src="images/kabakal/home.jpg" alt="Kabakal Fitness Equipment online shop home page" loading="lazy" />
                    </a>
                    <div class="card-hover-line"></div>
                    <div class="card-body">
                        <p class="card-tag">WEB</p>
                        <h3 class="card-title">KABAKAL</h3>
                        <p class="card-desc">[ A shop and inventory system for a fitness equipment store, with GCash payments and an admin side. ]</p>
                    </div>
                    <div class="card-footer">
                        <a href="portfolio.php#sec-kabakal" class="card-link">VIEW PROJECT &#9658;</a>
                        <div class="card-dot"></div>
                    </div>
                </article>

                <article class="card">
                    <a class="card-img" href="portfolio.php#sec-photography" aria-label="Photography">
                        <div class="card-img-overlay"></div>
                        <img src="images/photos/intramuros-walls-night.jpg" alt="The old stone walls of Intramuros lit up at night" loading="lazy" />
                    </a>
                    <div class="card-hover-line"></div>
                    <div class="card-body">
                        <p class="card-tag">PHOTOGRAPHY</p>
                        <h3 class="card-title">INTRAMUROS</h3>
                        <p class="card-desc">[ One of my favorite night shots from exploring Intramuros ]</p>
                    </div>
                    <div class="card-footer">
                        <a href="portfolio.php#sec-photography" class="card-link">VIEW PHOTOS &#9658;</a>
                        <div class="card-dot"></div>
                    </div>
                </article>
            </div>
        </section>

        <div class="px-divider"></div>

        <!-- CTA -->
        <div class="cta-strip">
            <h2 class="cta-title">INTERESTED IN WORKING TOGETHER?</h2>
            <p class="cta-sub">[ Let's create something worth remembering. ]</p>
            <a href="contact.php" class="btn">GET IN TOUCH</a>
        </div>

    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
