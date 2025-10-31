# Product Customizer Architecture

## Data Model

### Product Meta Keys
```
_customizer_enabled (bool)           - Enable customizer for this product
_customizer_print_areas (array)      - Global print areas if no variations
_customizer_mockups (array)          - Mockups keyed by color/variation
_customizer_required (bool)          - Force customization (no plain option)
```

### Variation Meta Keys (for variable products)
```
_variation_mockup_attachment_id (int)     - Mockup image attachment ID
_variation_print_area (array)             - Print area: {x, y, w, h} in %
```

### Cart Item Meta
```
custom_design => [
  'attachment_ids' => [123, 124],
  'preview_url' => 'https://.../thumb.jpg',
  'applied_transform' => ['scale'=>1.2, 'x'=>10, 'y'=>20, 'rotation'=>0],
  'print_area_meta' => ['x'=>0.10, 'y'=>0.12, 'w'=>0.50, 'h'=>0.60],
  'print_type' => 'dtg',
  'fabric_type' => 'cotton',
  'color' => '#000000',
  'variation_id' => 123
]
unique_key => md5(microtime().rand())
```

### Order Item Meta
```
_custom_design (array)              - Same structure as cart
_custom_design_attachments (array)  - Attachment IDs for download
```

## WooCommerce Hooks

### Add to Cart
- `woocommerce_add_to_cart_validation` - Server-side boundary validation
- `woocommerce_add_cart_item_data` - Add custom design data

### Cart Display
- `woocommerce_get_item_data` - Display in cart
- `woocommerce_cart_item_thumbnail` - Show custom preview

### Order Creation
- `woocommerce_checkout_create_order_line_item` - Persist to order
- `woocommerce_order_item_display_meta_key` - Format meta keys
- `woocommerce_order_item_display_meta_value` - Format meta values

## AJAX Endpoints

### Admin
- `aakaari_save_print_area` - Save print area config
- `aakaari_upload_mockup` - Upload mockup image
- `aakaari_get_order_design` - Get design files

### Frontend
- `aakaari_upload_design` - Upload user design
- `aakaari_validate_design` - Validate boundaries
- `aakaari_add_customized_to_cart` - Add with validation

## File Structure

```
inc/
  customizer/
    class-customizer-core.php         - Main customizer class
    class-print-area-manager.php      - Print area management
    class-mockup-manager.php          - Mockup management
    class-cart-handler.php            - Cart integration
    class-order-handler.php           - Order persistence
    class-validator.php               - Server-side validation
    class-file-handler.php            - File uploads

assets/
  js/
    customizer-canvas.js              - Fabric.js implementation
    customizer-admin.js               - Admin interface
  css/
    customizer-frontend.css
    customizer-admin.css
```

## Coordinate System

All coordinates stored as **relative percentages (0-1 range)**:
- x: 0.10 = 10% from left
- y: 0.12 = 12% from top
- w: 0.50 = 50% of mockup width
- h: 0.60 = 60% of mockup height

Convert to pixels: `px = percentage * mockupDimension`

## Validation Flow

```
Client Upload → Client Bounds Check → AJAX to Server
                                            ↓
                               Server Bounds Check
                                            ↓
                                    Pass? → Add to Cart
                                    Fail? → Return Error
```

## Security Layers

1. Nonce validation on all AJAX
2. Capability checks (manage_options for admin)
3. File type validation (images only)
4. File size limits (wp_max_upload_size)
5. Sanitization of all inputs
6. Bounding box validation server-side
