<?php
/**
 * Template Name: Admin Dashboard
 * 
 * Custom admin dashboard for Aakaari
 */

// Redirect if not admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(home_url('/adminlogin/'));
    exit;
}

// Get current admin user info
$current_user = wp_get_current_user();
$admin_email = $current_user->user_email;
$admin_display_name = $current_user->display_name;
$admin_initials = substr($admin_display_name, 0, 1);

// Mock data for dashboard
// In a real implementation, you would fetch this data from your database
$stats = array(
    'totalResellers' => 1247,
    'activeResellers' => 1089,
    'pendingApplications' => 23,
    'totalOrders' => 5432,
    'todayOrders' => 89,
    'totalRevenue' => 2847650,
    'thisMonthRevenue' => 456780,
    'pendingPayouts' => 125340
);

// Fetch actual reseller applications
$applications_query = new WP_Query(array(
    'post_type' => 'reseller_application',
    'posts_per_page' => -1, // Get all applications
    'orderby' => 'date',
    'order' => 'DESC',
    'tax_query' => array(
        // Optionally filter by status, e.g., 'pending' by default
        // array(
        //     'taxonomy' => 'reseller_application_status',
        //     'field'    => 'slug',
        //     'terms'    => 'pending',
        // ),
    ),
));

$applications = array();
if ($applications_query->have_posts()) {
    while ($applications_query->have_posts()) {
        $applications_query->the_post();
        $post_id = get_the_ID();
        $status_terms = wp_get_post_terms($post_id, 'reseller_application_status');
        $status = !empty($status_terms) ? $status_terms[0]->slug : 'pending'; // Default to pending if no status set

        $applications[] = array(
            'id' => $post_id, // Use Post ID
            'name' => get_post_meta($post_id, 'reseller_name', true),
            'email' => get_post_meta($post_id, 'reseller_email', true),
            'phone' => get_post_meta($post_id, 'reseller_phone', true),
            'businessName' => get_post_meta($post_id, 'reseller_business', true),
            'businessType' => get_post_meta($post_id, 'reseller_business_type', true), // Assuming you save business_type meta
            'city' => get_post_meta($post_id, 'reseller_city', true),
            'state' => get_post_meta($post_id, 'reseller_state', true),
            'appliedDate' => get_the_date('Y-m-d'),
            'status' => $status
        );
    }
    wp_reset_postdata(); // Important after custom WP_Query
}

// NOTE: You might need to add 'reseller_business_type' to the saved meta fields
// in become-a-reseller.php or reseller-application.php if it's not already there.

// Mock resellers data
$resellers = array(
    array(
        'id' => '1',
        'name' => 'Vikram Singh',
        'email' => 'vikram@example.com',
        'phone' => '+91 9876543213',
        'totalOrders' => 145,
        'totalRevenue' => 287500,
        'commission' => 28750,
        'status' => 'active',
        'joinedDate' => '2025-01-15'
    ),
    array(
        'id' => '2',
        'name' => 'Anita Desai',
        'email' => 'anita@example.com',
        'phone' => '+91 9876543214',
        'totalOrders' => 89,
        'totalRevenue' => 156700,
        'commission' => 15670,
        'status' => 'active',
        'joinedDate' => '2025-02-20'
    ),
    array(
        'id' => '3',
        'name' => 'Mohammed Ali',
        'email' => 'mohammed@example.com',
        'phone' => '+91 9876543215',
        'totalOrders' => 234,
        'totalRevenue' => 456800,
        'commission' => 45680,
        'status' => 'active',
        'joinedDate' => '2024-12-10'
    )
);

