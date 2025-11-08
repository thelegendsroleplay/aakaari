<?php
/**
 * HTML Email Templates for Reseller Application System
 * 
 * @package Aakaari
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get email header HTML
 */
function aakaari_get_email_header($title = 'Aakaari Reseller Program') {
    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f3f4f6;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
';
}

/**
 * Get email footer HTML
 */
function aakaari_get_email_footer() {
    $site_url = home_url('/');
    $contact_url = home_url('/contact/');
    $year = date('Y');
    
    return '
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 30px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 15px 0; font-size: 14px; color: #6b7280;">
                                Need help? <a href="' . esc_url($contact_url) . '" style="color: #3b82f6; text-decoration: none;">Contact our support team</a>
                            </p>
                            <p style="margin: 0 0 10px 0; font-size: 13px; color: #9ca3af;">
                                <a href="' . esc_url($site_url) . '" style="color: #9ca3af; text-decoration: none;">Visit Aakaari</a>
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                © ' . $year . ' Aakaari. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Application Approved Email Template
 */
function aakaari_email_application_approved($data) {
    $name = $data['name'] ?? 'Reseller';
    $email = $data['email'] ?? '';
    $dashboard_url = home_url('/reseller-dashboard/');
    
    $header = aakaari_get_email_header('Application Approved - Aakaari');
    $footer = aakaari_get_email_footer();
    
    $body = '
                    <!-- Header with Logo/Brand -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🎉 Congratulations!</h1>
                            <p style="margin: 10px 0 0 0; color: #d1fae5; font-size: 16px;">Your reseller application has been approved</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Great news! Your reseller application has been approved. Welcome to the Aakaari reseller family! 🚀
                            </p>
                            
                            <div style="background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <h3 style="margin: 0 0 15px 0; color: #065f46; font-size: 18px;">What\'s Next?</h3>
                                <ol style="margin: 0; padding-left: 20px; color: #047857; font-size: 15px; line-height: 1.8;">
                                    <li>Access your reseller dashboard</li>
                                    <li>Browse our product catalog</li>
                                    <li>Start sharing products with your customers</li>
                                    <li>Track your orders and commissions</li>
                                </ol>
                            </div>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($dashboard_url) . '" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    Access Your Dashboard →
                                </a>
                            </div>
                            
                            <p style="margin: 30px 0 0 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                If you have any questions or need assistance getting started, our support team is here to help!
                            </p>
                        </td>
                    </tr>
';
    
    return $header . $body . $footer;
}

/**
 * Application Rejected Email Template
 */
function aakaari_email_application_rejected($data) {
    $name = $data['name'] ?? 'User';
    $reason = $data['reason'] ?? '';
    $cooldown_date = $data['cooldown_date'] ?? '';
    $cooldown_human = $data['cooldown_human'] ?? '7 days';
    $reapply_url = home_url('/become-a-reseller/');
    
    $header = aakaari_get_email_header('Application Status - Aakaari');
    $footer = aakaari_get_email_footer();
    
    $body = '
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">Application Update</h1>
                            <p style="margin: 10px 0 0 0; color: #fecaca; font-size: 16px;">Regarding your reseller application</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Thank you for your interest in becoming an Aakaari reseller. After reviewing your application, we are unable to approve it at this time.
                            </p>
                            
                            ' . ($reason ? '
                            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #991b1b; font-size: 16px;">Reason:</h4>
                                <p style="margin: 0; color: #7f1d1d; font-size: 15px; line-height: 1.6;">' . esc_html($reason) . '</p>
                            </div>
                            ' : '') . '
                            
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #92400e; font-size: 16px;">⏰ Reapplication Period</h4>
                                <p style="margin: 0; color: #78350f; font-size: 15px; line-height: 1.6;">
                                    You may reapply after <strong>' . esc_html($cooldown_human) . '</strong>' . ($cooldown_date ? ' (on ' . esc_html($cooldown_date) . ')' : '') . '.
                                </p>
                            </div>
                            
                            <p style="margin: 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                We encourage you to review your application details and reapply once the waiting period has passed. In the meantime, feel free to contact our support team if you have any questions.
                            </p>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($reapply_url) . '" style="display: inline-block; background: #e5e7eb; color: #374151; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                    View Application Page
                                </a>
                            </div>
                        </td>
                    </tr>
';
    
    return $header . $body . $footer;
}

/**
 * Resubmission Allowed Email Template
 */
function aakaari_email_resubmission_allowed($data) {
    $name = $data['name'] ?? 'User';
    $reapply_url = home_url('/become-a-reseller/');
    
    $header = aakaari_get_email_header('Resubmission Available - Aakaari');
    $footer = aakaari_get_email_footer();
    
    $body = '
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🔄 Resubmission Available</h1>
                            <p style="margin: 10px 0 0 0; color: #bfdbfe; font-size: 16px;">You can now resubmit your application</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Good news! Your reseller application is now open for resubmission. You can update your information and submit a new application.
                            </p>
                            
                            <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px;">What This Means:</h4>
                                <ul style="margin: 0; padding-left: 20px; color: #1e3a8a; font-size: 15px; line-height: 1.8;">
                                    <li>Any cooldown period has been cleared</li>
                                    <li>You can resubmit immediately</li>
                                    <li>Please review and update your information before resubmitting</li>
                                </ul>
                            </div>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($reapply_url) . '" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    Resubmit Application →
                                </a>
                            </div>
                            
                            <p style="margin: 30px 0 0 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                Make sure to provide accurate and complete information to help us process your application quickly.
                            </p>
                        </td>
                    </tr>
';
    
    return $header . $body . $footer;
}

/**
 * Documents Requested Email Template
 */
function aakaari_email_documents_requested($data) {
    $name = $data['name'] ?? 'User';
    $requested_docs = $data['requested_documents'] ?? array();
    $message = $data['message'] ?? '';
    $upload_url = home_url('/become-a-reseller/');
    
    $doc_names = array(
        'aadhaar_front' => 'Aadhaar Card (Front)',
        'aadhaar_back' => 'Aadhaar Card (Back)',
        'pan_card' => 'PAN Card',
        'bank_proof' => 'Bank Proof (Cancelled Cheque or Statement)',
        'business_proof' => 'Business Registration/GST Certificate'
    );
    
    $header = aakaari_get_email_header('Documents Required - Aakaari');
    $footer = aakaari_get_email_footer();
    
    $docs_list = '';
    if (!empty($requested_docs)) {
        $docs_list = '<ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 15px; line-height: 1.8;">';
        foreach ($requested_docs as $doc_key) {
            if (isset($doc_names[$doc_key])) {
                $docs_list .= '<li><strong>' . esc_html($doc_names[$doc_key]) . '</strong></li>';
            }
        }
        $docs_list .= '</ul>';
    }
    
    $body = '
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">📄 Action Required</h1>
                            <p style="margin: 10px 0 0 0; color: #fde68a; font-size: 16px;">Additional documents needed</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                We need some additional documents to continue processing your reseller application. Please upload the following:
                            </p>
                            
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <h4 style="margin: 0 0 15px 0; color: #92400e; font-size: 16px;">Required Documents:</h4>
                                ' . $docs_list . '
                            </div>
                            
                            ' . ($message ? '
                            <div style="background-color: #f3f4f6; padding: 20px; margin: 20px 0; border-radius: 8px;">
                                <h4 style="margin: 0 0 10px 0; color: #374151; font-size: 16px;">Additional Information:</h4>
                                <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.6;">' . nl2br(esc_html($message)) . '</p>
                            </div>
                            ' : '') . '
                            
                            <p style="margin: 20px 0; font-size: 15px; color: #374151; line-height: 1.6;">
                                <strong>Accepted formats:</strong> JPG, PNG, or PDF<br>
                                <strong>Maximum size:</strong> 5MB per file
                            </p>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($upload_url) . '" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);">
                                    Upload Documents →
                                </a>
                            </div>
                            
                            <p style="margin: 30px 0 0 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                Please upload these documents as soon as possible to avoid delays in processing your application.
                            </p>
                        </td>
                    </tr>
';
    
    return $header . $body . $footer;
}

/**
 * Application Submitted/Received Email Template
 */
function aakaari_email_application_submitted($data) {
    $name = $data['name'] ?? 'Reseller';
    $application_id = $data['application_id'] ?? '';
    $submitted_date = $data['submitted_date'] ?? date('F j, Y');
    
    $header = aakaari_get_email_header('Application Received - Aakaari');
    $footer = aakaari_get_email_footer();
    
    $body = '
                    <!-- Header with Brand -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 40px; text-align: center;">
                            <table cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; text-align: center; vertical-align: middle; font-size: 48px; line-height: 80px; margin-bottom: 20px;">✓</td>
                                </tr>
                            </table>
                            <h1 style="margin: 20px 0 0 0; color: #ffffff; font-size: 28px; font-weight: 700;">Application Received!</h1>
                            <p style="margin: 10px 0 0 0; color: #bfdbfe; font-size: 16px;">We\'re reviewing your reseller application</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Thank you for your interest in becoming an Aakaari reseller! We\'ve successfully received your application and our team will review it shortly.
                            </p>
                            
                            <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; padding: 24px; margin: 30px 0; border: 2px solid #3b82f6;">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 8px 0; color: #1e40af; font-size: 14px; font-weight: 600;">Application ID:</td>
                                        <td style="padding: 8px 0; color: #1e3a8a; font-size: 14px; text-align: right;">#' . esc_html($application_id) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #1e40af; font-size: 14px; font-weight: 600;">Submitted:</td>
                                        <td style="padding: 8px 0; color: #1e3a8a; font-size: 14px; text-align: right;">' . esc_html($submitted_date) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #1e40af; font-size: 14px; font-weight: 600;">Review Time:</td>
                                        <td style="padding: 8px 0; color: #1e3a8a; font-size: 14px; text-align: right;">24-48 hours</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style="background-color: #f9fafb; border-radius: 12px; padding: 24px; margin: 30px 0;">
                                <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px; font-weight: 700;">📋 What Happens Next?</h3>
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td style="padding: 12px 0; vertical-align: middle; width: 50px;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; text-align: center; vertical-align: middle; color: #ffffff; font-weight: 700; font-size: 16px; line-height: 36px;">1</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="padding: 12px 0 12px 10px; vertical-align: middle;">
                                            <p style="margin: 0; color: #374151; font-size: 15px; line-height: 1.6;"><strong>Document Verification</strong><br><span style="color: #6b7280;">We\'ll verify your KYC documents (Aadhaar, PAN, Bank details)</span></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; vertical-align: middle; width: 50px;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; text-align: center; vertical-align: middle; color: #ffffff; font-weight: 700; font-size: 16px; line-height: 36px;">2</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="padding: 12px 0 12px 10px; vertical-align: middle;">
                                            <p style="margin: 0; color: #374151; font-size: 15px; line-height: 1.6;"><strong>Profile Review</strong><br><span style="color: #6b7280;">Our team reviews your business profile and eligibility</span></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; vertical-align: middle; width: 50px;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 36px; height: 36px; background: #3b82f6; border-radius: 50%; text-align: center; vertical-align: middle; color: #ffffff; font-weight: 700; font-size: 16px; line-height: 36px;">3</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="padding: 12px 0 12px 10px; vertical-align: middle;">
                                            <p style="margin: 0; color: #374151; font-size: 15px; line-height: 1.6;"><strong>Approval Email</strong><br><span style="color: #6b7280;">You\'ll receive login credentials and dashboard access</span></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; vertical-align: middle; width: 50px;">
                                            <table cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="width: 36px; height: 36px; background: #10b981; border-radius: 50%; text-align: center; vertical-align: middle; color: #ffffff; font-weight: 700; font-size: 16px; line-height: 36px;">4</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td style="padding: 12px 0 12px 10px; vertical-align: middle;">
                                            <p style="margin: 0; color: #374151; font-size: 15px; line-height: 1.6;"><strong>Start Earning!</strong><br><span style="color: #6b7280;">Access products, share links, and start earning commissions</span></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; color: #92400e; font-size: 16px; font-weight: 700;">💡 Pro Tip</p>
                                <p style="margin: 0; color: #78350f; font-size: 15px; line-height: 1.6;">
                                    While you wait, check out our <a href="' . esc_url(home_url('/products/')) . '" style="color: #d97706; text-decoration: underline;">product catalog</a> and start planning which items you\'d like to promote!
                                </p>
                            </div>
                            
                            <p style="margin: 30px 0 0 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                <strong>Questions?</strong> Feel free to reach out to our support team anytime. We\'re here to help you succeed!
                            </p>
                        </td>
                    </tr>
';
    
    return $header . $body . $footer;
}

/**
 * Application Deleted Email Template
 */
function aakaari_email_application_deleted($data) {
    $name = $data['name'] ?? 'User';
    $apply_url = home_url('/become-a-reseller/');
    
    $header = aakaari_get_email_header('Application Removed - Aakaari');
    $footer = aakaari_get_email_footer();
    
    $body = '
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">Application Removed</h1>
                            <p style="margin: 10px 0 0 0; color: #e5e7eb; font-size: 16px;">Your reseller application has been removed</p>
                        </td>
                    </tr>
                    
                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Your previous reseller application has been removed from our system.
                            </p>
                            
                            <div style="background-color: #f3f4f6; border-left: 4px solid #6b7280; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <p style="margin: 0; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                    You can submit a fresh application anytime. All previous restrictions or cooldown periods have been cleared.
                                </p>
                            </div>
                            
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($apply_url) . '" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    Submit New Application →
                                </a>
                            </div>
                        </td>
                    </tr>
';
    
    return $header . $body . $footer;
}


/**
 * Password Reset Email Template
 *
 * @param string $to_email User's email address
 * @param string $name User's display name
 * @param string $reset_link Password reset link
 * @return bool Whether the email was sent successfully
 */
function aakaari_send_password_reset_email($to_email, $name, $reset_link) {
    $header = aakaari_get_email_header('Password Reset - Aakaari');
    $footer = aakaari_get_email_footer();

    $body = '
                    <!-- Header with Logo/Brand -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🔐 Password Reset Request</h1>
                            <p style="margin: 10px 0 0 0; color: #bfdbfe; font-size: 16px;">Reset your account password</p>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                Hi <strong>' . esc_html($name) . '</strong>,
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                We received a request to reset the password for your Aakaari account. If you made this request, click the button below to create a new password:
                            </p>

                            <div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($reset_link) . '" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    Reset My Password →
                                </a>
                            </div>

                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; color: #92400e; font-size: 15px; font-weight: 600;">
                                    ⏱️ Important Information:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #92400e; font-size: 14px; line-height: 1.8;">
                                    <li>This link will expire in <strong>24 hours</strong></li>
                                    <li>For security, you can only use this link once</li>
                                    <li>After resetting, you will need to login with your new password</li>
                                </ul>
                            </div>

                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #374151; line-height: 1.6;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>

                            <div style="background-color: #f3f4f6; padding: 15px; border-radius: 6px; word-break: break-all; font-family: monospace; font-size: 13px; color: #4b5563;">
                                ' . esc_html($reset_link) . '
                            </div>

                            <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 30px 0; border-radius: 8px;">
                                <p style="margin: 0 0 10px 0; color: #991b1b; font-size: 15px; font-weight: 600;">
                                    🚨 Did not request this?
                                </p>
                                <p style="margin: 0; color: #991b1b; font-size: 14px; line-height: 1.6;">
                                    If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged, and your account is secure.
                                </p>
                            </div>

                            <p style="margin: 30px 0 0 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                                Best regards,<br>
                                <strong style="color: #374151;">The Aakaari Team</strong>
                            </p>
                        </td>
                    </tr>
';

    $html_message = $header . $body . $footer;

    // Set email headers for HTML
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: Aakaari <noreply@aakaari.com>'
    );

    // Send email
    return wp_mail($to_email, 'Password Reset Request - Aakaari', $html_message, $headers);
}
