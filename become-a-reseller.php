<?php
/**
 * Template Name: Become a Reseller
 *
 * @package Aakaari
 */

$submitted = false;
$form_errors = [];
$blocked_submission = false;

// Get user IP (sanitized and supports proxies)
$user_ip = filter_var(
    isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
    FILTER_VALIDATE_IP
);
if (!$user_ip) {
    $user_ip = '0.0.0.0'; // Fallback for invalid IPs
}

// --- Get user ID and onboarding status ---
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$onboarding_status = get_user_meta($user_id, 'onboarding_status', true);

// Get application status using the proper function
$application_info = get_reseller_application_status($current_user->user_email);
$application_status = $application_info['status'];
$application = $application_info['application'];

// Check if user can reapply (handles cooldown logic)
$can_reapply = function_exists('can_user_reapply') ? can_user_reapply($user_id) : true;

// Check if this is a successful submission redirect
// Only show success message if user has a recent application and we're on the submitted page
$submitted = isset($_GET['submitted']) && $_GET['submitted'] === '1' && $application && in_array($application_status, array('pending', 'under_review'));

// Debugging for admins
if (current_user_can('administrator') && isset($_POST['submit_application'])) {
    error_log("Reseller Form Debug - User ID: $user_id | Status: $application_status | Can Reapply: " . ($can_reapply ? 'YES' : 'NO') . " | Blocked: " . ($blocked_submission ? 'YES' : 'NO'));
}

// If user is already approved, redirect them to their dashboard
if ($application_status === 'approved') {
    $dashboard_page_id = get_option('aakaari_dashboard_page_id');
    $dashboard_url = $dashboard_page_id ? get_permalink($dashboard_page_id) : home_url('/reseller-dashboard/');
    wp_redirect($dashboard_url);
    exit;
}

// Check if this IP has submitted within the last 7 days - Keep existing check but add exception for logged-in users
$recent_submission = check_recent_submission_by_ip($user_ip);
if ($recent_submission && !$onboarding_status) {
    $blocked_submission = true;
    $days_to_wait = calculate_days_remaining($recent_submission);
} else {
    $blocked_submission = false; // Allow submission for users with pending status who are completing their profile
}

// Check cooldown status for rejected users
$cooldown_active = false;
$cooldown_days_remaining = 0;
$cooldown_hours_remaining = 0;
$cooldown_minutes_remaining = 0;
$cooldown_expires = 0;

if ($application_status === 'rejected') {
    $cooldown_expires = get_user_meta($user_id, 'cooldown_expires_at', true);
    
    // Only show cooldown if it's explicitly set AND still active
    if ($cooldown_expires && time() < intval($cooldown_expires)) {
        // Cooldown is still active
        $cooldown_active = true;
        $remaining_seconds = intval($cooldown_expires) - time();
        $cooldown_days_remaining = floor($remaining_seconds / DAY_IN_SECONDS);
        $remaining_seconds %= DAY_IN_SECONDS;
        $cooldown_hours_remaining = floor($remaining_seconds / HOUR_IN_SECONDS);
        $remaining_seconds %= HOUR_IN_SECONDS;
        $cooldown_minutes_remaining = floor($remaining_seconds / MINUTE_IN_SECONDS);
    }
    // If cooldown is not set or expired, cooldown_active remains false
    // This allows the user to see the form
}

