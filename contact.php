<?php
require __DIR__ . '/includes/init.php';

$user = current_user();
$errors = [];
$sent = false;
$form = ['name' => $user['name'] ?? '', 'email' => $user['email'] ?? '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (!empty($_POST['website'])) {
        $sent = true; // honeypot filled in: a bot, pretend it worked
    } else {
        if ($form['name'] === '' || mb_strlen($form['name']) > 80) $errors[] = 'Enter your name.';
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if (mb_strlen($form['subject']) > 150) $errors[] = 'Subject is too long.';
        if (mb_strlen($form['message']) < 5) $errors[] = 'Write a message.';
        if (mb_strlen($form['message']) > 5000) $errors[] = 'Message is too long (5000 characters max).';

        // simple flood guard: one message every 30 seconds per visitor
        if (!$errors && isset($_SESSION['last_msg']) && time() - $_SESSION['last_msg'] < 30) {
            $errors[] = 'You just sent one. Wait a few seconds before sending another.';
        }

        if (!$errors) {
            db()->prepare('INSERT INTO messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)')
                ->execute([$user['id'] ?? null, $form['name'], $form['email'], $form['subject'], $form['message']]);
            $_SESSION['last_msg'] = time();
            $sent = true;
        }
    }
}

if (is_ajax() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    json_out($sent
        ? ['ok' => true, 'email' => $form['email']]
        : ['ok' => false, 'errors' => $errors], $sent ? 200 : 422);
}

$title = 'Contact | Lemuel D. Chua';
$page = 'contact';
$bodyClass = 'contact-body-bg';
$description = 'Get in touch with Lemuel D. Chua for photography, design and web work.';
$crumb = 'CONTACT';
require __DIR__ . '/includes/header.php';
?>
    <main id="main">

        <!-- PAGE HERO -->
        <section class="contact-hero page-hero-anim">
            <h1 class="hero-name">Get In Touch</h1>
            <p class="hero-role">Let's work together</p>
        </section>

        <!-- CONTACT CONTENT -->
        <section class="contact-wrapper">

            <div class="section-header">
                <span class="section-num">01</span>
                <h2 class="section-title">CONTACT ME</h2>
            </div>
            <div class="section-line"></div>

            <div class="contact-grid">

                <!-- LEFT: Info + Socials -->
                <div class="contact-info-col">

                    <div class="status-badge">
                        <div class="status-dot"></div>
                        <span>OPEN FOR FREELANCE &amp; PART-TIME WORK</span>
                    </div>

                    <div class="info-card">
                        <span class="info-icon">&#9993;</span>
                        <div>
                            <p class="info-label">EMAIL</p>
                            <p class="info-val"><a href="mailto:lemuelvhchua@gmail.com">lemuelvhchua@gmail.com</a></p>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-icon">&#128205;</span>
                        <div>
                            <p class="info-label">LOCATION</p>
                            <p class="info-val">Valenzuela City, Philippines</p>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-icon">&#127979;</span>
                        <div>
                            <p class="info-label">SCHOOL</p>
                            <p class="info-val">Pamantasan ng Lungsod ng Valenzuela<br>BS Information Technology</p>
                        </div>
                    </div>

                    <?= resume_button('contact-resume') ?>

                    <div class="socials-card">
                        <div class="socials-header">&#128279; FIND ME ON</div>
                        <ul class="social-list">
                            <li><a href="https://www.facebook.com/LemuelChua05/" target="_blank" rel="noopener"><span class="s-icon">&#128101;</span> FACEBOOK <span class="s-arrow">▸</span></a></li>
                            <li><a href="https://www.instagram.com/shinooooooow/" target="_blank" rel="noopener"><span class="s-icon">&#128247;</span> INSTAGRAM <span class="s-arrow">▸</span></a></li>
                            <li><a href="https://github.com/lemuelvhchua-pixel" target="_blank" rel="noopener"><span class="s-icon">&#128025;</span> GITHUB <span class="s-arrow">▸</span></a></li>
                        </ul>
                    </div>

                </div>

                <!-- RIGHT: Form -->
                <div class="contact-form-col">
                    <div class="form-terminal">

                        <div class="terminal-bar">
                            <div class="t-dot red"></div>
                            <div class="t-dot yellow"></div>
                            <div class="t-dot green"></div>
                            <span class="terminal-title">send_message.exe</span>
                        </div>

                        <div class="form-body">

                            <?php if ($sent): ?>
                                <div class="form-success visible" role="status">
                                    <span class="success-icon">[ OK ]</span>
                                    <p>MESSAGE SENT!</p>
                                    <p>I'LL GET BACK TO YOU AT <?= e(strtoupper($form['email'])) ?>.</p>
                                    <a href="contact.php" class="btn">WRITE ANOTHER</a>
                                </div>
                            <?php else: ?>

                                <?php if ($errors): ?>
                                    <div class="alert err" role="alert">
                                        <?php foreach ($errors as $er): ?><p><?= e($er) ?></p><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- shown by JavaScript after sending, no reload -->
                                <div class="form-success" id="contactDone" role="status" aria-live="polite" hidden>
                                    <span class="success-icon">[ OK ]</span>
                                    <p>MESSAGE SENT!</p>
                                    <p>I'LL GET BACK TO YOU AT <span class="contact-email"></span>.</p>
                                    <button type="button" class="btn" id="contactAgain">WRITE ANOTHER</button>
                                </div>

                                <form method="post" action="contact.php" class="php-form" data-kind="contact" data-busy="SENDING..." novalidate>
                                    <?= csrf_field() ?>
                                    <!-- left empty by people; bots tend to fill it -->
                                    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>

                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label" for="fname">NAME <span>*</span></label>
                                            <input class="form-input" type="text" id="fname" name="name" value="<?= e($form['name']) ?>" placeholder="Your name" autocomplete="name" maxlength="80" required />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="femail">EMAIL <span>*</span></label>
                                            <input class="form-input" type="email" id="femail" name="email" value="<?= e($form['email']) ?>" placeholder="your@email.com" autocomplete="email" required />
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="fsubject">SUBJECT</label>
                                        <input class="form-input" type="text" id="fsubject" name="subject" value="<?= e($form['subject']) ?>" placeholder="Photo shoot, poster, website..." maxlength="150" />
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="fmessage">MESSAGE <span>*</span></label>
                                        <textarea class="form-textarea" id="fmessage" name="message" placeholder="Tell me about your project..." maxlength="5000" required><?= e($form['message']) ?></textarea>
                                    </div>

                                    <button type="submit" class="form-submit">&#9654; SEND MESSAGE</button>
                                    <p class="form-note">* REQUIRED FIELDS - YOUR INFO STAYS PRIVATE</p>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <div class="px-divider"></div>

    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
