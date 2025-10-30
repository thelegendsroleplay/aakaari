<?php
/**
 * Automatic Page Creation on Theme Activation
 * Creates all required pages automatically when theme is activated or pages are missing
 *
 * @package Aakaari
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get page definitions
 */
function aakaari_get_page_definitions() {
    // Define all pages needed by the theme
    $pages = array(
        // Home page (must be first for front page setting)
        'home' => array(
            'title' => 'Home',
            'content' => '',
            'template' => 'page-home.php',
            'option' => 'aakaari_home_page_id'
        ),

        // WooCommerce pages
        'shop' => array(
            'title' => 'Shop',
            'content' => '<!-- wp:shortcode -->[products limit="12" columns="4" orderby="date" order="DESC"]<!-- /wp:shortcode -->',
            'template' => '',
            'option' => 'woocommerce_shop_page_id'
        ),
        'cart' => array(
            'title' => 'Cart',
            'content' => '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
            'template' => 'template-cart.php',
            'option' => 'woocommerce_cart_page_id'
        ),
        'checkout' => array(
            'title' => 'Checkout',
            'content' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
            'template' => 'template-checkout.php',
            'option' => 'woocommerce_checkout_page_id'
        ),
        'my-account' => array(
            'title' => 'My Account',
            'content' => '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->',
            'template' => '',
            'option' => 'woocommerce_myaccount_page_id'
        ),

        // Custom theme pages
        'login' => array(
            'title' => 'Login',
            'content' => '',
            'template' => 'login.php',
            'option' => 'aakaari_login_page_id'
        ),
        'register' => array(
            'title' => 'Register',
            'content' => '',
            'template' => 'register.php',
            'option' => 'aakaari_register_page_id'
        ),
        'become-a-reseller' => array(
            'title' => 'Become a Reseller',
            'content' => '<!-- wp:shortcode -->[become_a_reseller]<!-- /wp:shortcode -->',
            'template' => 'become-a-reseller.php',
            'option' => 'aakaari_reseller_page_id'
        ),
        'application-pending' => array(
            'title' => 'Application Pending',
            'content' => '<p>Your reseller application is being reviewed. We will notify you once it has been approved.</p>',
            'template' => 'application-pending.php',
            'option' => 'aakaari_pending_page_id'
        ),
        'reseller-dashboard' => array(
            'title' => 'Reseller Dashboard',
            'content' => '',
            'template' => 'dashboard.php',
            'option' => 'aakaari_dashboard_page_id'
        ),
        'admin-dashboard' => array(
            'title' => 'Admin Dashboard',
            'content' => '',
            'template' => 'admindashboard.php',
            'option' => 'aakaari_admin_dashboard_page_id'
        ),
        'track-order' => array(
            'title' => 'Track Order',
            'content' => '',
            'template' => 'page-track-order.php',
            'option' => 'aakaari_track_order_page_id'
        ),
        'contact' => array(
            'title' => 'Contact',
            'content' => '<p>Get in touch with us for any questions or support.</p>',
            'template' => 'contact.php',
            'option' => 'aakaari_contact_page_id'
        ),
        'how-it-works' => array(
            'title' => 'How It Works',
            'content' => '<p>Learn how our custom product design system works.</p>',
            'template' => 'how-it-works.php',
            'option' => 'aakaari_how_it_works_page_id'
        ),
        'pricing' => array(
            'title' => 'Pricing',
            'content' => '<p>View our pricing plans and packages.</p>',
            'template' => 'pricing.php',
            'option' => 'aakaari_pricing_page_id'
        ),
    );

    return $pages;
}

/**
 * Update template for existing pages
 */
