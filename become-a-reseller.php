<?php
/**
 * Template Name: Become a Reseller
 *
 * @package Aakaari
 */

$submitted = false;
$form_errors = [];
$blocked_submission = false;

// Get user IP
$user_ip = $_SERVER['REMOTE_ADDR'];

// --- Get user ID and onboarding status ---
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$onboarding_status = get_user_meta($user_id, 'onboarding_status', true);

// If user is already approved, redirect them to their dashboard.
if ($onboarding_status === 'approved' || $onboarding_status === 'completed') {
    wp_redirect(aakaari_get_dashboard_url());
    exit;
}

// CRITICAL FIX: REMOVED THE DASHBOARD REDIRECT COMPLETELY
// The code that was redirecting to dashboard has been removed to prevent the redirect loop

// Check if this IP has submitted within the last 7 days - Keep existing check but add exception for logged-in users
$recent_submission = check_recent_submission_by_ip($user_ip);
if ($recent_submission && !$onboarding_status) {
    $blocked_submission = true;
    $days_to_wait = calculate_days_remaining($recent_submission);
} else {
    $blocked_submission = false; // Allow submission for users with pending status who are completing their profile
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && ! $blocked_submission) {

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

        // Prefer plugin helper (keeps logic centralized). Make sure plugin is active.
        if ( function_exists('aar_process_reseller_submission') ) {
            // Call the plugin helper which handles uploads, validation and post creation.
            $result = aar_process_reseller_submission( $_POST, $_FILES );

            if ( isset($result['success']) && $result['success'] ) {
                $submitted = true;
                // Prevent duplicate (plugin already stores submitDate/meta)
                setcookie('reseller_application_submitted', time(), time() + (7 * DAY_IN_SECONDS), '/');

                // Mark onboarding as submitted (awaiting approval)
                if ($user_id) {
                    update_user_meta($user_id, 'onboarding_status', 'submitted');
                }
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
            // Plugin not active — fallback: create post in the plugin CPT and set taxonomy
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
                $application_data = array(
                    'post_title'    => sanitize_text_field($_POST['fullName']) . ' - ' . date('Y-m-d'),
                    'post_status'   => 'private',
                    'post_type'     => 'reseller_application', // plugin CPT
                );
                $post_id = wp_insert_post( $application_data );

                if ( ! is_wp_error( $post_id ) ) {
                    // Store meta using plugin-friendly keys
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

                    // Set taxonomy (status)
                    wp_set_object_terms( $post_id, 'pending', 'reseller_application_status' );
                    update_post_meta( $post_id, 'reseller_status', 'pending' );

                    // Admin notification
                    $admin_email = get_option('admin_email');
                    $subject = 'New Reseller Application: ' . sanitize_text_field($_POST['fullName']);
                    $message = "New reseller application received from:\n\nName: " . sanitize_text_field($_POST['fullName']) . "\nEmail: " . sanitize_email($_POST['email']) . "\nPhone: " . sanitize_text_field($_POST['phone']) . "\n\nView application: " . admin_url('post.php?post=' . $post_id . '&action=edit');
                    wp_mail( $admin_email, $subject, $message );

                    // Applicant confirmation email
                    wp_mail( sanitize_email($_POST['email']), 'Your Aakaari Reseller Application', "Thanks for applying. We'll review and be in touch." );

                    $submitted = true;
                    setcookie('reseller_application_submitted', time(), time() + (7 * DAY_IN_SECONDS), '/');
                    
                    // Mark onboarding as submitted (awaiting approval) — NOT completed
                    if ($user_id) {
                        update_user_meta($user_id, 'onboarding_status', 'submitted');
                    }

                } else {
                    $form_errors['general'] = 'Error creating application. Please try again.';
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
            } elseif (in_array('pending', $terms, true)) {
                $status = 'pending';
            } elseif (in_array('rejected', $terms, true)) {
                $status = 'rejected';
            }
        }
        
        wp_reset_postdata();
    }
    
    return array(
        'application' => $application,
        'status' => $status
    );
}

// Get application status from database
$application_info = get_reseller_application_status($current_user->user_email);
$db_application_status = $application_info['status'];
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
            
        <?php elseif ($display_status === 'rejected'): ?>
            <!-- Rejected Application Message -->
            <div class="error-card">
                <div class="error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <h2>Application Not Approved</h2>
                <p>Unfortunately, your reseller application was not approved at this time.</p>
                <p>Please contact our support team for more information or to discuss reapplying.</p>

                <div class="action-buttons">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Back to Home</a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary">Contact Support</a>
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
        <?php else: ?>
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
                <div class="application-form">
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

<?php get_footer(); ?>