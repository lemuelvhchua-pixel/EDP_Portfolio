<?php
require __DIR__ . '/includes/init.php';
$title = 'About | Lemuel D. Chua';
$page = 'about';
$bodyClass = 'about-body-bg';
$description = 'About Lemuel D. Chua - BSIT student at Pamantasan ng Lungsod ng Valenzuela, photographer, designer and co-founder of LikHub.';
$crumb = 'ABOUT';
require __DIR__ . '/includes/header.php';
?>
    <main id="main">

        <!-- PAGE HERO -->
        <section class="about-hero page-hero-anim">
            <h1 class="hero-name">About Me</h1>
            <p class="hero-role">Get to know me</p>
        </section>

        <!-- ABOUT CONTENT -->
        <section class="about-section">
            <div class="about-grid">

                <!-- Photo col -->
                <div class="about-img-col">
                    <div class="about-img-wrap">
                        <div class="about-img">
                            <img src="images/me.jpg" alt="Lemuel D. Chua" />
                        </div>
                    </div>
                    <p class="about-tag">[ LEMUEL D. CHUA ]</p>

                    <div class="about-status">
                        <span class="about-status-dot"></span>
                        <span>OPEN TO OPPORTUNITIES</span>
                    </div>
                </div>

                <!-- Text col -->
                <div class="about-text-col">
                    <h2 class="about-section-title">WHO I AM</h2>
                    <div class="section-line"></div>

                    <p class="about-body-text">
                        I'm Lemuel, an IT student at Pamantasan ng Lungsod ng Valenzuela. I like making things you can actually look at: photos, posters, app screens, and websites.
                    </p>

                    <p class="about-body-text">
                        Most of my photos come from walks around Manila, especially Intramuros and the Pasig River at night. On the tech side I co-founded LikHub with my classmate, a site where student artists can learn, post their work and take commissions, and I handled the development.
                    </p>

                    <p class="about-body-text">
                        Right now I'm getting better at web development and data, with SQL and Python on CodeSignal, while still doing design and editing on the side. I'm aiming to be a working student, so I'm open to freelance and part-time design work.
                    </p>

                    <!-- Details -->
                    <div class="about-details">
                        <div class="about-detail">
                            <span class="detail-label">NAME</span>
                            <span class="detail-val">Lemuel D. Chua</span>
                        </div>
                        <div class="about-detail">
                            <span class="detail-label">LOCATION</span>
                            <span class="detail-val">Valenzuela City, PH</span>
                        </div>
                        <div class="about-detail">
                            <span class="detail-label">SCHOOL</span>
                            <span class="detail-val">Pamantasan ng Lungsod ng Valenzuela</span>
                        </div>
                        <div class="about-detail">
                            <span class="detail-label">COURSE</span>
                            <span class="detail-val">BS Information Technology</span>
                        </div>
                        <div class="about-detail">
                            <span class="detail-label">ROLE</span>
                            <span class="detail-val">Co-Founder &amp; Developer, LikHub</span>
                        </div>
                    </div>

                    <br />
                    <a href="portfolio.php" class="btn">VIEW MY WORK</a>
                    <a href="contact.php" class="btn">CONTACT ME</a>
                    <?= resume_button() ?>
                </div>
            </div>
        </section>

        <div class="px-divider"></div>

        <!-- TOOLBOX -->
        <section class="about-section">
            <h2 class="about-section-title">MY TOOLBOX</h2>
            <div class="section-line"></div>
            <div class="toolbox">
                <div class="tool-group">
                    <h3>&#9658; DESIGN &amp; EDITING</h3>
                    <div class="chips">
                        <span class="chip">PHOTOSHOP</span><span class="chip">ILLUSTRATOR</span><span class="chip">PREMIERE PRO</span>
                        <span class="chip">ADOBE EXPRESS</span><span class="chip">CANVA</span><span class="chip">FIGMA</span>
                    </div>
                </div>
                <div class="tool-group">
                    <h3>&#9658; WEB</h3>
                    <div class="chips">
                        <span class="chip">HTML</span><span class="chip">CSS</span><span class="chip">JAVASCRIPT</span>
                        <span class="chip">PHP</span><span class="chip">MYSQL</span>
                    </div>
                </div>
                <div class="tool-group">
                    <h3>&#9658; DATA &amp; NETWORKS</h3>
                    <div class="chips">
                        <span class="chip">SQL</span><span class="chip">PYTHON</span><span class="chip">DATA ANALYSIS</span>
                        <span class="chip">CISCO PACKET TRACER</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="px-divider"></div>

        <!-- TIMELINE -->
        <section class="timeline-section">
            <div style="padding-top:48px;">
                <h2 class="about-section-title">MY JOURNEY</h2>
                <div class="section-line"></div>
            </div>

            <div class="timeline">

                <div class="timeline-item now">
                    <p class="tl-year">[ NOW ]</p>
                    <h3 class="tl-title">BS INFORMATION TECHNOLOGY - PLV</h3>
                    <p class="tl-desc">Studying IT at Pamantasan ng Lungsod ng Valenzuela, doing design, photography and web projects along the way.</p>
                </div>

                <div class="timeline-item">
                    <p class="tl-year">[ NOV 2026 ]</p>
                    <h3 class="tl-title">FINISHED "JOURNEY INTO SQL"</h3>
                    <p class="tl-desc">Completed the SQL learning path on CodeSignal, from basic queries and joins to advanced query techniques.</p>
                </div>

                <div class="timeline-item">
                    <p class="tl-year">[ OCT 2026 ]</p>
                    <h3 class="tl-title">BUILT KABAKAL</h3>
                    <p class="tl-desc">A shop management and online shopping system for a fitness equipment store, in plain PHP and MySQL.</p>
                </div>

                <div class="timeline-item">
                    <p class="tl-year">[ MAY 2026 ]</p>
                    <h3 class="tl-title">CO-FOUNDED LIKHUB</h3>
                    <p class="tl-desc">Started LikHub with my classmate Rex, a platform for student artists. I took care of the development.</p>
                </div>

                <div class="timeline-item">
                    <p class="tl-year">[ APR 2026 ]</p>
                    <h3 class="tl-title">DATA ANALYSIS WITH PYTHON</h3>
                    <p class="tl-desc">Finished CodeSignal's Intro to Data Analysis with Python path: cleaning, statistics, hypothesis testing and visualization.</p>
                </div>

                <div class="timeline-item">
                    <p class="tl-year">[ NOV 2025 ]</p>
                    <h3 class="tl-title">INTRAMUROS PHOTO WALK</h3>
                    <p class="tl-desc">Took most of my favorite photos while exploring Intramuros and the Pasig River at night.</p>
                </div>

            </div>
        </section>

    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
