# Product Customizer v2.0 - README

## Overview

The Aakaari Product Customizer is a comprehensive WooCommerce extension that allows customers to upload custom designs, position them on product mockups with real-time constraint enforcement, and complete orders with full data persistence from cart through to admin order management.

## Key Features

- ✅ **Real-time Design Constraints** - Fabric.js canvas with bounding box enforcement
- ✅ **Server-Side Validation** - Blocks invalid designs at add-to-cart
- ✅ **Variation-Aware Mockups** - Color/variation-specific mockup images
- ✅ **Secure File Handling** - WordPress Media Library integration with `wp_handle_upload()`
- ✅ **Complete Data Persistence** - Cart → Checkout → Order → Admin
- ✅ **Admin Order Management** - Thumbnails, downloads, and design details
- ✅ **Relative Coordinate System** - Device-independent print areas (0-1 range)
- ✅ **Security Hardened** - Nonce validation, capability checks, input sanitization

## Architecture

### Component Structure

```
/inc/customizer/
├── init.php                          # System initialization
├── class-customizer-core.php         # Main orchestrator
├── class-file-handler.php            # Secure upload handling
├── class-validator.php               # Server-side validation
├── class-cart-handler.php            # WooCommerce cart integration
├── class-order-handler.php           # Order persistence
├── class-print-area-manager.php      # Print area CRUD
├── class-mockup-manager.php          # Mockup storage/retrieval
└── views/                            # Admin UI templates (to be created)
    ├── admin-meta-box.php
    └── variation-fields.php
```

### Data Structure

#### Product Meta Keys

| Meta Key | Type | Description |
|----------|------|-------------|
| `_customizer_enabled` | string | `'yes'` or `'no'` - enables customization |
| `_customizer_mockups` | array | Color-based mockups `{color: {attachment_id, url}}` |
| `_customizer_print_areas` | array | Print areas `{area_name: {x, y, w, h}}` |
| `_customizer_required` | string | `'yes'` or `'no'` - require customization |

#### Variation Meta Keys

| Meta Key | Type | Description |
|----------|------|-------------|
| `_variation_mockup_attachment_id` | int | Mockup attachment ID for this variation |
| `_variation_print_area` | array | Print area override `{x, y, w, h}` |

#### Cart Item Data

```php
'custom_design' => array(
    'attachment_ids' => array(123, 456),    // Uploaded design file IDs
    'preview_url' => 'https://...',         // Canvas snapshot URL
    'applied_transform' => array(
        'scale' => 1.0,                     // Scale factor (1.0 = 100%)
        'x' => 0.5,                         // X position (0-1 relative)
        'y' => 0.5,                         // Y position (0-1 relative)
        'rotation' => 0                     // Rotation in degrees
    ),
    'print_area_meta' => array(
        'x' => 0.1,                         // Print area X (0-1 relative)
        'y' => 0.2,                         // Print area Y (0-1 relative)
        'w' => 0.8,                         // Print area width (0-1 relative)
        'h' => 0.6                          // Print area height (0-1 relative)
    ),
    'print_type' => 'direct',               // Selected print type
    'fabric_type' => 'polyester',           // Selected fabric type
    'color' => 'white',                     // Selected color
    'variation_id' => 123                   // Variation ID if applicable
),
'unique_key' => 'abc123...'                 // Unique cart item identifier
```

#### Order Item Meta

| Meta Key | Type | Description |
|----------|------|-------------|
| `_custom_design` | array | Complete design data (same as cart) |
| `_custom_design_attachments` | array | Attachment IDs for quick access |
| `_customizer_print_type` | string | Human-readable print type |
| `_customizer_fabric_type` | string | Human-readable fabric type |
| `_customizer_color` | string | Selected color |
| `_is_customized` | string | `'yes'` - flag for filtering |

## Coordinate System

All print areas use **relative coordinates** in the range `0.0` to `1.0`:

- `x = 0.0` → Left edge of mockup
- `x = 1.0` → Right edge of mockup
- `y = 0.0` → Top edge of mockup
- `y = 1.0` → Bottom edge of mockup

**Example:**
```php
array(
    'x' => 0.25,  // Start 25% from left
    'y' => 0.30,  // Start 30% from top
    'w' => 0.50,  // Width is 50% of mockup
    'h' => 0.40   // Height is 40% of mockup
)
```

This ensures print areas scale correctly across different mockup sizes and device resolutions.

## WooCommerce Integration

### Hooks Used

