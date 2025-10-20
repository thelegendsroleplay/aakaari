<?php
/**
 * Template Name: Application Pending
 * 
 * Displays the status of a reseller application
 * 
 * @package Aakaari
 */

// Redirect non-logged in users to login page
if (!is_user_logged_in()) {
    wp_redirect(site_url('/login?redirect_to=' . urlencode(get_permalink())));
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_email = $current_user->user_email;

// Check email verification
$email_verified = get_user_meta($user_id, 'email_verified', true);
if ($email_verified !== 'true' && $email_verified !== true && $email_verified !== '1' && $email_verified !== 1) {
    wp_redirect(site_url('/register?verify=1'));
    exit;
}

// Get application information
$application = null;
$application_status = 'not-submitted';
$submission_date = '';
$application_id = 0;

// Query for the user's application
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
    $application_id = get_the_ID();
    $terms = wp_get_post_terms(get_the_ID(), 'reseller_application_status', array('fields' => 'slugs'));
    
    // Get application submission date
    $submission_date = get_post_meta($application_id, 'submitDate', true);
    if (empty($submission_date)) {
        $submission_date = get_the_date('F j, Y');
    }
    
    // Check application status
    if (!is_wp_error($terms) && !empty($terms)) {
        if (in_array('approved', $terms, true)) {
            $application_status = 'approved';
        } elseif (in_array('pending', $terms, true)) {
            $application_status = 'pending';
        } elseif (in_array('rejected', $terms, true)) {
            $application_status = 'rejected';
        }
    }
    
    wp_reset_postdata();
} else {
    // No application found, redirect to become-a-reseller
    wp_redirect(site_url('/become-a-reseller'));
    exit;
}

// If application is approved, redirect to dashboard
if ($application_status === 'approved') {
    wp_redirect(site_url('/dashboard'));
    exit;
}

// Now include the header - AFTER all redirects
get_header();

// Get any application notes (rejection reason etc.)
$application_notes = get_post_meta($application_id, 'admin_notes', true);
?>

<div class="application-status-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="status-card">
                    <?php if ($application_status === 'pending'): ?>
                        <!-- Pending Application View -->
                        <div class="status-header pending">
                            <div class="status-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <h1>Application Under Review</h1>
                        </div>
                        
                        <div class="status-body">
                            <div class="status-info">
                                <p class="status-message">Thank you for applying to be an Aakaari reseller. Your application is currently being reviewed by our team.</p>
                                
                                <div class="application-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Application ID:</span>
                                        <span class="detail-value">#<?php echo $application_id; ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Submission Date:</span>
                                        <span class="detail-value"><?php echo $submission_date; ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Status:</span>
                                        <span class="detail-value status-badge pending">Under Review</span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Estimated Review Time:</span>
                                        <span class="detail-value">24-48 hours</span>
                                    </div>
                                </div>
                                
                                <div class="next-steps">
                                    <h3>What's Next?</h3>
                                    <ol>
                                        <li>Our team will verify your KYC documents</li>
                                        <li>You'll receive an approval email</li>
                                        <li>Access your dashboard and start ordering</li>
                                        <li>Share product links and start earning!</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    
                    <?php elseif ($application_status === 'rejected'): ?>
                        <!-- Rejected Application View -->
                        <div class="status-header rejected">
                            <div class="status-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                            </div>
                            <h1>Application Not Approved</h1>
                        </div>
                        
                        <div class="status-body">
                            <div class="status-info">
                                <p class="status-message">Unfortunately, we could not approve your reseller application at this time.</p>
                                
                                <div class="application-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Application ID:</span>
                                        <span class="detail-value">#<?php echo $application_id; ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Submission Date:</span>
                                        <span class="detail-value"><?php echo $submission_date; ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Status:</span>
                                        <span class="detail-value status-badge rejected">Not Approved</span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($application_notes)): ?>
                                <div class="rejection-reason">
                                    <h3>Reason:</h3>
                                    <p><?php echo esc_html($application_notes); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="reapplication-info">
                                    <h3>What's Next?</h3>
                                    <p>Please contact our support team for more information or to discuss options for reapplying.</p>
                                </div>
                            </div>
                        </div>
                    
                    <?php else: ?>
                        <!-- Unknown Status View -->
                        <div class="status-header unknown">
                            <div class="status-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                            </div>
                            <h1>Application Status Unknown</h1>
                        </div>
                        
                        <div class="status-body">
                            <div class="status-info">
                                <p class="status-message">Your application is in our system but we're unable to determine its current status.</p>
                                
                                <div class="application-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Application ID:</span>
                                        <span class="detail-value">#<?php echo $application_id; ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <span class="detail-label">Submission Date:</span>
                                        <span class="detail-value"><?php echo $submission_date; ?></span>
                                    </div>
                                </div>
                                
                                <p>Please contact our support team for assistance.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="status-footer">
                        <div class="action-buttons">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-outline">Back to Home</a>
                            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add styles for the status page -->
<style>
.application-status-page {
    padding: 40px 0;
    background: #f8f9fa;
    min-height: 70vh;
}

.status-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.status-header {
    padding: 30px;
    text-align: center;
    color: #fff;
}

.status-header.pending {
    background: linear-gradient(135deg, #3498db, #2980b9);
}

.status-header.rejected {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.status-header.unknown {
    background: linear-gradient(135deg, #95a5a6, #7f8c8d);
}

.status-icon {
    margin-bottom: 15px;
}

.status-header h1 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 0;
}

.status-body {
    padding: 30px;
}

.status-info {
    margin-bottom: 20px;
}

.status-message {
    font-size: 18px;
    margin-bottom: 25px;
    text-align: center;
}

.application-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.detail-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
    padding-bottom: 0;
}

.detail-label {
    font-weight: 600;
    color: #555;
}

.detail-value {
    color: #333;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: 500;
}

.status-badge.pending {
    background-color: #3498db;
    color: #fff;
}

.status-badge.rejected {
    background-color: #e74c3c;
    color: #fff;
}

.next-steps, .rejection-reason, .reapplication-info {
    margin-top: 20px;
}

.next-steps h3, .rejection-reason h3, .reapplication-info h3 {
    font-size: 18px;
    margin-bottom: 15px;
    font-weight: 600;
}

.next-steps ol {
    padding-left: 20px;
}

.next-steps li {
    margin-bottom: 10px;
}

.status-footer {
    background: #f8f9fa;
    padding: 20px 30px;
    border-top: 1px solid #eee;
}

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-outline {
    border: 1px solid #3498db;
    color: #3498db;
    background: transparent;
}

.btn-outline:hover {
    background: #f0f7fc;
}

.btn-primary {
    background: #3498db;
    color: #fff;
    border: 1px solid #3498db;
}

.btn-primary:hover {
    background: #2980b9;
    border-color: #2980b9;
}
</style>

<?php get_footer(); ?>