function aakaari_update_page_templates() {
    $pages = aakaari_get_page_definitions();
    $updated = 0;

    foreach ($pages as $slug => $page) {
        if (empty($page['template'])) {
            continue;
        }

        $page_id = get_option($page['option']);
        if (!$page_id) {
            continue;
        }

        $existing_page = get_post($page_id);
        if (!$existing_page || $existing_page->post_status === 'trash') {
            continue;
        }

        // Check if template file exists
        $template_file = get_template_directory() . '/' . $page['template'];
        if (!file_exists($template_file)) {
            error_log("Aakaari: Template file not found: {$template_file}");
            continue;
        }

        // Get current template
        $current_template = get_post_meta($page_id, '_wp_page_template', true);

        // Update if template is not set or different
        if ($current_template !== $page['template']) {
            update_post_meta($page_id, '_wp_page_template', $page['template']);
            $updated++;
            error_log("Aakaari: Updated template for '{$page['title']}' to '{$page['template']}'");
        }
    }

    return $updated;
}

/**
 * Create all required pages for the theme
 */
function aakaari_create_required_pages() {
    $pages = aakaari_get_page_definitions();
    $created_pages = array();
    $updated_pages = array();

    foreach ($pages as $slug => $page) {
        // Check if page already exists
        $page_id = get_option($page['option']);
        $page_exists = false;

        if ($page_id) {
            $existing_page = get_post($page_id);
            if ($existing_page && $existing_page->post_status !== 'trash') {
                $page_exists = true;
            }
        }

        if (!$page_exists) {
            // Check if a page with this slug already exists (maybe created manually)
            $existing = get_page_by_path($slug);

            if ($existing && $existing->post_status !== 'trash') {
                // Page exists with correct slug, just update the option
                update_option($page['option'], $existing->ID);

                // Update template if specified and file exists
                if (!empty($page['template'])) {
                    $template_file = get_template_directory() . '/' . $page['template'];
                    if (file_exists($template_file)) {
                        update_post_meta($existing->ID, '_wp_page_template', $page['template']);
                        error_log("Aakaari: Assigned template '{$page['template']}' to existing page '{$page['title']}'");
                    } else {
                        error_log("Aakaari: Template file not found: {$template_file}");
                    }
                }

                $updated_pages[] = $page['title'];
                error_log("Aakaari: Found existing page '{$page['title']}' (ID: {$existing->ID}), updated option");
            } else {
                // Create new page
                $page_data = array(
                    'post_title'   => $page['title'],
                    'post_content' => $page['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_name'    => $slug,
                    'post_author'  => 1,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                );

                $new_page_id = wp_insert_post($page_data);

                if ($new_page_id && !is_wp_error($new_page_id)) {
                    // Set template if specified and file exists
                    if (!empty($page['template'])) {
                        $template_file = get_template_directory() . '/' . $page['template'];
                        if (file_exists($template_file)) {
                            update_post_meta($new_page_id, '_wp_page_template', $page['template']);
                            error_log("Aakaari: Assigned template '{$page['template']}' to page '{$page['title']}'");
                        } else {
                            error_log("Aakaari: Template file not found: {$template_file}");
                        }
                    }

                    // Store page ID in option
                    update_option($page['option'], $new_page_id);

                    $created_pages[] = $page['title'];
                    error_log("Aakaari: Created page '{$page['title']}' (ID: {$new_page_id})");
                } else {
                    error_log("Aakaari: Failed to create page '{$page['title']}'");
                }
            }
        }
    }

    // Store creation timestamp
    update_option('aakaari_pages_created_at', current_time('mysql'));

    // Configure WordPress and WooCommerce settings
    aakaari_configure_site_settings();

    // Return summary
    return array(
        'created' => $created_pages,
        'updated' => $updated_pages,
        'total' => count($created_pages) + count($updated_pages)
    );
}

/**
 * Get correct dashboard URL based on user role
 */
function aakaari_get_dashboard_url() {
    if (!is_user_logged_in()) {
        return home_url('/login/');
    }

    // Check if user is admin
    if (current_user_can('manage_options')) {
        $admin_dashboard_id = get_option('aakaari_admin_dashboard_page_id');
        if ($admin_dashboard_id) {
            return get_permalink($admin_dashboard_id);
        }
        return home_url('/admin-dashboard/');
    }

    // Check if user is reseller
    if (current_user_can('edit_posts')) {
        $reseller_dashboard_id = get_option('aakaari_dashboard_page_id');
        if ($reseller_dashboard_id) {
            return get_permalink($reseller_dashboard_id);
        }
        return home_url('/reseller-dashboard/');
    }

    // Default to WooCommerce My Account
    return wc_get_page_permalink('myaccount');
}

/**
 * Configure WordPress reading settings and WooCommerce settings
 */
function aakaari_configure_site_settings() {
    // Get home page ID
    $home_page_id = get_option('aakaari_home_page_id');

    // Set WordPress reading settings to use static front page
    if ($home_page_id) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_page_id);
        error_log("Aakaari: Set home page (ID: {$home_page_id}) as front page");
    }

    // Ensure WooCommerce pages are properly configured
    $wc_pages = array(
        'woocommerce_shop_page_id',
        'woocommerce_cart_page_id',
        'woocommerce_checkout_page_id',
        'woocommerce_myaccount_page_id'
    );

    foreach ($wc_pages as $option) {
        $page_id = get_option($option);
        if ($page_id) {
            // Verify page exists and is published
            $page = get_post($page_id);
            if ($page && $page->post_status === 'publish') {
                error_log("Aakaari: Verified WooCommerce page option '{$option}' = {$page_id}");
            }
        }
    }

    // Flush rewrite rules to ensure proper routing
    flush_rewrite_rules();
    error_log("Aakaari: Flushed rewrite rules");
}