**Filters:**
```php
// Validate add-to-cart submissions
add_filter('woocommerce_add_to_cart_validation', array($cart_handler, 'validate_add_to_cart'), 10, 3);

// Add custom data to cart item
add_filter('woocommerce_add_cart_item_data', array($cart_handler, 'add_cart_item_data'), 10, 2);

// Make each customized item unique in cart
add_filter('woocommerce_cart_item_key', array($cart_handler, 'make_cart_item_unique'), 10, 3);

// Display custom thumbnail in cart
add_filter('woocommerce_cart_item_thumbnail', array($cart_handler, 'cart_item_thumbnail'), 10, 3);

// Display meta in cart
add_filter('woocommerce_get_item_data', array($cart_handler, 'display_cart_item_data'), 10, 2);

// Format order meta keys for display
add_filter('woocommerce_order_item_display_meta_key', array($order_handler, 'format_meta_key'), 10, 3);

// Format order meta values for display
add_filter('woocommerce_order_item_display_meta_value', array($order_handler, 'format_meta_value'), 10, 3);
```

**Actions:**
```php
// Save customization data to order
add_action('woocommerce_checkout_create_order_line_item', array($order_handler, 'save_to_order'), 10, 4);

// Add admin order columns
add_action('woocommerce_admin_order_item_headers', array($order_handler, 'admin_order_item_headers'));
add_action('woocommerce_admin_order_item_values', array($order_handler, 'admin_order_item_values'), 10, 3);
```

## AJAX Endpoints

### Frontend (Customer-Facing)

**Upload Design File:**
```javascript
jQuery.ajax({
    url: aakaari_customizer.ajax_url,
    method: 'POST',
    data: formData, // FormData with file
    processData: false,
    contentType: false,
    success: function(response) {
        // response.data = {attachment_id, url, thumbnail, width, height}
    }
});
```

**Action:** `aakaari_upload_design`
**Capability:** `upload_files`
**Handler:** `Aakaari_File_Handler::ajax_upload_design()`

**Validate Design:**
```javascript
jQuery.post(aakaari_customizer.ajax_url, {
    action: 'aakaari_validate_design',
    nonce: aakaari_customizer.nonce,
    product_id: 123,
    variation_id: 456,
    design_data: JSON.stringify(designData)
});
```

**Action:** `aakaari_validate_design`
**Capability:** None (public)
**Handler:** `Aakaari_Validator::ajax_validate()`

**Add Customized Product to Cart:**
```javascript
jQuery.post(aakaari_customizer.ajax_url, {
    action: 'aakaari_add_customized_to_cart',
    nonce: aakaari_customizer.nonce,
    product_id: 123,
    variation_id: 456,
    quantity: 1,
    custom_design: JSON.stringify(designData)
});
```

**Action:** `aakaari_add_customized_to_cart`
**Capability:** None (public)
**Handler:** `Aakaari_Cart_Handler::ajax_add_to_cart()`

### Backend (Admin)

**Save Print Area:**
```javascript
jQuery.post(ajaxurl, {
    action: 'aakaari_save_print_area',
    nonce: aakaari_customizer_admin.nonce,
    product_id: 123,
    variation_id: 0, // or specific variation ID
    print_area: JSON.stringify({x, y, w, h})
});
```

**Action:** `aakaari_save_print_area`
**Capability:** `edit_products`
**Handler:** `Aakaari_Print_Area_Manager::ajax_save()`

**Upload Mockup:**
```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: formData, // FormData with file + product_id + color
    processData: false,
    contentType: false
});
```

**Action:** `aakaari_upload_mockup`
**Capability:** `manage_options`
**Handler:** `Aakaari_Mockup_Manager::ajax_upload()`

**Get Design Files (for download):**
```javascript
jQuery.post(ajaxurl, {
    action: 'aakaari_get_design_files',
    nonce: aakaari_customizer_admin.nonce,
    item_id: 123 // Order item ID
});
```

**Action:** `aakaari_get_design_files`
**Capability:** `edit_shop_orders`
**Handler:** `Aakaari_Order_Handler::ajax_get_design()`

## Security

### Nonce Validation

All AJAX endpoints validate WordPress nonces:

```php
// Frontend nonce
check_ajax_referer('aakaari_customizer', 'nonce');

// Admin nonce
check_ajax_referer('aakaari_customizer_admin', 'nonce');
```

### Capability Checks

| Endpoint | Required Capability |
|----------|-------------------|
| Upload design (frontend) | `upload_files` |
| Validate design | None (public) |
| Add to cart | None (public) |
| Save print area | `edit_products` |
| Upload mockup | `manage_options` |
| Download order files | `edit_shop_orders` |

### Input Sanitization

All inputs are sanitized:

```php
$product_id = absint($_POST['product_id']);
$color = sanitize_text_field($_POST['color']);
$print_area = array(
    'x' => floatval($print_area['x']),
    'y' => floatval($print_area['y']),
    'w' => floatval($print_area['w']),
    'h' => floatval($print_area['h'])
);
```

### File Validation

File uploads are validated for:
- **Type:** Only images (MIME type check)
- **Size:** Respects WordPress upload limits
- **Security:** Uses `wp_handle_upload()` with security checks

