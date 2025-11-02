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
            <!-- Desktop Navigation Menu -->
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_id' => 'primary-menu',
                'container' => false,
                'menu_class' => 'nav-menu',
                'fallback_cb' => function() {
                    echo '<ul class="nav-menu">';
                    echo '<li><a href="' . esc_url(home_url('/')) . '">Home</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/products/')) . '">Products</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/how-it-works/')) . '">How It Works</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/contact/')) . '">Contact</a></li>';
                    echo '</ul>';
                }
            ));
            ?>

            <!-- Desktop Auth Buttons -->
            <div class="auth-buttons">
                <?php if (class_exists('WooCommerce')): ?>
                    <?php
                    $cart_url = wc_get_cart_url();
                    $cart_count = WC()->cart->get_cart_contents_count();
                    ?>
                    <a href="<?php echo esc_url($cart_url); ?>" class="login-btn cart-toggle" title="View your shopping cart">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z"/>
                        </svg>
                        <span>Cart</span>
                        <?php if ($cart_count > 0): ?>
                            <span class="ml-1 bg-blue-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo esc_html($cart_count); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="login-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5V6H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V7H6a.5.5 0 0 1 0-1h1.5V4.5A.5.5 0 0 1 8 4z"/>
                            <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                            <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                        </svg>
                        <span><?php echo current_user_can('manage_options') ? 'Admin Dashboard' : 'Dashboard'; ?></span>
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="btn-reseller-header btn-logout">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0v2z"/>
                            <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3z"/>
                        </svg>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="login-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M10 3.5a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 1 1 0v2A1.5 1.5 0 0 1 9.5 14h-8A1.5 1.5 0 0 1 0 12.5v-9A1.5 1.5 0 0 1 1.5 2h8A1.5 1.5 0 0 1 11 3.5v2a.5.5 0 0 1-1 0v-2z"/>
                            <path fill-rule="evenodd" d="M4.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H14.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3z"/>
                        </svg>
                        <span>Reseller Login</span>
                    </a>
                    <?php
                    $reseller_page_id = get_option('reseller_page_id');
                    $reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
                    $login_link = home_url('/login/');
                    $final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
                    ?>
                    <a href="<?php echo esc_url($final_reseller_href); ?>" class="btn-reseller-header">Become a Reseller</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Sidebar (Only visible on mobile) -->
            <div class="sidebar-container">
                <!-- Close Button -->
                <button class="mobile-menu-close" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>

                <!-- Search Bar -->
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="search" placeholder="Search products..." value="<?php echo get_search_query(); ?>" name="s">
                </div>

                <?php if (is_user_logged_in()): ?>
                    <!-- User Profile -->
                    <div class="user-profile">
                        <div class="profile-info">
                            <div class="avatar-bg">
                                <?php echo get_avatar(get_current_user_id(), 40, '', '', array('class' => 'avatar')); ?>
                            </div>
                            <div class="user-details">
                                <span class="username"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                                <span class="email"><?php echo esc_html(wp_get_current_user()->user_email); ?></span>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right"></i>
                    </div>
                <?php endif; ?>

                <!-- Menu Section -->
                <div class="menu-section">
                    <div class="section-title">MENU</div>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="menu-item">
                        <i class="fas fa-home menu-icon"></i>
                        <span>Home</span>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </a>
                    <a href="<?php echo esc_url(home_url('/products/')); ?>" class="menu-item products-item">
                        <i class="fas fa-box menu-icon"></i>
                        <span>Products</span>
                        <span class="new-tag">New</span>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </a>
                    <a href="<?php echo esc_url(home_url('/how-it-works/')); ?>" class="menu-item">
                        <i class="fas fa-question-circle menu-icon"></i>
                        <span>How It Works</span>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="menu-item">
                        <i class="fas fa-comment-dots menu-icon"></i>
                        <span>Contact</span>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </a>
                </div>

                <?php if (is_user_logged_in()): ?>
                    <!-- Quick Actions -->
                    <div class="menu-section">
                        <div class="section-title">QUICK ACTIONS</div>
                        <a href="<?php echo esc_url(aakaari_get_dashboard_url()); ?>" class="menu-item">
                            <i class="fas fa-th-large menu-icon purple-icon"></i>
                            <span><?php echo current_user_can('manage_options') ? 'Admin Dashboard' : 'Dashboard'; ?></span>
                            <i class="fas fa-chevron-right arrow-icon"></i>
                        </a>
                        <?php if (class_exists('WooCommerce')): ?>
                            <?php
                            $order_count = wc_get_customer_order_count(get_current_user_id());
                            ?>
                            <a href="<?php echo esc_url(home_url('/reseller-dashboard/#orders')); ?>" class="menu-item">
                                <i class="fas fa-receipt menu-icon purple-icon"></i>
                                <span>My Orders</span>
                                <?php if ($order_count > 0): ?>
                                    <span class="badge"><?php echo esc_html($order_count); ?></span>
                                <?php endif; ?>
                                <i class="fas fa-chevron-right arrow-icon"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Help & Support -->
                <div class="menu-section">
                    <div class="section-title">HELP & SUPPORT</div>
                    <a href="<?php echo esc_url(home_url('/how-it-works/')); ?>" class="menu-item">
                        <i class="fas fa-clipboard-list menu-icon orange-icon"></i>
                        <span>FAQ</span>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="menu-item">
                        <i class="fas fa-question menu-icon orange-icon"></i>
                        <span>Support Center</span>
                        <i class="fas fa-chevron-right arrow-icon"></i>
                    </a>
                </div>

                <!-- Bottom Actions -->
                <div class="bottom-actions">
                    <?php if (class_exists('WooCommerce')): ?>
                        <?php
                        $cart_url = wc_get_cart_url();
                        $cart_total = WC()->cart->get_cart_total();
                        ?>
                        <a href="<?php echo esc_url($cart_url); ?>" class="shopping-cart-btn">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Shopping Cart</span>
                            <span class="cart-amount"><?php echo $cart_total; ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="logout-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Reseller Login</span>
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