<?php
/**
 * Template Name: Login Page
 *
 * @package Aakaari
 */

// Redirect if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/my-account'));
    exit;
}

// Process the login form if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aakaari_login_nonce'])) {
    // Verify nonce
    if (wp_verify_nonce($_POST['aakaari_login_nonce'], 'aakaari_login')) {
        $creds = array(
            'user_login'    => sanitize_text_field($_POST['email']),
            'user_password' => $_POST['password'],
            'remember'      => isset($_POST['remember_me']) ? true : false
        );

        // Authenticate user
        $user = wp_signon($creds, is_ssl());

        // Check for errors
        if (is_wp_error($user)) {
            $login_error = $user->get_error_message();
        } else {
            // Successful login - redirect
            $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : home_url('/my-account');
            wp_redirect($redirect);
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
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#2563EB" viewBox="0 0 16 16">
                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
            </svg>
            <span class="logo-text">Aakaari</span>
        </div>
        
        <h1 class="login-title">Reseller Login</h1>
        <p class="login-subtitle">Welcome back! Please login to your account.</p>

        <?php if (isset($login_error)): ?>
            <div class="login-error">
                <?php echo esc_html($login_error); ?>
            </div>
        <?php endif; ?>

        <form id="aakaari-login-form" class="login-form" method="post" action="">
            <?php wp_nonce_field('aakaari_login', 'aakaari_login_nonce'); ?>
            
            <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect_url); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
                        </svg>
                    </span>
                    <input type="email" id="email" name="email" placeholder="reseller@example.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                        </svg>
                    </span>
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
            
            <button type="submit" class="login-button">Login</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'register' ) ) ); ?>">Create Account</a>
        </div>
        
        <div class="demo-credentials">
            <h3>Demo Login Credentials:</h3>
            <p>Email: demo@reseller.com</p>
            <p>Password: demo123</p>
        </div>
    </div>
</div>

<?php get_footer('minimal'); // Use a minimal footer or create one ?>