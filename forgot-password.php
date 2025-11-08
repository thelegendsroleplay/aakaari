<?php
/**
 * Template Name: Forgot Password
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

$reset_message = '';
$reset_error = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aakaari_reset_nonce'])) {
    if (wp_verify_nonce($_POST['aakaari_reset_nonce'], 'aakaari_password_reset')) {
        $user_login = sanitize_text_field($_POST['user_login']);

        // Retrieve user by email or username
        if (is_email($user_login)) {
            $user_data = get_user_by('email', $user_login);
        } else {
            $user_data = get_user_by('login', $user_login);
        }

        if (!$user_data) {
            $reset_error = 'No account found with that email address or username.';
        } else {
            // Generate password reset key
            $key = get_password_reset_key($user_data);

            if (is_wp_error($key)) {
                $reset_error = $key->get_error_message();
            } else {
                // Send password reset email with custom branded template
                $reset_page = get_page_by_path('reset-password');

                // If reset-password page doesn't exist, create it automatically
                if (!$reset_page) {
                    $reset_page_id = wp_insert_post(array(
                        'post_title'   => 'Reset Password',
                        'post_name'    => 'reset-password',
                        'post_status'  => 'publish',
                        'post_type'    => 'page',
                        'post_content' => '',
                        'page_template' => 'reset-password.php'
                    ));

                    if ($reset_page_id && !is_wp_error($reset_page_id)) {
                        update_option('aakaari_reset_password_page_id', $reset_page_id);
                        update_post_meta($reset_page_id, '_wp_page_template', 'reset-password.php');
                        $reset_page = get_post($reset_page_id);
                        // Flush rewrite rules so the new page URL works immediately
                        flush_rewrite_rules();
                    }
                }

                $reset_link = add_query_arg(
                    array(
                        'key' => $key,
                        'login' => rawurlencode($user_data->user_login)
                    ),
                    get_permalink($reset_page)
                );

                $subject = 'Password Reset Request - Aakaari';

                // Use custom branded HTML email template
                if (function_exists('aakaari_send_password_reset_email')) {
                    $sent = aakaari_send_password_reset_email($user_data->user_email, $user_data->display_name, $reset_link);
                } else {
                    // Fallback to simple text email if function doesn't exist
                    $message = "Hello " . $user_data->display_name . ",\n\n";
                    $message .= "You requested a password reset for your Aakaari account.\n\n";
                    $message .= "Click the link below to reset your password:\n";
                    $message .= $reset_link . "\n\n";
                    $message .= "This link will expire in 24 hours.\n\n";
                    $message .= "If you didn't request this, please ignore this email.\n\n";
                    $message .= "Thank you,\nAakaari Team";

                    $sent = wp_mail($user_data->user_email, $subject, $message);
                }

                if ($sent) {
                    $reset_message = 'Password reset instructions have been sent to your email address.';
                } else {
                    $reset_error = 'Failed to send reset email. Please try again later.';
                }
            }
        }
    }
}

get_header('minimal');
?>

<div class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-3.png'); ?>" alt="<?php bloginfo('name'); ?>" class="login-logo-img">
        </div>

        <h1 class="login-title">Reset Password</h1>
        <p class="login-subtitle">Enter your email address and we'll send you instructions to reset your password.</p>

        <?php if (!empty($reset_error)): ?>
            <div class="login-error">
                <?php echo esc_html($reset_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($reset_message)): ?>
            <div class="login-message" style="background-color: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 6px; padding: 12px; margin-bottom: 20px; color: #065F46; font-size: 14px; text-align: center;">
                <?php echo esc_html($reset_message); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="post" action="">
            <?php wp_nonce_field('aakaari_password_reset', 'aakaari_reset_nonce'); ?>

            <div class="form-group">
                <label for="user_login">Email Address or Username</label>
                <div class="input-wrapper">
                    <span class="input-icon"></span>
                    <input type="text" id="user_login" name="user_login" placeholder="Enter your email or username" required>
                </div>
            </div>

            <button type="submit" class="login-button">Send Reset Link</button>
        </form>

        <div class="register-link" style="margin-top: 20px;">
            Remember your password? <a href="<?php echo esc_url(get_permalink(get_page_by_path('login'))); ?>">Login</a>
        </div>
    </div>
</div>

<?php get_footer('minimal'); ?>
