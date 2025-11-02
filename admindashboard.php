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

// Get real-time dashboard statistics
$stats = aakaari_get_dashboard_stats();

// Get active tab from URL or default to overview
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

// Handle filtering and pagination
$current_page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$items_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// Fetch applications with filtering
$applications_args = array(
    'status' => $filter_status,
    'search' => $search_term,
    'posts_per_page' => $items_per_page,
    'paged' => $current_page
);
$applications_data = aakaari_get_applications($applications_args);
$applications = $applications_data['applications'];

// Fetch resellers with filtering
$resellers_args = array(
    'status' => $filter_status,
    'search' => $search_term,
    'number' => $items_per_page,
    'paged' => $current_page
);
$resellers_data = aakaari_get_resellers($resellers_args);
$resellers = $resellers_data['resellers'];

// Fetch orders with filtering
$orders_args = array(
    'status' => $filter_status,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'limit' => $items_per_page,
    'paged' => $current_page
);
$orders_data = aakaari_get_orders($orders_args);
$orders = $orders_data['orders'];

// Fetch product statistics
$product_stats = aakaari_get_product_stats();

// Fetch payout statistics
$payout_stats = aakaari_get_payout_stats();

// Format the current date for display
$current_date = date('F j, Y');

// Note: Scripts and styles are enqueued via wp_enqueue_scripts hook in admin-dashboard-functions.php

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
<!-- Replace the current notification bell button with this dropdown implementation -->
<div class="aakaari-dropdown aakaari-notification-dropdown">
    <button class="aakaari-notification-button aakaari-dropdown-trigger" id="notification-bell-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <?php if ($stats['unseenNotifications'] > 0): ?>
            <span class="aakaari-notification-badge" id="notification-badge"><?php echo esc_html($stats['unseenNotifications']); ?></span>
        <?php endif; ?>
    </button>
    
    <div class="aakaari-dropdown-content aakaari-notification-dropdown-content">
        <div class="aakaari-notification-header">
            <h4>Notifications</h4>
            <?php if ($stats['pendingApplications'] > 0): ?>
                <a href="<?php echo esc_url(add_query_arg('tab', 'applications')); ?>" class="aakaari-notification-action">View All</a>
            <?php endif; ?>
        </div>
        
        <div class="aakaari-notification-list">
            <?php 
            $pending_apps_args = array(
                'status' => 'pending',
                'posts_per_page' => 5
            );
            $pending_apps_data = aakaari_get_applications($pending_apps_args);
            $pending_apps = $pending_apps_data['applications'];
            
            if (!empty($pending_apps)): 
                foreach ($pending_apps as $app): 
            ?>
                <div class="aakaari-notification-item">
                    <div class="aakaari-notification-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                    </div>
                    <div class="aakaari-notification-content">
                        <div class="aakaari-notification-title">New Application</div>
                        <div class="aakaari-notification-message"><?php echo esc_html($app['name']); ?> has applied for a reseller account.</div>
                        <div class="aakaari-notification-time"><?php echo esc_html(human_time_diff(strtotime($app['appliedDate']), current_time('timestamp'))); ?> ago</div>
                    </div>
                </div>
            <?php 
                endforeach;
            else: 
            ?>
                <div class="aakaari-notification-empty">No new notifications</div>
            <?php endif; ?>
        </div>
    </div>
