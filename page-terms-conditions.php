<?php
/**
 * Template Name: Terms & Conditions
 */

get_header(); ?>

<main id="site-content" role="main">
  <section class="terms-conditions-page">
    <div class="container">
      <div class="terms-header">
        <h1>Terms & Conditions</h1>
        <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
      </div>

      <div class="terms-content">
        <p class="intro-text">
          Welcome to Aakaari ("we", "our", "us"). By accessing or using our platform, website, or services, you agree to be bound by these Terms & Conditions. Please read them carefully before using Aakaari.
        </p>

        <div class="terms-section">
          <h2>1. Overview</h2>
          <p>Aakaari is a white-label print-on-demand and dropshipping platform that enables sellers ("you", "your") to create, customize, and sell products under their own brand.</p>
          <p>All orders are placed and paid for directly by the seller; Aakaari does not collect payments from the seller's customers.</p>
        </div>

        <div class="terms-section">
          <h2>2. Registration & Verification</h2>
          <p>To use Aakaari's services, you must register and complete the KYC verification process.</p>
          <p>You agree to provide accurate and complete information during registration.</p>
          <p>Aakaari reserves the right to approve or reject applications at its discretion.</p>
        </div>

        <div class="terms-section">
          <h2>3. Orders & Payments</h2>
          <p>Sellers can order even a single product at Aakaari's base or wholesale price.</p>
          <p>All orders must be prepaid by the seller; Cash on Delivery (COD) is not supported.</p>
          <p>Payment must be made through the available payment methods listed on the platform.</p>
          <p>Prices displayed on the website are subject to change without prior notice.</p>
        </div>

        <div class="terms-section">
          <h2>4. Production & Shipping</h2>
          <p>Aakaari handles printing, packaging, and shipping directly to your customer under your brand.</p>
          <p>Shipping timelines may vary depending on product type, location, and courier availability.</p>
          <p>Tracking details are provided once the order is dispatched.</p>
          <p>Aakaari is not responsible for delays caused by third-party logistics providers.</p>
        </div>

        <div class="terms-section">
          <h2>5. Branding & Intellectual Property</h2>
          <p>All branding, design, and creative elements uploaded by the seller remain the property of the seller.</p>
          <p>The seller is responsible for ensuring that uploaded content does not infringe on any third-party intellectual property rights.</p>
          <p>Aakaari reserves the right to remove or reject designs that violate copyright or trademark laws.</p>
        </div>

        <div class="terms-section">
          <h2>6. Returns, Replacements & Refunds</h2>
          <p>Replacements are only accepted for defective, misprinted, or damaged items, reported within 7 days of delivery.</p>
          <p>Sellers must provide unboxing or product images/videos for verification.</p>
          <p>Refunds are not applicable once production has begun, except in approved cases of manufacturing defect.</p>
        </div>

        <div class="terms-section">
          <h2>7. Prohibited Use</h2>
          <p>You agree not to use Aakaari for:</p>
          <ul>
            <li>Selling or promoting illegal, offensive, or copyrighted material.</li>
            <li>Misrepresenting your affiliation with Aakaari.</li>
            <li>Engaging in fraudulent, misleading, or deceptive practices.</li>
          </ul>
        </div>

        <div class="terms-section">
          <h2>8. Limitation of Liability</h2>
          <p>Aakaari will not be liable for any indirect, incidental, or consequential damages arising from use of our services, including but not limited to loss of profit, data, or reputation.</p>
        </div>

        <div class="terms-section">
          <h2>9. Account Termination</h2>
          <p>Aakaari reserves the right to suspend or terminate your account at any time if you violate these Terms, misuse our platform, or engage in fraudulent activity.</p>
        </div>

        <div class="terms-section">
          <h2>10. Policy Updates</h2>
          <p>We may update these Terms & Conditions from time to time. Continued use of Aakaari after any changes constitutes your acceptance of the revised Terms.</p>
        </div>

        <div class="terms-section">
          <h2>11. Contact Us</h2>
          <p>For support, queries, or legal concerns, contact:</p>
          <ul class="contact-info">
            <li>📧 <a href="mailto:support@aakaari.com">support@aakaari.com</a></li>
            <li>🌐 <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank">www.aakaari.com</a></li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

