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
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="menu-item">
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
                    <a href="<?php echo esc_url(home_url('/faq/')); ?>" class="menu-item">
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