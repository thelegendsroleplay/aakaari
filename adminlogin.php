<?php
/**
 * Template Name: Admin Login
 * 
 * Custom admin login template for Aakaari
 */

// Redirect if already logged in and is admin
if (is_user_logged_in() && current_user_can('manage_options')) {
    $dashboard_page_id = get_option('aakaari_dashboard_page_id');
    $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : admin_url('admin.php?page=aakaari-admin-dashboard');
    wp_redirect($dashboard_url);
    exit;
}

get_header('minimal'); // Use a minimal header or create one
?>

<div class="aakaari-admin-login-container">
    <div class="aakaari-admin-login-card">
        <div class="aakaari-admin-login-header">
            <div class="aakaari-admin-login-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shield-icon">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
            </div>
            <h1>Admin Access</h1>
            <p>Restricted area - Admin credentials required</p>
        </div>

        <div class="aakaari-admin-login-content">
            <div id="aakaari-admin-login-alert" class="aakaari-admin-login-alert" style="display: none;">
                <!-- Error messages will be shown here -->
            </div>

            <form id="aakaari-admin-login-form">
                <?php wp_nonce_field('aakaari_admin_login', 'aakaari_admin_login_nonce'); ?>
                
                <div class="aakaari-form-group">
                    <label for="admin_email">Admin Email or Username</label>
                    <div class="aakaari-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mail-icon">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input id="admin_email" name="admin_email" type="text" placeholder="admin@aakaari.com" required>
                    </div>
                </div>

                <div class="aakaari-form-group">
                    <label for="admin_password">Password</label>
                    <div class="aakaari-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lock-icon">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input id="admin_password" name="admin_password" type="password" placeholder="Enter admin password" required>
                    </div>
                </div>

                <button type="submit" class="aakaari-admin-login-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shield-icon-small">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Access Admin Panel
                </button>

                <div class="aakaari-admin-login-footer">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="aakaari-admin-login-back">
                        ← Back to Homepage
                    </a>
                </div>
            </form>

            <div class="aakaari-admin-login-demo">
                <div class="aakaari-admin-login-demo-content">
                    <p class="aakaari-admin-login-demo-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shield-icon-small">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                        Admin Access:
                    </p>
                    <p class="aakaari-admin-login-demo-text">Use your WordPress admin credentials</p>
                    <p class="aakaari-admin-login-demo-text">Only administrators can login here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer('minimal'); ?>