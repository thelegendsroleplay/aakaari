<?php
/**
 * Template Name: Login Page
 *
 * @package Aakaari
 */

// Redirect if user is already logged in
if (is_user_logged_in()) {
    if (function_exists('aakaari_get_dashboard_url')) {
        wp_safe_redirect(aakaari_get_dashboard_url());
    } else {
        wp_safe_redirect(home_url('/my-account/'));
    }
    exit;
}

$login_error = '';
$login_message = '';

// Check for verification success
if (isset($_GET['verification']) && $_GET['verification'] === 'success') {
    $login_message = 'Your email has been verified successfully. Please login.';
}

// Check for cooldown status
if (isset($_POST['email'])) {
    $username = sanitize_text_field($_POST['email']);
    $user_obj = get_user_by('login', $username) ?: get_user_by('email', $username);
    
    if ($user_obj) {
        $user_id = $user_obj->ID;
        $cooldown_until = (int)get_user_meta($user_id, 'cooldown_until', true);

        if ($cooldown_until > time()) {
            $remaining = $cooldown_until - time();
            $minutes = ceil($remaining / MINUTE_IN_SECONDS);
            $login_error = sprintf(
                'Too many failed login attempts. Please wait %d minute(s) before trying again.',
                $minutes
            );
        }
    }
}


// Process the login form if submitted AND not in cooldown
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aakaari_login_nonce']) && empty($login_error)) {
    // Verify nonce
    if (wp_verify_nonce($_POST['aakaari_login_nonce'], 'aakaari_login')) {
        $creds = array(
            'user_login'    => sanitize_text_field($_POST['email']),
            'user_password' => $_POST['password'],
            'remember'      => isset($_POST['remember_me']) ? true : false
        );

        // Authenticate user (this will trigger our custom 'authenticate' and 'wp_login_failed' hooks)
        $user = wp_signon($creds, is_ssl());

        // Check for errors
if (is_wp_error($user)) {
    
    // *** NEW LOGIC: Intercept 'email_not_verified' error and redirect to OTP page ***
    if ($user->get_error_code() === 'email_not_verified') {
        // The verification hook (in security-features.php) has already re-sent the OTP 
        // and set the session needed for the registration/verification page to load.
        wp_safe_redirect(home_url('/register/?action=verify'));
        exit;
    }
    // ******************************************************************************
    
    $login_error = $user->get_error_message();
} else {
    // Successful login - redirect
    // Note: Our 'wp_authenticate_user' hook might still block unverified users here.
    
    // Use the proper dashboard URL function for redirect
    if (function_exists('aakaari_get_dashboard_url')) {
        $redirect = aakaari_get_dashboard_url();
    } else {
        $redirect = home_url('/my-account/');
    }

    // Check for custom redirect_to param
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : '';
    if (!empty($redirect_url) && wp_http_validate_url($redirect_url)) {
        $redirect = $redirect_url;
    }

    wp_safe_redirect($redirect);
    exit;
}
    }
}

// Get redirect URL if any
$redirect_url = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '';

// Remove default WordPress styling for the page
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wc_gallery_noscript');

get_header('minimal'); // Use a minimal header or create one
?>

<div class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-3.png'); ?>" alt="<?php bloginfo('name'); ?>" class="login-logo-img">
        </div>
        
        <h1 class="login-title">Reseller Login</h1>
        
        <div id="login-with-password-container">
            <p class="login-subtitle">Welcome back! Please login to your account.</p>

            <?php if (!empty($login_error)): ?>
                <div class="login-error">
                    <?php echo $login_error; // Already escaped by WP, but use wp_kses_post for links ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($login_message)): ?>
                <div class="login-message" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 12px; margin-bottom: 20px; color: #065F46; font-size: 14px; text-align: center;">
                    <?php echo esc_html($login_message); ?>
                </div>
            <?php endif; ?>

            <form id="aakaari-login-form" class="login-form" method="post" action="">
                <?php wp_nonce_field('aakaari_login', 'aakaari_login_nonce'); ?>
                
                <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect_url); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon"></span>
                        <input type="email" id="email" name="email" placeholder="reseller@example.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon"></span>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <label for="remember_me">Remember me</label>
                    </div>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-button" <?php echo !empty($login_error) ? 'disabled' : ''; ?>>Login</button>
            </form>
            
            <div class="login-separator" style="text-align: center; margin: 20px 0; color: #9ca3af;">OR</div>
            
            <button type="button" class="login-button" id="show-login-otp-btn" style="background-color: #f3f4f6; color: #1f2937; border: 1px solid #e5e7eb;">
                Login with OTP
            </button>
        </div>
        
        <div id="login-with-otp-container" style="display:none;">
            <p class="login-subtitle">Enter your email to receive a one-time login code.</p>
            
            <div class="validation-message" id="login-otp-validation-msg"></div>

            <form id="aakaari-login-otp-form" class="login-form">
                <div id="login-otp-step-1">
                    <div class="form-group">
                        <label for="login-otp-email">Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon"></span>
                            <input type="email" id="login-otp-email" name="login-otp-email" placeholder="reseller@example.com" required>
                        </div>
                    </div>
                    <button type="submit" class="login-button" id="send-login-otp-btn">Send Login Code</button>
                </div>
                
                <div id="login-otp-step-2" style="display:none;">
                    <div class="form-group">
                        <label for="login-otp-code">Verification Code</label>
                        <div class="input-wrapper">
                            <input type="text" id="login-otp-code" name="login-otp-code" placeholder="123456" required maxlength="6" inputmode="numeric">
                        </div>
                    </div>
                    <div id="login-otp-timer"></div>
                    <button type="submit" class="login-button" id="verify-login-otp-btn">Login with Code</button>
                    <button type="button" id="resend-login-otp-btn" class="link-button" style="margin-top: 15px; text-align:center; width: 100%;">Resend Code</button>
                </div>
            </form>
            
            <div class="login-separator" style="text-align: center; margin: 20px 0; color: #9ca3af;">OR</div>
            
            <button type="button" class="login-button" id="show-login-password-btn" style="background-color: #f3f4f6; color: #1f2937; border: 1px solid #e5e7eb;">
                Login with Password
            </button>
        </div>
        
        
        <div class="register-link">
            Don't have an account? <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'register' ) ) ); ?>">Create Account</a>
        </div>
        
    </div>
</div>

<style>
/* Add this to login.css */
.link-button {
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
.validation-message {
    font-size: 0.8rem;
    color: #dc2626; /* Red */
    margin-bottom: 15px;
    background: #fef2f2;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #fecaca;
    display: none; /* Hidden by default */
}
.validation-message.success {
    color: #16a34a; /* Green */
    background: #ecfdf5;
    border-color: #a7f3d0;
}
.validation-message.loading {
    color: #6b7280; /* Gray */
    background: #f9fafb;
    border-color: #e5e7eb;
}
#login-otp-timer {
    text-align: center;
    color: #6b7280;
    margin: 10px 0;
}
</style>

<?php get_footer('minimal'); // Use a minimal footer or create one ?>