<?php
$page_title = 'Frequently Asked Questions';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <div class="section-label">Help Center</div>
    <h1>Frequently Asked Questions</h1>
    <p>Everything you need to know about LikHub, commissions, accounts, tutorials, and more.</p>
  </div>
</header>

<section class="section">
  <div class="u-max-w-780 container">

    <div class="u-p-0 form-section">

      <!-- GENERAL -->
      <div class="u-section-divider faq-item open">
        <h3 class="u-mb-12">What is LikHub?</h3>
        <p>LikHub is a student-driven art platform built by student developers from Pamantasan ng Lungsod ng Valenzuela (PLV). It is a space where art enthusiasts can learn drawing skills through tutorials, browse and commission artwork from talented student artists, and explore a growing gallery of original creations all in one place.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Is LikHub free to use?</h3>
        <p>Yes. Creating an account and accessing tutorials and the artist gallery on LikHub is completely free. Commission prices are set by individual artists and agreed upon directly between you and the artist before any work begins.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Who can join LikHub?</h3>
        <p>LikHub is open to everyone  whether you are an art enthusiast looking to learn, a client who wants to commission original artwork, or a student artist ready to showcase your skills and take on commissions. Registration is free and open to all.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">What are the different account roles?</h3>
        <p class="u-mb-10">LikHub has three main account roles:</p>
        <ul class="u-terms-list">
          <li><strong>Learner</strong> - Access tutorials and browse the gallery. Great for anyone wanting to improve their art skills.</li>
          <li><strong>Client</strong> - All learner features, plus the ability to browse artist profiles and submit commission requests.</li>
          <li><strong>Artist</strong> - Create a profile, upload your portfolio, set your commission details, and receive commission requests from clients.</li>
        </ul>
      </div>

      <!-- COMMISSIONS -->
      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">How do commissions work on LikHub?</h3>
        <p>Browse the artist roster, find an artist whose style suits your vision, and submit a commission request with your description, size preference, medium (Digital, Traditional, or Painting), and any reference images. The artist will review your request and reach out to discuss the details and finalize the price. Payment is handled directly between you and the artist, LikHub does not process or hold funds.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">How much does a commission cost?</h3>
        <p>Each artist sets their own pricing range, which you can see on their profile. The price shown is an estimate, the final cost is agreed upon between you and the artist before work begins, based on size, complexity, medium, and other factors.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">What commission sizes are available?</h3>
        <p class="u-mb-10">Artists on LikHub offer commissions in three standard sizes. Refer to our <a class="u-color-navy" href="/likhub/size-guide.php">Size Guide</a> for full details, but as a general overview:</p>
        <ul class="u-terms-list">
          <li><strong>Small</strong> - Ideal for icons, bust shots, and simple compositions.</li>
          <li><strong>Medium</strong> - Great for half-body or more detailed pieces.</li>
          <li><strong>Large</strong> - Full scenes, full-body pieces, and complex illustrations.</li>
        </ul>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Can I request revisions to my commissioned artwork?</h3>
        <p>Revision policies are set by individual artists. Before confirming a commission, review the artist's profile for their revision and refund terms. We recommend discussing revision expectations with your chosen artist before work begins to avoid misunderstandings.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">What if I have a dispute with an artist?</h3>
        <p>LikHub encourages open communication between clients and artists to resolve any issues. Since payments and agreements are made directly between parties, LikHub is not able to mediate financial disputes or guarantee delivery. If you experience a serious issue, please contact us at <a class="u-color-navy" href="mailto:hello@likhub.ph">hello@likhub.ph</a> and we will do our best to help.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Can I commission artwork for commercial use?</h3>
        <p>By default, commissioned artwork is licensed for personal use only. If you need commercial rights for products, merchandise, or business use, you must negotiate a separate commercial license directly with the artist and get the agreement in writing before work begins.</p>
      </div>

      <!-- ARTISTS -->
      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">How do I become an artist on LikHub?</h3>
        <p>Register for a LikHub account and select the Artist role during sign-up. Once registered, complete your artist profile by adding a bio, setting your commission details (media, pricing, and availability), and uploading portfolio samples. Your profile will be visible to clients once it is active.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Can I turn my commission availability on or off?</h3>
        <p>Yes. Artists can toggle their commission status between open and closed at any time from their dashboard. When closed, clients will see that you are not currently accepting commissions.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Who owns the artwork I create and post on LikHub?</h3>
        <p>You do. All artwork you post on LikHub remains your intellectual property. By posting on the platform, you grant LikHub a non-exclusive license to display your work on the site and in promotional materials, always with attribution to you as the creator. You can request removal of your work at any time.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">What content is not allowed on LikHub?</h3>
        <p>LikHub does not allow content that is sexually explicit or violent, infringes on another person's intellectual property, harasses or discriminates against individuals or groups, depicts real persons in a defamatory way, or promotes illegal activity. Violations may result in immediate account termination. See our <a class="u-color-navy" href="/likhub/terms.php">Terms of Service</a> for full details.</p>
      </div>

      <!-- TUTORIALS -->
      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">How do tutorials work?</h3>
        <p>Tutorials on LikHub are created by our student instructors and cover a range of drawing and art topics. They are accessible to all registered users at no cost. Browse the <a class="u-color-navy" href="/likhub/learn.php">Learn</a> section to find tutorials organized by topic and skill level.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">Can I submit my own tutorial?</h3>
        <p>Tutorial submissions are currently managed by LikHub's team. If you are an artist interested in contributing educational content to the platform, please reach out to us at <a class="u-color-navy" href="mailto:hello@likhub.ph">hello@likhub.ph</a> with your proposal.</p>
      </div>

      <!-- ACCOUNT -->
      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">How do I reset my password?</h3>
        <p>On the login page, click "Forgot Password" and enter your registered email address. You will receive an OTP (one-time password) to verify your identity and set a new password. If you do not receive the email, check your spam folder or contact us for assistance.</p>
      </div>

      <div class="u-section-divider faq-item">
        <h3 class="u-mb-12">How do I delete my account?</h3>
        <p>To request account deletion, please email us at <a class="u-color-navy" href="mailto:hello@likhub.ph">hello@likhub.ph</a> from your registered email address. We will process your request within a reasonable timeframe. Note that deleting your account will permanently remove your profile, portfolio, and associated data from the platform.</p>
      </div>

      <div class="u-pb-8">
        <h3 class="u-mb-12">I have a question that is not listed here. How do I contact LikHub?</h3>
        <p>We are happy to help! Reach us through any of the following:</p>
        <div class="u-terms-box">
          <p><i class="u-color-navy-mr8 fa-solid fa-envelope"></i> <a class="u-color-navy" href="mailto:hello@likhub.ph">hello@likhub.ph</a></p>
          <p class="u-mt-8"><i class="u-color-navy-mr8 fa-solid fa-comment"></i> <a class="u-color-navy" href="/likhub/contact.php">Contact Form</a></p>
          <p class="u-mt-8"><i class="u-color-navy-mr8 fa-solid fa-location-dot"></i> Valenzuela City, Metro Manila, Philippines</p>
          <p class="u-mt-8"><i class="u-color-navy-mr8 fa-solid fa-building-columns"></i> Pamantasan ng Lungsod ng Valenzuela</p>
        </div>
      </div>

    </div>

    <div class="u-border-top">
      <a href="/likhub/commission.php" class="btn btn-primary btn-sm">Browse Artists</a>
      <a href="/likhub/register.php" class="btn btn-outline btn-sm">Create Account</a>
      <a href="/likhub/contact.php" class="btn btn-outline btn-sm">Contact Us</a>
    </div>

  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
