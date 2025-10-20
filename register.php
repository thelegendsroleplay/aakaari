<?php
/**
 * Template Name: Reseller Registration
 *
 * This template handles the display of the reseller registration form.
 */

get_header(); // Includes your theme's header
?>

<div id="reseller-registration-container">
    <div class="reseller-card">
        <div class="reseller-card-header">
            <div class="header-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span class="header-brand-name">Aakaari</span>
            </div>
            <h1 class="header-title">Create Reseller Account</h1>
            <p class="header-subtitle">Join our reseller network and start earning today!</p>
        </div>
        <div class="reseller-card-content">
            <form id="reseller-registration-form" class="form-grid" novalidate>
                
                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="grid-cols-2">
                        <div class="form-group">
                            <label for="fullName">Full Name *</label>
                            <div class="input-with-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <input id="fullName" name="fullName" type="text" placeholder="John Doe" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <div class="input-with-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <input id="phone" name="phone" type="tel" placeholder="+91 9876543210" required />
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            <input id="email" name="email" type="email" placeholder="john@example.com" required />
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Business Information</h3>
                    <div class="grid-cols-2">
                        <div class="form-group">
                            <label for="businessName">Business Name</label>
                            <div class="input-with-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
                                <input id="businessName" name="businessName" type="text" placeholder="Your Business Name" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="businessType">Business Type</label>
                            <select id="businessType" name="businessType">
                                <option value="">Select business type</option>
                                <option value="individual">Individual/Freelancer</option>
                                <option value="online-store">Online Store</option>
                                <option value="retail">Retail Shop</option>
                                <option value="wholesale">Wholesale Distributor</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid-cols-2">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <div class="input-with-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <input id="city" name="city" type="text" placeholder="Mumbai" required />
                            </div>
                        </div>
                         <div class="form-group">
                            <label for="state">State *</label>
                            <select id="state" name="state" required>
                                <option value="">Select state</option>
                                <option value="maharashtra">Maharashtra</option>
                                <option value="delhi">Delhi</option>
                                <option value="karnataka">Karnataka</option>
                                <option value="tamil-nadu">Tamil Nadu</option>
                                <option value="uttar-pradesh">Uttar Pradesh</option>
                                <option value="gujarat">Gujarat</option>
                                <option value="rajasthan">Rajasthan</option>
                                <option value="west-bengal">West Bengal</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Security</h3>
                    <div class="grid-cols-2">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <div class="input-with-icon">
                               <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input id="password" name="password" type="password" placeholder="Min. 8 characters" required minlength="8" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password *</label>
                            <div class="input-with-icon">
                               <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <input id="confirmPassword" name="confirmPassword" type="password" placeholder="Re-enter password" required />
                            </div>
                        </div>
                    </div>
                    <p class="password-hint">Password must be at least 8 characters long.</p>
                </div>

                <div class="terms-group">
                    <input id="acceptTerms" name="acceptTerms" type="checkbox" required />
                    <label for="acceptTerms">
                        I agree to the <a href="/terms-and-conditions">Terms & Conditions</a> and <a href="/privacy-policy">Privacy Policy</a>. I understand my account will be reviewed and approved by the admin.
                    </label>
                </div>

                <button type="submit" class="submit-button">Create Account</button>
                
                <div class="login-link">
                    <span>Already have an account? </span>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'login' ) ) ); ?>">Login here</a>
                </div>
            </form>

            <div class="approval-note-container">
                <div class="approval-note">
                    <p><strong>Note:</strong> After registration, your account will be reviewed by our admin team. You'll receive an email notification once your account is approved (typically within 24-48 hours).</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="toast-notification" class=""></div>

<?php
get_footer(); // Includes your theme's footer
?>