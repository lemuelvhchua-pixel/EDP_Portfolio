<?php
$page_title = 'Contact Us';
$active_nav = 'contact';
require_once __DIR__ . '/php/db.php';

$errors  = [];
$success = false;
$vals    = ['name'=>'','email'=>'','subject'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid form token.';
    } else {
        $vals['name']    = trim($_POST['name']    ?? '');
        $vals['email']   = trim($_POST['email']   ?? '');
        $vals['subject'] = trim($_POST['subject'] ?? '');
        $vals['message'] = trim($_POST['message'] ?? '');

        if (strlen($vals['name'])    < 2) $errors[] = 'Name is required.';
        if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
        if (strlen($vals['message']) < 10) $errors[] = 'Message must be at least 10 characters.';

        if (!$errors) {
            $stmt = db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)');
            $stmt->execute([$vals['name'], $vals['email'], $vals['subject'], $vals['message']]);
            $success = true;
            $vals = ['name'=>'','email'=>'','subject'=>'','message'=>''];
        }
    }
}

include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <div class="section-label">Get in Touch</div>
    <h1>Reach Us</h1>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="contact-grid">
      
      <div class="fade-up">
        <h2>Contact Us</h2>
        <p class="contact-intro">We'd love to hear from you. Reach out anytime.</p>

        <div class="reach-item">
          <div class="reach-item__label"><i class="fa-solid fa-envelope"></i> Email</div>
          <div class="reach-item__value"><a href="mailto:likhubbusiness@gmail.com">likhubbusiness@gmail.com</a></div>
        </div>
        <div class="reach-item">
          <div class="reach-item__label"><i class="fa-solid fa-phone"></i> Phone</div>
          <div class="reach-item__value">+63 943 216 1023</div>
        </div>
        <div class="reach-item">
          <div class="reach-item__label"><i class="fa-solid fa-location-dot"></i> Address</div>
          <div class="reach-item__value">Maysan Road corner Tongco Street, Barangay Maysan, Valenzuela City, Metro Manila, 1440</div>
        </div>

        <div class="follow-us">
          <div class="follow-us__label">Follow Us</div>
          <div class="social-row">
            <a href="https://www.facebook.com/share/1Cx9sMswDW/" class="social-btn" aria-label="Facebook" target="_blank"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="https://www.youtube.com/@LikHub" class="social-btn" aria-label="YouTube" target="_blank"><i class="fa-brands fa-youtube"></i></a>
            <a href="mailto:likhubbusiness@gmail.com" class="social-btn" aria-label="Email"><i class="fa-solid fa-envelope"></i></a>
          </div>
        </div>
      </div>

      <div class="form-section fade-up fade-up-delay-1">
        <?php if ($success): ?>
          <div class="alert alert--success"><i class="fa-solid fa-circle-check"></i> Message sent successfully!</div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert alert--error"><i class="fa-solid fa-circle-xmark"></i> <?= implode(' ', array_map('e', $errors)) ?></div>
        <?php endif; ?>

        <form method="POST" action="/likhub/contact.php" novalidate>
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          
          <div class="form-group">
            <label class="form-label" for="name">Name</label>
            <input type="text" name="name" id="name" class="form-control" value="<?= e($vals['name']) ?>" placeholder="Your full name" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= e($vals['email']) ?>" placeholder="your@email.com" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="subject">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" value="<?= e($vals['subject']) ?>" placeholder="How can we help?">
          </div>
          <div class="form-group">
            <label class="form-label" for="message">Message</label>
            <textarea name="message" id="message" class="form-control" rows="5" placeholder="Tell us more..." required><?= e($vals['message']) ?></textarea>
          </div>
          
          <button type="submit" class="btn btn-primary btn-full">Send Message</button>
        </form>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>