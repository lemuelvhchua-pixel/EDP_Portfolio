<?php
$page_title = 'Terms of Service';
$active_nav = '';
require_once __DIR__ . '/php/db.php';
include __DIR__ . '/partials/header.php';
?>

<header class="page-header">
  <div class="container">
    <div class="section-label">Legal</div>
    <h1>Terms of Service</h1>
    <p>Last updated: January 2025 &nbsp;·&nbsp; Effective immediately upon registration or use of LikHub.</p>
  </div>
</header>

<section class="section">
  <div class="u-max-w-780 container">

    <div class="u-p-0 form-section">

      <div class="u-section-divider faq-item open">
        <h3 class="u-mb-12">1. Acceptance of Terms</h3>
        <p>By accessing or using LikHub ("the Platform"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Platform. LikHub is a student-driven art platform operated by student developers from Pamantasan ng Lungsod ng Valenzuela (PLV), Valenzuela City, Metro Manila, Philippines.</p>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">2. Eligibility</h3>
        <p>LikHub is open to all users, with particular focus on student artists and art enthusiasts. By registering, you confirm that the information you provide is accurate and that you will keep it up to date. You are responsible for maintaining the confidentiality of your account credentials.</p>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">3. Commission Policy</h3>
        <p class="u-mb-10">LikHub provides a platform for connecting clients with student artists. By submitting a commission request:</p>
        <ul class="u-terms-list" style="color:
          <li>You agree that payment is arranged <strong>directly between you and the artist</strong>. LikHub does not process, hold, or guarantee payments.</li>
          <li>Prices shown are estimates. The final price is agreed upon by the client and artist before work begins.</li>
          <li>LikHub is <strong>not responsible</strong> for disputes, non-delivery, or quality disagreements between clients and artists.</li>
          <li>Commission requests must be for legal, non-offensive content. LikHub reserves the right to reject any request at its discretion.</li>
          <li>Refund and revision policies are set by individual artists. Review the artist's terms before commissioning.</li>
        </ul>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">4. Artwork & Intellectual Property</h3>
        <p class="u-mb-10">All artwork posted on LikHub remains the intellectual property of the original artist unless explicitly transferred in writing.</p>
        <ul class="u-terms-list" style="color:
          <li>Artists grant LikHub a non-exclusive license to display their work on the Platform for promotional purposes.</li>
          <li>Commissioned artwork is licensed for personal use by the client unless a commercial license is separately negotiated.</li>
          <li>Do not upload, post, or claim artwork that is not your original creation.</li>
          <li>Plagiarism or art theft will result in immediate account termination.</li>
        </ul>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">5. Prohibited Content</h3>
        <p>You agree not to upload or request content that:</p>
        <ul class="u-terms-list" style="color:
          <li>Is sexually explicit, violent, or otherwise harmful</li>
          <li>Infringes on another person's intellectual property</li>
          <li>Harasses, bullies, or discriminates against individuals or groups</li>
          <li>Depicts real persons in a defamatory or misleading way</li>
          <li>Promotes illegal activity</li>
        </ul>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">6. Gallery Submissions</h3>
        <p>Artists who submit artwork for the LikHub gallery confirm that:</p>
        <ul class="u-terms-list" style="color:
          <li>The work is their original creation</li>
          <li>They have the right to publish the work publicly</li>
          <li>LikHub may feature the work in promotional materials with attribution</li>
          <li>Submissions are reviewed and may be declined without notice</li>
        </ul>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">7. Limitation of Liability</h3>
        <p>LikHub and its developers are student creators and the Platform is provided on a best-effort basis. To the maximum extent permitted by law, LikHub disclaims all warranties and is not liable for any direct, indirect, incidental, or consequential damages arising from your use of the Platform, including but not limited to commission disputes, data loss, or service interruptions.</p>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">8. Privacy</h3>
        <p>LikHub collects minimal personal information necessary to operate the Platform (name, email, account data). We do not sell your data to third parties. By using the Platform, you consent to the collection and use of this information as described in our <a class="u-color-navy" href="/likhub/contact.php">Privacy Policy</a>.</p>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">9. Account Termination</h3>
        <p>LikHub reserves the right to suspend or terminate any account that violates these Terms, at its sole discretion, with or without notice. You may delete your account at any time by contacting us at <a class="u-color-navy" href="mailto:hello@likhub.ph">hello@likhub.ph</a>.</p>
      </div>

      <div class="u-section-divider">
        <h3 class="u-mb-12">10. Changes to Terms</h3>
        <p>LikHub may update these Terms at any time. Continued use of the Platform after changes constitutes acceptance of the new Terms. We will notify registered users of significant changes via email when reasonably possible.</p>
      </div>

      <div class="u-pb-8">
        <h3 class="u-mb-12">11. Contact & Dispute Resolution</h3>
        <p>For questions about these Terms or to report violations, please contact us:</p>
        <div class="u-terms-box">
          <p><i class="u-color-navy-mr8 fa-solid fa-envelope"></i> <a class="u-color-navy" href="mailto:hello@likhub.ph">hello@likhub.ph</a></p>
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
