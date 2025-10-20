<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="aakaari-header">
    <div class="container">
        <!-- Logo -->
        <div class="site-branding">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#2563EB" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741zM1 11.105l4.708-2.897L1 5.383v5.722z"/>
                </svg>
                <span>Aakaari</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav id="site-navigation" class="main-navigation">
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
                    echo '<li><a href="' . esc_url(home_url('/pricing/')) . '">Pricing</a></li>';
                    echo '<li><a href="' . esc_url(home_url('/contact/')) . '">Contact</a></li>';
                    echo '</ul>';
                }
            ));
            ?>

            <!-- Auth buttons (inside navigation for mobile menu) -->
            <div class="auth-buttons">
                <a href="<?php echo esc_url(home_url('/login/')); ?>" class="login-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 3.5a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 1 1 0v2A1.5 1.5 0 0 1 9.5 14h-8A1.5 1.5 0 0 1 0 12.5v-9A1.5 1.5 0 0 1 1.5 2h8A1.5 1.5 0 0 1 11 3.5v2a.5.5 0 0 1-1 0v-2z"/>
                        <path fill-rule="evenodd" d="M4.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H14.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3z"/>
                    </svg>
                    <span>Reseller Login</span>
                </a>
                <?php
$reseller_page_id = get_option('reseller_page_id');
$reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/'); // Fallback
$login_link = home_url('/login/');
$dashboard_link = home_url('/dashboard/');

if (is_user_logged_in()) {
    // User is logged in, send them to the dashboard.
    $final_reseller_href = $dashboard_link;
} else {
    // User is not logged in. Send them to the login page.
    // We'll pass a redirect so they land on the application page after login.
    $final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
}
?>
<a href="<?php echo esc_url($final_reseller_href); ?>" class="btn-reseller-header">Become a Reseller</a>
            </div>
        </nav>

        <!-- Mobile menu toggle button -->
        <button id="mobile-menu-toggle" class="mobile-menu-toggle" aria-controls="site-navigation" aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="sr-only">Menu</span>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </button>
    </div>
</header>

<?php wp_footer(); ?>
</body>
</html>