</div>
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
                        <p>Welcome back! Here's what's happening with your platform as of <?php echo esc_html($current_date); ?></p>
                    </div>

                    <!-- Stats Grid -->
                    <div class="aakaari-stats-grid">
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <div>
                                    <p class="aakaari-stat-label">Total Resellers</p>
                                    <p class="aakaari-stat-value"><?php echo esc_html(number_format($stats['totalResellers'])); ?></p>
                                    <p class="aakaari-stat-trend trend-up">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                        <?php echo esc_html($stats['activeResellers']); ?> active resellers
                                    </p>
                                </div>
                                <svg class="aakaari-stat-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                        </div>

                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <div>
                                    <p class="aakaari-stat-label">Total Orders</p>
                                    <p class="aakaari-stat-value"><?php echo esc_html(number_format($stats['totalOrders'])); ?></p>
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
                                    <p class="aakaari-stat-value">
                                        <?php 
                                        if ($stats['totalRevenue'] >= 100000) {
                                            echo '₹' . esc_html(round($stats['totalRevenue'] / 100000, 1)) . 'L';
                                        } else {
                                            echo '₹' . esc_html(number_format($stats['totalRevenue']));
                                        }
                                        ?>
                                    </p>
                                    <p class="aakaari-stat-trend trend-up">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                                        +₹<?php echo esc_html(number_format(round($stats['thisMonthRevenue']))); ?> this month
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
                                    <?php if ($stats['pendingApplications'] > 0): ?>
                                        <p class="aakaari-stat-trend trend-attention">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12"></polyline><polyline points="12 16 12.01 16"></polyline></svg>
                                            Needs attention
                                        </p>
                                    <?php else: ?>
                                        <p class="aakaari-stat-trend">
                                            All clear
                                        </p>
                                    <?php endif; ?>
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
                                <?php 
                                $recent_orders_args = array(
                                    'limit' => 3,
                                    'paged' => 1,
                                    'orderby' => 'date',
                                    'order' => 'DESC'
                                );
                                $recent_orders_data = aakaari_get_orders($recent_orders_args);
                                $recent_orders = $recent_orders_data['orders'];
                                
                                if (!empty($recent_orders)): 
                                    foreach ($recent_orders as $order): 
                                ?>
                                    <div class="aakaari-activity-item">
                                        <div>
                                            <div class="aakaari-item-title"><?php echo esc_html($order['orderId']); ?></div>
                                            <div class="aakaari-item-subtitle"><?php echo esc_html($order['reseller']); ?></div>
                                        </div>
                                        <div class="aakaari-item-details">
                                            <div class="aakaari-item-amount">₹<?php echo esc_html(number_format($order['amount'])); ?></div>
                                            <span class="aakaari-status-badge status-<?php echo esc_attr($order['status']); ?>">
                                                <?php echo esc_html(ucfirst($order['status'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach; 
                                else: 
                                ?>
                                    <div class="aakaari-activity-item">
                                        <p>No recent orders found.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="aakaari-activity-card">
                            <div class="aakaari-card-header">
                                <h3>Pending Applications</h3>
                            </div>
                            <div class="aakaari-card-content">
                                <?php 
                                $pending_apps_args = array(
                                    'status' => 'pending',
                                    'posts_per_page' => 3
                                );
                                $pending_apps_data = aakaari_get_applications($pending_apps_args);
                                $pending_apps = $pending_apps_data['applications'];
                                
                                if (!empty($pending_apps)): 
                                    foreach ($pending_apps as $app): 
                                ?>
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
                                <?php 
                                    endforeach;
                                else: 
                                ?>
                                    <div class="aakaari-activity-item">
                                        <p>No pending applications.</p>
                                    </div>
                                <?php endif; ?>
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
                            <form id="applications-filter" method="get" class="aakaari-filter-form">
                                <input type="hidden" name="tab" value="applications">
                                
                                <select name="status" class="aakaari-select" onchange="this.form.submit()">
                                    <option value="" <?php selected($filter_status, ''); ?>>All Applications</option>
                                    <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pending</option>
                                    <option value="approved" <?php selected($filter_status, 'approved'); ?>>Approved</option>
                                    <option value="rejected" <?php selected($filter_status, 'rejected'); ?>>Rejected</option>
                                </select>
                                
                                <input type="text" name="search" class="aakaari-search-input" placeholder="Search applicants..." value="<?php echo esc_attr($search_term); ?>">
                                <button type="submit" class="aakaari-button aakaari-button-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    Search
                                </button>
                            </form>
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
                                    <?php if (!empty($applications)): ?>
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
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="aakaari-text-center aakaari-py-8">
                                                <p class="aakaari-text-muted">No applications found matching your criteria.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <?php if ($applications_data['max_pages'] > 1): ?>
                                <div class="aakaari-pagination">
                                    <?php
                                    // Generate pagination links
                                    $pagination_args = array_merge($_GET, array('tab' => 'applications'));
                                    
                                    // Previous page link
                                    if ($current_page > 1) {
                                        $prev_args = array_merge($pagination_args, array('page_num' => $current_page - 1));
                                        echo '<a href="' . esc_url(add_query_arg($prev_args)) . '" class="aakaari-pagination-link">Previous</a>';
                                    } else {
                                        echo '<span class="aakaari-pagination-link disabled">Previous</span>';
                                    }
                                    
                                    // Page numbers
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($applications_data['max_pages'], $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $page_args = array_merge($pagination_args, array('page_num' => $i));
                                        if ($i == $current_page) {
                                            echo '<span class="aakaari-pagination-link active">' . $i . '</span>';
                                        } else {
                                            echo '<a href="' . esc_url(add_query_arg($page_args)) . '" class="aakaari-pagination-link">' . $i . '</a>';
                                        }
                                    }
                                    
                                    // Next page link
                                    if ($current_page < $applications_data['max_pages']) {
                                        $next_args = array_merge($pagination_args, array('page_num' => $current_page + 1));
                                        echo '<a href="' . esc_url(add_query_arg($next_args)) . '" class="aakaari-pagination-link">Next</a>';
                                    } else {
                                        echo '<span class="aakaari-pagination-link disabled">Next</span>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
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
                            <form id="resellers-filter" method="get" class="aakaari-filter-form">
                                <input type="hidden" name="tab" value="resellers">
                                
                                <div class="aakaari-search">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <input type="text" name="search" class="aakaari-search-input" placeholder="Search resellers..." value="<?php echo esc_attr($search_term); ?>">
                                </div>
                                
                                <select name="status" class="aakaari-select" onchange="this.form.submit()">
                                    <option value="" <?php selected($filter_status, ''); ?>>All Resellers</option>
                                    <option value="active" <?php selected($filter_status, 'active'); ?>>Active</option>
                                    <option value="inactive" <?php selected($filter_status, 'inactive'); ?>>Inactive</option>
                                    <option value="suspended" <?php selected($filter_status, 'suspended'); ?>>Suspended</option>
                                </select>
                                
                                <button type="submit" class="aakaari-button aakaari-button-outline">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                    Filter
                                </button>
                            </form>
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
                                    <?php if (!empty($resellers)): ?>
                                        <?php foreach ($resellers as $reseller): ?>
                                            <tr>
                                                <td>
                                                    <div class="aakaari-user-row">
                                                        <div class="aakaari-avatar">
                                                            <?php 
                                                                $initials = '';
                                                                $name_parts = explode(' ', $reseller['name']);
                                                                foreach ($name_parts as $part) {
                                                                    if (!empty($part)) {
                                                                        $initials .= substr($part, 0, 1);
                                                                    }
                                                                }
                                                                if (empty($initials)) {
                                                                    $initials = '?';
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
                                                            <a href="#" class="aakaari-dropdown-item" data-action="view-reseller" data-id="<?php echo esc_attr($reseller['id']); ?>">View Details</a>
                                                            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'orders', 'reseller_id' => $reseller['id']))); ?>" class="aakaari-dropdown-item">View Orders</a>
                                                            <?php if ($reseller['status'] === 'active'): ?>
                                                                <a href="#" class="aakaari-dropdown-item" data-action="suspend-reseller" data-id="<?php echo esc_attr($reseller['id']); ?>">Suspend Account</a>
                                                            <?php elseif ($reseller['status'] === 'suspended'): ?>
                                                                <a href="#" class="aakaari-dropdown-item" data-action="activate-reseller" data-id="<?php echo esc_attr($reseller['id']); ?>">Activate Account</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="aakaari-text-center aakaari-py-8">
                                                <p class="aakaari-text-muted">No resellers found matching your criteria.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <?php if ($resellers_data['max_pages'] > 1): ?>
                                <div class="aakaari-pagination">
                                    <?php
                                    // Generate pagination links
                                    $pagination_args = array_merge($_GET, array('tab' => 'resellers'));
                                    
                                    // Previous page link
                                    if ($current_page > 1) {
                                        $prev_args = array_merge($pagination_args, array('page_num' => $current_page - 1));
                                        echo '<a href="' . esc_url(add_query_arg($prev_args)) . '" class="aakaari-pagination-link">Previous</a>';
                                    } else {
                                        echo '<span class="aakaari-pagination-link disabled">Previous</span>';
                                    }
                                    
                                    // Page numbers
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($resellers_data['max_pages'], $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $page_args = array_merge($pagination_args, array('page_num' => $i));
                                        if ($i == $current_page) {
                                            echo '<span class="aakaari-pagination-link active">' . $i . '</span>';
                                        } else {
                                            echo '<a href="' . esc_url(add_query_arg($page_args)) . '" class="aakaari-pagination-link">' . $i . '</a>';
                                        }
                                    }
                                    
                                    // Next page link
                                    if ($current_page < $resellers_data['max_pages']) {
                                        $next_args = array_merge($pagination_args, array('page_num' => $current_page + 1));
                                        echo '<a href="' . esc_url(add_query_arg($next_args)) . '" class="aakaari-pagination-link">Next</a>';
                                    } else {
                                        echo '<span class="aakaari-pagination-link disabled">Next</span>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
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
                            <form id="orders-filter" method="get" class="aakaari-filter-form">
                                <input type="hidden" name="tab" value="orders">
                                
                                <select name="status" class="aakaari-select" onchange="this.form.submit()">
                                    <option value="" <?php selected($filter_status, ''); ?>>All Orders</option>
                                    <option value="pending" <?php selected($filter_status, 'pending'); ?>>Pending</option>
                                    <option value="processing" <?php selected($filter_status, 'processing'); ?>>Processing</option>
                                    <option value="shipped" <?php selected($filter_status, 'shipped'); ?>>Shipped</option>
                                    <option value="completed" <?php selected($filter_status, 'completed'); ?>>Delivered</option>
                                </select>
                                
                                <div class="aakaari-date-range">
                                    <input type="date" name="date_from" class="aakaari-input-date" value="<?php echo esc_attr($date_from); ?>" placeholder="From">
                                    <input type="date" name="date_to" class="aakaari-input-date" value="<?php echo esc_attr($date_to); ?>" placeholder="To">
                                </div>
                                
                                <button type="submit" class="aakaari-button aakaari-button-outline">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                    Filter
                                </button>
                                
                                <button type="button" class="aakaari-button aakaari-button-outline" id="exportOrdersBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                    Export
                                </button>
                            </form>
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
                                        <th>Customization</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orders)): ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?php echo esc_html($order['orderId']); ?></td>
                                                <td><?php echo esc_html($order['reseller']); ?></td>
                                                <td><?php echo esc_html($order['customer']); ?></td>
                                                <td><?php echo esc_html($order['products']); ?> items</td>
                                                <td style="text-align: center;">
                                                    <?php if (!empty($order['has_customization'])): ?>
                                                        <span style="display: inline-block; background: #2271b1; color: white; font-size: 10px; padding: 3px 8px; border-radius: 3px; font-weight: bold;">CUSTOM</span>
                                                    <?php else: ?>
                                                        <span style="color: #999;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>₹<?php echo esc_html(number_format($order['amount'])); ?></td>
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
                                                            <a href="#" class="aakaari-dropdown-item" data-action="view-order" data-id="<?php echo esc_attr($order['id']); ?>">View Details</a>
                                                            <a href="#" class="aakaari-dropdown-item" data-action="update-status" data-id="<?php echo esc_attr($order['id']); ?>">Update Status</a>
                                                            <a href="#" class="aakaari-dropdown-item" data-action="download-invoice" data-id="<?php echo esc_attr($order['id']); ?>">Download Invoice</a>
                                                            <?php if ($order['reseller_id'] > 0): ?>
                                                                <a href="mailto:<?php echo esc_attr($order['reseller_email']); ?>" class="aakaari-dropdown-item">Contact Reseller</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="aakaari-text-center aakaari-py-8">
                                                <p class="aakaari-text-muted">No orders found matching your criteria.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <?php if ($orders_data['max_pages'] > 1): ?>
                                <div class="aakaari-pagination">
                                    <?php
                                    // Generate pagination links
                                    $pagination_args = array_merge($_GET, array('tab' => 'orders'));
                                    
                                    // Previous page link
                                    if ($current_page > 1) {
                                        $prev_args = array_merge($pagination_args, array('page_num' => $current_page - 1));
                                        echo '<a href="' . esc_url(add_query_arg($prev_args)) . '" class="aakaari-pagination-link">Previous</a>';
                                    } else {
                                        echo '<span class="aakaari-pagination-link disabled">Previous</span>';
                                    }
                                    
                                    // Page numbers
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($orders_data['max_pages'], $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        $page_args = array_merge($pagination_args, array('page_num' => $i));
                                        if ($i == $current_page) {
                                            echo '<span class="aakaari-pagination-link active">' . $i . '</span>';
                                        } else {
                                            echo '<a href="' . esc_url(add_query_arg($page_args)) . '" class="aakaari-pagination-link">' . $i . '</a>';
                                        }
                                    }
                                    
                                    // Next page link
                                    if ($current_page < $orders_data['max_pages']) {
                                        $next_args = array_merge($pagination_args, array('page_num' => $current_page + 1));
                                        echo '<a href="' . esc_url(add_query_arg($next_args)) . '" class="aakaari-pagination-link">Next</a>';
                                    } else {
                                        echo '<span class="aakaari-pagination-link disabled">Next</span>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

<?php if ($active_tab === 'products'): ?>
    <div class="aakaari-tab-content">
        <div class="aakaari-tab-header">
            <div>
                <h2>Custom Print Products</h2>
                <p>Manage your customizable print products and options</p>
            </div>
        </div>

        <!-- Print Studio UI directly integrated -->
        <div id="custom-print-studio-app">
            <!-- This div will be populated by JavaScript -->
            <div class="cps-loading">
                <div class="aakaari-loading-spinner"></div>
                <p>Loading print studio interface...</p>
            </div>
        </div>
        
        <div id="dialog-container"></div>
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
                        <button class="aakaari-button aakaari-button-green" id="processPayoutsBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Process Payouts
                        </button>
                    </div>
                                        <div class="aakaari-stats-grid aakaari-stats-grid-3">
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <p class="aakaari-stat-label">Pending Payouts</p>
                                <p class="aakaari-stat-value">₹<?php echo esc_html(number_format($payout_stats['pendingAmount'])); ?></p>
                                <p class="aakaari-stat-trend trend-attention"><?php echo esc_html($payout_stats['pendingResellers']); ?> resellers pending</p>
                            </div>
                        </div>
                        
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <p class="aakaari-stat-label">This Month Paid</p>
                                <p class="aakaari-stat-value">₹<?php echo esc_html(number_format($payout_stats['monthPaid'])); ?></p>
                                <p class="aakaari-stat-trend trend-up"><?php echo esc_html($payout_stats['monthTransactions']); ?> transactions</p>
                            </div>
                        </div>
                        
                        <div class="aakaari-stat-card">
                            <div class="aakaari-stat-content">
                                <p class="aakaari-stat-label">Total Paid (Lifetime)</p>
                                <p class="aakaari-stat-value">
                                    <?php
                                    if ($payout_stats['lifetimePaid'] >= 100000) {
                                        echo '₹' . esc_html(round($payout_stats['lifetimePaid'] / 100000, 1)) . 'L';
                                    } else {
                                        echo '₹' . esc_html(number_format($payout_stats['lifetimePaid']));
                                    }
                                    ?>
                                </p>
                                <p class="aakaari-stat-trend"><?php echo esc_html($stats['totalResellers']); ?> resellers</p>
                            </div>
                        </div>
                    </div>

                    <div class="aakaari-card">
                        <div class="aakaari-card-header">
                            <h3>Pending Payouts</h3>
                        </div>
                        <div class="aakaari-card-content">
                            <?php
                            // Get resellers with pending payouts
                            $pending_payouts_args = array(
                                'status' => 'active',
                                'number' => 10,
                                'paged' => $current_page,
                                'meta_query' => array(
                                    array(
                                        'key' => 'wallet_balance',
                                        'value' => 0,
                                        'compare' => '>',
                                        'type' => 'NUMERIC'
                                    )
                                )
                            );
                            $pending_payouts_data = aakaari_get_resellers($pending_payouts_args);
                            $resellers_with_payouts = $pending_payouts_data['resellers'];
                            ?>
                            
                            <table class="aakaari-table">
                                <thead>
                                    <tr>
                                        <th>Reseller</th>
                                        <th>Email</th>
                                        <th>Total Sales</th>
                                        <th>Wallet Balance</th>
                                        <th>Last Payout</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($resellers_with_payouts)): ?>
                                        <?php foreach ($resellers_with_payouts as $reseller): ?>
                                            <tr>
                                                <td><?php echo esc_html($reseller['name']); ?></td>
                                                <td><?php echo esc_html($reseller['email']); ?></td>
                                                <td>₹<?php echo esc_html(number_format($reseller['totalRevenue'])); ?></td>
                                                <td class="aakaari-text-green">₹<?php echo esc_html(number_format($reseller['walletBalance'])); ?></td>
                                                <td>
                                                    <?php
                                                    // Get last payout date (mock data for now)
                                                    $last_payout = '2025-09-15';
                                                    echo esc_html($last_payout);
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="aakaari-button aakaari-button-sm aakaari-button-green" data-action="process-payout" data-id="<?php echo esc_attr($reseller['id']); ?>" data-amount="<?php echo esc_attr($reseller['walletBalance']); ?>">
                                                        Process Payout
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="aakaari-text-center aakaari-py-8">
                                                <p class="aakaari-text-muted">No pending payouts found.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <!-- Payout Result Modal -->
<div class="aakaari-modal" id="payoutResultModal">
    <div class="aakaari-modal-overlay"></div>
    <div class="aakaari-modal-container">
        <div class="aakaari-modal-header">
            <h3>Payout Results</h3>
            <p>Summary of processed payouts</p>
        </div>
        
        <div class="aakaari-modal-body">
            <div id="payoutResultContent">
                <!-- Results will be loaded here -->
            </div>
        </div>
        
        <div class="aakaari-modal-footer">
            <button class="aakaari-button" id="closePayoutResultBtn">Close</button>
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
                        <div class="aakaari-card-header">
                            <h3>Commission Settings</h3>
                        </div>
                        <div class="aakaari-card-content">
                            <form id="commission-settings-form" class="aakaari-form">
                                <div class="aakaari-form-row">
                                    <div class="aakaari-form-group">
                                        <label for="default-commission">Default Commission Rate (%)</label>
                                        <input type="number" id="default-commission" name="default_commission" min="0" max="100" step="0.01" value="15" class="aakaari-input">
                                        <small class="aakaari-help-text">Default commission percentage for all resellers</small>
                                    </div>
                                    
                                    <div class="aakaari-form-group">
                                        <label for="min-payout">Minimum Payout Amount (₹)</label>
                                        <input type="number" id="min-payout" name="min_payout" min="0" step="1" value="1000" class="aakaari-input">
                                        <small class="aakaari-help-text">Minimum balance required for payout processing</small>
                                    </div>
                                </div>
                                
                                <div class="aakaari-form-row">
                                    <div class="aakaari-form-group">
                                        <label for="payout-schedule">Payout Schedule</label>
                                        <select id="payout-schedule" name="payout_schedule" class="aakaari-select">
                                            <option value="weekly">Weekly</option>
                                            <option value="biweekly">Bi-weekly</option>
                                            <option value="monthly" selected>Monthly</option>
                                        </select>
                                    </div>
                                    
                                    <div class="aakaari-form-group">
                                        <label for="payout-day">Payout Day</label>
                                        <select id="payout-day" name="payout_day" class="aakaari-select">
                                            <option value="1">1st of month</option>
                                            <option value="15" selected>15th of month</option>
                                            <option value="lastday">Last day of month</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="aakaari-form-actions">
                                    <button type="submit" class="aakaari-button">Save Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="aakaari-card aakaari-mt-4">
                        <div class="aakaari-card-header">
                            <h3>Email Notification Settings</h3>
                        </div>
                        <div class="aakaari-card-content">
                            <form id="notification-settings-form" class="aakaari-form">
                                <div class="aakaari-form-group">
                                    <label class="aakaari-checkbox-label">
                                        <input type="checkbox" name="notify_new_applications" checked> 
                                        <span>Notify admins about new applications</span>
                                    </label>
                                </div>
                                
                                <div class="aakaari-form-group">
                                    <label class="aakaari-checkbox-label">
                                        <input type="checkbox" name="notify_new_orders" checked> 
                                        <span>Notify resellers about new orders</span>
                                    </label>
                                </div>
                                
                                <div class="aakaari-form-group">
                                    <label class="aakaari-checkbox-label">
                                        <input type="checkbox" name="notify_low_stock"> 
                                        <span>Notify admins about low stock</span>
                                    </label>
                                </div>
                                
                                <div class="aakaari-form-group">
                                    <label class="aakaari-checkbox-label">
                                        <input type="checkbox" name="notify_payouts" checked> 
                                        <span>Send payout notifications to resellers</span>
                                    </label>
                                </div>
                                
                                <div class="aakaari-form-actions">
                                    <button type="submit" class="aakaari-button">Save Settings</button>
                                </div>
                            </form>
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
                
                <!-- Quick Actions Grid -->
                <div class="quick-actions-section">
                    <h4 class="section-header">Quick Actions</h4>
                    <div class="quick-actions-grid">
                        <button class="quick-action-card" id="requestDocCard" title="Opens a dialog to specify which documents are needed. An email will be sent to the applicant with the requirements. Status changes to 'Documents Requested'.">
                            <div class="quick-action-icon" style="background: #dbeafe;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </div>
                            <div class="quick-action-content">
                                <span class="quick-action-title">Request Documentation</span>
                                <span class="quick-action-subtitle">Specify needed docs</span>
                            </div>
                        </button>

                        <button class="quick-action-card" id="allowResubmitCard" title="Enables the reseller to re-upload documents or edit information if their application was rejected or incomplete. Status changes to 'Resubmission Allowed'.">
                            <div class="quick-action-icon" style="background: #dcfce7;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </div>
                            <div class="quick-action-content">
                                <span class="quick-action-title">Allow Resubmission</span>
                                <span class="quick-action-subtitle">Enable document upload</span>
                            </div>
                        </button>

                        <button class="quick-action-card" id="setCooldownCard" title="Locks the application for a specific time period (1-720 hours) before admin can take further action. This helps prevent repeated reviews. Status becomes 'On Cooldown'.">
                            <div class="quick-action-icon" style="background: #fef3c7;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div class="quick-action-content">
                                <span class="quick-action-title">Set Review Cooldown</span>
                                <span class="quick-action-subtitle">Lock for review</span>
                            </div>
                        </button>

                        <button class="quick-action-card" id="resetCooldownCard" title="Unlocks the application early and marks it active for admin review again. Removes cooldown timer and sets status back to 'Pending'.">
                            <div class="quick-action-icon" style="background: #f0f9ff;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0369a1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                            </div>
                            <div class="quick-action-content">
                                <span class="quick-action-title">Reset Cooldown</span>
                                <span class="quick-action-subtitle">Make application active</span>
                            </div>
                        </button>
                    </div>
                </div>
                
                <!-- Conditional Action Forms -->
                <div id="actionFormsContainer" style="display: none;">
                    <!-- Rejection Reason Section -->
                    <div class="action-form-section" id="rejectionReasonSection" style="display: none;">
                        <div class="action-form-header">
                            <div class="action-form-icon" style="background: #fee2e2;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">Reject Application</h4>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-medium);">Provide a reason for rejecting this application</p>
                            </div>
                        </div>
                        <div class="action-form-body">
                            <textarea id="rejectionReason" class="aakaari-textarea" placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                        <div class="action-form-footer">
                            <button type="button" class="aakaari-button aakaari-button-red" id="submitRejectionBtn">
                                Confirm Rejection
                            </button>
                        </div>
                    </div>
                    
                    <!-- Documentation Request Section -->
                    <div class="action-form-section" id="documentRequestSection" style="display: none;">
                        <div class="action-form-header">
                            <div class="action-form-icon" style="background: #dbeafe;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">Request Additional Documentation</h4>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-medium);">Specify what documents are needed</p>
                            </div>
                        </div>
                        <div class="action-form-body">
                            <textarea id="documentRequest" class="aakaari-textarea" placeholder="Please specify what additional documentation is required (e.g., GST certificate, business license, etc.)..."></textarea>
                        </div>
                        <div class="action-form-footer">
                            <button type="button" class="aakaari-button aakaari-button-green" id="submitDocRequestBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                Send Request
                            </button>
                        </div>
                    </div>
                    
                    <!-- Cooldown Section -->
                    <div class="action-form-section" id="cooldownSection" style="display: none;">
                        <div class="action-form-header">
                            <div class="action-form-icon" style="background: #fef3c7;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 1rem; font-weight: 600;">Set Review Cooldown</h4>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--text-medium);">Lock the application for a specified duration</p>
                            </div>
                        </div>
                        <div class="action-form-body">
                            <div class="aakaari-form-group">
                                <label for="cooldownDuration">Duration (hours)</label>
                                <input type="number" id="cooldownDuration" class="aakaari-input" min="1" max="720" value="24" placeholder="Enter hours">
                                <small style="color: #666; display: block; margin-top: 0.5rem;">The application will be locked for review for this duration</small>
                            </div>
                        </div>
                        <div class="action-form-footer">
                            <button type="button" class="aakaari-button aakaari-button-green" id="submitCooldownBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Set Cooldown
                            </button>
                        </div>
                    </div>
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
    
    <!-- Order Status Update Modal -->
    <div class="aakaari-modal" id="orderStatusModal">
        <div class="aakaari-modal-overlay"></div>
        <div class="aakaari-modal-container">
            <div class="aakaari-modal-header">
                <h3>Update Order Status</h3>
                <p>Change the status of order <span id="orderIdDisplay"></span></p>
            </div>
            
            <div class="aakaari-modal-body">
                <div class="aakaari-form-group">
                    <label for="orderStatus">New Status</label>
                    <select id="orderStatus" class="aakaari-select">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="aakaari-form-group">
                    <label for="statusNotes">Notes (optional)</label>
                    <textarea id="statusNotes" class="aakaari-textarea" placeholder="Add notes about this status change..."></textarea>
                </div>
                
                <div class="aakaari-form-group">
                    <label class="aakaari-checkbox-label">
                        <input type="checkbox" id="notifyCustomer" checked>
                        <span>Notify customer about this update</span>
                    </label>
                </div>
            </div>
            
            <div class="aakaari-modal-footer">
                <button class="aakaari-button aakaari-button-outline" id="closeOrderStatusBtn">Cancel</button>
                <button class="aakaari-button" id="updateOrderStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>

<?php get_footer('minimal'); ?>