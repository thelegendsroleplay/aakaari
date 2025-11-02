<?php
/**
 * Template Name: Shipping Policy
 */

get_header(); ?>

<main id="site-content" role="main">
  <section class="shipping-policy-page">
    <div class="container">
      <div class="shipping-header">
        <h1>Shipping Policy – Aakaari</h1>
        <p class="effective-date">Effective Date: <?php echo date('F j, Y'); ?></p>
        <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
      </div>

      <div class="shipping-content">
        <p class="intro-text">
          Thank you for shopping with Aakaari.<br><br>
          We aim to deliver your orders quickly, safely, and with complete transparency. This Shipping Policy explains our shipping procedures, timelines, charges, and conditions.
        </p>

        <div class="shipping-section">
          <h2>1. Order Processing Time</h2>
          <p>All orders are processed within 1–3 business days after payment confirmation.</p>
          <p>Orders are not shipped or delivered on Sundays or public holidays.</p>
          <p>For custom or personalized products, additional processing time (2–5 days) may apply depending on design approval and production load.</p>
          <p>If we experience a high volume of orders or unforeseen delays, we'll notify you via email or WhatsApp with an updated estimated delivery date.</p>
        </div>

        <div class="shipping-section">
          <h2>2. Shipping Methods and Timelines</h2>
          <p>We partner with trusted courier and logistics providers to ensure reliable service across India.</p>
          
          <div class="shipping-table-wrapper">
            <table class="shipping-table">
              <thead>
                <tr>
                  <th>Shipping Type</th>
                  <th>Estimated Delivery Time</th>
                  <th>Availability</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Standard Shipping</td>
                  <td>5–8 business days</td>
                  <td>All over India</td>
                </tr>
                <tr>
                  <td>Express Shipping</td>
                  <td>2–4 business days</td>
                  <td>Selected cities</td>
                </tr>
                <tr>
                  <td>International Shipping</td>
                  <td>10–20 business days (customs may apply)</td>
                  <td>Selected countries</td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <p class="note-text"><strong>Note:</strong> Delivery timelines are estimates and may vary due to external factors such as courier delays, weather, or local restrictions.</p>
        </div>

        <div class="shipping-section">
          <h2>3. Shipping Charges</h2>
          <ul>
            <li>Free Shipping on all prepaid orders above ₹999.</li>
            <li>A flat fee of ₹70 applies to prepaid orders below ₹999.</li>
            <li>Cash on Delivery (COD) orders may include an additional ₹50 service charge.</li>
            <li>International shipping costs are calculated at checkout based on destination and weight.</li>
          </ul>
        </div>

        <div class="shipping-section">
          <h2>4. Order Tracking</h2>
          <p>Once your order is shipped, you will receive:</p>
          <ul>
            <li>A tracking ID and courier details via email or SMS/WhatsApp.</li>
            <li>You can track your package anytime on our website under "Track Order" or directly on the courier partner's site.</li>
          </ul>
        </div>

        <div class="shipping-section">
          <h2>5. Incorrect Address or Non-Delivery</h2>
          <p>Please double-check your delivery details before placing an order.</p>
          <p>Aakaari is not responsible for failed deliveries due to incorrect or incomplete addresses.</p>
          <p>If a package is returned due to such reasons, re-shipping will incur additional charges.</p>
        </div>

        <div class="shipping-section">
          <h2>6. Damaged or Lost Packages</h2>
          <p>We take full responsibility for any damage or loss during transit.</p>
          <p>If your order arrives damaged, please report it within 48 hours of delivery by emailing <a href="mailto:support@aakaari.com">support@aakaari.com</a> with photos and order details.</p>
          <p>We will investigate and reship or refund as appropriate.</p>
        </div>

        <div class="shipping-section">
          <h2>7. International Shipping (If Applicable)</h2>
          <p>Customs duties, taxes, and import charges (if any) are the responsibility of the buyer.</p>
          <p>Aakaari is not liable for delays caused by customs clearance or international courier procedures.</p>
        </div>

        <div class="shipping-section">
          <h2>8. Delivery Delays</h2>
          <p>We make every effort to meet delivery timelines; however, unexpected delays (weather, logistics, or public holidays) may occur.</p>
          <p>In such cases, we'll keep you informed promptly.</p>
        </div>

        <div class="shipping-section">
          <h2>9. Contact Us</h2>
          <p>For shipping-related inquiries, please contact:</p>
          <ul class="contact-info">
            <li>📧 <a href="mailto:support@aakaari.com">support@aakaari.com</a></li>
            <li>🌐 <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank">www.aakaari.com</a></li>
            <li>🕐 Monday – Saturday | 10:00 AM – 7:00 PM</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>

