<?php
/**
 * Optimized Mobile Menu Template
 * Lightweight and matches desktop header exactly
 */

defined('ABSPATH') || exit;

// Get user data
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;
$user_name = $is_logged_in ? $current_user->display_name : '';

// Get cart data
$cart_count = 0;
$cart_url = '#';
if (class_exists('WooCommerce')) {
    $cart_count = WC()->cart->get_cart_contents_count();
    $cart_url = wc_get_cart_url();
}

// Get reseller link (matches desktop exactly)
$reseller_page_id = get_option('reseller_page_id');
$reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
$login_link = home_url('/login/');
$final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
?>

<!-- Mobile Menu Backdrop -->
<div class="mobile-menu-backdrop" id="mobile-menu-backdrop"></div>

<!-- Mobile Menu Panel -->
<nav class="mobile-menu" id="mobile-menu" aria-label="Mobile navigation">
    <div class="mobile-menu__container">

        <!-- Close Button -->
        <button class="mobile-menu__close" id="mobile-menu-close" aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Scrollable Content -->
        <div class="mobile-menu__content">

            <!-- User Info (if logged in) -->
            <?php if ($is_logged_in): ?>
            <div class="mobile-menu__user">
                <div class="mobile-menu__avatar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span class="mobile-menu__username"><?php echo esc_html($user_name); ?></span>
            </div>
            <?php endif; ?>

            <!-- Main Navigation (matches desktop exactly) -->
            <div class="mobile-menu__nav">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Home</span>
                </a>

                <a href="<?php echo esc_url(home_url('/products/')); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m7.5 4.27 9 5.15"></path>
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                        <path d="m3.3 7 8.7 5 8.7-5"></path>
                        <path d="M12 22V12"></path>
                    </svg>
                    <span>Products</span>
                </a>

                <a href="<?php echo esc_url(home_url('/how-it-works/')); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 16v-4"></path>
                        <path d="M12 8h.01"></path>
                    </svg>
                    <span>How It Works</span>
                </a>

                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                    </svg>
                    <span>Contact</span>
                </a>

                <?php if ($is_logged_in): ?>
                <!-- Logged In User Links -->
                <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                        <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                        <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                        <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                    </svg>
                    <span><?php echo current_user_can('manage_options') ? 'Admin Dashboard' : 'Dashboard'; ?></span>
                </a>

                <a href="<?php echo esc_url($cart_url); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="8" cy="21" r="1"></circle>
                        <circle cx="19" cy="21" r="1"></circle>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                    </svg>
                    <span>Cart</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="mobile-menu__badge"><?php echo esc_html($cart_count); ?></span>
                    <?php endif; ?>
                </a>

                <?php else: ?>
                <!-- Not Logged In Links -->
                <a href="<?php echo esc_url($login_link); ?>" class="mobile-menu__link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" x2="3" y1="12" y2="12"></line>
                    </svg>
                    <span>Reseller Login</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Bottom Actions -->
            <div class="mobile-menu__actions">
                <?php if ($is_logged_in): ?>
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="mobile-menu__btn mobile-menu__btn--logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" x2="9" y1="12" y2="12"></line>
                    </svg>
                    Logout
                </a>
                <?php else: ?>
                <a href="<?php echo esc_url($final_reseller_href); ?>" class="mobile-menu__btn mobile-menu__btn--primary">
                    Become a Reseller
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</nav>
