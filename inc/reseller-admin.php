<?php
/**
 * Reseller Application Admin Dashboard
 * Adds admin management capabilities for reseller applications
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Reseller Application CPT with improved admin UI
 */
function aakaari_register_reseller_application_cpt() {
    $labels = array(
        'name'               => 'Reseller Applications',
        'singular_name'      => 'Reseller Application',
        'menu_name'          => 'Reseller Apps',
        'name_admin_bar'     => 'Reseller App',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Application',
        'new_item'           => 'New Application',
        'edit_item'          => 'View Application',
        'view_item'          => 'View Application',
        'all_items'          => 'All Applications',
        'search_items'       => 'Search Applications',
        'parent_item_colon'  => 'Parent Applications:',
        'not_found'          => 'No applications found.',
        'not_found_in_trash' => 'No applications found in Trash.'
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => true,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-id-alt',
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'supports'            => array('title'),
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
        'map_meta_cap'        => true
    );

    register_post_type('reseller_application', $args);
    
    // Register Application Status Taxonomy
    register_taxonomy(
        'reseller_app_status',
        'reseller_application',
        array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'              => 'Status',
                'singular_name'     => 'Status',
                'search_items'      => 'Search Statuses',
                'all_items'         => 'All Statuses',
                'edit_item'         => 'Edit Status',
                'update_item'       => 'Update Status',
                'add_new_item'      => 'Add New Status',
                'new_item_name'     => 'New Status Name',
                'menu_name'         => 'Status',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_quick_edit' => true,
        )
    );
    
    // Create default statuses if they don't exist
    $statuses = array(
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    );
    
    foreach ($statuses as $slug => $name) {
        if (!term_exists($slug, 'reseller_app_status')) {
            wp_insert_term($name, 'reseller_app_status', array(
                'slug' => $slug
            ));
        }
    }
}
add_action('init', 'aakaari_register_reseller_application_cpt');

/**
 * Add custom columns to reseller applications admin list
 */
function aakaari_reseller_application_columns($columns) {
    $new_columns = array(
        'cb' => $columns['cb'],
        'title' => __('Applicant Name'),
        'email' => __('Email'),
        'phone' => __('Phone'),
        'business' => __('Business'),
        'location' => __('Location'),
        'submitted' => __('Submitted'),
        'taxonomy-reseller_app_status' => __('Status'),
        'actions' => __('Actions')
    );
    
    return $new_columns;
}
add_filter('manage_reseller_application_posts_columns', 'aakaari_reseller_application_columns');

/**
 * Populate custom columns in reseller applications admin list
 */
function aakaari_reseller_application_column_content($column, $post_id) {
    switch ($column) {
        case 'email':
            echo esc_html(get_post_meta($post_id, 'reseller_email', true));
            break;
        
        case 'phone':
            echo esc_html(get_post_meta($post_id, 'reseller_phone', true));
            break;
            
        case 'business':
            $business_name = get_post_meta($post_id, 'reseller_business', true);
            echo !empty($business_name) ? esc_html($business_name) : '<em>Not specified</em>';
            break;
            
        case 'location':
            $city = get_post_meta($post_id, 'reseller_city', true);
            $state = get_post_meta($post_id, 'reseller_state', true);
            if ($city && $state) {
                echo esc_html($city . ', ' . $state);
            } else {
                echo esc_html($city . $state); // Show whatever is available
            }
            break;
            
        case 'submitted':
            $submit_date = get_post_meta($post_id, 'submitDate', true);
            if ($submit_date) {
                echo esc_html(date_i18n('M j, Y', strtotime($submit_date)));
            } else {
                echo '<em>Unknown</em>';
            }
            break;
            
        case 'actions':
            // Get the user ID associated with this application
            $user_id = get_post_meta($post_id, 'user_id', true);
            
            // Get current status
            $terms = wp_get_post_terms($post_id, 'reseller_app_status');
            $current_status = !empty($terms) ? $terms[0]->slug : 'pending';
            
            // Show different action buttons based on status
            if ($current_status === 'pending') {
                echo '<div class="reseller-action-buttons">';
                echo '<a href="#" class="button button-small approve-application" data-id="' . esc_attr($post_id) . '" data-user="' . esc_attr($user_id) . '">Approve</a> ';
                echo '<a href="#" class="button button-small reject-application" data-id="' . esc_attr($post_id) . '" data-user="' . esc_attr($user_id) . '">Reject</a>';
                echo '</div>';
            } else if ($current_status === 'approved') {
                echo '<div class="reseller-action-buttons">';
                if ($user_id) {
                    echo '<a href="' . esc_url(admin_url('user-edit.php?user_id=' . $user_id)) . '" class="button button-small">View User</a> ';
                }
                echo '<a href="#" class="button button-small reject-application" data-id="' . esc_attr($post_id) . '" data-user="' . esc_attr($user_id) . '">Reject</a>';
                echo '</div>';
            } else {
                echo '<div class="reseller-action-buttons">';
                echo '<a href="#" class="button button-small approve-application" data-id="' . esc_attr($post_id) . '" data-user="' . esc_attr($user_id) . '">Approve</a>';
                echo '</div>';
            }
            break;
    }
}
add_action('manage_reseller_application_posts_custom_column', 'aakaari_reseller_application_column_content', 10, 2);