// Mock orders data
$orders = array(
    array(
        'id' => '1',
        'orderId' => 'ORD-2025-1234',
        'reseller' => 'Vikram Singh',
        'customer' => 'Ramesh Verma',
        'products' => 3,
        'amount' => 1899,
        'status' => 'processing',
        'date' => '2025-10-20',
        'paymentStatus' => 'paid'
    ),
    array(
        'id' => '2',
        'orderId' => 'ORD-2025-1235',
        'reseller' => 'Anita Desai',
        'customer' => 'Sunita Rao',
        'products' => 5,
        'amount' => 2499,
        'status' => 'shipped',
        'date' => '2025-10-19',
        'paymentStatus' => 'paid'
    ),
    array(
        'id' => '3',
        'orderId' => 'ORD-2025-1236',
        'reseller' => 'Mohammed Ali',
        'customer' => 'Deepak Joshi',
        'products' => 2,
        'amount' => 1299,
        'status' => 'pending',
        'date' => '2025-10-20',
        'paymentStatus' => 'pending'
    )
);

// Enqueue styles and scripts
wp_enqueue_style('aakaari-admin-dashboard-style', get_template_directory_uri() . '/assets/css/admindashboard.css', array(), '1.0.0');
wp_enqueue_script('aakaari-admin-dashboard-script', get_template_directory_uri() . '/assets/js/admindashboard.js', array('jquery'), '1.0.0', true);

// Get active tab from URL or default to overview
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

get_header('minimal'); // Use a minimal header or create one
?>

