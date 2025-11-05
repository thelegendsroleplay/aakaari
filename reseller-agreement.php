<?php
/**
 * Template Name: Reseller Agreement
 *
 * @package Aakaari
 */

get_header();
?>

<style>
/* Reseller Agreement Page Styles */
.agreement-page {
    background: #ffffff;
    padding: 60px 0 80px;
}

.agreement-header {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    padding: 60px 0;
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
}

.agreement-header h1 {
    font-size: 42px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 16px 0;
    letter-spacing: -0.02em;
}

.agreement-header .last-updated {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.agreement-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 60px 20px;
}

.agreement-intro {
    background: #f8fafc;
    border-left: 4px solid #2563eb;
    padding: 24px 28px;
    margin-bottom: 48px;
    border-radius: 8px;
}

.agreement-intro p {
    font-size: 16px;
    line-height: 1.8;
    color: #374151;
    margin: 0;
}

.agreement-section {
    margin-bottom: 48px;
}

.agreement-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 20px 0;
    letter-spacing: -0.01em;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.agreement-section h3 {
    font-size: 22px;
    font-weight: 600;
    color: #1f2937;
    margin: 32px 0 16px 0;
}

.agreement-section p {
    font-size: 16px;
    line-height: 1.8;
    color: #4b5563;
    margin-bottom: 16px;
}

.agreement-section ul,
.agreement-section ol {
    margin: 16px 0;
    padding-left: 28px;
}

.agreement-section li {
    font-size: 16px;
    line-height: 1.8;
    color: #4b5563;
    margin-bottom: 12px;
}

.agreement-section strong {
    color: #111827;
    font-weight: 600;
}

.highlight-box {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 20px 24px;
    margin: 24px 0;
    border-radius: 8px;
}

.highlight-box p {
    color: #92400e;
    margin: 0;
    font-weight: 500;
}

.agreement-footer {
    background: #f8fafc;
    padding: 32px;
    border-radius: 12px;
    text-align: center;
    margin-top: 60px;
}

.agreement-footer p {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 20px 0;
    line-height: 1.6;
}

.agreement-footer .contact-btn {
    display: inline-block;
    background: #2563eb;
    color: #ffffff;
    padding: 12px 32px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.agreement-footer .contact-btn:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .agreement-header {
        padding: 40px 20px;
    }

    .agreement-header h1 {
        font-size: 32px;
    }

    .agreement-container {
        padding: 40px 16px;
    }

    .agreement-section h2 {
        font-size: 24px;
    }

    .agreement-section h3 {
        font-size: 20px;
    }

    .agreement-section p,
    .agreement-section li {
        font-size: 15px;
    }
}

@media (max-width: 480px) {
    .agreement-header h1 {
        font-size: 28px;
    }

    .agreement-intro,
    .highlight-box {
        padding: 16px 20px;
    }

    .agreement-section h2 {
        font-size: 22px;
    }

    .agreement-section h3 {
        font-size: 18px;
    }
}
</style>