/**
 * Add meta box for reseller application details
 */
function aakaari_add_application_meta_boxes() {
    add_meta_box(
        'reseller_application_details',
        'Application Details',
        'aakaari_application_details_meta_box',
        'reseller_application',
        'normal',
        'high'
    );
    
    add_meta_box(
        'reseller_application_documents',
        'Documents & Verification',
        'aakaari_application_documents_meta_box',
        'reseller_application',
        'normal',
        'default'
    );
    
    add_meta_box(
        'reseller_application_bank',
        'Bank Details',
        'aakaari_application_bank_meta_box',
        'reseller_application',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'aakaari_add_application_meta_boxes');

/**
 * Render application details meta box
 */
function aakaari_application_details_meta_box($post) {
    $name = get_post_meta($post->ID, 'reseller_name', true);
    $business = get_post_meta($post->ID, 'reseller_business', true);
    $email = get_post_meta($post->ID, 'reseller_email', true);
    $phone = get_post_meta($post->ID, 'reseller_phone', true);
    $address = get_post_meta($post->ID, 'reseller_address', true);
    $city = get_post_meta($post->ID, 'reseller_city', true);
    $state = get_post_meta($post->ID, 'reseller_state', true);
    $pincode = get_post_meta($post->ID, 'reseller_pincode', true);
    $gstin = get_post_meta($post->ID, 'reseller_gstin', true);
    $ip = get_post_meta($post->ID, 'ipAddress', true);
    $submit_date = get_post_meta($post->ID, 'submitDate', true);
    
    // Find associated user
    $user = get_user_by('email', $email);
    $user_id = $user ? $user->ID : null;
    
    // Save user ID with application if not already saved
    if ($user_id && !get_post_meta($post->ID, 'user_id', true)) {
        update_post_meta($post->ID, 'user_id', $user_id);
    }
    
    ?>
    <style>
        .reseller-details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .reseller-details-table tr td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .reseller-details-table tr:last-child td {
            border-bottom: none;
        }
        .reseller-details-table .label {
            width: 150px;
            font-weight: 600;
        }
        .reseller-meta-heading {
            margin: 15px 0 5px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
            color: #23282d;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
    
    <table class="reseller-details-table">
        <tr>
            <td class="label">Full Name</td>
            <td><?php echo esc_html($name); ?></td>
        </tr>
        <tr>
            <td class="label">Business Name</td>
            <td><?php echo !empty($business) ? esc_html($business) : '<em>Not provided</em>'; ?></td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td><?php echo esc_html($email); ?></td>
        </tr>
        <tr>
            <td class="label">Phone</td>
            <td><?php echo esc_html($phone); ?></td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td>
                <?php echo esc_html($address); ?><br>
                <?php echo esc_html($city . ', ' . $state . ' - ' . $pincode); ?>
            </td>
        </tr>
        <tr>
            <td class="label">GSTIN</td>
            <td><?php echo !empty($gstin) ? esc_html($gstin) : '<em>Not provided</em>'; ?></td>
        </tr>
        <tr>
            <td class="label">Submission Info</td>
            <td>
                <?php 
                if ($submit_date) {
                    echo 'Submitted on ' . esc_html(date_i18n('F j, Y \a\t g:i a', strtotime($submit_date)));
                }
                if ($ip) {
                    echo ' from IP ' . esc_html($ip);
                }
                ?>
            </td>
        </tr>
        <?php if ($user_id): ?>
        <tr>
            <td class="label">User Account</td>
            <td>
                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $user_id)); ?>" class="button">
                    View User Profile
                </a>
                <p class="description">User ID: <?php echo esc_html($user_id); ?></p>
            </td>
        </tr>
        <?php endif; ?>
    </table>
    
    <?php
}