<div class="aakaari-admin-dashboard">
    <!-- Admin Header -->
    <header class="aakaari-admin-header">
        <div class="aakaari-header-container">
            <div class="aakaari-header-left">
                <h1 class="aakaari-admin-title">Aakaari Admin</h1>
                <div class="aakaari-admin-badge">Administrator</div>
            </div>
            <div class="aakaari-header-right">
                <button class="aakaari-notification-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span class="aakaari-notification-badge"><?php echo esc_html($stats['pendingApplications']); ?></span>
                </button>
                <div class="aakaari-user-profile">
                    <div class="aakaari-avatar">
                        <span><?php echo esc_html($admin_initials); ?></span>
                    </div>
                    <div class="aakaari-user-info">
                        <div class="aakaari-user-name"><?php echo esc_html($admin_display_name); ?></div>
                        <div class="aakaari-user-email"><?php echo esc_html($admin_email); ?></div>
                    </div>
                    <div class="aakaari-dropdown">
                        <button class="aakaari-dropdown-trigger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                        </button>
                        <div class="aakaari-dropdown-content">
                            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="aakaari-dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="aakaari-admin-body">
        <!-- Sidebar Navigation -->
        <aside class="aakaari-admin-sidebar">
            <nav class="aakaari-admin-nav">
                <a href="<?php echo esc_url(add_query_arg('tab', 'overview')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Overview
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'applications')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'applications' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                    Applications
                    <?php if ($stats['pendingApplications'] > 0): ?>
                        <span class="aakaari-nav-badge"><?php echo esc_html($stats['pendingApplications']); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'resellers')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'resellers' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Resellers
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'orders')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    Orders
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'products')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'products' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    Products
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'payouts')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'payouts' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    Payouts
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'settings')); ?>" 
                   class="aakaari-nav-item <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="aakaari-admin-content">
            <!-- Overview Tab -->
            <?php if ($active_tab === 'overview'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header">
                        <h2>Dashboard Overview</h2>
                        <p>Welcome back! Here's what's happening with your platform.</p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="aakaari-stats-grid">
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <div>
                                    <p class="aakaari-stat-label">Total Resellers</p>
                                    <p class="aakaari-stat-value"><?php echo esc_html($stats['totalResellers']); ?></p>
                                    <p class="aakaari-stat-trend trend-up">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                        +12% from last month
                                    </p>
                                </div>
                                <svg class="aakaari-stat-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                        </div>

                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <div>
                                    <p class="aakaari-stat-label">Total Orders</p>
                                    <p class="aakaari-stat-value"><?php echo esc_html($stats['totalOrders']); ?></p>
                                    <p class="aakaari-stat-trend trend-up">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                        +<?php echo esc_html($stats['todayOrders']); ?> today
                                    </p>
                                </div>
                                <svg class="aakaari-stat-icon icon-green" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            </div>
                        </div>

                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <div>
                                    <p class="aakaari-stat-label">Total Revenue</p>
                                    <p class="aakaari-stat-value">₹<?php echo esc_html(round($stats['totalRevenue'] / 100000, 1)); ?>L</p>
                                    <p class="aakaari-stat-trend trend-up">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                        +₹<?php echo esc_html(round($stats['thisMonthRevenue'] / 1000, 0)); ?>K this month
                                    </p>
                                </div>
                                <svg class="aakaari-stat-icon icon-emerald" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            </div>
                        </div>

                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <div>
                                    <p class="aakaari-stat-label">Pending Applications</p>
                                    <p class="aakaari-stat-value"><?php echo esc_html($stats['pendingApplications']); ?></p>
                                    <p class="aakaari-stat-trend trend-attention">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12"></polyline><polyline points="12 16 12.01 16"></polyline></svg>
                                        Needs attention
                                    </p>
                                </div>
                                <svg class="aakaari-stat-icon icon-orange" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="aakaari-recent-activity">
                        <div class="aakaari-activity-card">
                            <div class="aakaari-card-header">
                                <h3>Recent Orders</h3>
                            </div>
                            <div class="aakaari-card-content">
                                <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                                    <div class="aakaari-activity-item">
                                        <div>
                                            <div class="aakaari-item-title"><?php echo esc_html($order['orderId']); ?></div>
                                            <div class="aakaari-item-subtitle"><?php echo esc_html($order['reseller']); ?></div>
                                        </div>
                                        <div class="aakaari-item-details">
                                            <div class="aakaari-item-amount">₹<?php echo esc_html($order['amount']); ?></div>
                                            <span class="aakaari-status-badge status-<?php echo esc_attr($order['status']); ?>">
                                                <?php echo esc_html(ucfirst($order['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="aakaari-activity-card">
                            <div class="aakaari-card-header">
                                <h3>Pending Applications</h3>
                            </div>
                            <div class="aakaari-card-content">
                                <?php foreach ($applications as $app): ?>
                                    <div class="aakaari-activity-item">
                                        <div>
                                            <div class="aakaari-item-title"><?php echo esc_html($app['name']); ?></div>
                                            <div class="aakaari-item-subtitle"><?php echo esc_html($app['businessType']); ?></div>
                                        </div>
                                        <button class="aakaari-button aakaari-button-sm" 
                                                data-application-id="<?php echo esc_attr($app['id']); ?>">
                                            Review
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Applications Tab -->
            <?php if ($active_tab === 'applications'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header aakaari-flex-between">
                        <div>
                            <h2>Reseller Applications</h2>
                            <p>Review and approve new reseller registrations</p>
                        </div>
                        <div class="aakaari-actions">
                            <select class="aakaari-select">
                                <option value="all">All Applications</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-content">
                            <table class="aakaari-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Business</th>
                                        <th>Location</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <div><?php echo esc_html($app['name']); ?></div>
                                                    <div class="aakaari-table-subtitle"><?php echo esc_html($app['phone']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($app['email']); ?></td>
                                            <td>
                                                <div>
                                                    <div><?php echo !empty($app['businessName']) ? esc_html($app['businessName']) : 'Individual'; ?></div>
                                                    <div class="aakaari-table-subtitle"><?php echo esc_html($app['businessType']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($app['city'] . ', ' . $app['state']); ?></td>
                                            <td><?php echo esc_html($app['appliedDate']); ?></td>
                                            <td>
                                                <span class="aakaari-status-badge status-<?php echo esc_attr($app['status']); ?>">
                                                    <?php echo esc_html(ucfirst($app['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="aakaari-button aakaari-button-sm aakaari-button-outline"
                                                        data-application-id="<?php echo esc_attr($app['id']); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                    Review
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Resellers Tab -->
            <?php if ($active_tab === 'resellers'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header aakaari-flex-between">
                        <div>
                            <h2>Manage Resellers</h2>
                            <p>View and manage all registered resellers</p>
                        </div>
                        <div class="aakaari-actions">
                            <div class="aakaari-search">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" class="aakaari-search-input" placeholder="Search resellers...">
                            </div>
                            <button class="aakaari-button aakaari-button-outline">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                Filter
                            </button>
                        </div>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-content">
                            <table class="aakaari-table">
                                <thead>
                                    <tr>
                                        <th>Reseller</th>
                                        <th>Contact</th>
                                        <th>Total Orders</th>
                                        <th>Revenue Generated</th>
                                        <th>Commission</th>
                                        <th>Joined Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resellers as $reseller): ?>
                                        <tr>
                                            <td>
                                                <div class="aakaari-user-row">
                                                    <div class="aakaari-avatar">
                                                        <?php 
                                                            $initials = '';
                                                            $name_parts = explode(' ', $reseller['name']);
                                                            foreach ($name_parts as $part) {
                                                                $initials .= substr($part, 0, 1);
                                                            }
                                                        ?>
                                                        <span><?php echo esc_html($initials); ?></span>
                                                    </div>
                                                    <div><?php echo esc_html($reseller['name']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><?php echo esc_html($reseller['email']); ?></div>
                                                    <div class="aakaari-table-subtitle"><?php echo esc_html($reseller['phone']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($reseller['totalOrders']); ?></td>
                                            <td>₹<?php echo esc_html(number_format($reseller['totalRevenue'])); ?></td>
                                            <td class="aakaari-text-green">₹<?php echo esc_html(number_format($reseller['commission'])); ?></td>
                                            <td><?php echo esc_html($reseller['joinedDate']); ?></td>
                                            <td>
                                                <span class="aakaari-status-badge status-<?php echo esc_attr($reseller['status']); ?>">
                                                    <?php echo esc_html(ucfirst($reseller['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="aakaari-dropdown">
                                                    <button class="aakaari-dropdown-trigger aakaari-button-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                                    </button>
                                                    <div class="aakaari-dropdown-content">
                                                        <a href="#" class="aakaari-dropdown-item">View Details</a>
                                                        <a href="#" class="aakaari-dropdown-item">View Orders</a>
                                                        <a href="#" class="aakaari-dropdown-item">Suspend Account</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Orders Tab -->
            <?php if ($active_tab === 'orders'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header aakaari-flex-between">
                        <div>
                            <h2>Order Management</h2>
                            <p>Monitor and manage all platform orders</p>
                        </div>
                        <div class="aakaari-actions">
                            <select class="aakaari-select">
                                <option value="all">All Orders</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                            </select>
                            <button class="aakaari-button aakaari-button-outline">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Export
                            </button>
                        </div>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-content">
                            <table class="aakaari-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Reseller</th>
                                        <th>Customer</th>
                                        <th>Products</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo esc_html($order['orderId']); ?></td>
                                            <td><?php echo esc_html($order['reseller']); ?></td>
                                            <td><?php echo esc_html($order['customer']); ?></td>
                                            <td><?php echo esc_html($order['products']); ?> items</td>
                                            <td>₹<?php echo esc_html($order['amount']); ?></td>
                                            <td><?php echo esc_html($order['date']); ?></td>
                                            <td>
                                                <span class="aakaari-status-badge status-<?php echo esc_attr($order['paymentStatus']); ?>">
                                                    <?php echo esc_html(ucfirst($order['paymentStatus'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="aakaari-status-badge status-<?php echo esc_attr($order['status']); ?>">
                                                    <?php echo esc_html(ucfirst($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="aakaari-dropdown">
                                                    <button class="aakaari-dropdown-trigger aakaari-button-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                                    </button>
                                                    <div class="aakaari-dropdown-content">
                                                        <a href="#" class="aakaari-dropdown-item">View Details</a>
                                                        <a href="#" class="aakaari-dropdown-item">Update Status</a>
                                                        <a href="#" class="aakaari-dropdown-item">Download Invoice</a>
                                                        <a href="#" class="aakaari-dropdown-item">Contact Reseller</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Products Tab -->
            <?php if ($active_tab === 'products'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header aakaari-flex-between">
                        <div>
                            <h2>Product Management</h2>
                            <p>Manage your product catalog and pricing</p>
                        </div>
                        <button class="aakaari-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            Add New Product
                        </button>
                    </div>

                    <div class="aakaari-stats-grid aakaari-stats-grid-3">
                        <div class="aakaari-stat-card aakaari-stat-center">
                            <div class="aakaari-stat-icon-large">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                            </div>
                            <p class="aakaari-stat-value">156</p>
                            <p class="aakaari-stat-label">Total Products</p>
                        </div>
                        
                        <div class="aakaari-stat-card aakaari-stat-center">
                            <div class="aakaari-stat-icon-large icon-green">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <p class="aakaari-stat-value">142</p>
                            <p class="aakaari-stat-label">In Stock</p>
                        </div>
                        
                        <div class="aakaari-stat-card aakaari-stat-center">
                            <div class="aakaari-stat-icon-large icon-orange">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            </div>
                            <p class="aakaari-stat-value">14</p>
                            <p class="aakaari-stat-label">Low Stock</p>
                        </div>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-content aakaari-text-center aakaari-py-8">
                            <p class="aakaari-text-muted">Product management interface will be displayed here</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Payouts Tab -->
            <?php if ($active_tab === 'payouts'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header aakaari-flex-between">
                        <div>
                            <h2>Commission & Payouts</h2>
                            <p>Manage reseller commissions and wallet payouts</p>
                        </div>
                        <button class="aakaari-button aakaari-button-green">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Process Payouts
                        </button>
                    </div>

                    <div class="aakaari-stats-grid aakaari-stats-grid-3">
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <p class="aakaari-stat-label">Pending Payouts</p>
                                <p class="aakaari-stat-value">₹<?php echo esc_html(number_format($stats['pendingPayouts'])); ?></p>
                                <p class="aakaari-stat-trend trend-attention">47 resellers pending</p>
                            </div>
                        </div>
                        
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <p class="aakaari-stat-label">This Month Paid</p>
                                <p class="aakaari-stat-value">₹234,500</p>
                                <p class="aakaari-stat-trend trend-up">89 transactions</p>
                            </div>
                        </div>
                        
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <p class="aakaari-stat-label">Total Paid (Lifetime)</p>
                                <p class="aakaari-stat-value">₹12.5L</p>
                                <p class="aakaari-stat-trend">1,247 resellers</p>
                            </div>
                        </div>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-content aakaari-text-center aakaari-py-8">
                            <p class="aakaari-text-muted">Payout management interface will be displayed here</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Settings Tab -->
            <?php if ($active_tab === 'settings'): ?>
                <div class="aakaari-tab-content">
                    <div class="aakaari-tab-header">
                        <h2>Platform Settings</h2>
                        <p>Configure platform settings and preferences</p>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-content aakaari-text-center aakaari-py-8">
                            <p class="aakaari-text-muted">Settings interface will be displayed here</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Application Review Modal -->
    <div class="aakaari-modal" id="applicationModal">
        <div class="aakaari-modal-overlay"></div>
        <div class="aakaari-modal-container">
            <div class="aakaari-modal-header">
                <h3>Review Application</h3>
                <p>Review the reseller application details and approve or reject</p>
            </div>
            
            <div class="aakaari-modal-body">
                <div id="applicationDetails"></div>
                
                <div class="aakaari-form-group">
                    <label for="rejectionReason">Rejection Reason (if rejecting)</label>
                    <textarea id="rejectionReason" class="aakaari-textarea" placeholder="Please provide a reason for rejection..."></textarea>
                </div>
            </div>
            
            <div class="aakaari-modal-footer">
                <button class="aakaari-button aakaari-button-outline" id="closeModalBtn">Close</button>
                <button class="aakaari-button aakaari-button-red" id="rejectAppBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    Reject
                </button>
                <button class="aakaari-button aakaari-button-green" id="approveAppBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Approve
                </button>
            </div>
        </div>
    </div>
</div>

<?php get_footer('minimal'); ?>