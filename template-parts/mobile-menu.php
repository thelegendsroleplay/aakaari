<?php
/**
 * Mobile Menu Template
 * Converted from Figma design (React/TypeScript) to WordPress/PHP
 */

defined('ABSPATH') || exit;

// Get current user information
$is_logged_in = is_user_logged_in();
$user_name = '';
$user_role = null;

if ($is_logged_in) {
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;

    // Determine user role
    if (in_array('administrator', $current_user->roles)) {
        $user_role = 'admin';
    } elseif (get_user_meta($current_user->ID, 'reseller_approved', true) == '1') {
        $user_role = 'reseller';
    }
}

// Get cart count
$cart_count = 0;
if (class_exists('WooCommerce')) {
    $cart_count = WC()->cart->get_cart_contents_count();
}

// Get current page
$current_page_slug = '';
if (is_front_page()) {
    $current_page_slug = 'home';
} elseif (is_page()) {
    $current_page_slug = get_post_field('post_name', get_queried_object_id());
} elseif (is_shop() || is_product()) {
    $current_page_slug = 'products';
}
?>

<!-- Mobile Menu Backdrop -->
<div class="mobile-menu-backdrop" id="mobile-menu-backdrop"></div>

<!-- Mobile Menu Panel -->
<div class="mobile-menu" id="mobile-menu">
    <div class="mobile-menu__container">
        <!-- Close Button -->
        <button class="mobile-menu__close-btn" id="mobile-menu-close" aria-label="Close menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Header Section -->
        <div class="mobile-menu-header">
            <!-- Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="mobile-menu-header__logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-header__logo-icon">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" x2="21" y1="6" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span class="mobile-menu-header__logo-text">Aakaari</span>
            </a>

            <!-- User Info (if logged in) -->
            <?php if ($is_logged_in): ?>
            <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu-header__user">
                <div class="mobile-menu-header__avatar">
                    <?php if ($user_role === 'admin'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"></path>
                        </svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="mobile-menu-header__user-info">
                    <span class="mobile-menu-header__user-name"><?php echo esc_html($user_name); ?></span>
                    <span class="mobile-menu-header__user-role"><?php echo $user_role === 'admin' ? 'Admin' : 'Reseller'; ?></span>
                </div>
            </a>
            <?php endif; ?>
        </div>

        <!-- Scrollable Content -->
        <div class="mobile-menu__content">
            <!-- Quick Actions (for logged-in users) -->
            <?php if ($is_logged_in): ?>
            <div class="mobile-menu-actions">
                <div class="mobile-menu-actions__grid">
                    <!-- Cart -->
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="mobile-menu-actions__item">
                        <div class="mobile-menu-actions__icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-actions__icon">
                                <circle cx="8" cy="21" r="1"></circle>
                                <circle cx="19" cy="21" r="1"></circle>
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                            </svg>
                            <?php if ($cart_count > 0): ?>
                                <span class="mobile-menu-actions__badge"><?php echo esc_html($cart_count); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="mobile-menu-actions__label">Cart</span>
                    </a>

                    <?php if ($user_role === 'reseller'): ?>
                    <!-- Orders -->
                    <a href="<?php echo esc_url(home_url('/track-order/')); ?>" class="mobile-menu-actions__item">
                        <div class="mobile-menu-actions__icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-actions__icon">
                                <path d="m16 16 2 2 4-4"></path>
                                <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                            </svg>
                        </div>
                        <span class="mobile-menu-actions__label">Orders</span>
                    </a>

                    <!-- Wallet -->
                    <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu-actions__item">
                        <div class="mobile-menu-actions__icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-actions__icon">
                                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                                <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                                <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                            </svg>
                        </div>
                        <span class="mobile-menu-actions__label">Wallet</span>
                    </a>

                    <!-- Earnings -->
                    <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu-actions__item">
                        <div class="mobile-menu-actions__icon-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-actions__icon">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>
                        </div>
                        <span class="mobile-menu-actions__label">Earnings</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Navigation -->
            <nav class="mobile-menu-nav">
                <!-- Main Navigation Section -->
                <div class="mobile-menu-nav__section">
                    <div class="mobile-menu-nav__list">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="mobile-menu-nav__item <?php echo $current_page_slug === 'home' ? 'mobile-menu-nav__item--active' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon">
                                <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            <span class="mobile-menu-nav__label">Home</span>
                        </a>

                        <a href="<?php echo esc_url(home_url('/products/')); ?>" class="mobile-menu-nav__item <?php echo $current_page_slug === 'products' ? 'mobile-menu-nav__item--active' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon">
                                <path d="m7.5 4.27 9 5.15"></path>
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                                <path d="m3.3 7 8.7 5 8.7-5"></path>
                                <path d="M12 22V12"></path>
                            </svg>
                            <span class="mobile-menu-nav__label">Products</span>
                        </a>

                        <a href="<?php echo esc_url(home_url('/custom-products/')); ?>" class="mobile-menu-nav__item <?php echo $current_page_slug === 'custom-products' ? 'mobile-menu-nav__item--active' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon">
                                <circle cx="13.5" cy="6.5" r=".5"></circle>
                                <circle cx="17.5" cy="10.5" r=".5"></circle>
                                <circle cx="8.5" cy="7.5" r=".5"></circle>
                                <circle cx="6.5" cy="12.5" r=".5"></circle>
                                <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"></path>
                            </svg>
                            <span class="mobile-menu-nav__label">Custom Design</span>
                        </a>

                        <a href="<?php echo esc_url(home_url('/how-it-works/')); ?>" class="mobile-menu-nav__item <?php echo $current_page_slug === 'how-it-works' ? 'mobile-menu-nav__item--active' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <span class="mobile-menu-nav__label">How It Works</span>
                        </a>

                        <a href="<?php echo esc_url(home_url('/pricing/')); ?>" class="mobile-menu-nav__item <?php echo $current_page_slug === 'pricing' ? 'mobile-menu-nav__item--active' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon">
                                <line x1="12" x2="12" y1="2" y2="22"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            <span class="mobile-menu-nav__label">Pricing</span>
                        </a>

                        <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="mobile-menu-nav__item <?php echo $current_page_slug === 'contact' ? 'mobile-menu-nav__item--active' : ''; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            <span class="mobile-menu-nav__label">Contact</span>
                        </a>
                    </div>
                </div>

                <!-- Reseller Tools Section -->
                <?php if ($is_logged_in && $user_role === 'reseller'): ?>
                <div class="mobile-menu-nav__section">
                    <button class="mobile-menu-nav__section-header" data-section="reseller">
                        <span>Reseller Tools</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__chevron">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="mobile-menu-nav__collapsible">
                        <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                                <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                                <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                                <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                        <a href="<?php echo esc_url(home_url('/track-order/')); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <path d="m16 16 2 2 4-4"></path>
                                <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                            </svg>
                            <span>My Orders</span>
                        </a>
                        <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                                <polyline points="16 7 22 7 22 13"></polyline>
                            </svg>
                            <span>Analytics</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Admin Tools Section -->
                <?php if ($is_logged_in && $user_role === 'admin'): ?>
                <div class="mobile-menu-nav__section">
                    <button class="mobile-menu-nav__section-header" data-section="admin">
                        <span>Admin Tools</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__chevron">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="mobile-menu-nav__collapsible">
                        <a href="<?php echo esc_url(admin_url()); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <rect width="7" height="9" x="3" y="3" rx="1"></rect>
                                <rect width="7" height="5" x="14" y="3" rx="1"></rect>
                                <rect width="7" height="9" x="14" y="12" rx="1"></rect>
                                <rect width="7" height="5" x="3" y="16" rx="1"></rect>
                            </svg>
                            <span>Admin Dashboard</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <path d="m7.5 4.27 9 5.15"></path>
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                            </svg>
                            <span>Manage Products</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('users.php')); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span>User Management</span>
                        </a>
                        <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" class="mobile-menu-nav__subitem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-nav__icon mobile-menu-nav__icon--small">
                                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>

            <!-- CTA Section (for non-logged-in users) -->
            <?php if (!$is_logged_in): ?>
            <div class="mobile-menu__cta">
                <?php
                $reseller_page_id = get_option('reseller_page_id');
                $reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
                $login_link = home_url('/login/');
                $final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
                ?>
                <a href="<?php echo esc_url($final_reseller_href); ?>" class="mobile-menu__cta-btn mobile-menu__cta-btn--primary">
                    Become a Reseller
                </a>
                <a href="<?php echo esc_url($login_link); ?>" class="mobile-menu__cta-btn mobile-menu__cta-btn--secondary">
                    Login
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="mobile-menu-footer">
            <!-- Quick Links -->
            <div class="mobile-menu-footer__links">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="mobile-menu-footer__link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-footer__icon">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                        <path d="M12 17h.01"></path>
                    </svg>
                    <span>Help & Support</span>
                </a>
                <?php if ($is_logged_in): ?>
                <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="mobile-menu-footer__link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-footer__icon">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/how-it-works/')); ?>" class="mobile-menu-footer__link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-footer__icon">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>Terms & Privacy</span>
                </a>
            </div>

            <!-- Logout Button -->
            <?php if ($is_logged_in): ?>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="mobile-menu-footer__logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-footer__icon">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" x2="9" y1="12" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
            <?php endif; ?>

            <!-- Version Info -->
            <div class="mobile-menu-footer__info">
                <p>Aakaari Platform v1.0.0</p>
                <p>&copy; <?php echo date('Y'); ?> All rights reserved</p>
            </div>
        </div>
    </div>
</div>
