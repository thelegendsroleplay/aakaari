<?php
/**
 * Reseller Application Form
 * 
 * This file contains:
 * - Become a Reseller form functionality
 * - Form submission handler (admin-post)
 * - Shortcode registration
 * - Application assets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (is_user_logged_in()) {
    update_user_meta(get_current_user_id(), 'onboarding_status', 'completed');
}
/**
 * Enqueue reseller application assets
 */
function aar_enqueue_reseller_assets() {
    wp_enqueue_style(
        'become-reseller-css',
        get_stylesheet_directory_uri() . '/assets/css/become-a-reseller.css',
        array(),
        '2.0' // Ultimate fix: Simplified CSS - file input hidden with display:none
    );

    // Add random query parameter directly to URL for maximum cache busting
    $script_url = get_stylesheet_directory_uri() . '/assets/js/become-a-reseller.js?nocache=' . wp_rand();

    wp_enqueue_script(
        'become-reseller-js',
        $script_url,
        array(),
        null, // Set to null to prevent WordPress from adding its own version parameter
        true
    );

    // Add cache-control headers to prevent any caching of this script
    add_filter('script_loader_tag', 'aar_add_nocache_to_reseller_script', 10, 2);
}
add_action('wp_enqueue_scripts', 'aar_enqueue_reseller_assets');

/**
 * Add no-cache attributes to reseller script
 */
function aar_add_nocache_to_reseller_script($tag, $handle) {
    if ('become-reseller-js' === $handle) {
        // Add crossorigin and data attribute to force reload
        $tag = str_replace(' src=', ' data-no-cache="true" crossorigin="anonymous" src=', $tag);
    }
    return $tag;
}

/**
 * Register shortcode for reseller application form
 */
function aar_reseller_shortcode($atts) {
    ob_start();
    $tpl = get_stylesheet_directory() . '/template-parts/become-a-reseller.php';
    
    if (file_exists($tpl)) {
        include $tpl;
    } else {
        echo '<p><strong>Reseller form template missing.</strong> Please add <code>template-parts/become-a-reseller.php</code> to your theme.</p>';
    }
    
    return ob_get_clean();
}
add_shortcode('become_a_reseller', 'aar_reseller_shortcode');

/**
 * Handle reseller application form submission
 */
