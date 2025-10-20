<?php
/**
 * Template Name: Product Customizer
 * Template Post Type: product
 *
 * @package YourTheme
 */

get_header();

// Get the product
global $product;

// Ensure we have a product
if (!is_a($product, 'WC_Product')) {
    $product_id = get_the_ID();
    $product = wc_get_product($product_id);
}

// If still no product, show error
if (!is_a($product, 'WC_Product')) {
    echo '<div class="container"><p>Product not found.</p></div>';
    get_footer();
    return;
}

// Get product attributes
$colors = [];
$sizes = [];
$attributes = $product->get_attributes();

// Get available colors
if (isset($attributes['pa_color'])) {
    $terms = get_terms([
        'taxonomy' => 'pa_color',
        'hide_empty' => false,
    ]);
    
    foreach ($terms as $term) {
        $color_value = get_term_meta($term->term_id, 'color_value', true);
        if (!$color_value) {
            $color_value = '#FFFFFF'; // Default to white
        }
        
        $colors[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'color' => $color_value
        ];
    }
}

// Get available sizes
if (isset($attributes['pa_size'])) {
    $terms = get_terms([
        'taxonomy' => 'pa_size',
        'hide_empty' => false,
    ]);
    
    foreach ($terms as $term) {
        $sizes[] = [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug
        ];
    }
}

// Set default values
$default_color = !empty($colors) ? $colors[0]['name'] : 'White';
$default_size = !empty($sizes) ? $sizes[0]['name'] : 'M';

// Base price and print costs
$base_price = $product->get_price();
$print_cost = 45; // You can make this dynamic based on product or design
$total_cost = $base_price + $print_cost;
$suggested_retail = $total_cost * 2.4; // Example markup

// Pre-made designs
$designs = [
    [
        'id' => 'abstract',
        'name' => 'Abstract Art',
        'image' => get_template_directory_uri() . '/assets/designs/abstract.jpg'
    ],
    [
        'id' => 'graphic',
        'name' => 'Graphic Pattern',
        'image' => get_template_directory_uri() . '/assets/designs/graphic.jpg'
    ],
    [
        'id' => 'colorful',
        'name' => 'Colorful Wave',
        'image' => get_template_directory_uri() . '/assets/designs/colorful.jpg'
    ],
    [
        'id' => 'geometric',
        'name' => 'Geometric',
        'image' => get_template_directory_uri() . '/assets/designs/geometric.jpg'
    ],
    [
        'id' => 'minimalist',
        'name' => 'Minimalist',
        'image' => get_template_directory_uri() . '/assets/designs/minimalist.jpg'
    ],
    [
        'id' => 'illustration',
        'name' => 'Illustration',
        'image' => get_template_directory_uri() . '/assets/designs/illustration.jpg'
    ],
];
?>

