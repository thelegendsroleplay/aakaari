<?php
/**
 * Template Name: Reseller Dashboard
 *
 * @package Aakaari
 */

// --- START ACCESS CONTROL ---

// 1. Check if user is logged in
if (!is_user_logged_in()) {
    wp_safe_redirect(home_url('/login/'));
    exit;
}

// 2. User is logged in, get info
$current_user = wp_get_current_user();
$user_display_name = $current_user->display_name;
$user_id = $current_user->ID;

// 3. Check email verification status
$email_verified = get_user_meta($user_id, 'email_verified', true);
if (!$email_verified) {
    wp_safe_redirect(home_url('/register/'));
    exit;
}

// 4. Check onboarding status OR approved application
$onboarding_status = get_user_meta($user_id, 'onboarding_status', true);
$application_approved = false;

// Try to find the user's application by email and read its taxonomy status
$user_email = $current_user->user_email;

// Query the most recent application for this email
$application_query = new WP_Query(array(
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

if ($application_query->have_posts()) {
    $application_query->the_post();
    $app_id = get_the_ID();
    $terms = wp_get_post_terms($app_id, 'reseller_application_status', array('fields' => 'slugs'));
    if (!is_wp_error($terms) && !empty($terms)) {
        $application_approved = in_array('approved', $terms, true);
    }
    wp_reset_postdata();
}

// If onboarding isn't completed and there is no approved application, redirect to complete onboarding
if ($onboarding_status !== 'completed' && !$application_approved) {
    $reseller_page_id  = get_option('reseller_page_id');
    $reseller_page_url = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
    // Optionally pass a status to show a message
    // Detect pending/rejected to help UX
    $status_param = 'pending';
    if (isset($app_id)) {
        $terms = isset($terms) ? $terms : array();
        if (in_array('rejected', $terms, true)) {
            $status_param = 'rejected';
        } elseif (in_array('approved', $terms, true)) {
            $status_param = 'approved';
        }
    }
    $target = add_query_arg('status', $status_param, $reseller_page_url);
    wp_safe_redirect($target);
    exit;
}


// 5. All checks passed. User can view the dashboard.
// --- END ACCESS CONTROL ---

// Get user's orders from WooCommerce
$customer_orders = wc_get_orders(array(
    'customer' => $current_user->ID,
    'limit' => -1,
));

// Calculate statistics
$total_orders = count($customer_orders);
$total_earnings = 0;
$current_month_earnings = 0;
$monthly_goal = 15000; // Set your default goal amount

// Loop through orders to calculate earnings
$month_orders_count = 0;
foreach ($customer_orders as $order) {
    $order_total = $order->get_total();
    // Calculate commission (adjust the formula as needed)
    $commission = $order_total * 0.15; // 15% commission example
    $total_earnings += $commission;
    
    // Check if order is from current month
    $order_date = $order->get_date_created();
    if ($order_date && $order_date->format('Y-m') === date('Y-m')) {
        $current_month_earnings += $commission;
        $month_orders_count++;
    }
}

// Set wallet balance (could come from a custom user meta or plugin)
$wallet_balance = get_user_meta($current_user->ID, 'wallet_balance', true);
if (empty($wallet_balance)) {
    $wallet_balance = $total_earnings * 0.3; // Example: 30% of earnings are in wallet
    update_user_meta($current_user->ID, 'wallet_balance', $wallet_balance);
}

// Get active products count (you may need to adjust this based on your setup)
$active_products = 45; // Example static value, replace with dynamic data

// Recent orders (last 3)
$recent_orders = wc_get_orders(array(
    'customer' => $current_user->ID,
    'limit' => 3,
    'orderby' => 'date',
    'order' => 'DESC',
));

// Recent transactions (for wallet section)
$transactions = array(
    array(
        'id' => 'TXN-001',
        'type' => 'Credit',
        'description' => 'Commission from #ORD-1234',
        'date' => '2025-10-15',
        'amount' => 925
    ),
    array(
        'id' => 'TXN-002',
        'type' => 'Debit',
        'description' => 'Withdrawal to bank',
        'date' => '2025-10-14',
        'amount' => -5000
    ),
    array(
        'id' => 'TXN-003',
        'type' => 'Credit',
        'description' => 'Commission from #ORD-1233',
        'date' => '2025-10-13',
        'amount' => 1200
    ),
);

// Calculate monthly goal percentage
$goal_percentage = ($current_month_earnings / $monthly_goal) * 100;
$goal_percentage = min(100, $goal_percentage); // Cap at 100%

get_header();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="container">
            <div class="dashboard-title-section">
                <h1>Reseller Dashboard</h1>
                <p>Welcome back, <?php echo esc_html($user_display_name); ?>!</p>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    Browse Products
                </a>
            </div>
        </div>
    </div>


    <div class="dashboard-content">
        <div class="container">
            <!-- Navigation Tabs -->
            <div class="dashboard-tabs">
                <a href="#overview" class="tab-item active" data-tab="overview">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    Overview
                </a>
                <a href="#orders" class="tab-item" data-tab="orders">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                    Orders
                </a>
                <a href="#bulk-order" class="tab-item" data-tab="bulk-order">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M9 12h2l-2 1.5L9 12Zm-4 0H3l2 1.5L5 12Z"/>
                        <path d="M12 1a1 1 0 0 1 1 1v10.755S12 11 8 11s-5 1.755-5 1.755V2a1 1 0 0 1 1-1h8ZM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4Z"/>
                    </svg>
                    Bulk Order
                </a>
                <a href="#wallet" class="tab-item" data-tab="wallet">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
                    </svg>
                    Wallet
                </a>
                <a href="#downloads" class="tab-item" data-tab="downloads">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2zm2.354 6.854-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5a.5.5 0 0 1 1 0v3.793l1.146-1.147a.5.5 0 0 1 .708.708z"/>
                    </svg>
                    Downloads
                </a>
            </div>

            <!-- Tab Content: Overview -->
            <div class="tab-content active" id="overview-content">
                <!-- Statistics Cards -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon orders-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l.84 4.479 9.144-.459L13.89 4H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="change-label positive">+12%</div>
                            <div class="stat-value"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon earnings-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 2.5A1.5 1.5 0 0 1 1.5 1h13A1.5 1.5 0 0 1 16 2.5v3A1.5 1.5 0 0 1 14.5 7h-13A1.5 1.5 0 0 1 0 5.5v-3zM1.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-13z"/>
                                <path d="M2 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1z"/>
                                <path d="M0 8.5A1.5 1.5 0 0 1 1.5 7h13A1.5 1.5 0 0 1 16 8.5v3a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 11.5v-3zM1.5 8a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-13z"/>
                                <path d="M2 11.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="change-label positive">+23%</div>
                            <div class="stat-value">₹<?php echo number_format($total_earnings); ?></div>
                            <div class="stat-label">Total Earnings</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon wallet-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="change-label available">Available</div>
                            <div class="stat-value">₹<?php echo number_format($wallet_balance); ?></div>
                            <div class="stat-label">Wallet Balance</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon products-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
                            </svg>
                        </div>
                        <div class="stat-info">
                            <div class="change-label selling">Selling</div>
                            <div class="stat-value"><?php echo $active_products; ?></div>
                            <div class="stat-label">Active Products</div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="panel recent-orders">
                    <div class="panel-header">
                        <h2>Recent Orders</h2>
                        <a href="#orders" class="view-all" data-tab-trigger="orders">View All</a>
                    </div>
                    <div class="panel-content">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_orders)) : ?>
                                    <?php foreach ($recent_orders as $order) : ?>
                                        <?php
                                        $order_id = $order->get_id();
                                        $order_number = $order->get_order_number();
                                        $order_date = $order->get_date_created()->format('Y-m-d');
                                        $order_total = $order->get_total();
                                        $commission = $order_total * 0.15; // 15% commission example
                                        
                                        // Get billing info
                                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                        
                                        // Get order status
                                        $status = $order->get_status();
                                        $status_label = ucfirst($status);
                                        $status_class = '';
                                        
                                        switch ($status) {
                                            case 'completed':
                                                $status_label = 'Delivered';
                                                $status_class = 'delivered';
                                                break;
                                            case 'processing':
                                                $status_label = 'Processing';
                                                $status_class = 'processing';
                                                break;
                                            case 'on-hold':
                                                $status_label = 'On Hold';
                                                $status_class = 'on-hold';
                                                break;
                                            case 'pending':
                                                $status_label = 'Pending';
                                                $status_class = 'pending';
                                                break;
                                            case 'shipped':
                                                $status_label = 'Shipped';
                                                $status_class = 'shipped';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td>#ORD-<?php echo $order_number; ?></td>
                                            <td><?php echo $order_date; ?></td>
                                            <td><?php echo esc_html($customer_name); ?></td>
                                            <td>₹<?php echo number_format($order_total); ?></td>
                                            <td class="commission">₹<?php echo number_format($commission); ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="no-orders">No orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Quick Action Cards -->
                <div class="quick-actions">
                    <div class="action-card">
                        <div class="action-icon store-links-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5h3Z"/>
                                <path d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-12Z"/>
                                <path d="M8.5 6.5a.5.5 0 0 0-1 0V8H6a.5.5 0 0 0 0 1h1.5v1.5a.5.5 0 0 0 1 0V9H10a.5.5 0 0 0 0-1H8.5V6.5Z"/>
                            </svg>
                        </div>
                        <h3>My Store Links</h3>
                        <p>Generate and share your personalized product links</p>
                        <a href="#" class="btn btn-outline" data-action="generate-link">Generate Link</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon withdrawal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5 6a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 5 6zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 5 8zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/>
                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                            </svg>
                        </div>
                        <h3>Request Withdrawal</h3>
                        <p>Withdraw your earnings to your bank account</p>
                        <a href="#wallet" class="btn btn-outline" data-tab-trigger="wallet">Withdraw Funds</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon catalog-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2zm2.354 6.854-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5a.5.5 0 0 1 1 0v3.793l1.146-1.147a.5.5 0 0 1 .708.708z"/>
                            </svg>
                        </div>
                        <h3>Download Catalog</h3>
                        <p>Get the latest product catalog and images</p>
                        <a href="#" class="btn btn-outline" data-action="download-catalog">Download Now</a>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Wallet -->
            <div class="tab-content" id="wallet-content">
                <div class="wallet-section">
                    <div class="wallet-balance-card">
                        <div class="balance-section">
                            <h2>Wallet Balance</h2>
                            <div class="balance-amount">₹<?php echo number_format($wallet_balance); ?></div>
                            <p class="balance-status">Available for withdrawal</p>
                            <a href="#" class="btn btn-primary withdraw-button" data-action="withdraw-funds">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1H0V4zm0 3v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7H0zm3 2h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1z"/>
                                </svg>
                                Withdraw to Bank
                            </a>
                        </div>
                    </div>
                    
                    <div class="earnings-card">
                        <h2>Earnings This Month</h2>
                        <div class="earnings-amount">₹<?php echo number_format($current_month_earnings); ?></div>
                        <p class="earnings-info">From <?php echo $month_orders_count; ?> orders</p>
                        <div class="goal-progress">
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $goal_percentage; ?>%"></div>
                            </div>
                            <div class="goal-text"><?php echo round($goal_percentage); ?>% of monthly goal (₹<?php echo number_format($monthly_goal); ?>)</div>
                        </div>
                    </div>
                </div>
                
                <div class="panel recent-transactions">
                    <div class="panel-header">
                        <h2>Recent Transactions</h2>
                    </div>
                    <div class="panel-content">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn) : ?>
                                    <tr>
                                        <td><?php echo $txn['id']; ?></td>
                                        <td>
                                            <span class="txn-type <?php echo strtolower($txn['type']); ?>">
                                                <?php echo $txn['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $txn['description']; ?></td>
                                        <td><?php echo $txn['date']; ?></td>
                                        <td class="amount <?php echo $txn['amount'] > 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo $txn['amount'] > 0 ? '+' : ''; ?>₹<?php echo number_format(abs($txn['amount'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Orders -->
            <div class="tab-content" id="orders-content">
                <div class="panel all-orders">
                    <div class="panel-header">
                        <h2>All Orders</h2>
                    </div>
                    <div class="panel-content">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($customer_orders)) : ?>
                                    <?php foreach ($customer_orders as $order) : ?>
                                        <?php
                                        $order_id = $order->get_id();
                                        $order_number = $order->get_order_number();
                                        $order_date = $order->get_date_created()->format('Y-m-d');
                                        $order_total = $order->get_total();
                                        $commission = $order_total * 0.15; // 15% commission example
                                        
                                        // Get billing info
                                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                        
                                        // Get item count
                                        $item_count = count($order->get_items());
                                        
                                        // Get order status
                                        $status = $order->get_status();
                                        $status_label = ucfirst($status);
                                        $status_class = '';
                                        
                                        switch ($status) {
                                            case 'completed':
                                                $status_label = 'Delivered';
                                                $status_class = 'delivered';
                                                break;
                                            case 'processing':
                                                $status_label = 'Processing';
                                                $status_class = 'processing';
                                                break;
                                            case 'on-hold':
                                                $status_label = 'On Hold';
                                                $status_class = 'on-hold';
                                                break;
                                            case 'pending':
                                                $status_label = 'Pending';
                                                $status_class = 'pending';
                                                break;
                                            case 'shipped':
                                                $status_label = 'Shipped';
                                                $status_class = 'shipped';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td>#ORD-<?php echo $order_number; ?></td>
                                            <td><?php echo $order_date; ?></td>
                                            <td><?php echo esc_html($customer_name); ?></td>
                                            <td><?php echo $item_count; ?> items</td>
                                            <td>₹<?php echo number_format($order_total); ?></td>
                                            <td class="commission">₹<?php echo number_format($commission); ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                            <td><a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="view-link">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="8" class="no-orders">No orders found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Bulk Order (Placeholder) -->
            <div class="tab-content" id="bulk-order-content">
                <div class="bulk-order-form-container">
                    <h2>Bulk Order</h2>
                    <p class="section-description">Place orders for multiple products at once by uploading a CSV file with product details.</p>
                    
                    <div class="bulk-order-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Download Template</h3>
                                <p>Download our CSV template with the required format</p>
                                <a href="#" class="btn btn-sm btn-outline">Download Template</a>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Fill in Details</h3>
                                <p>Add your product IDs, quantities and customer details</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Upload File</h3>
                                <p>Upload your completed CSV file</p>
                                <div class="file-upload-container">
                                    <input type="file" id="bulkOrderFile" accept=".csv" class="file-input">
                                    <label for="bulkOrderFile" class="file-label">Choose File</label>
                                    <span class="selected-file">No file chosen</span>
                                </div>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h3>Submit Order</h3>
                                <p>Review and confirm your bulk order</p>
                                <button class="btn btn-primary" disabled>Upload & Review</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Content: Downloads (Placeholder) -->
            <div class="tab-content" id="downloads-content">
                <div class="downloads-container">
                    <h2>Downloads</h2>
                    <p class="section-description">Access catalogs, price lists, and marketing materials for your business.</p>
                    
                    <div class="download-grid">
                        <div class="download-card">
                            <div class="download-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H3V2a1 1 0 0 1 1-1h5.5v2zM3 12v-2h2v2H3zm0 1h2v2H4a1 1 0 0 1-1-1v-1zm3 2v-2h3v2H6zm4 0v-2h3v1a1 1 0 0 1-1 1h-2zm3-3h-3v-2h3v2zm-7 0v-2h3v2H6z"/>
                                </svg>
                            </div>
                            <h3>Product Catalog</h3>
                            <p>Complete catalog with all products and wholesale pricing</p>
                            <div class="download-meta">
                                <span class="file-type">PDF</span>
                                <span class="file-size">4.2 MB</span>
                                <span class="update-date">Updated: 2025-10-01</span>
                            </div>
                            <a href="#" class="btn btn-outline btn-download">Download</a>
                        </div>
                        
                        <div class="download-card">
                            <div class="download-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H3V2a1 1 0 0 1 1-1h5.5v2zM3 12v-2h2v2H3zm0 1h2v2H4a1 1 0 0 1-1-1v-1zm3 2v-2h3v2H6zm4 0v-2h3v1a1 1 0 0 1-1 1h-2zm3-3h-3v-2h3v2zm-7 0v-2h3v2H6z"/>
                                </svg>
                            </div>
                            <h3>Price List</h3>
                            <p>Latest wholesale and recommended retail prices</p>
                            <div class="download-meta">
                                <span class="file-type">XLSX</span>
                                <span class="file-size">1.8 MB</span>
                                <span class="update-date">Updated: 2025-10-05</span>
                            </div>
                            <a href="#" class="btn btn-outline btn-download">Download</a>
                        </div>
                        
                        <div class="download-card">
                            <div class="download-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                    <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13zm13 1a.5.5 0 0 1 .5.5v6l-3.775-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12v.54A.505.505 0 0 1 1 12.5v-9a.5.5 0 0 1 .5-.5h13z"/>
                                </svg>
                            </div>
                            <h3>Product Images</h3>
                            <p>High-resolution product images for marketing</p>
                            <div class="download-meta">
                                <span class="file-type">ZIP</span>
                                <span class="file-size">156 MB</span>
                                <span class="update-date">Updated: 2025-10-10</span>
                            </div>
                            <a href="#" class="btn btn-outline btn-download">Download</a>
                        </div>
                        
                        <div class="download-card">
                            <div class="download-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555ZM0 4.697v7.104l5.803-3.558L0 4.697ZM6.761 8.83l-6.57 4.026A2 2 0 0 0 2 14h6.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.606-3.446l-.367-.225L8 9.586l-1.239-.757ZM16 4.697v4.974A4.491 4.491 0 0 0 12.5 8a4.49 4.49 0 0 0-1.965.45l-.338-.207L16 4.697Z"/>
                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1.5a.5.5 0 0 1-1 0V11a.5.5 0 0 1 1 0Zm0 3a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0Z"/>
                                </svg>
                            </div>
                            <h3>Marketing Kit</h3>
                            <p>Templates and resources for promotions</p>
                            <div class="download-meta">
                                <span class="file-type">ZIP</span>
                                <span class="file-size">78 MB</span>
                                <span class="update-date">Updated: 2025-09-25</span>
                            </div>
                            <a href="#" class="btn btn-outline btn-download">Download</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>