/**
 * Render application documents meta box
 */
function aakaari_application_documents_meta_box($post) {
    $id_proof_url = get_post_meta($post->ID, 'reseller_id_proof_url', true);
    ?>
    
    <h4 class="reseller-meta-heading">ID Proof Document</h4>
    <?php if (!empty($id_proof_url)): ?>
        <div class="reseller-document">
            <?php 
            $file_type = wp_check_filetype($id_proof_url);
            $extension = strtolower($file_type['ext']);
            
            if (in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                echo '<img src="' . esc_url($id_proof_url) . '" style="max-width: 100%; max-height: 400px;">';
            } else {
                echo '<a href="' . esc_url($id_proof_url) . '" class="button" target="_blank">View Document</a>';
                echo '<p class="description">File Type: ' . esc_html(strtoupper($extension)) . '</p>';
            }
            ?>
        </div>
    <?php else: ?>
        <p><em>No ID proof document uploaded.</em></p>
    <?php endif; ?>
    
    <div class="reseller-verification">
        <h4 class="reseller-meta-heading">Verification Status</h4>
        
        <?php
        // Get current status
        $terms = wp_get_post_terms($post->ID, 'reseller_app_status');
        $current_status = !empty($terms) ? $terms[0]->slug : 'pending';
        $status_class = '';
        
        if ($current_status === 'approved') {
            $status_class = 'approved';
            $status_message = 'This application has been approved';
        } else if ($current_status === 'rejected') {
            $status_class = 'rejected';
            $status_message = 'This application has been rejected';
        } else {
            $status_class = 'pending';
            $status_message = 'This application is pending review';
        }
        ?>
        
        <div class="verification-status <?php echo esc_attr($status_class); ?>">
            <p><?php echo esc_html($status_message); ?></p>
        </div>
        
        <h4 class="reseller-meta-heading">Actions</h4>
        <?php
        $user_id = get_post_meta($post->ID, 'user_id', true);
        ?>
        
        <div class="reseller-actions">
            <?php if ($current_status !== 'approved'): ?>
            <button type="button" class="button button-primary approve-application" 
                    data-id="<?php echo esc_attr($post->ID); ?>" 
                    data-user="<?php echo esc_attr($user_id); ?>">
                Approve Application
            </button>
            <?php endif; ?>
            
            <?php if ($current_status !== 'rejected'): ?>
            <button type="button" class="button reject-application" 
                    data-id="<?php echo esc_attr($post->ID); ?>" 
                    data-user="<?php echo esc_attr($user_id); ?>">
                Reject Application
            </button>
            <?php endif; ?>
        </div>
        
        <style>
            .verification-status {
                padding: 10px 12px;
                border-radius: 3px;
                margin-bottom: 15px;
            }
            .verification-status.approved {
                background: #ecf8ee;
                border-left: 4px solid #46b450;
            }
            .verification-status.rejected {
                background: #feeaea;
                border-left: 4px solid #dc3232;
            }
            .verification-status.pending {
                background: #fef8ee;
                border-left: 4px solid #ffb900;
            }
            .verification-status p {
                margin: 0;
            }
            .reseller-actions {
                display: flex;
                gap: 10px;
            }
        </style>
    </div>
    <?php
}