// Get requested documents if status is documents_requested
$requested_documents = array();
$document_request_message = '';
if ($application_status === 'documents_requested' && $application) {
    $requested_documents = get_post_meta($application->ID, 'requested_documents', true) ?: array();
    $document_request_message = get_post_meta($application->ID, 'document_request_message', true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && ! $blocked_submission && $can_reapply) {

    // Basic server-side validation (keeps your existing errors array)
    $required_fields = ['fullName', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'bankName', 'accountNumber', 'ifsc'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $form_errors[$field] = 'This field is required';
        }
    }
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = 'Please enter a valid email address';
    }
    if (!isset($_POST['agreed'])) {
        $form_errors['agreed'] = 'You must agree to the Terms & Conditions';
    }

    // Validate required document uploads (server-side check)
    $required_docs = ['aadhaarFront', 'aadhaarBack', 'panCard', 'bankProof'];
    foreach ($required_docs as $doc) {
        if (empty($_FILES[$doc]['name'])) {
            $form_errors[$doc] = 'This document is required';
        } else {
            // Check file size (5MB = 5242880 bytes)
            if ($_FILES[$doc]['size'] > 5242880) {
                $form_errors[$doc] = 'File size must not exceed 5MB';
            }
            // Check file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!in_array($_FILES[$doc]['type'], $allowed_types)) {
                $form_errors[$doc] = 'Only JPG, PNG, or PDF files are allowed';
            }
        }
    }

    // Validate optional business proof if uploaded
    if (!empty($_FILES['businessProof']['name'])) {
        if ($_FILES['businessProof']['size'] > 5242880) {
            $form_errors['businessProof'] = 'File size must not exceed 5MB';
        }
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!in_array($_FILES['businessProof']['type'], $allowed_types)) {
            $form_errors['businessProof'] = 'Only JPG, PNG, or PDF files are allowed';
        }
    }

    // If any validation errors, we fall through and show them (don't process)
    if (empty($form_errors)) {

        // Check if this is a resubmission (user has existing application)
        $is_resubmission = $application && in_array($application_status, array('resubmission_allowed', 'documents_requested'));

        // Prefer plugin helper (keeps logic centralized). Make sure plugin is active.
        if ( function_exists('aar_process_reseller_submission') ) {
            // Call the plugin helper which handles uploads, validation and post creation.
            $result = aar_process_reseller_submission( $_POST, $_FILES );

            if ( isset($result['success']) && $result['success'] ) {
                // Mark onboarding as submitted (awaiting approval)
                if ($user_id) {
                    update_user_meta($user_id, 'onboarding_status', 'submitted');
                }

                // Prevent duplicate submissions
                setcookie('reseller_application_submitted', time(), time() + (7 * DAY_IN_SECONDS), '/');

                // Redirect to show success message and prevent form resubmission
                wp_safe_redirect(add_query_arg('submitted', '1', get_permalink()));
                exit;
            } else {
                // Map plugin errors back into $form_errors for display
                if ( isset($result['errors']) && is_array($result['errors']) ) {
                    foreach ( $result['errors'] as $k => $v ) {
                        $form_errors[$k] = $v;
                    }
                } else {
                    $form_errors['general'] = 'Failed to submit application. Please try again later.';
                }
            }
        } else {
            // Plugin not active — fallback: handle resubmission vs new application
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/reseller-documents/';
            if ( ! file_exists( $target_dir ) ) wp_mkdir_p( $target_dir );

            // Handle multiple document uploads
            $uploaded_documents = array();
            $document_fields = ['aadhaarFront', 'aadhaarBack', 'panCard', 'bankProof', 'businessProof'];
            $upload_success = true;

            foreach ($document_fields as $field) {
                if (!empty($_FILES[$field]['name'])) {
                    $file_ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                    $filename = $field . '_' . time() . '_' . sanitize_title($_POST['fullName']) . '.' . $file_ext;
                    $target_file = $target_dir . $filename;
                    $file_url = $upload_dir['baseurl'] . '/reseller-documents/' . $filename;

                    if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_file)) {
                        $uploaded_documents[$field] = $file_url;
                    } else {
                        $upload_success = false;
                        $form_errors[$field] = 'Failed to upload file. Please try again.';
                    }
                }
            }

            if ($upload_success && !empty($uploaded_documents)) {
                if ($is_resubmission && $application) {
                    // Update existing application for resubmission
                    $post_id = $application->ID;

                    // Update post title and date
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_title' => sanitize_text_field($_POST['fullName']) . ' - Resubmission - ' . date('Y-m-d'),
                        'post_modified' => current_time('mysql'),
                        'post_modified_gmt' => current_time('mysql', true)
                    ));

                    // Clear old document URLs and update with new ones
                    $old_docs = array('aadhaar_front_url', 'aadhaar_back_url', 'pan_card_url', 'bank_proof_url', 'business_proof_url', 'reseller_id_proof_url');
                    foreach ($old_docs as $meta_key) {
                        delete_post_meta($post_id, $meta_key);
                    }
                } else {
                    // Create new application for fresh submission
                    $application_data = array(
                        'post_title'    => sanitize_text_field($_POST['fullName']) . ' - ' . date('Y-m-d'),
                        'post_status'   => 'private',
                        'post_type'     => 'reseller_application',
                    );
                    $post_id = wp_insert_post( $application_data );

                    if ( is_wp_error( $post_id ) ) {
                        $form_errors['general'] = 'Error creating application. Please try again.';
                        $upload_success = false;
                    }
                }

                if ($upload_success && !is_wp_error($post_id)) {
                    // Store/update meta using plugin-friendly keys
                    update_post_meta( $post_id, 'reseller_name', sanitize_text_field($_POST['fullName']) );
                    update_post_meta( $post_id, 'reseller_business', sanitize_text_field($_POST['businessName'] ?? '') );
                    update_post_meta( $post_id, 'reseller_email', sanitize_email($_POST['email']) );
                    update_post_meta( $post_id, 'reseller_phone', sanitize_text_field($_POST['phone']) );
                    update_post_meta( $post_id, 'reseller_address', sanitize_textarea_field($_POST['address']) );
                    update_post_meta( $post_id, 'reseller_city', sanitize_text_field($_POST['city']) );
                    update_post_meta( $post_id, 'reseller_state', sanitize_text_field($_POST['state']) );
                    update_post_meta( $post_id, 'reseller_pincode', sanitize_text_field($_POST['pincode']) );
                    update_post_meta( $post_id, 'reseller_gstin', sanitize_text_field($_POST['gstin'] ?? '') );
                    update_post_meta( $post_id, 'reseller_bank', sanitize_text_field($_POST['bankName']) );
                    update_post_meta( $post_id, 'reseller_account', sanitize_text_field($_POST['accountNumber']) );
                    update_post_meta( $post_id, 'reseller_ifsc', strtoupper( sanitize_text_field($_POST['ifsc']) ) );

                    // Save all uploaded document URLs
                    if (isset($uploaded_documents['aadhaarFront'])) {
                        update_post_meta( $post_id, 'aadhaar_front_url', esc_url_raw($uploaded_documents['aadhaarFront']) );
                    }
                    if (isset($uploaded_documents['aadhaarBack'])) {
                        update_post_meta( $post_id, 'aadhaar_back_url', esc_url_raw($uploaded_documents['aadhaarBack']) );
                    }
                    if (isset($uploaded_documents['panCard'])) {
                        update_post_meta( $post_id, 'pan_card_url', esc_url_raw($uploaded_documents['panCard']) );
                    }
                    if (isset($uploaded_documents['bankProof'])) {
                        update_post_meta( $post_id, 'bank_proof_url', esc_url_raw($uploaded_documents['bankProof']) );
                    }
                    if (isset($uploaded_documents['businessProof'])) {
                        update_post_meta( $post_id, 'business_proof_url', esc_url_raw($uploaded_documents['businessProof']) );
                    }
                    // Keep legacy field for backward compatibility
                    update_post_meta( $post_id, 'reseller_id_proof_url', esc_url_raw($uploaded_documents['aadhaarFront'] ?? '') );
                    update_post_meta( $post_id, 'ipAddress', $_SERVER['REMOTE_ADDR'] ?? '' );
                    update_post_meta( $post_id, 'submitDate', current_time('mysql') );
                    // Add business type for dashboard display
                    update_post_meta( $post_id, 'reseller_business_type', sanitize_text_field($_POST['businessType'] ?? 'Individual/Freelancer') );

                    // Set taxonomy status to pending/under_review
                    wp_set_object_terms( $post_id, 'pending', 'reseller_application_status' );
                    update_post_meta( $post_id, 'reseller_status', 'pending' );

                    // Add resubmission metadata if applicable
                    if ($is_resubmission) {
                        update_post_meta( $post_id, 'resubmitted_at', current_time('mysql') );
                        update_post_meta( $post_id, 'resubmission_count', intval(get_post_meta($post_id, 'resubmission_count', true)) + 1 );
                    }

                    // Admin notification
                    $subject_prefix = $is_resubmission ? 'Resubmitted Reseller Application' : 'New Reseller Application';
                    $admin_email = get_option('admin_email');
                    $subject = $subject_prefix . ': ' . sanitize_text_field($_POST['fullName']);
                    $message = ($is_resubmission ? "Resubmitted" : "New") . " reseller application received from:\n\nName: " . sanitize_text_field($_POST['fullName']) . "\nEmail: " . sanitize_email($_POST['email']) . "\nPhone: " . sanitize_text_field($_POST['phone']) . "\n\nView application: " . admin_url('post.php?post=' . $post_id . '&action=edit');
                    wp_mail( $admin_email, $subject, $message );

                    // Applicant confirmation email with HTML template
                    $email_data = array(
                        'name' => sanitize_text_field($_POST['fullName']),
                        'application_id' => $post_id,
                        'submitted_date' => date('F j, Y')
                    );
                    
                    $subject = 'Application Received - Thank You for Applying!';
                    $message = aakaari_email_application_submitted($email_data);
                    
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail( sanitize_email($_POST['email']), $subject, $message, $headers );

                    // Mark onboarding as submitted (awaiting approval)
                    if ($user_id) {
                        update_user_meta($user_id, 'onboarding_status', 'submitted');
                    }

                    // Prevent duplicate submissions
                    setcookie('reseller_application_submitted', time(), time() + (7 * DAY_IN_SECONDS), '/');

                    // Redirect to show success message and prevent form resubmission
                    wp_safe_redirect(add_query_arg('submitted', '1', get_permalink()));
                    exit;

                } else {
                    if (empty($form_errors)) {
                        $form_errors['general'] = 'Error ' . ($is_resubmission ? 'updating' : 'creating') . ' application. Please try again.';
                    }
                }
            } else {
                if (empty($form_errors)) {
                    $form_errors['general'] = 'Failed to upload one or more documents. Please try again.';
                }
            }
        } // end plugin-helper branch
    } // end if no errors
}