<div class="product-customizer-wrapper">
    <div class="product-customizer">
        <div class="customizer-header">
            <h1>Customize Your Product</h1>
            <div class="action-buttons">
                <button type="button" id="reset-design" class="btn-reset">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/>
                    </svg>
                    Reset
                </button>
                <button type="button" id="save-design" class="btn-save">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                    </svg>
                    Save Design
                </button>
            </div>
        </div>

        <div class="customizer-main">
            <div class="customizer-preview">
                <div class="product-info">
                    <div class="product-thumb">
                        <img src="<?php echo wp_get_attachment_url($product->get_image_id()); ?>" alt="Product thumbnail">
                    </div>
                    <div class="product-details">
                        <span id="product-title"><?php echo esc_html($default_color); ?> T-Shirt</span>
                        <span id="product-meta">Front View • Size <?php echo esc_html($default_size); ?></span>
                    </div>
                </div>

                <div class="view-controls">
                    <button type="button" data-view="front" class="view-btn active">Front</button>
                    <button type="button" data-view="back" class="view-btn">Back</button>
                </div>

                <div class="product-canvas-container">
                    <div id="product-canvas" class="product-canvas">
                        <!-- Canvas will be inserted here by JavaScript -->
                    </div>
                </div>

                <div class="design-controls">
                    <div class="control-group">
                        <label>Design Size <span id="size-value">65%</span></label>
                        <div class="slider-control">
                            <input type="range" id="design-size" min="10" max="100" value="65">
                        </div>
                    </div>
                    <div class="control-group">
                        <label>Vertical Position</label>
                        <div class="slider-control">
                            <input type="range" id="vertical-position" min="0" max="100" value="50">
                        </div>
                    </div>
                    <div class="control-group">
                        <label>Horizontal Position</label>
                        <div class="slider-control">
                            <input type="range" id="horizontal-position" min="0" max="100" value="50">
                        </div>
                    </div>
                </div>
            </div>

            <div class="customizer-options">
                <div class="option-section">
                    <h3>T-Shirt Color</h3>
                    <div class="color-options">
                        <?php foreach ($colors as $index => $color): ?>
                            <div class="color-option <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 data-color-id="<?php echo esc_attr($color['id']); ?>"
                                 data-color-name="<?php echo esc_attr($color['name']); ?>"
                                 data-color-slug="<?php echo esc_attr($color['slug']); ?>">
                                <div class="color-swatch" style="background-color: <?php echo esc_attr($color['color']); ?>"></div>
                                <span class="color-name"><?php echo esc_html($color['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="option-section">
                    <h3>Size</h3>
                    <div class="size-options">
                        <?php foreach ($sizes as $index => $size): ?>
                            <div class="size-option <?php echo $index === 0 ? 'active' : ''; ?>"
                                 data-size-id="<?php echo esc_attr($size['id']); ?>"
                                 data-size-name="<?php echo esc_attr($size['name']); ?>">
                                <?php echo esc_html($size['name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="option-section">
                    <div class="design-tabs">
                        <button type="button" class="design-tab active" data-tab="designs">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zm.995-14.901a1 1 0 1 0-1.99 0A5.002 5.002 0 0 0 3 6c0 1.098-.5 6-2 7h14c-1.5-1-2-5.902-2-7 0-2.42-1.72-4.44-4.005-4.901z"/>
                            </svg>
                            Designs
                        </button>
                        <button type="button" class="design-tab" data-tab="upload">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                            </svg>
                            Upload
                        </button>
                    </div>
                    
                    <div class="design-content active" id="designs-tab">
                        <div class="premade-designs">
                            <?php foreach ($designs as $design): ?>
                                <div class="design-item" data-design-id="<?php echo esc_attr($design['id']); ?>">
                                    <img src="<?php echo esc_url($design['image']); ?>" alt="<?php echo esc_attr($design['name']); ?>">
                                    <span class="design-name"><?php echo esc_html($design['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="design-content" id="upload-tab">
                        <div class="upload-area">
                            <div id="design-dropzone" class="dropzone">
                                <div class="dz-message">
                                    <svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                        <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                                    </svg>
                                    <p>Drag and drop your design here or click to browse</p>
                                </div>
                            </div>
                            <div class="upload-notes">
                                <p>Recommended: PNG files with transparent background</p>
                                <p>Max file size: 5MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pricing-section">
                    <div class="price-row">
                        <span>Base Price:</span>
                        <span class="price">₹<?php echo number_format($base_price, 2); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Print Cost:</span>
                        <span class="price">₹<?php echo number_format($print_cost, 2); ?></span>
                    </div>
                    <div class="price-row total">
                        <span>Your Cost:</span>
                        <span class="price">₹<?php echo number_format($total_cost, 2); ?></span>
                    </div>
                    <div class="price-row suggested">
                        <span>Suggested Retail:</span>
                        <span class="price">₹<?php echo number_format($suggested_retail, 2); ?></span>
                    </div>

                    <div class="add-to-cart-section">
                        <div class="quantity">
                            <button type="button" class="qty-btn minus">-</button>
                            <input type="number" id="product-quantity" min="1" value="1">
                            <button type="button" class="qty-btn plus">+</button>
                        </div>
                        <button type="button" id="add-to-cart" class="btn-primary">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for customization data -->
<form id="customization-data" style="display:none;">
    <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
    <input type="hidden" name="color_id" value="">
    <input type="hidden" name="size_id" value="">
    <input type="hidden" name="design_data" value="">
    <input type="hidden" name="design_position" value="">
    <input type="hidden" name="quantity" value="1">
    <?php wp_nonce_field('product_customization', 'customization_nonce'); ?>
</form>

<?php
// Localize data for JavaScript
wp_localize_script('product-customizer-js', 'customizer_data', [
    'product_id' => $product->get_id(),
    'product_name' => $product->get_name(),
    'colors' => $colors,
    'sizes' => $sizes,
    'designs' => $designs,
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('product_customization')
]);

get_footer();
?>