```php
$upload = wp_handle_upload($file, array(
    'test_form' => false,
    'mimes' => array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png'
    )
));
```

## Validation Flow

### Client-Side (Real-Time)

1. User manipulates design on canvas (drag/scale/rotate)
2. Fabric.js `object:modified` event fires
3. `enforceConstraints()` function calculates bounding box
4. If design exceeds print area, position/scale is adjusted
5. Canvas updates instantly

### Server-Side (Add-to-Cart)

1. User clicks "Add to Cart"
2. Design data submitted via AJAX
3. `Aakaari_Validator::validate_design_data()` checks structure
4. `Aakaari_Validator::validate_design_boundaries()` calculates bounds
5. If invalid, error returned and cart addition blocked
6. If valid, item added to cart

**Validation Tolerance:**
```php
const TOLERANCE = 0.001; // 0.1% tolerance for floating-point comparison
```

## Helper Functions

### Get Customizer Instance

```php
$customizer = aakaari_customizer();
```

Returns singleton instance of `Aakaari_Customizer_Core`.

### Get Product Mockups

```php
$mockups = aakaari_get_product_mockups($product_id);

// Returns:
array(
    'white' => array(
        'attachment_id' => 123,
        'url' => 'https://...'
    ),
    'variation_456' => array(
        'attachment_id' => 789,
        'url' => 'https://...',
        'attributes' => array('pa_color' => 'black')
    )
)
```

### Check if Product is Customizable

```php
$is_customizable = get_post_meta($product_id, '_customizer_enabled', true) === 'yes';
```

### Get Print Areas

```php
$print_areas = get_post_meta($product_id, '_customizer_print_areas', true);
// Returns: array('default' => array('x' => 0.1, 'y' => 0.2, 'w' => 0.8, 'h' => 0.6))
```

### Get Variation Print Area

```php
$print_area = get_post_meta($variation_id, '_variation_print_area', true);
// Returns: array('x' => 0.1, 'y' => 0.2, 'w' => 0.8, 'h' => 0.6) or false
```

## Frontend Implementation

See `FRONTEND-IMPLEMENTATION-GUIDE.md` for complete Fabric.js canvas implementation.

### Basic Product Template Integration

```php
<?php if (get_post_meta($product_id, '_customizer_enabled', true) === 'yes'): ?>
    <div id="customizer-wrapper">
        <!-- Color Selector -->
        <div id="color-selector">
            <?php
            $mockups = aakaari_get_product_mockups($product_id);
            foreach ($mockups as $color => $mockup):
            ?>
                <button class="color-swatch" data-color="<?php echo esc_attr($color); ?>" data-mockup-url="<?php echo esc_url($mockup['url']); ?>">
                    <?php echo esc_html(ucfirst($color)); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Canvas -->
        <canvas id="customizer-canvas"></canvas>

        <!-- Controls -->
        <button id="upload-design">Upload Design</button>
        <select id="print-type">
            <option value="direct">Direct Print</option>
            <option value="dtg">DTG</option>
        </select>
        <select id="fabric-type">
            <option value="polyester">Polyester</option>
            <option value="cotton">Cotton</option>
        </select>
        <button id="add-to-cart">Add to Cart</button>
    </div>
<?php endif; ?>
```

### Required JavaScript Libraries

```html
<!-- Fabric.js for canvas manipulation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
```

## Admin Usage

### Enabling Customization on a Product

1. Edit product in WP Admin
2. Scroll to "Product Customization" meta box
3. Check "Enable Customization"
4. Save product

### Configuring Print Areas

1. In "Product Customization" meta box
2. Upload mockup image for reference
3. Use visual editor or enter coordinates:
   - X: `0.1` (10% from left)
   - Y: `0.2` (20% from top)
   - Width: `0.8` (80% of mockup width)
   - Height: `0.6` (60% of mockup height)
4. Save print area

### Uploading Mockups

**For Color-Based Products:**
1. In "Product Mockups" section
2. Select color: White
3. Upload mockup image
4. Repeat for each color

**For Variable Products:**
1. Edit variation
2. Upload "Variation Mockup"
3. Save variation
4. Repeat for each variation

### Managing Orders with Customizations

1. Navigate to WooCommerce → Orders
2. Customized items show thumbnail in "Customization" column
3. Click thumbnail to view full preview
4. Click "Download Files" to get design assets
5. All customization details visible in order item meta

## Troubleshooting

### "Design extends outside print area" error

**Cause:** Server-side validation detected design outside boundaries.

**Solution:**
1. Check print area configuration is correct
2. Verify coordinates are in 0-1 range
3. Test constraint enforcement on frontend
4. Check browser console for JavaScript errors

### Mockup not loading

**Cause:** Mockup attachment ID doesn't exist or file deleted.