/**
 * Check if this IP has submitted an application within the past 7 days
 */
function check_recent_submission_by_ip($ip) {
    // Query for applications from this IP in the last 7 days (plugin CPT)
    $args = array(
        'post_type' => 'reseller_application',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'ipAddress',
                'value' => $ip,
                'compare' => '='
            ),
            array(
                'key' => 'submitDate',
                'value' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'compare' => '>',
                'type' => 'DATETIME'
            )
        )
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $query->the_post();
        $submit_date = get_post_meta(get_the_ID(), 'submitDate', true);
        wp_reset_postdata();
        return $submit_date;
    }

    return false;
}


/**
 * Calculate days remaining before new submission is allowed
 */
function calculate_days_remaining($submit_date) {
    $submit_timestamp = strtotime($submit_date);
    $current_timestamp = current_time('timestamp');
    $time_diff = $submit_timestamp + (7 * DAY_IN_SECONDS) - $current_timestamp;
    
    return ceil($time_diff / DAY_IN_SECONDS);
}

// NEW FUNCTION: Check for application and its status
// Note: get_reseller_application_status() is now in inc/reseller-application.php
// Application status already retrieved above
$db_application_status = $application_status;
$application = $application_info['application'];

// Use URL parameter status if provided, otherwise use status from database
$display_status = $db_application_status;