/**
 * Render bank details meta box
 */
function aakaari_application_bank_meta_box($post) {
    $bank = get_post_meta($post->ID, 'reseller_bank', true);
    $account = get_post_meta($post->ID, 'reseller_account', true);
    $ifsc = get_post_meta($post->ID, 'reseller_ifsc', true);
    ?>
    
    <table class="reseller-details-table">
        <tr>
            <td class="label">Bank Name</td>
            <td><?php echo esc_html($bank); ?></td>
        </tr>
        <tr>
            <td class="label">Account Number</td>
            <td><?php echo esc_html($account); ?></td>
        </tr>
        <tr>
            <td class="label">IFSC Code</td>
            <td><?php echo esc_html($ifsc); ?></td>
        </tr>
    </table>
    <?php
}

/**
 * Add admin scripts for reseller application management
 */
function aakaari_reseller_admin_scripts($hook) {
    global $post_type;
    
    // Only load on reseller application pages
    if ($post_type !== 'reseller_application') {
        return;
    }
    
    wp_enqueue_script(
        'reseller-admin-js',
        get_stylesheet_directory_uri() . '/assets/js/reseller-admin.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_localize_script('reseller-admin-js', 'reseller_admin_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('reseller_admin_nonce'),
        'approve_confirm' => 'Are you sure you want to approve this application?',
        'reject_confirm' => 'Are you sure you want to reject this application?',
    ));
    
    // Add some inline styles for admin
    echo '<style>
        .reseller-action-buttons {
            white-space: nowrap;
        }
        .reseller-action-buttons .button {
            margin-right: 5px;
        }
        .approve-application {
            color: #46b450;
        }
        .reject-application {
            color: #dc3232;
        }
    </style>';
}
add_action('admin_enqueue_scripts', 'aakaari_reseller_admin_scripts');

/**
 * AJAX handler for approving applications
 */