/**
 * Run on theme activation
 */
add_action('after_switch_theme', 'aakaari_create_required_pages');

/**
 * Add admin notice after pages are created
 */
add_action('admin_notices', function() {
    if (get_transient('aakaari_pages_created_notice')) {
        $summary = get_transient('aakaari_pages_created_notice');
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <strong>Aakaari Theme:</strong>
                <?php if ($summary['total'] > 0): ?>
                    Successfully set up <?php echo esc_html($summary['total']); ?> pages.
                    <?php if (!empty($summary['created'])): ?>
                        <br>Created: <?php echo esc_html(implode(', ', $summary['created'])); ?>
                    <?php endif; ?>
                    <?php if (!empty($summary['updated'])): ?>
                        <br>Updated: <?php echo esc_html(implode(', ', $summary['updated'])); ?>
                    <?php endif; ?>
                <?php else: ?>
                    All required pages already exist.
                <?php endif; ?>
            </p>
        </div>
        <?php
        delete_transient('aakaari_pages_created_notice');
    }
});

/**
 * Store summary for admin notice
 */
add_action('after_switch_theme', function() {
    $summary = aakaari_create_required_pages();

    // Also update templates for any existing pages
    $template_updates = aakaari_update_page_templates();
    if ($template_updates > 0) {
        error_log("Aakaari: Updated templates for {$template_updates} pages after theme activation");
    }

    set_transient('aakaari_pages_created_notice', $summary, 60);
}, 20); // Run after pages are created

/**
 * Add admin menu item for manual page creation
 */
add_action('admin_menu', function() {
    add_theme_page(
        'Aakaari Pages Setup',
        'Setup Pages',
        'manage_options',
        'aakaari-pages-setup',
        'aakaari_pages_setup_page'
    );
});

/**
 * Admin page for manual page creation
 */
