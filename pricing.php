<?php
/**
 * Template Name: Pricing
 *
 * @package Aakaari
 */

get_header();
?>

<main id="primary" class="site-main">
    <!-- Hero Section -->
    <section class="pricing-hero">
        <div class="container">
            <h1>Simple, Transparent Pricing</h1>
            <p class="subtitle">Start for free, upgrade when you need more. No hidden fees.</p>
        </div>
    </section>

    <!-- Pricing Cards -->
    <section class="pricing-cards">
        <div class="container">
            <div class="pricing-grid">
                <!-- Free Plan -->
                <div class="pricing-card">
                    <div class="card-header">
                        <h2 class="plan-name">Free</h2>
                        <div class="price">
                            <span class="currency">₹</span>
                            <span class="amount">0</span>
                        </div>
                        <p class="billing-cycle">Forever</p>
                        <p class="plan-description">Perfect for getting started</p>
                    </div>
                    <div class="card-cta">
                        <a href="#" class="btn btn-outline">Get Started Free</a>
                    </div>
                    <div class="card-features">
                        <ul>
                            <li class="feature-included">Access to full product catalog</li>
                            <li class="feature-included">Standard wholesale pricing</li>
                            <li class="feature-included">Minimum order quantities apply</li>
                            <li class="feature-included">Standard shipping (3-7 days)</li>
                            <li class="feature-included">Email support</li>
                            <li class="feature-included">Dashboard access</li>
                            <li class="feature-included">Product images download</li>
                            <li class="feature-included">Commission tracking</li>
                            <li class="feature-not-included">Priority support</li>
                            <li class="feature-not-included">Bulk pricing tiers</li>
                            <li class="feature-not-included">Dedicated account manager</li>
                        </ul>
                    </div>
                </div>

                <!-- Pro Plan -->
                <div class="pricing-card popular">
                    <div class="popular-badge">Most Popular</div>
                    <div class="card-header">
                        <h2 class="plan-name">Pro</h2>
                        <div class="price">
                            <span class="currency">₹</span>
                            <span class="amount">999</span>
                        </div>
                        <p class="billing-cycle">per month</p>
                        <p class="plan-description">For serious resellers</p>
                    </div>
                    <div class="card-cta">
                        <a href="#" class="btn btn-primary">Start Pro Trial</a>
                    </div>
                    <div class="card-features">
                        <ul>
                            <li class="feature-included">Everything in Free plan</li>
                            <li class="feature-included">Better wholesale pricing (5-10% off)</li>
                            <li class="feature-included">Reduced MOQ on select products</li>
                            <li class="feature-included">Priority shipping (2-4 days)</li>
                            <li class="feature-included">Priority email & WhatsApp support</li>
                            <li class="feature-included">Advanced analytics dashboard</li>
                            <li class="feature-included">Marketing material support</li>
                            <li class="feature-included">Referral bonuses</li>
                            <li class="feature-included">Monthly product updates</li>
                            <li class="feature-not-included">Dedicated account manager</li>
                        </ul>
                    </div>
                </div>

                <!-- Enterprise Plan -->
                <div class="pricing-card">
                    <div class="card-header">
                        <h2 class="plan-name">Enterprise</h2>
                        <div class="price">
                            <span class="amount">Custom</span>
                        </div>
                        <p class="billing-cycle">Contact us</p>
                        <p class="plan-description">For high-volume businesses</p>
                    </div>
                    <div class="card-cta">
                        <a href="#" class="btn btn-outline">Contact Sales</a>
                    </div>
                    <div class="card-features">
                        <ul>
                            <li class="feature-included">Everything in Pro plan</li>
                            <li class="feature-included">Best wholesale pricing (10-15% off)</li>
                            <li class="feature-included">Custom MOQ arrangements</li>
                            <li class="feature-included">Express shipping (1-2 days)</li>
                            <li class="feature-included">Dedicated account manager</li>
                            <li class="feature-included">24/7 priority support</li>
                            <li class="feature-included">Custom product sourcing</li>
                            <li class="feature-included">White-label options</li>
                            <li class="feature-included">API access</li>
                            <li class="feature-included">Custom payment terms</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Comparison -->
    <section class="feature-comparison">
        <div class="container">
            <h2>Feature Comparison</h2>
            <p class="section-subtitle">See what's included in each plan</p>

            <div class="table-container">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Free</th>
                            <th>Pro</th>
                            <th>Enterprise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Product catalog access</td>
                            <td><span class="check-mark">✓</span></td>
                            <td><span class="check-mark">✓</span></td>
                            <td><span class="check-mark">✓</span></td>
                        </tr>
                        <tr>
                            <td>Wholesale pricing</td>
                            <td>Standard</td>
                            <td>5-10% off</td>
                            <td>10-15% off</td>
                        </tr>
                        <tr>
                            <td>Shipping time</td>
                            <td>3-7 days</td>
                            <td>2-4 days</td>
                            <td>1-2 days</td>
                        </tr>
                        <tr>
                            <td>Support</td>
                            <td>Email</td>
                            <td>Priority</td>
                            <td>24/7 Dedicated</td>
                        </tr>
                        <tr>
                            <td>Account manager</td>
                            <td>-</td>
                            <td>-</td>
                            <td><span class="check-mark">✓</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Pricing FAQs -->
    <section class="pricing-faqs">
        <div class="container">
            <h2>Pricing FAQs</h2>
            
            <div class="faq-container">
                <div class="faq-item">
                    <h3>Can I change plans later?</h3>
                    <div class="faq-answer">
                        <p>Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>Is there a free trial for Pro plan?</h3>
                    <div class="faq-answer">
                        <p>Yes, we offer a 7-day free trial for the Pro plan. No credit card required.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>What payment methods do you accept?</h3>
                    <div class="faq-answer">
                        <p>We accept UPI, credit/debit cards, net banking, and bank transfers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Questions CTA -->
    <section class="questions-cta">
        <div class="container">
            <h2>Still Have Questions?</h2>
            <p>Our team is here to help you choose the right plan</p>
            <div class="cta-button-wrapper">
                <a href="#" class="btn btn-light">Contact Sales</a>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();