// Optional debugging for admins only
if (current_user_can('administrator')) {
    echo "<!-- DEBUG: User ID: $user_id | Onboarding: $onboarding_status | Status param: $status_param | DB status: $db_application_status | Display: $display_status -->";
}

get_header();

$benefits = [
    'Access to 1500+ wholesale products',
    '50-100% profit margins on every sale',
    'Zero inventory investment',
    'Direct shipping to customers',
    'Real-time order tracking',
    'Instant commission payouts',
    'Dedicated support team',
    'Marketing materials & catalogs',
];
?>

<div class="reseller-page">
    <!-- Header -->
    <div class="reseller-header">
        <div class="container">
            <h1>Become a Reseller</h1>
            <p>Join thousands of successful resellers and start your dropshipping business today</p>
        </div>
    </div>

    <div class="container">
        <?php if ($submitted): ?>
            <!-- Success Message -->
            <div class="success-card">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2>Application Submitted Successfully!</h2>
                <p>Thank you for your interest in becoming an Aakaari reseller. We'll review your application and get back to you within 24 hours.</p>
                
                <div class="next-steps">
                    <h3>What's Next?</h3>
                    <ol>
                        <li>Our team will verify your KYC documents</li>
                        <li>You'll receive an approval email with login access</li>
                        <li>Access your dashboard and start ordering</li>
                        <li>Share product links and start earning!</li>
                    </ol>
                </div>
                
                <div class="action-buttons">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Back to Home</a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary">Contact Support</a>
                </div>
            </div>

        <?php elseif ($onboarding_status === 'submitted' || $display_status === 'pending'): ?>
            <!-- Pending Approval Message -->
            <div class="warning-card">
                <div class="warning-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h2>Your Application is Under Review</h2>
                <p>Thanks for submitting your details. Our team is reviewing your application. You'll receive an email once it's approved.</p>

                <div class="action-buttons">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Back to Home</a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary">Contact Support</a>
                </div>
            </div>
            
        <?php elseif ($display_status === 'rejected' && $cooldown_active): ?>
            <!-- Cooldown Period Warning - Only show when cooldown is ACTIVE -->
            <div class="warning-card">
                <div class="warning-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h2>Application Not Approved</h2>
                <p>Unfortunately, we could not approve your reseller application at this time.</p>
                <?php if ($cooldown_active): ?>
                <p class="countdown-subtitle">⏳ You can reapply after the cooldown period expires:</p>
                <div class="cooldown-timer" data-expires="<?php echo esc_attr($cooldown_expires); ?>">
                    <div class="timer-display timer-days">
                        <span class="timer-number"><?php echo str_pad($cooldown_days_remaining, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="timer-label">days</span>
                    </div>
                    <div class="timer-display timer-hours">
                        <span class="timer-number"><?php echo str_pad($cooldown_hours_remaining, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="timer-label">hours</span>
                    </div>
                    <div class="timer-display timer-minutes">
                        <span class="timer-number"><?php echo str_pad($cooldown_minutes_remaining, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="timer-label">minutes</span>
                    </div>
                    <div class="timer-display timer-seconds">
                        <span class="timer-number">00</span>
                        <span class="timer-label">seconds</span>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 2rem; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 16px; margin: 2rem 0; border: 2px solid #10b981;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">✓</div>
                    <p style="font-size: 1.25rem; font-weight: 700; color: #065f46; margin: 0 0 0.5rem 0;">Cooldown Period Expired!</p>
                    <p style="color: #047857; margin: 0;">You can now submit a new reseller application.</p>
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <?php if (!$cooldown_active): ?>
                    <a href="#application-form" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Reapply Now
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary">Contact Support</a>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Back to Home</a>
                </div>
            </div>

        <?php elseif ($blocked_submission): ?>
            <!-- Duplicate Submission Warning -->
            <div class="warning-card">
                <div class="warning-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h2>Application Already Submitted</h2>
                <p>We've detected that you've already submitted a reseller application within the past 7 days.</p>
                <p>Please wait <?php echo $days_to_wait; ?> more day<?php echo $days_to_wait > 1 ? 's' : ''; ?> before submitting another application.</p>

                <div class="action-buttons">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Back to Home</a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary">Contact Support</a>
                </div>
            </div>
        <?php endif; ?>

        <?php 
        // Show form for: new users, resubmission_allowed, documents_requested, and rejected users (ONLY after cooldown expires)
        $show_form = !$submitted && 
                     !in_array($application_status, array('pending', 'under_review', 'approved')) && 
                     !$blocked_submission && 
                     !$cooldown_active && // Don't show form if cooldown is active
                     $can_reapply;
        ?>

        <?php if ($show_form): ?>
            <?php if ($application_status === 'resubmission_allowed'): ?>
                <!-- Resubmission Allowed Notice -->
                <div class="info-card" style="margin-bottom: 2rem;">
                    <div class="info-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <h2>Resubmission Available</h2>
                    <p>Your reseller application can now be resubmitted. Please review and update your information below.</p>
                </div>
            <?php endif; ?>

            <?php if ($application_status === 'documents_requested'): ?>
                <!-- Documents Requested Notice -->
                <div class="info-card" style="margin-bottom: 2rem;">
                    <div class="info-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <h2>Action Required: Upload Documents</h2>
                    <p>We need additional documents to continue processing your reseller application.</p>

                    <?php if (!empty($requested_documents)): ?>
                    <div class="requested-documents">
                        <h3>Requested Documents:</h3>
                        <ul>
                            <?php
                            $doc_names = array(
                                'aadhaar_front' => 'Aadhaar Card (Front)',
                                'aadhaar_back' => 'Aadhaar Card (Back)',
                                'pan_card' => 'PAN Card',
                                'bank_proof' => 'Bank Proof (Cancelled Cheque or Statement)',
                                'business_proof' => 'Business Registration/GST Certificate'
                            );
                            foreach ($requested_documents as $doc_key) {
                                if (isset($doc_names[$doc_key])) {
                                    echo '<li>' . esc_html($doc_names[$doc_key]) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                        <?php if (!empty($document_request_message)): ?>
                        <p><strong>Additional Information:</strong> <?php echo esc_html($document_request_message); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="reseller-content">
                <!-- Benefits Sidebar -->
                <div class="benefits-sidebar">
                    <div class="benefits-card">
                        <h2>Why Join Aakaari?</h2>
                        <ul class="benefits-list">
                            <?php foreach ($benefits as $benefit): ?>
                            <li>
                                <span class="check-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </span>
                                <?php echo $benefit; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Application Form -->
                <div class="application-form" id="application-form">
                    <div class="form-card">
                        <h2>Reseller Application Form</h2>
                        
                        <?php if (!empty($form_errors['general'])): ?>
                            <div class="form-error-message">
                                <?php echo esc_html($form_errors['general']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form id="resellerForm" method="post" enctype="multipart/form-data">
                            <!-- Personal Information -->
                            <div class="form-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Personal Information
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="fullName">Full Name <span class="required">*</span></label>
                                        <input type="text" id="fullName" name="fullName" value="<?php echo isset($_POST['fullName']) ? esc_attr($_POST['fullName']) : ''; ?>" required>
                                        <?php if (isset($form_errors['fullName'])): ?>
                                            <span class="form-error"><?php echo $form_errors['fullName']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="businessName">Business Name</label>
                                        <input type="text" id="businessName" name="businessName" value="<?php echo isset($_POST['businessName']) ? esc_attr($_POST['businessName']) : ''; ?>" placeholder="Optional">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address <span class="required">*</span></label>
                                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" required>
                                        <?php if (isset($form_errors['email'])): ?>
                                            <span class="form-error"><?php echo $form_errors['email']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">Phone Number <span class="required">*</span></label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" required>
                                        <?php if (isset($form_errors['phone'])): ?>
                                            <span class="form-error"><?php echo $form_errors['phone']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Add hidden field for business type -->
                                <input type="hidden" name="businessType" value="Individual/Freelancer">
                            </div>
                            
                            <!-- Address Details -->
                            <div class="form-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    Address Details
                                </h3>
                                
                                <div class="form-group">
                                    <label for="address">Street Address <span class="required">*</span></label>
                                    <textarea id="address" name="address" rows="2" required><?php echo isset($_POST['address']) ? esc_textarea($_POST['address']) : ''; ?></textarea>
                                    <?php if (isset($form_errors['address'])): ?>
                                        <span class="form-error"><?php echo $form_errors['address']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-row three-columns">
                                    <div class="form-group">
                                        <label for="city">City <span class="required">*</span></label>
                                        <input type="text" id="city" name="city" value="<?php echo isset($_POST['city']) ? esc_attr($_POST['city']) : ''; ?>" required>
                                        <?php if (isset($form_errors['city'])): ?>
                                            <span class="form-error"><?php echo $form_errors['city']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="state">State <span class="required">*</span></label>
                                        <input type="text" id="state" name="state" value="<?php echo isset($_POST['state']) ? esc_attr($_POST['state']) : ''; ?>" required>
                                        <?php if (isset($form_errors['state'])): ?>
                                            <span class="form-error"><?php echo $form_errors['state']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="pincode">Pincode <span class="required">*</span></label>
                                        <input type="text" id="pincode" name="pincode" value="<?php echo isset($_POST['pincode']) ? esc_attr($_POST['pincode']) : ''; ?>" required>
                                        <?php if (isset($form_errors['pincode'])): ?>
                                            <span class="form-error"><?php echo $form_errors['pincode']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Business Details -->
                            <div class="form-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                                    </svg>
                                    Business Details
                                </h3>

                                <div class="form-group">
                                    <label for="gstin">GSTIN (Optional)</label>
                                    <input type="text" id="gstin" name="gstin" value="<?php echo isset($_POST['gstin']) ? esc_attr($_POST['gstin']) : ''; ?>" placeholder="22AAAAA0000A1Z5">
                                    <small class="help-text">Provide GSTIN if you have GST registration</small>
                                </div>
                            </div>

                            <!-- Document Uploads -->
                            <div class="form-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                    </svg>
                                    Document Uploads
                                </h3>
                                <p class="section-description">Please upload clear, readable copies of the required documents. Max file size: 5MB per file.</p>

                                <!-- Aadhaar Card Front -->
                                <div class="form-group">
                                    <label for="aadhaarFront">Upload Aadhaar Card (Front Side) <span class="required">*</span></label>
                                    <div class="file-upload-area" data-input-id="aadhaarFront">
                                        <input type="file" id="aadhaarFront" name="aadhaarFront" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <div class="upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                        </div>
                                        <p>Click to upload or drag and drop</p>
                                        <p class="file-types">PDF, JPG, PNG (Max 5MB)</p>
                                    </div>
                                    <div class="selected-file" data-for="aadhaarFront"></div>
                                    <?php if (isset($form_errors['aadhaarFront'])): ?>
                                        <span class="form-error"><?php echo $form_errors['aadhaarFront']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Aadhaar Card Back -->
                                <div class="form-group">
                                    <label for="aadhaarBack">Upload Aadhaar Card (Back Side) <span class="required">*</span></label>
                                    <div class="file-upload-area" data-input-id="aadhaarBack">
                                        <input type="file" id="aadhaarBack" name="aadhaarBack" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <div class="upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                        </div>
                                        <p>Click to upload or drag and drop</p>
                                        <p class="file-types">PDF, JPG, PNG (Max 5MB)</p>
                                    </div>
                                    <div class="selected-file" data-for="aadhaarBack"></div>
                                    <?php if (isset($form_errors['aadhaarBack'])): ?>
                                        <span class="form-error"><?php echo $form_errors['aadhaarBack']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- PAN Card -->
                                <div class="form-group">
                                    <label for="panCard">Upload PAN Card <span class="required">*</span></label>
                                    <div class="file-upload-area" data-input-id="panCard">
                                        <input type="file" id="panCard" name="panCard" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <div class="upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                        </div>
                                        <p>Click to upload or drag and drop</p>
                                        <p class="file-types">PDF, JPG, PNG (Max 5MB)</p>
                                    </div>
                                    <div class="selected-file" data-for="panCard"></div>
                                    <?php if (isset($form_errors['panCard'])): ?>
                                        <span class="form-error"><?php echo $form_errors['panCard']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Bank Proof -->
                                <div class="form-group">
                                    <label for="bankProof">Upload Bank Proof (Cancelled Cheque or Statement) <span class="required">*</span></label>
                                    <div class="file-upload-area" data-input-id="bankProof">
                                        <input type="file" id="bankProof" name="bankProof" class="file-input" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <div class="upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                        </div>
                                        <p>Click to upload or drag and drop</p>
                                        <p class="file-types">PDF, JPG, PNG (Max 5MB)</p>
                                    </div>
                                    <div class="selected-file" data-for="bankProof"></div>
                                    <?php if (isset($form_errors['bankProof'])): ?>
                                        <span class="form-error"><?php echo $form_errors['bankProof']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Business Proof (Optional) -->
                                <div class="form-group">
                                    <label for="businessProof">Upload Business Registration Certificate / GST (Optional)</label>
                                    <div class="file-upload-area" data-input-id="businessProof">
                                        <input type="file" id="businessProof" name="businessProof" class="file-input" accept=".pdf,.jpg,.jpeg,.png">
                                        <div class="upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="17 8 12 3 7 8"></polyline>
                                                <line x1="12" y1="3" x2="12" y2="15"></line>
                                            </svg>
                                        </div>
                                        <p>Click to upload or drag and drop</p>
                                        <p class="file-types">PDF, JPG, PNG (Max 5MB) - Optional</p>
                                    </div>
                                    <div class="selected-file" data-for="businessProof"></div>
                                    <?php if (isset($form_errors['businessProof'])): ?>
                                        <span class="form-error"><?php echo $form_errors['businessProof']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Bank Details -->
                            <div class="form-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                    Bank Details
                                </h3>
                                
                                <div class="form-group">
                                    <label for="bankName">Bank Name <span class="required">*</span></label>
                                    <input type="text" id="bankName" name="bankName" value="<?php echo isset($_POST['bankName']) ? esc_attr($_POST['bankName']) : ''; ?>" required>
                                    <?php if (isset($form_errors['bankName'])): ?>
                                        <span class="form-error"><?php echo $form_errors['bankName']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="accountNumber">Account Number <span class="required">*</span></label>
                                        <input type="text" id="accountNumber" name="accountNumber" value="<?php echo isset($_POST['accountNumber']) ? esc_attr($_POST['accountNumber']) : ''; ?>" required>
                                        <?php if (isset($form_errors['accountNumber'])): ?>
                                            <span class="form-error"><?php echo $form_errors['accountNumber']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="ifsc">IFSC Code <span class="required">*</span></label>
                                        <input type="text" id="ifsc" name="ifsc" value="<?php echo isset($_POST['ifsc']) ? esc_attr($_POST['ifsc']) : ''; ?>" required>
                                        <?php if (isset($form_errors['ifsc'])): ?>
                                            <span class="form-error"><?php echo $form_errors['ifsc']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Terms and Agreement -->
                            <div class="form-section terms-section">
                                <div class="alert">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="agreed" name="agreed" required <?php echo isset($_POST['agreed']) ? 'checked' : ''; ?>>
                                        <label for="agreed">
                                            I agree to the <a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>" target="_blank">Terms & Conditions</a> and 
                                            <a href="<?php echo esc_url(home_url('/reseller-agreement/')); ?>" target="_blank">Reseller Agreement</a>. 
                                            I understand that Aakaari will verify my KYC documents before approval.
                                        </label>
                                    </div>
                                    <?php if (isset($form_errors['agreed'])): ?>
                                        <span class="form-error"><?php echo $form_errors['agreed']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Form Actions -->
                            <div class="form-actions">
                                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Cancel</a>
                                <button type="submit" name="submit_application" class="btn btn-primary">Submit Application</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Additional Styles for New Features -->
<style>
/* Enhanced Status Card Styles */
.success-card,
.warning-card,
.error-card,
.info-card {
    border-radius: 16px;
    padding: 3rem 2rem;
    text-align: center;
    max-width: 700px;
    margin: 2rem auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Success Card (Application Submitted) */
.success-card {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 2px solid #10b981;
}

.success-card .success-icon {
    color: #10b981;
    margin-bottom: 1.5rem;
    animation: scaleIn 0.5s ease-out 0.3s both;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
    }
    to {
        transform: scale(1);
    }
}

.success-card h2 {
    color: #065f46;
    font-size: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.success-card p {
    color: #047857;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.next-steps {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin: 2rem 0;
    text-align: left;
}

.next-steps h3 {
    color: #065f46;
    margin-top: 0;
    margin-bottom: 1rem;
}

.next-steps ol {
    margin: 0;
    padding-left: 1.5rem;
}

.next-steps li {
    color: #047857;
    margin-bottom: 0.75rem;
    line-height: 1.6;
}

/* Warning Card (Pending/Cooldown) */
.warning-card {
    background: #ffffff;
    border: none;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
    position: relative;
    overflow: hidden;
}

.warning-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
}

.warning-card .warning-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
}

.warning-card h2 {
    color: #1f2937;
    font-size: 2rem;
    margin-bottom: 0.75rem;
    font-weight: 700;
}

.warning-card p {
    color: #6b7280;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Error Card (Rejected) */
.error-card {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 2px solid #ef4444;
}

.error-card .error-icon {
    color: #ef4444;
    margin-bottom: 1.5rem;
}

.error-card h2 {
    color: #991b1b;
    font-size: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.error-card p {
    color: #7f1d1d;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Info Card (Resubmission/Documents Requested) */
.info-card {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 2px solid #3b82f6;
}

.info-card .info-icon {
    color: #3b82f6;
    margin-bottom: 1.5rem;
}

.info-card h2 {
    color: #1e40af;
    font-size: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.info-card p {
    color: #1e3a8a;
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Cooldown Timer Styles */
.cooldown-timer {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin: 2.5rem auto;
    max-width: 500px;
    padding: 0 0.5rem;
}

@media (max-width: 640px) {
    .cooldown-timer {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        max-width: 100%;
    }
}

.timer-display {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 16px;
    padding: 1.5rem 0.75rem;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.timer-display::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
    pointer-events: none;
}

@media (hover: hover) and (pointer: fine) {
    .timer-display:hover {
        transform: translateY(-8px) scale(1.05);
        box-shadow: 0 12px 32px rgba(59, 130, 246, 0.35);
    }
}

.timer-number {
    font-size: 3rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    display: block;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.timer-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    margin-top: 0.5rem;
    font-weight: 700;
    letter-spacing: 1px;
    display: block;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2.5rem;
}

.action-buttons .btn {
    padding: 1rem 2.5rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    overflow: hidden;
}

.action-buttons .btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

@media (hover: hover) and (pointer: fine) {
    .action-buttons .btn:hover::before {
        width: 300px;
        height: 300px;
    }
}

.action-buttons .btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    border: 2px solid transparent;
}

@media (hover: hover) and (pointer: fine) {
    .action-buttons .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(59, 130, 246, 0.4);
    }
}

.action-buttons .btn-outline {
    background: transparent;
    color: #3b82f6;
    border: 2px solid #e5e7eb;
}

@media (hover: hover) and (pointer: fine) {
    .action-buttons .btn-outline:hover {
        background: #f9fafb;
        border-color: #3b82f6;
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
    }
}

/* Touch-friendly tap states for mobile */
.action-buttons .btn:active {
    transform: scale(0.97);
}

.timer-display:active {
    transform: scale(0.97);
}

/* Requested Documents Styles */
.requested-documents {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.requested-documents h3 {
    margin-top: 0;
    color: #374151;
}

.requested-documents ul {
    margin: 0;
    padding-left: 1.5rem;
}

.requested-documents li {
    margin-bottom: 0.5rem;
    color: #4b5563;
}

/* Countdown Expired Styles */
.countdown-expired {
    text-align: center;
    padding: 2rem;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-radius: 16px;
    border: 2px solid #10b981;
}

.expired-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: #065f46;
    margin: 0 0 0.75rem 0;
}

.expired-subtext {
    color: #047857;
    margin: 0 0 1.5rem 0;
    font-size: 1.1rem;
}

/* Countdown Subtitle */
.countdown-subtitle {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #6b7280;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .reseller-page {
        padding: 0;
    }
    
    .reseller-header {
        padding: 2rem 1rem;
    }
    
    .reseller-header h1 {
        font-size: 1.75rem;
    }
    
    .reseller-header p {
        font-size: 1rem;
    }
    
    .container {
        padding: 0 1rem;
    }
    
    .warning-card,
    .error-card,
    .success-card,
    .info-card {
        padding: 2rem 1.25rem;
        margin: 1.5rem 0;
        border-radius: 12px;
    }
    
    .warning-card .warning-icon,
    .error-card .error-icon,
    .success-card .success-icon,
    .info-card .info-icon {
        width: 60px;
        height: 60px;
    }
    
    .warning-card h2,
    .error-card h2,
    .success-card h2,
    .info-card h2 {
        font-size: 1.5rem;
        line-height: 1.3;
    }
    
    .warning-card p,
    .error-card p,
    .success-card p,
    .info-card p {
        font-size: 1rem;
    }
    
    .countdown-subtitle {
        font-size: 0.875rem;
        padding: 0 0.5rem;
    }
    
    .cooldown-timer {
        gap: 0.75rem;
        margin: 2rem 0;
    }
    
    .timer-display {
        padding: 1rem 0.5rem;
        border-radius: 12px;
    }
    
    .timer-number {
        font-size: 2rem;
    }
    
    .timer-label {
        font-size: 0.65rem;
        margin-top: 0.25rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.75rem;
        padding: 0 0.5rem;
    }
    
    .action-buttons .btn {
        width: 100%;
        justify-content: center;
        padding: 0.875rem 1.5rem;
        font-size: 0.95rem;
    }
    
    .next-steps {
        padding: 1.5rem;
    }
    
    .next-steps h3 {
        font-size: 1.1rem;
    }
    
    .next-steps ol {
        font-size: 0.95rem;
    }
}

@media (max-width: 480px) {
    .reseller-header h1 {
        font-size: 1.5rem;
    }
    
    .warning-card,
    .error-card,
    .success-card,
    .info-card {
        padding: 1.5rem 1rem;
    }
    
    .warning-card h2,
    .error-card h2,
    .success-card h2,
    .info-card h2 {
        font-size: 1.25rem;
    }
    
    .timer-number {
        font-size: 1.75rem;
    }
    
    .timer-label {
        font-size: 0.6rem;
    }
    
    .timer-display {
        padding: 0.875rem 0.375rem;
    }
}
</style>

<script src="<?php echo get_template_directory_uri(); ?>/assets/js/reseller-countdown.js"></script>

<?php get_footer(); ?>