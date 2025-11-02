<?php
/**
 * Template Name: Privacy Policy
 */

get_header(); ?>

<main id="site-content" role="main">
  <section class="privacy-policy-page">
    <div class="container">
      <div class="privacy-header">
        <h1>Privacy Policy – Aakaari</h1>
        <p class="effective-date">Effective Date: <?php echo date('F j, Y'); ?></p>
        <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
      </div>

      <div class="privacy-content">
        <p class="intro-text">
          Welcome to Aakaari ("we," "our," "us").<br><br>
          Your privacy is important to us. This Privacy Policy explains how we collect, use, and protect your personal information when you visit or make a purchase through our website <a href="<?php echo esc_url(home_url('/')); ?>">www.aakaari.com</a> or any related platform operated by Aakaari.
        </p>

        <div class="privacy-section">
          <h2>1. Information We Collect</h2>
          <p>We collect personal and financial information to provide our services safely and efficiently. The types of data we collect include:</p>
          
          <h3>Personal Identification Details</h3>
          <ul>
            <li>Full Name</li>
            <li>Email Address</li>
            <li>Contact Number</li>
            <li>Residential/Shipping Address</li>
          </ul>

          <h3>Financial & Verification Information</h3>
          <ul>
            <li>Bank Account Details (for payments, refunds, or dropshipping settlements)</li>
            <li>Aadhaar Card (for identity verification)</li>
            <li>PAN Card (for tax and compliance purposes)</li>
            <li>Cancelled Cheque or Bank Statement (for account verification and payments)</li>
          </ul>

          <h3>Automatically Collected Information</h3>
          <ul>
            <li>Device details (IP address, browser type, operating system)</li>
            <li>Website usage data through cookies and analytics</li>
            <li>Transaction history and interaction with our platform</li>
          </ul>
        </div>

        <div class="privacy-section">
          <h2>2. How We Use Your Information</h2>
          <p>We use your information for the following purposes:</p>
          <ul>
            <li>To verify your identity for business or seller registration</li>
            <li>To process payments, refunds, or dropshipping settlements</li>
            <li>To provide customer support and order tracking</li>
            <li>To improve our website and user experience</li>
            <li>To comply with legal and regulatory requirements</li>
            <li>To communicate offers, updates, and service changes</li>
          </ul>
        </div>

        <div class="privacy-section">
          <h2>3. How We Protect Your Data</h2>
          <p>We take data security seriously.</p>
          <ul>
            <li>All personal and financial information is stored in encrypted and access-controlled environments.</li>
            <li>Sensitive documents (PAN, Aadhaar, Bank Cheque, etc.) are used only for verification and never shared with third parties without your consent.</li>
            <li>Payment information is processed using secure, PCI-DSS compliant gateways.</li>
          </ul>
        </div>

        <div class="privacy-section">
          <h2>4. Data Sharing and Disclosure</h2>
          <p>We do not sell or rent your data to any third party.</p>
          <p>We may share data only with:</p>
          <ul>
            <li>Verified third-party service providers (e.g., courier, payment gateway, or verification partners) under strict confidentiality agreements.</li>
            <li>Law enforcement or government authorities, if legally required.</li>
          </ul>
        </div>

        <div class="privacy-section">
          <h2>5. Data Retention</h2>
          <p>We retain your information as long as your account remains active or as needed to comply with legal, tax, or business obligations.</p>
          <p>You can request data deletion anytime by contacting our support team.</p>
        </div>

        <div class="privacy-section">
          <h2>6. Your Rights</h2>
          <p>You have the right to:</p>
          <ul>
            <li>Access and review your personal data</li>
            <li>Request correction or deletion of data</li>
            <li>Withdraw consent for specific uses</li>
            <li>Opt out of promotional emails anytime</li>
          </ul>
          <p>For any such request, please contact us at:</p>
          <ul class="contact-info">
            <li>📧 <a href="mailto:support@aakaari.com">support@aakaari.com</a></li>
          </ul>
        </div>

        <div class="privacy-section">
          <h2>7. Cookies</h2>
          <p>We use cookies to:</p>
          <ul>
            <li>Improve website performance</li>
            <li>Save user preferences</li>
            <li>Analyze visitor traffic</li>
          </ul>
          <p>You may choose to disable cookies in your browser, though some features may not function properly.</p>
        </div>

        <div class="privacy-section">
          <h2>8. Updates to This Policy</h2>
          <p>We may update this Privacy Policy periodically. The updated version will always be available on our website with a revised "Last Updated" date.</p>
        </div>

        <div class="privacy-section">
          <h2>9. Contact Us</h2>
          <p>If you have any questions about our privacy practices, please contact:</p>
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

