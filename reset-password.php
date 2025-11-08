<?php
/**
 * Template Name: Reset Password
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

$reset_error = '';
$reset_success = false;
$show_form = false;

// Get reset key and login from URL
$reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$user_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

// Validate reset key
if ($reset_key && $user_login) {
    $user = check_password_reset_key($reset_key, $user_login);

    if (is_wp_error($user)) {
        if ($user->get_error_code() === 'expired_key') {
            $reset_error = 'This password reset link has expired. Please request a new one.';
        } else {
            $reset_error = 'Invalid password reset link. Please request a new one.';
        }
    } else {
        $show_form = true;

        // Handle password reset submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aakaari_new_password_nonce'])) {
            if (wp_verify_nonce($_POST['aakaari_new_password_nonce'], 'aakaari_reset_password_' . $user->ID)) {
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                // Validate passwords
                if (empty($new_password)) {
                    $reset_error = 'Please enter a new password.';
                } elseif (strlen($new_password) < 8) {
                    $reset_error = 'Password must be at least 8 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $reset_error = 'Passwords do not match. Please try again.';
                } else {
                    // Reset the password
                    reset_password($user, $new_password);

                    // Clear user meta
                    delete_user_meta($user->ID, 'otp_code');
                    delete_user_meta($user->ID, 'otp_generated_at');
                    delete_user_meta($user->ID, 'otp_verify_attempts');
                    delete_user_meta($user->ID, 'otp_resend_count');

                    $reset_success = true;
                    $show_form = false;
                }
            } else {
                $reset_error = 'Security verification failed. Please try again.';
            }
        }
    }
} else {
    $reset_error = 'Invalid password reset request. Please use the link sent to your email.';
}

get_header('minimal');
?>

<div class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-3.png'); ?>" alt="<?php bloginfo('name'); ?>" class="login-logo-img">
        </div>

        <?php if ($reset_success): ?>
            <!-- Success Message -->
            <h1 class="login-title">Password Reset Successful!</h1>
            <p class="login-subtitle">Your password has been successfully reset.</p>

            <div class="login-message" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 16px; margin-bottom: 24px; color: #065F46; font-size: 14px; text-align: center;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-right: 8px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <strong>Success!</strong> You can now login with your new password.
            </div>

            <a href="<?php echo esc_url(get_permalink(get_page_by_path('login'))); ?>" class="login-button" style="display: block; text-align: center; text-decoration: none;">
                Continue to Login
            </a>

        <?php elseif ($show_form): ?>
            <!-- Reset Password Form -->
            <h1 class="login-title">Create New Password</h1>
            <p class="login-subtitle">Enter your new password below.</p>

            <?php if (!empty($reset_error)): ?>
                <div class="login-error">
                    <?php echo esc_html($reset_error); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="post" action="">
                <?php wp_nonce_field('aakaari_reset_password_' . $user->ID, 'aakaari_new_password_nonce'); ?>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required minlength="8">
                    </div>
                    <small style="color: #6B7280; font-size: 12px; display: block; margin-top: 4px;">
                        Password must be at least 8 characters long
                    </small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required minlength="8">
                    </div>
                </div>

                <button type="submit" class="login-button">Reset Password</button>
            </form>

        <?php else: ?>
            <!-- Error Message -->
            <h1 class="login-title">Password Reset Failed</h1>

            <div class="login-error">
                <?php echo esc_html($reset_error); ?>
            </div>

            <div style="margin-top: 24px; text-align: center;">
                <a href="<?php echo esc_url(get_permalink(get_page_by_path('forgot-password'))); ?>" class="login-button" style="display: inline-block; text-decoration: none;">
                    Request New Reset Link
                </a>
            </div>
        <?php endif; ?>

        <div class="register-link" style="margin-top: 20px;">
            Remember your password? <a href="<?php echo esc_url(get_permalink(get_page_by_path('login'))); ?>">Login</a>
        </div>
    </div>
</div>

<?php get_footer('minimal'); ?>