function aakaari_approve_application() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reseller_admin_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid application ID'));
        exit;
    }
    
    // Set application status to approved
    wp_set_object_terms($post_id, 'approved', 'reseller_app_status');
    
    // Update user's onboarding status if user exists
    if ($user_id) {
        update_user_meta($user_id, 'onboarding_status', 'completed');
        
        // Get user email for notification
        $user = get_user_by('id', $user_id);
        if ($user) {
            // Send approval email to user
            $subject = 'Your Aakaari Reseller Application has been Approved!';
            $message = "Dear " . get_user_meta($user_id, 'full_name', true) . ",\n\n";
            $message .= "Congratulations! Your application to become an Aakaari Reseller has been approved.\n\n";
            $message .= "You can now log in to your dashboard and start selling:\n";
            $message .= home_url('/dashboard/') . "\n\n";
            $message .= "Thank you for joining Aakaari!\n\n";
            $message .= "Best regards,\nThe Aakaari Team";
            
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    wp_send_json_success(array(
        'message' => 'Application approved successfully',
        'redirect' => admin_url('edit.php?post_type=reseller_application')
    ));
    exit;
}
add_action('wp_ajax_approve_reseller_application', 'aakaari_approve_application');

/**
 * AJAX handler for rejecting applications
 */
function aakaari_reject_application() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reseller_admin_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed'));
        exit;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid application ID'));
        exit;
    }
    
    // Set application status to rejected
    wp_set_object_terms($post_id, 'rejected', 'reseller_app_status');
    
    // Update user's status if user exists
    if ($user_id) {
        update_user_meta($user_id, 'onboarding_status', 'rejected');
        
        // Get user email for notification
        $user = get_user_by('id', $user_id);
        if ($user) {
            // Send rejection email to user
            $subject = 'Update on Your Aakaari Reseller Application';
            $message = "Dear " . get_user_meta($user_id, 'full_name', true) . ",\n\n";
            $message .= "Thank you for your interest in becoming an Aakaari Reseller.\n\n";
            $message .= "After reviewing your application, we regret to inform you that we are unable to approve it at this time.\n\n";
            $message .= "If you'd like to discuss this further or provide additional information, please contact our support team.\n\n";
            $message .= "Best regards,\nThe Aakaari Team";
            
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    wp_send_json_success(array(
        'message' => 'Application rejected successfully',
        'redirect' => admin_url('edit.php?post_type=reseller_application')
    ));
    exit;
}
add_action('wp_ajax_reject_reseller_application', 'aakaari_reject_application');

/**
 * Make columns sortable
 */
function aakaari_reseller_application_sortable_columns($columns) {
    $columns['email'] = 'email';
    $columns['submitted'] = 'submitted';
    $columns['business'] = 'business';
    $columns['location'] = 'location';
    return $columns;
}
add_filter('manage_edit-reseller_application_sortable_columns', 'aakaari_reseller_application_sortable_columns');

/**
 * Handle custom sorting
 */
function aakaari_reseller_application_sort_columns($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'reseller_application') {
        return;
    }

    $orderby = $query->get('orderby');
    
    switch ($orderby) {
        case 'email':
            $query->set('meta_key', 'reseller_email');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'submitted':
            $query->set('meta_key', 'submitDate');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'business':
            $query->set('meta_key', 'reseller_business');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'location':
            $query->set('meta_key', 'reseller_city');
            $query->set('orderby', 'meta_value');
            break;
    }
}
add_action('pre_get_posts', 'aakaari_reseller_application_sort_columns');

/**
 * Add admin JS file for handling application approval/rejection
 */
function aakaari_create_reseller_admin_js() {
    // Create JS directory if it doesn't exist
    $js_dir = get_stylesheet_directory() . '/assets/js';
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    // Create JS file if it doesn't exist
    $js_file = $js_dir . '/reseller-admin.js';
    if (!file_exists($js_file)) {
        $js_content = '/**
 * Reseller Application Admin JS
 */
jQuery(document).ready(function($) {
    // Approve application
    $(document).on("click", ".approve-application", function(e) {
        e.preventDefault();
        
        if (!confirm(reseller_admin_vars.approve_confirm)) {
            return;
        }
        
        var postId = $(this).data("id");
        var userId = $(this).data("user");
        
        $.ajax({
            url: reseller_admin_vars.ajax_url,
            type: "POST",
            data: {
                action: "approve_reseller_application",
                nonce: reseller_admin_vars.nonce,
                post_id: postId,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    alert("Application approved successfully");
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(response.data.message || "Error approving application");
                }
            },
            error: function() {
                alert("Server error. Please try again.");
            }
        });
    });
    
    // Reject application
    $(document).on("click", ".reject-application", function(e) {
        e.preventDefault();
        
        if (!confirm(reseller_admin_vars.reject_confirm)) {
            return;
        }
        
        var postId = $(this).data("id");
        var userId = $(this).data("user");
        
        $.ajax({
            url: reseller_admin_vars.ajax_url,
            type: "POST",
            data: {
                action: "reject_reseller_application",
                nonce: reseller_admin_vars.nonce,
                post_id: postId,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    alert("Application rejected successfully");
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(response.data.message || "Error rejecting application");
                }
            },
            error: function() {
                alert("Server error. Please try again.");
            }
        });
    });
});';
        
        file_put_contents($js_file, $js_content);
    }
}
add_action('after_switch_theme', 'aakaari_create_reseller_admin_js');

/**
 * Link to reseller_application from functions.php
 * Add this to your theme's functions.php
 */
function aakaari_include_reseller_admin() {
    require_once get_template_directory() . '/inc/reseller-admin.php';
}