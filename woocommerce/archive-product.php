<?php
/**
 * The Template for displaying product archives (Shop Page).
 * Uses custom HTML structure and CSS classes (shop-style.css).
 */

defined( 'ABSPATH' ) || exit;

// SECURITY: Check if user can access products
$can_access_products = false;
$access_message = '';
$access_status = 'not_logged_in';

if (current_user_can('manage_options')) {
    $can_access_products = true;
} elseif (is_user_logged_in()) {
    $user = wp_get_current_user();
    $user_email = $user->user_email;
    
    if (function_exists('get_reseller_application_status')) {
        $application_info = get_reseller_application_status($user_email);
        $access_status = $application_info['status'];
        
        if ($application_info['status'] === 'approved') {
            $can_access_products = true;
        }
    }
}

get_header( 'shop' );

// Show access denied message if user cannot access products
if (!$can_access_products) {
    ?>
    <div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 2rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
        <div style="max-width: 600px; width: 100%; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 3rem; text-align: center;">
            <?php if (!is_user_logged_in()): ?>
                <!-- Not Logged In -->
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 2rem; font-weight: 700;">Access Restricted</h2>
                <p style="margin: 0 0 2rem 0; color: #6b7280; font-size: 1.125rem; line-height: 1.6;">Please login to view our product catalog.</p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.125rem; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4); transition: all 0.3s ease;">
                    Login to Continue
                </a>
            <?php elseif ($access_status === 'pending' || $access_status === 'under_review'): ?>
                <!-- Application Pending -->
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(251, 191, 36, 0.4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 2rem; font-weight: 700;">Application Under Review</h2>
                <p style="margin: 0 0 2rem 0; color: #6b7280; font-size: 1.125rem; line-height: 1.6;">Your reseller application is being reviewed. You'll be able to access our catalog once approved.</p>
                <a href="<?php echo esc_url(home_url('/application-pending')); ?>" style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.125rem; box-shadow: 0 10px 30px rgba(251, 191, 36, 0.4); transition: all 0.3s ease;">
                    Check Status
                </a>
            <?php else: ?>
                <!-- Not Applied / Rejected -->
                <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 50%; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <h2 style="margin: 0 0 1rem 0; color: #1f2937; font-size: 2.25rem; font-weight: 700;">Become a Reseller to Access Our Catalog</h2>
                <p style="margin: 0 0 2rem 0; color: #6b7280; font-size: 1.125rem; line-height: 1.6;">Join our reseller network and get exclusive access to our entire product catalog with wholesale pricing.</p>
                
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 16px; padding: 2rem; margin: 2rem 0; text-align: left;">
                    <h3 style="margin: 0 0 1rem 0; color: #2563eb; font-size: 1.25rem; font-weight: 700;">✨ Reseller Benefits:</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 0.5rem 0; color: #1e40af; font-size: 1rem;">✓ Wholesale pricing on all products</li>
                        <li style="padding: 0.5rem 0; color: #1e40af; font-size: 1rem;">✓ Exclusive product catalog access</li>
                        <li style="padding: 0.5rem 0; color: #1e40af; font-size: 1rem;">✓ Dedicated reseller dashboard</li>
                        <li style="padding: 0.5rem 0; color: #1e40af; font-size: 1rem;">✓ Order tracking & management</li>
                        <li style="padding: 0.5rem 0; color: #1e40af; font-size: 1rem;">✓ Priority support</li>
                    </ul>
                </div>
                
                <a href="<?php echo esc_url(home_url('/become-a-reseller')); ?>" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 1.25rem 3rem; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.25rem; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4); transition: all 0.3s ease; margin-bottom: 1rem;">
                    Apply Now - It's Free! →
                </a>
                
                <p style="margin: 1rem 0 0 0; color: #9ca3af; font-size: 0.875rem;">Application takes less than 5 minutes</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    get_footer();
    exit;
}

do_action( 'woocommerce_before_main_content' );
?>

<?php // 1. Custom Shop Header Section ?>
<div class="shop-header">
    <div class="shop-container">
        <h1 class="shop-header-title">Custom Print Studio</h1>
        <p class="shop-header-subtitle">
            Create unique, personalized products with our easy-to-use design tools
        </p>
        <div class="shop-header-features">
            <div class="feature-item">
                <i data-lucide="paintbrush" class="feature-icon"></i>
                Multiple Print Methods
            </div>
            <div class="feature-item">
                <i data-lucide="package" class="feature-icon"></i>
                High Quality Products
            </div>
        </div>
    </div>
</div>

<?php // 2. Main Shop Content Area ?>
<div class="shop-main">
    <div class="shop-container">

        <?php // 3. Filter Bar ?>
        <div class="shop-filters">
            <div class="search-wrapper">
                <i data-lucide="search" class="search-icon"></i>
                <input
                    id="search-input"
                    placeholder="Search products..."
                    class="search-input"
                    type="search"
                />
            </div>
            <select id="category-select" class="category-filter">
                <option value="all">All Categories</option>
                <?php // Options added by shop-logic.js ?>
            </select>
        </div>

        <?php // 4. Product Grid (Populated by JS) ?>
        <div id="product-grid" class="product-grid">
            <p class="loading-message">Loading products...</p> <?php // Loading message ?>
        </div>

        <?php // 5. No Products Message (Hidden initially) ?>
        <div id="no-products-message" class="no-products-message hidden">
            <i data-lucide="package-x" class="no-products-icon"></i>
            <h3 class="no-products-title">No products found</h3>
            <p class="no-products-text">Try adjusting your search or filters.</p>
        </div>

    </div> <?php // End .shop-container ?>
</div> <?php // End .shop-main ?>

<?php
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
?>