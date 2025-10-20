// When admin approves application
function approve_reseller_application($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reseller_applications';
    
    // Update application status
    $wpdb->update(
        $table_name,
        array('status' => 'approved'),
        array('user_id' => $user_id)
    );
    
    // Update user meta
    update_user_meta($user_id, 'onboarding_status', 'approved');
    
    // Send approval email
    // ...existing code...
}