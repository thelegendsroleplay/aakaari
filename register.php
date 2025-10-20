<?php
/**
 * Template Name: Reseller Registration
 *
 * This template handles the display of the reseller registration form
 * AND the OTP verification step.
 */

// Check if a user is already logged in
if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/dashboard/'));
    exit;
}

// Check if a user is in the process of verifying their email
$user_verifying_id = null;
$user_verifying_email = '';
if (function_exists('WC') && WC()->session) {
    $user_verifying_id = WC()->session->get('aakaari_user_verifying');
}

if ($user_verifying_id) {
    $user_data = get_user_by('id', $user_verifying_id);
    if ($user_data) {
        // Check if user is already verified (e.g., pressed back button)
        if (get_user_meta($user_verifying_id, 'email_verified', true)) {
            // Already verified, clear session and send to login
            WC()->session->set('aakaari_user_verifying', null);
            wp_safe_redirect(home_url('/login/?verification=success'));
            exit;
        }
        $user_verifying_email = $user_data->user_email;
    } else {
        // Invalid user ID in session, clear it
        WC()->session->set('aakaari_user_verifying', null);
    }
}


get_header(); // Includes your theme's header
?>

<div id="reseller-registration-container">
    <div class="reseller-card">
        
        <div id="registration-form-container" <?php echo ($user_verifying_id) ? 'style="display:none;"' : ''; ?>>
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
                    
                    <p class="aakaari-hp-field" style="display:none !important;" aria-hidden="true">
                        <label for="aakaari_hp">Leave this field empty</label>
                        <input type="text" name="aakaari_hp" id="aakaari_hp" tabindex="-1" autocomplete="off">
                    </p>

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
                                <div class="validation-message" id="phone-validation-msg"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <div class="input-with-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <input id="email" name="email" type="email" placeholder="john@example.com" required />
                            </div>
                            <div class="validation-message" id="email-validation-msg"></div>
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
                                    <input id="password" name="password" type="password" placeholder="Min. 8 characters" required />
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
                        <div id="password-strength-meter">
                            <p>Password must contain:</p>
                            <ul>
                                <li id="ps-length">At least 8 characters</li>
                                <li id="ps-uppercase">1 uppercase letter (A-Z)</li>
                                <li id="ps-number">1 number (0-9)</li>
                                <li id="ps-special">1 special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                        <div class="validation-message" id="confirm-password-validation-msg"></div>
                    </div>

                    <div class="terms-group">
                        <input id="acceptTerms" name="acceptTerms" type="checkbox" required />
                        <label for="acceptTerms">
                            I agree to the <a href="/terms-and-conditions">Terms & Conditions</a> and <a href="/privacy-policy">Privacy Policy</a>.
                        </label>
                    </div>

                    <button type="submit" class="submit-button" id="register-submit-btn">Create Account</button>
                    
                    <div class="login-link">
                        <span>Already have an account? </span>
                        <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'login' ) ) ); ?>">Login here</a>
                    </div>
                </form>

            </div>
        </div>
        
        <div id="otp-verification-container" <?php echo (!$user_verifying_id) ? 'style="display:none;"' : ''; ?>>
            <div class="reseller-card-header">
                <div class="header-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.2 8.4c.5.38.8.97.8 1.6v7a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V10a2 2 0 0 1 .8-1.6l8-6a2 2 0 0 1 2.4 0l8 6Z"/><path d="m22 10-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 10"/></svg>
                    <span class="header-brand-name">Aakaari</span>
                </div>
                <h1 class="header-title">Verify Your Email</h1>
                <p class="header-subtitle">We sent a 6-digit code to <strong id="otp-email-display"><?php echo esc_html($user_verifying_email); ?></strong>. Please enter it below.</p>
            </div>
            <div class="reseller-card-content">
                <form id="otp-verification-form" class="form-grid">
                    <div class="form-group">
                        <label for="otpCode">Verification Code *</label>
                        <input id="otpCode" name="otpCode" type="text" placeholder="123456" required maxlength="6" pattern="\d{6}" inputmode="numeric" />
                    </div>
                    
                    <div id="otp-timer"></div>
                    <div class="validation-message" id="otp-validation-msg"></div>
                    
                    <button type="submit" class="submit-button" id="otp-submit-btn">Verify Account</button>
                    
                    <div class="login-link">
                        <button type="button" id="resend-otp-btn" class="link-button">Resend Code</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>
<div id="toast-notification" class=""></div>

<style>
/* Add this CSS for the new elements. You can move this to register.css */
.validation-message {
    font-size: 0.8rem;
    color: #dc2626; /* Red */
    margin-top: 5px;
    display: none; /* Hidden by default */
}
.validation-message.success {
    color: #16a34a; /* Green */
}
.validation-message.loading {
    color: #6b7280; /* Gray */
}
#password-strength-meter {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 10px;
}
#password-strength-meter p {
    margin: 0 0 5px;
    font-weight: 600;
}
#password-strength-meter ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}
#password-strength-meter li {
    position: relative;
    padding-left: 20px;
    color: #dc2626; /* Red by default */
}
#password-strength-meter li::before {
    content: '×';
    position: absolute;
    left: 0;
    font-weight: 700;
}
#password-strength-meter li.valid {
    color: #16a34a; /* Green */
}
#password-strength-meter li.valid::before {
    content: '✓';
}
button.link-button {
    background: none;
    border: none;
    color: var(--primary-color);
    text-decoration: underline;
    cursor: pointer;
    font-size: inherit;
    padding: 0;
}
button.link-button:disabled {
    color: #9ca3af;
    text-decoration: none;
    cursor: not-allowed;
}
#otp-timer {
    text-align: center;
    color: #6b7280;
    margin: 10px 0;
}
</style>

<?php
get_footer(); // Includes your theme's footer
?>