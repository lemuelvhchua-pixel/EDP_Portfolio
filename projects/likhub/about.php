<?php
$page_title = 'About LikHub';
$active_nav = 'about';
require_once __DIR__ . '/php/db.php';
include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <div class="section-label">Our Story</div>
    <h1>About LikHub</h1>
  </div>
</header>

<section class="section">
  <div class="container">
    <div class="mission-grid">
      <div>
        <div class="section-label">Our Mission</div>
        <h2>Nurturing Artistic Talent, One Creation at a Time</h2>
        <div class="blockquote">"LikHub was born from the belief that every student artist deserves a space to grow, share, and earn from their craft."</div>
        <p class="about-intro-p">We combine structured learning resources, a vibrant portfolio gallery, and a trusted commission marketplace all in one student-friendly ecosystem.</p>
        <p class="about-tagline">From Metro Manila with love, built for creators everywhere.</p>
      </div>
      <div class="mission-img">
        <img src="/likhub/images/website_asset.jpg" alt="LikHub" class="about-img"> </div>
    </div>
  </div>
</section>

<section class="section section--pale">
  <div class="container">
    <div class="section__header section__header--center">
      <div class="section-label">The Team</div>
      <h2>Founded by Students, for Students</h2>
    </div>
    <div class="team-grid">
      <div class="team-card fade-up">
        <div class="team-card__avatar team-card__avatar--photo"><img src="/likhub/images/Lemuel_Chua.jpg" alt="Lemuel D. Chua"></div>
        <div class="team-card__name">Lemuel D. Chua</div>
        <div class="team-card__role">Co-Founder & Developer</div>
        <div class="team-card__course">BSIT 2-5, Pamantasan ng Lungsod ng Valenzuela</div>
      </div>
      <div class="team-card fade-up fade-up-delay-1">
        <div class="team-card__avatar team-card__avatar--photo"><img src="/likhub/images/Rex_Jean_Villa.jpg" alt="Rex Jean Mikel Villa"></div>
        <div class="team-card__name">Rex Jean Mikel Villa</div>
        <div class="team-card__role">Co-Founder & Designer</div>
        <div class="team-card__course">BSIT 2-5, Pamantasan ng Lungsod ng Valenzuela</div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section__header section__header--center">
      <h2>Our Values</h2>
    </div>
    <div class="values-grid fade-up">
      <div class="value-card">
        <div class="value-card__title">Accessibility</div>
        <p>Free or affordable resources for every aspiring artist, regardless of background or budget.</p>
      </div>
      <div class="value-card">
        <div class="value-card__title">Community</div>
        <p>A safe, supportive space where artists lift each other up through genuine feedback and encouragement.</p>
      </div>
      <div class="value-card">
        <div class="value-card__title">Growth</div>
        <p>A clear pathway from learning fundamentals to building a portfolio to earning through commissions.</p>
      </div>
    </div>
  </div>
</section>

<section class="quote-banner fade-up">
  <div class="container">
    <blockquote>"Many young artists struggle to find beginner-friendly resources without expensive subscriptions, while individuals seeking affordable custom artwork don't know where to find reliable student artists. LikHub solves both."</blockquote>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>