function aar_handle_reseller_submission() {
    // Verify nonce
    if (!isset($_POST['reseller_nonce']) || !wp_verify_nonce($_POST['reseller_nonce'], 'reseller_apply_nonce')) {
        wp_die('Security check failed', 'Error', array('response' => 403));
    }

    // Sanitize form inputs
    $name     = sanitize_text_field($_POST['reseller_full_name'] ?? '');
    $business = sanitize_text_field($_POST['reseller_business_name'] ?? '');
    $email    = sanitize_email($_POST['reseller_email'] ?? '');
    $phone    = sanitize_text_field($_POST['reseller_phone'] ?? '');
    $address  = sanitize_text_field($_POST['reseller_address'] ?? '');
    $city     = sanitize_text_field($_POST['reseller_city'] ?? '');
    $state    = sanitize_text_field($_POST['reseller_state'] ?? '');
    $pincode  = sanitize_text_field($_POST['reseller_pincode'] ?? '');
    $gstin    = sanitize_text_field($_POST['reseller_gstin'] ?? '');
    $bank     = sanitize_text_field($_POST['reseller_bank_name'] ?? '');
    $account  = sanitize_text_field($_POST['reseller_account'] ?? '');
    $ifsc     = sanitize_text_field($_POST['reseller_ifsc'] ?? '');
    $tnc      = isset($_POST['reseller_tnc']) ? 1 : 0;

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($address) || 
        empty($city) || empty($state) || empty($pincode) || empty($bank) || 
        empty($account) || empty($ifsc) || !$tnc) {
        
        $ref = wp_get_referer() ? wp_get_referer() : home_url('/');
        wp_redirect(add_query_arg('reseller_status', 'missing', $ref));
        exit;
    }

    // Handle file upload (ID proof)
    $uploaded_file_url = '';
    if (!empty($_FILES['reseller_id_proof']) && !empty($_FILES['reseller_id_proof']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $uploaded = wp_handle_upload($_FILES['reseller_id_proof'], array('test_form' => false));
        if (isset($uploaded['url'])) {
            $uploaded_file_url = esc_url_raw($uploaded['url']);
        }
    }

    // Create private post to store submission
    $postarr = array(
        'post_title'   => wp_strip_all_tags($name . ' — ' . $business),
        'post_content' => '',
        'post_status'  => 'private',
        'post_type'    => 'reseller_application'
    );
    
    $post_id = wp_insert_post($postarr);
    
    if ($post_id) {
        // Save all meta data
        update_post_meta($post_id, 'reseller_name', $name);
        update_post_meta($post_id, 'reseller_business', $business);
        update_post_meta($post_id, 'reseller_email', $email);
        update_post_meta($post_id, 'reseller_phone', $phone);
        update_post_meta($post_id, 'reseller_address', $address);
        update_post_meta($post_id, 'reseller_city', $city);
        update_post_meta($post_id, 'reseller_state', $state);
        update_post_meta($post_id, 'reseller_pincode', $pincode);
        update_post_meta($post_id, 'reseller_gstin', $gstin);
        update_post_meta($post_id, 'reseller_bank', $bank);
        update_post_meta($post_id, 'reseller_account', $account);
        update_post_meta($post_id, 'reseller_ifsc', $ifsc);
        update_post_meta($post_id, 'reseller_id_proof_url', $uploaded_file_url);
    }

    // Send notification to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Reseller Application: ' . $name;
    $message = "A new reseller application has been submitted:\n\n";
    $message .= "Name: $name\n";
    $message .= "Business: $business\n";
    $message .= "Email: $email\n";
    $message .= "Phone: $phone\n";
    $message .= "Address: $address, $city, $state - $pincode\n";
    $message .= "Bank: $bank (A/C: $account) IFSC: $ifsc\n";
    $message .= "GSTIN: $gstin\n";
    $message .= "ID Proof: $uploaded_file_url\n\n";
    
    wp_mail($admin_email, $subject, $message);

    // Redirect with success
    $ref = wp_get_referer() ? wp_get_referer() : home_url('/');
    wp_redirect(add_query_arg('reseller_status', 'success', $ref));
    exit;
}
add_action('admin_post_nopriv_submit_reseller_application', 'aar_handle_reseller_submission');
add_action('admin_post_submit_reseller_application', 'aar_handle_reseller_submission');

/**
 * Get reseller application status for a given email
 * 
 * @param string $user_email User's email address
 * @return array Array with 'status' and 'application' keys
 */
function get_reseller_application_status($user_email) {
    $application = null;
    $status = 'not-submitted';
    
    $q = new WP_Query(array(
        'post_type'      => 'reseller_application',
        'post_status'    => array('private', 'publish', 'draft', 'pending'),
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'   => 'reseller_email',
                'value' => $user_email,
            ),
        ),
    ));

    if ($q->have_posts()) {
        $q->the_post();
        $application = get_post();
        $terms = wp_get_post_terms(get_the_ID(), 'reseller_application_status', array('fields' => 'slugs'));
        
        if (!is_wp_error($terms) && !empty($terms)) {
            if (in_array('approved', $terms, true)) {
                $status = 'approved';
            } elseif (in_array('resubmission_allowed', $terms, true)) {
                $status = 'resubmission_allowed';
            } elseif (in_array('documents_requested', $terms, true)) {
                $status = 'documents_requested';
            } elseif (in_array('pending', $terms, true) || in_array('under_review', $terms, true)) {
                $status = 'pending';
            } elseif (in_array('rejected', $terms, true)) {
                $status = 'rejected';
            }
        }
        
        wp_reset_postdata();
    }
    
    return array(
        'status' => $status,
        'application' => $application,
    );
}

/**
 * Register Reseller Application CPT
 * Note: This should be called on init hook
 */
function register_reseller_application_cpt() {
    register_post_type('reseller_application', array(
        'labels' => array(
            'name' => 'Reseller Applications',
            'singular_name' => 'Reseller Application',
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'supports' => array('title', 'custom-fields'),
    ));
}
add_action('init', 'register_reseller_application_cpt');