**Solution:**
1. Check WordPress Media Library for mockup image
2. Re-upload mockup in product settings
3. Verify attachment ID in database:
```sql
SELECT * FROM wp_postmeta WHERE meta_key = '_customizer_mockups' AND post_id = [PRODUCT_ID];
```

### Customization data lost in cart

**Cause:** Session expiry or cart data not properly stored.

**Solution:**
1. Check that `Aakaari_Cart_Handler` hooks are registered
2. Verify cart item has `unique_key` to prevent aggregation
3. Check `WC()->session` is available
4. Test with different user roles (guest vs logged-in)

### Admin can't download design files

**Cause:** Capability check or attachment not found.

**Solution:**
1. Verify user has `edit_shop_orders` capability
2. Check order item meta contains `_custom_design_attachments`
3. Verify attachment IDs exist in `wp_posts` table
4. Check file exists in `wp-content/uploads/`

### Canvas laggy or slow

**Cause:** Large mockup images or too many canvas objects.

**Solution:**
1. Optimize mockup images (compress, resize to ~1000px)
2. Use `fabric.Image.fromURL()` with crossOrigin
3. Limit number of design elements
4. Consider disabling object caching if many objects

## Performance Tips

1. **Optimize Mockup Images:**
   - Max 1000-1500px width
   - Compress with tools like TinyPNG
   - Use progressive JPEG or optimized PNG

2. **Limit Canvas Objects:**
   - Reasonable limit: 10-20 objects per design
   - Flatten/group objects when possible

3. **Lazy Load Mockups:**
   - Only load mockup when variation selected
   - Preload next likely variation

4. **Cache Print Areas:**
   - Store print area in JavaScript variable
   - Don't re-fetch on every color change

5. **Database Optimization:**
   - Attachment IDs instead of base64 = 99% less data
   - Index custom meta keys if searching frequently

## Browser Compatibility

Tested and supported:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile Safari (iOS 14+)
- ✅ Mobile Chrome (Android 10+)

## Dependencies

- **WordPress:** 5.8+
- **WooCommerce:** 5.0+
- **PHP:** 7.4+
- **Fabric.js:** 5.3.0+

## File Locations

- **PHP Classes:** `/inc/customizer/`
- **JavaScript:** `/assets/js/customizer-canvas.js`
- **CSS:** `/assets/css/customizer-frontend.css`
- **Admin JS:** `/assets/js/customizer-admin.js`
- **Admin CSS:** `/assets/css/customizer-admin.css`
- **Uploads:** `/wp-content/uploads/customizer/`

## Constants

Defined in `Aakaari_Customizer_Core`:

```php
const META_ENABLED = '_customizer_enabled';
const META_PRINT_AREAS = '_customizer_print_areas';
const META_MOCKUPS = '_customizer_mockups';
const META_REQUIRED = '_customizer_required';
const VAR_MOCKUP_ID = '_variation_mockup_attachment_id';
const VAR_PRINT_AREA = '_variation_print_area';
const CART_CUSTOM_DESIGN = 'custom_design';
const CART_UNIQUE_KEY = 'unique_key';
const ORDER_CUSTOM_DESIGN = '_custom_design';
const ORDER_ATTACHMENTS = '_custom_design_attachments';
```

## Extending the System

### Add Custom Print Types

```php
add_filter('aakaari_customizer_print_types', function($types) {
    $types['screen'] = __('Screen Print', 'aakaari');
    $types['sublimation'] = __('Sublimation', 'aakaari');
    return $types;
});
```

### Add Custom Fabric Types

```php
add_filter('aakaari_customizer_fabric_types', function($types) {
    $types['silk'] = __('Silk', 'aakaari');
    $types['leather'] = __('Leather', 'aakaari');
    return $types;
});
```

### Modify Validation Logic

```php
add_filter('aakaari_customizer_validate_design', function($is_valid, $design_data, $product_id) {
    // Custom validation logic
    if ($design_data['width'] < 0.2) {
        return new WP_Error('design_too_small', 'Design must be at least 20% of mockup width');
    }
    return $is_valid;
}, 10, 3);
```

### Customize Order Display

```php
add_filter('aakaari_customizer_order_item_meta', function($meta, $item, $design_data) {
    $meta['Custom Field'] = 'Custom Value';
    return $meta;
}, 10, 3);
```

## Support

For issues, bugs, or feature requests:
1. Check `QA-RUNBOOK.md` for testing procedures
2. Review `MIGRATION-GUIDE.md` if upgrading
3. Check browser console for JavaScript errors
4. Check WordPress debug log for PHP errors
5. Contact development team with:
   - Product ID
   - Order ID (if applicable)
   - Steps to reproduce
   - Browser and version
   - Screenshots or error messages

## License

Proprietary - Aakaari Theme

---

**Version:** 2.0
**Last Updated:** 2025-10-31
**Author:** Aakaari Development Team