function aakaari_pages_setup_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Handle manual page creation trigger
    if (isset($_POST['create_pages']) && check_admin_referer('aakaari_create_pages')) {
        $summary = aakaari_create_required_pages();

        echo '<div class="notice notice-success"><p>';
        if ($summary['total'] > 0) {
            echo '<strong>Success!</strong> Set up ' . esc_html($summary['total']) . ' pages.';
            if (!empty($summary['created'])) {
                echo '<br>Created: ' . esc_html(implode(', ', $summary['created']));
            }
            if (!empty($summary['updated'])) {
                echo '<br>Updated: ' . esc_html(implode(', ', $summary['updated']));
            }
        } else {
            echo 'All required pages already exist.';
        }
        echo '</p></div>';
    }

    // Handle manual template update trigger
    if (isset($_POST['update_templates']) && check_admin_referer('aakaari_update_templates')) {
        $updated = aakaari_update_page_templates();

        echo '<div class="notice notice-success"><p>';
        if ($updated > 0) {
            echo '<strong>Success!</strong> Updated templates for ' . esc_html($updated) . ' pages.';
        } else {
            echo 'All page templates are already correctly assigned.';
        }
        echo '</p></div>';
    }

    // Display page
    ?>
    <div class="wrap">
        <h1>Aakaari Pages Setup</h1>
        <p>This tool will create all required pages for the Aakaari theme if they don't already exist.</p>

        <h2>Required Pages:</h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><strong>WooCommerce:</strong> Shop, Cart, Checkout, My Account</li>
            <li><strong>Authentication:</strong> Login, Register</li>
            <li><strong>Reseller:</strong> Become a Reseller, Application Pending, Reseller Dashboard</li>
            <li><strong>Admin:</strong> Admin Dashboard</li>
            <li><strong>Other:</strong> Track Order, Contact, How It Works, Pricing</li>
        </ul>

        <h2>Current Status:</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;">Page</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 30%;">Template</th>
                    <th style="width: 20%;">Template Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pages_def = aakaari_get_page_definitions();

                foreach ($pages_def as $slug => $page_def) {
                    $page_id = get_option($page_def['option']);
                    $page = $page_id ? get_post($page_id) : null;
                    $status = $page && $page->post_status === 'publish' ? '✅ Exists' : '❌ Missing';
                    $id_display = $page_id ? $page_id : '-';

                    // Check template status
                    $template_display = $page_def['template'] ? $page_def['template'] : 'Default';
                    $template_status = '-';

                    if ($page && $page->post_status === 'publish' && !empty($page_def['template'])) {
                        $current_template = get_post_meta($page_id, '_wp_page_template', true);
                        $template_file = get_template_directory() . '/' . $page_def['template'];

                        if (!file_exists($template_file)) {
                            $template_status = '❌ File Missing';
                        } elseif ($current_template === $page_def['template']) {
                            $template_status = '✅ Correct';
                        } else {
                            $template_status = '⚠️ Not Assigned';
                        }
                    }

                    echo '<tr>';
                    echo '<td><strong>' . esc_html($page_def['title']) . '</strong></td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td>' . esc_html($id_display) . '</td>';
                    echo '<td><code>' . esc_html($template_display) . '</code></td>';
                    echo '<td>' . $template_status . '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        <div style="margin-top: 20px; display: flex; gap: 15px; flex-wrap: wrap;">
            <form method="post" style="margin: 0;">
                <?php wp_nonce_field('aakaari_create_pages'); ?>
                <button type="submit" name="create_pages" class="button button-primary button-large">
                    📄 Create/Update Missing Pages
                </button>
            </form>

            <form method="post" style="margin: 0;">
                <?php wp_nonce_field('aakaari_update_templates'); ?>
                <button type="submit" name="update_templates" class="button button-secondary button-large">
                    🎨 Update Page Templates
                </button>
            </form>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #0073aa; border-radius: 4px;">
            <p style="margin: 0 0 10px 0;"><strong>📄 Create/Update Missing Pages:</strong> Creates pages that don't exist and assigns templates.</p>
            <p style="margin: 0;"><strong>🎨 Update Page Templates:</strong> Updates templates for existing pages that have incorrect or missing template assignments.</p>
        </div>
    </div>
    <?php
}