<div class="agreement-page">
    <!-- Header -->
    <div class="agreement-header">
        <div class="container">
            <h1>Reseller Agreement</h1>
            <p class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></p>
        </div>
    </div>

    <div class="agreement-container">
        <!-- Introduction -->
        <div class="agreement-intro">
            <p>This Reseller Agreement ("Agreement") is entered into between <strong>Aakaari</strong> ("Company," "we," "us," or "our") and you ("Reseller," "you," or "your"). By registering as a reseller and using our platform, you agree to be bound by the terms and conditions set forth in this Agreement.</p>
        </div>

        <!-- Section 1: Definitions -->
        <div class="agreement-section">
            <h2>1. Definitions</h2>
            <ul>
                <li><strong>Platform:</strong> Refers to the Aakaari website, dashboard, and all related services provided to resellers.</li>
                <li><strong>Products:</strong> Customizable merchandise available through the Aakaari platform, including but not limited to apparel, accessories, and promotional items.</li>
                <li><strong>Base Price:</strong> The wholesale price charged by Aakaari for products, visible to approved resellers on the platform.</li>
                <li><strong>Retail Price:</strong> The selling price set by you (the reseller) to your customers.</li>
                <li><strong>White-Label Service:</strong> Products shipped under your brand name without any Aakaari branding visible to the end customer.</li>
            </ul>
        </div>

        <!-- Section 2: Scope of Agreement -->
        <div class="agreement-section">
            <h2>2. Scope of Agreement</h2>
            <p>This Agreement establishes a business relationship wherein:</p>
            <ol>
                <li>Aakaari provides access to its product catalog at wholesale prices (Base Price)</li>
                <li>You (Reseller) market and sell these products to end customers at your own retail prices</li>
                <li>You pay Aakaari the Base Price for each order placed</li>
                <li>Aakaari handles production, customization, packaging, and shipping under your brand name</li>
                <li>The profit margin (difference between Retail Price and Base Price) belongs entirely to you</li>
            </ol>
        </div>

        <!-- Section 3: Reseller Registration & Approval -->
        <div class="agreement-section">
            <h2>3. Reseller Registration & Approval</h2>

            <h3>3.1 Application Process</h3>
            <p>To become an approved reseller, you must:</p>
            <ul>
                <li>Complete the online application form with accurate information</li>
                <li>Submit valid KYC documents (Aadhaar Card, PAN Card, Bank Proof, and optionally Business Registration/GST)</li>
                <li>Provide legitimate contact details and banking information</li>
                <li>Agree to all terms and conditions outlined in this Agreement</li>
            </ul>

            <h3>3.2 Approval Criteria</h3>
            <p>Aakaari reserves the right to approve or reject any reseller application based on:</p>
            <ul>
                <li>Completeness and accuracy of submitted information</li>
                <li>Validity of KYC documents</li>
                <li>Compliance with applicable laws and regulations</li>
                <li>Business compatibility with Aakaari's values and standards</li>
            </ul>

            <h3>3.3 Approval Timeline</h3>
            <p>Applications are typically reviewed and processed within <strong>24-48 business hours</strong>. Approved resellers will receive login credentials via email.</p>

            <div class="highlight-box">
                <p><strong>Important:</strong> Providing false, misleading, or fraudulent information during registration will result in immediate rejection and potential legal action.</p>
            </div>
        </div>

        <!-- Section 4: Business Model -->
        <div class="agreement-section">
            <h2>4. Business Model & Operations</h2>

            <h3>4.1 How It Works</h3>
            <ol>
                <li><strong>Product Selection:</strong> Browse the product catalog on your reseller dashboard</li>
                <li><strong>Price Setting:</strong> Set your own retail prices (Aakaari does not dictate your selling price)</li>
                <li><strong>Customer Acquisition:</strong> Market and sell products through your own channels (website, social media, marketplace, etc.)</li>
                <li><strong>Payment Collection:</strong> Collect payments directly from your customers using your preferred method</li>
                <li><strong>Order Placement:</strong> Place orders on the Aakaari platform by paying the Base Price upfront</li>
                <li><strong>Fulfillment:</strong> Aakaari produces, customizes, packages, and ships the product under your brand name</li>
                <li><strong>Profit:</strong> You keep the entire difference between your Retail Price and Aakaari's Base Price</li>
            </ol>

            <h3>4.2 Independent Business Relationship</h3>
            <p>You acknowledge and agree that:</p>
            <ul>
                <li>You are an independent business entity, not an employee, agent, or partner of Aakaari</li>
                <li>You are responsible for your own customer relationships, marketing, and sales</li>
                <li>Aakaari is your backend production and fulfillment partner only</li>
                <li>This is not a franchise, MLM, affiliate, or commission-based model</li>
            </ul>
        </div>

        <!-- Section 5: Pricing & Payments -->
        <div class="agreement-section">
            <h2>5. Pricing & Payments</h2>

            <h3>5.1 Base Pricing</h3>
            <p>Aakaari provides transparent wholesale pricing (Base Price) visible on your reseller dashboard. Base prices may be updated periodically with prior notice.</p>

            <h3>5.2 Retail Pricing</h3>
            <p>You have complete freedom to set your own retail prices. Aakaari does not control or influence your pricing decisions.</p>

            <h3>5.3 Payment Terms</h3>
            <ul>
                <li>All orders must be <strong>prepaid by the reseller</strong> at the time of order placement</li>
                <li>Accepted payment methods: UPI, Net Banking, Credit/Debit Cards, and other methods specified on the platform</li>
                <li><strong>No COD (Cash on Delivery) is available for reseller orders</strong></li>
                <li>Payments are non-refundable once an order enters production</li>
            </ul>

            <h3>5.4 No Credit Facility</h3>
            <p>Aakaari does not offer credit terms, payment plans, or "pay later" options. All orders require full upfront payment.</p>
        </div>

        <!-- Section 6: Order Fulfillment -->
        <div class="agreement-section">
            <h2>6. Order Fulfillment & Shipping</h2>

            <h3>6.1 Order Processing</h3>
            <p>Once you place an order and payment is confirmed:</p>
            <ul>
                <li>Orders are processed within 1-3 business days</li>
                <li>Customization and printing are completed according to your specifications</li>
                <li>Products are packaged under your brand name (white-label service)</li>
            </ul>

            <h3>6.2 Shipping & Delivery</h3>
            <ul>
                <li><strong>Standard Delivery:</strong> 3-7 business days (depending on location)</li>
                <li><strong>Express Delivery:</strong> Available for select pin codes (additional charges may apply)</li>
                <li>Tracking information is provided for all shipments</li>
                <li>Shipping charges are included in the Base Price unless otherwise specified</li>
            </ul>

            <h3>6.3 White-Label Branding</h3>
            <p>All packages are shipped under your brand name. Aakaari's branding will <strong>not</strong> appear on:</p>
            <ul>
                <li>Product packaging</li>
                <li>Shipping labels (your brand name will be displayed)</li>
                <li>Invoices or packing slips sent to the end customer</li>
            </ul>
        </div>

        <!-- Section 7: Quality Assurance -->
        <div class="agreement-section">
            <h2>7. Quality Assurance & Returns</h2>

            <h3>7.1 Quality Standards</h3>
            <p>Aakaari maintains strict quality control standards for all products. Each item undergoes inspection before dispatch.</p>

            <h3>7.2 Defective Products & Replacements</h3>
            <p>In case of manufacturing defects or damaged products:</p>
            <ul>
                <li>Report the issue within <strong>7 days of delivery</strong></li>
                <li>Provide clear evidence (photos/videos of the defect)</li>
                <li>Submit order ID and customer details</li>
                <li>Aakaari will provide a <strong>replacement</strong> (not a refund) after verification</li>
            </ul>

            <div class="highlight-box">
                <p><strong>No Return Policy:</strong> Products cannot be returned for refunds. Only replacements are provided for defective or damaged items.</p>
            </div>

            <h3>7.3 Customer Complaints</h3>
            <p>As the reseller, you are responsible for handling customer complaints and disputes. Aakaari will assist with quality-related issues only.</p>
        </div>

        <!-- Section 8: Intellectual Property -->
        <div class="agreement-section">
            <h2>8. Intellectual Property & Branding</h2>

            <h3>8.1 Your Content</h3>
            <p>You retain ownership of all designs, logos, and branding elements you upload to the platform. By uploading content, you grant Aakaari a limited license to use it solely for production and fulfillment purposes.</p>

            <h3>8.2 Prohibited Content</h3>
            <p>You agree not to upload or use:</p>
            <ul>
                <li>Copyrighted material without proper authorization</li>
                <li>Trademarked logos or brand names you don't own</li>
                <li>Offensive, defamatory, or illegal content</li>
                <li>Content that violates any third-party rights</li>
            </ul>

            <h3>8.3 Aakaari's Intellectual Property</h3>
            <p>All platform technology, features, and Aakaari branding remain the exclusive property of Aakaari. You may not copy, modify, or reverse-engineer any part of the platform.</p>
        </div>

        <!-- Section 9: Prohibited Activities -->
        <div class="agreement-section">
            <h2>9. Prohibited Activities</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Misrepresent your relationship with Aakaari (e.g., claiming to be Aakaari or an official partner)</li>
                <li>Engage in fraudulent activities, including fake orders or payment fraud</li>
                <li>Use the platform for illegal purposes</li>
                <li>Share your login credentials with unauthorized parties</li>
                <li>Attempt to access restricted areas of the platform</li>
                <li>Spam or engage in unsolicited marketing using Aakaari's name</li>
                <li>Sell counterfeit or prohibited products</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Violation of these terms may result in immediate account suspension and legal action.</strong></p>
            </div>
        </div>

        <!-- Section 10: Data & Privacy -->
        <div class="agreement-section">
            <h2>10. Data Protection & Privacy</h2>

            <h3>10.1 Your Data</h3>
            <p>Aakaari collects and stores your personal and business information (name, contact details, banking information, KYC documents) as required for reseller operations.</p>

            <h3>10.2 Customer Data</h3>
            <p>When you place orders on behalf of your customers, you provide shipping and contact information to Aakaari. This data is used solely for order fulfillment.</p>

            <h3>10.3 Confidentiality</h3>
            <p>Aakaari will not share your customer information with third parties (except logistics partners for shipping purposes). We maintain strict confidentiality of all reseller data.</p>

            <h3>10.4 Data Security</h3>
            <p>While we implement industry-standard security measures, you acknowledge that no system is 100% secure. You are responsible for safeguarding your login credentials.</p>
        </div>

        <!-- Section 11: Taxes & Compliance -->
        <div class="agreement-section">
            <h2>11. Taxes & Legal Compliance</h2>

            <h3>11.1 GST & Taxation</h3>
            <ul>
                <li>If you have a GSTIN, provide it during registration for input tax credit benefits</li>
                <li>Base prices shown on the platform may include applicable GST</li>
                <li>You are responsible for filing your own tax returns and compliance</li>
                <li>Aakaari provides invoices for all orders placed</li>
            </ul>

            <h3>11.2 Legal Compliance</h3>
            <p>You agree to comply with all applicable laws, including:</p>
            <ul>
                <li>Consumer protection laws</li>
                <li>Advertising and marketing regulations</li>
                <li>Data protection and privacy laws</li>
                <li>E-commerce regulations</li>
            </ul>
        </div>

        <!-- Section 12: Termination -->
        <div class="agreement-section">
            <h2>12. Termination</h2>

            <h3>12.1 Termination by Reseller</h3>
            <p>You may terminate this Agreement at any time by ceasing to use the platform. Any pending orders must be fulfilled as per agreed terms.</p>

            <h3>12.2 Termination by Aakaari</h3>
            <p>Aakaari reserves the right to suspend or terminate your reseller account for:</p>
            <ul>
                <li>Violation of this Agreement</li>
                <li>Fraudulent activities or payment disputes</li>
                <li>Excessive customer complaints or quality issues</li>
                <li>Legal or regulatory concerns</li>
            </ul>

            <h3>12.3 Effect of Termination</h3>
            <p>Upon termination:</p>
            <ul>
                <li>Your access to the reseller dashboard will be revoked</li>
                <li>Pending orders will be completed or refunded at Aakaari's discretion</li>
                <li>You must cease all use of Aakaari's platform and services</li>
            </ul>
        </div>

        <!-- Section 13: Limitation of Liability -->
        <div class="agreement-section">
            <h2>13. Limitation of Liability</h2>
            <p>To the maximum extent permitted by law:</p>
            <ul>
                <li>Aakaari is not liable for any indirect, incidental, or consequential damages</li>
                <li>Our total liability is limited to the amount paid by you for the specific order in question</li>
                <li>Aakaari is not responsible for delays caused by third-party logistics providers</li>
                <li>We are not liable for losses arising from your business decisions or customer disputes</li>
            </ul>
        </div>

        <!-- Section 14: Dispute Resolution -->
        <div class="agreement-section">
            <h2>14. Dispute Resolution</h2>

            <h3>14.1 Governing Law</h3>
            <p>This Agreement is governed by the laws of India. Any disputes shall be subject to the exclusive jurisdiction of courts in [City Name], India.</p>

            <h3>14.2 Dispute Resolution Process</h3>
            <ol>
                <li><strong>Contact Support:</strong> Attempt to resolve the issue through our support team</li>
                <li><strong>Mediation:</strong> If unresolved, both parties agree to attempt mediation</li>
                <li><strong>Legal Action:</strong> As a last resort, disputes may be resolved through arbitration or court proceedings</li>
            </ol>
        </div>

        <!-- Section 15: Modifications -->
        <div class="agreement-section">
            <h2>15. Modifications to Agreement</h2>
            <p>Aakaari reserves the right to modify this Agreement at any time. Material changes will be communicated via email or dashboard notifications. Continued use of the platform after modifications constitutes acceptance of the updated terms.</p>
        </div>

        <!-- Section 16: General Provisions -->
        <div class="agreement-section">
            <h2>16. General Provisions</h2>

            <h3>16.1 Entire Agreement</h3>
            <p>This Agreement constitutes the entire understanding between you and Aakaari regarding the reseller relationship.</p>

            <h3>16.2 Severability</h3>
            <p>If any provision of this Agreement is found to be unenforceable, the remaining provisions will continue in full force.</p>

            <h3>16.3 Waiver</h3>
            <p>Failure by Aakaari to enforce any right or provision does not constitute a waiver of that right.</p>

            <h3>16.4 Assignment</h3>
            <p>You may not assign or transfer this Agreement without Aakaari's prior written consent.</p>
        </div>

        <!-- Contact Section -->
        <div class="agreement-footer">
            <p>If you have any questions about this Reseller Agreement, please contact us:</p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="contact-btn">Contact Support</a>
        </div>
    </div>
</div>

<?php
get_footer();
