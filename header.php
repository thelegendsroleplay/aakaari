<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    // Set site icon (favicon) to logo.png
    $favicon_url = get_template_directory_uri() . '/assets/img/logo.png';
    $favicon_path = get_template_directory() . '/assets/img/logo.png';
    if (file_exists($favicon_path)): ?>
        <link rel="icon" type="image/png" href="<?php echo esc_url($favicon_url); ?>">
        <link rel="apple-touch-icon" href="<?php echo esc_url($favicon_url); ?>">
    <?php endif; ?>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="aakaari-header">
    <div class="container">
        <div class="site-branding">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                <?php 
                $logo_url = get_template_directory_uri() . '/assets/img/logo.png';
                $logo_path = get_template_directory() . '/assets/img/logo.png';
                if (file_exists($logo_path)): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>" class="logo-img">
                <?php else: ?>
                    <span style="color: #2563EB; font-weight: 700; font-size: 20px;"><?php bloginfo('name'); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <nav id="site-navigation" class="main-navigation">
            <!-- Mobile Menu Header -->
            <div class="mobile-menu-header">
                <div class="mobile-menu-logo">
                    <?php
                    $logo_url = get_template_directory_uri() . '/assets/img/logo.png';
                    $logo_path = get_template_directory() . '/assets/img/logo.png';
                    if (file_exists($logo_path)): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>">
                    <?php else: ?>
                        <span class="mobile-menu-site-name"><?php bloginfo('name'); ?></span>
                    <?php endif; ?>
                </div>
                <button class="mobile-menu-close" aria-label="Close menu" onclick="document.getElementById('mobile-menu-toggle').click();">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu Content Container -->
            <div class="mobile-menu-content">
                <!-- Search Bar -->
                <div class="mobile-menu-search">
                    <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <div class="search-input-wrapper">
                            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input type="search" class="search-field" placeholder="Search products..." value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
                        </div>
                    </form>
                </div>

                <?php if (is_user_logged_in()): ?>
                    <!-- User Profile Section -->
                    <div class="mobile-menu-profile">
                        <div class="profile-avatar">
                            <?php echo get_avatar(get_current_user_id(), 48); ?>
                        </div>
                        <div class="profile-info">
                            <span class="profile-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                            <span class="profile-email"><?php echo esc_html(wp_get_current_user()->user_email); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Main Navigation Menu -->
                <div class="mobile-menu-section">
                    <h3 class="mobile-menu-section-title">Menu</h3>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_id' => 'primary-menu',
                        'container' => false,
                        'menu_class' => 'nav-menu',
                        'fallback_cb' => function() {
                            echo '<ul class="nav-menu">';
                            echo '<li><a href="' . esc_url(home_url('/')) . '"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg><span>Home</span></a></li>';
                            echo '<li><a href="' . esc_url(home_url('/products/')) . '"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg><span>Products</span></a></li>';
                            echo '<li><a href="' . esc_url(home_url('/how-it-works/')) . '"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg><span>How It Works</span></a></li>';
                            echo '<li><a href="' . esc_url(home_url('/contact/')) . '"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg><span>Contact</span></a></li>';
                            echo '</ul>';
                        }
                    ));
                    ?>
                </div>

                <!-- Quick Actions -->
                <?php if (is_user_logged_in()): ?>
                    <div class="mobile-menu-section">
                        <h3 class="mobile-menu-section-title">Quick Actions</h3>
                        <ul class="quick-actions-menu">
                            <li>
                                <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="3" y1="9" x2="21" y2="9"></line>
                                        <line x1="9" y1="21" x2="9" y2="9"></line>
                                    </svg>
                                    <span><?php echo current_user_can('manage_options') ? 'Admin Dashboard' : 'Dashboard'; ?></span>
                                </a>
                            </li>
                            <?php if (class_exists('WooCommerce')): ?>
                                <li>
                                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                            <line x1="8" y1="6" x2="21" y2="6"></line>
                                            <line x1="8" y1="12" x2="21" y2="12"></line>
                                            <line x1="8" y1="18" x2="21" y2="18"></line>
                                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                        </svg>
                                        <span>My Orders</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Support Section -->
                <div class="mobile-menu-section">
                    <h3 class="mobile-menu-section-title">Help & Support</h3>
                    <ul class="support-menu">
                        <li>
                            <a href="<?php echo esc_url(home_url('/faq/')); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <span>FAQ</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url(home_url('/contact/')); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <span>Contact Support</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Mobile Menu Footer -->
            <div class="mobile-menu-footer">
                <?php if (class_exists('WooCommerce')): ?>
                    <?php
                    $cart_url = wc_get_cart_url();
                    $cart_count = WC()->cart->get_cart_contents_count();
                    $cart_total = WC()->cart->get_cart_total();
                    ?>
                    <a href="<?php echo esc_url($cart_url); ?>" class="mobile-cart-button">
                        <div class="cart-button-content">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <div class="cart-info">
                                <span class="cart-label">Shopping Cart</span>
                                <span class="cart-total"><?php echo $cart_total; ?></span>
                            </div>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-count-badge"><?php echo esc_html($cart_count); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endif; ?>

                <div class="mobile-menu-footer-actions">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="mobile-logout-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="mobile-login-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 15 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                            Reseller Login
                        </a>
                        <?php
                        $reseller_page_id = get_option('reseller_page_id');
                        $reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
                        $login_link = home_url('/login/');
                        $final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
                        ?>
                        <a href="<?php echo esc_url($final_reseller_href); ?>" class="mobile-reseller-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Become a Reseller
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <button id="mobile-menu-toggle" class="mobile-menu-toggle" aria-controls="site-navigation" aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="sr-only">Menu</span>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </div>
    <script src="https://cdn.tailwindcss.com"></script>
</header>

<?php wp_footer(); ?>
</body>
</html>