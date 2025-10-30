# Master Fix Plan - All Issues

## Issues Summary

### Issue 1: Manual Page Creation After Database Reset
**Problem:** Every time database is reset or theme is deleted, pages need to be created manually
**Impact:** Time-consuming, error-prone, bad developer experience
**Priority:** HIGH

### Issue 2: Print Area Customization Issues
**Problem:**
- Designs can be uploaded/placed beyond print area boundaries
- Print area overlay shows different location than actual boundaries
**Impact:** Customers can create invalid designs, printing errors
**Priority:** CRITICAL

### Issue 3: Uploaded Images Drag Not Working Properly
**Problem:** Dragging uploaded images doesn't work correctly
**Impact:** Poor user experience, can't position images
**Priority:** HIGH

### Issue 4: Add to Cart Requires Design/Image
**Problem:** Cannot add product to cart without uploading design
**Impact:** Prevents simple product purchases without customization
**Priority:** HIGH

---

## ISSUE 1: Automatic Page Creation

### Current Situation Analysis

**Pages Needed:**
1. Shop page (WooCommerce)
2. Cart page (WooCommerce)
3. Checkout page (WooCommerce)
4. My Account page (WooCommerce)
5. Login page (custom)
6. Register page (custom)
7. Become a Reseller page (custom)
8. Application Pending page (custom)
9. Reseller Dashboard page (custom template)
10. Admin Dashboard page (custom template)
11. Track Order page (custom)
12. Contact page (custom)
13. How It Works page (custom)
14. Pricing page (custom)

### Solution: Theme Activation Hook

**Implementation:**

```php
// In functions.php or new file: inc/theme-pages-setup.php

function aakaari_create_required_pages() {
    $pages = array(
        // WooCommerce pages
        'shop' => array(
            'title' => 'Shop',
            'content' => '[woocommerce_shop]',
            'template' => '',
            'option' => 'woocommerce_shop_page_id'
        ),
        'cart' => array(
            'title' => 'Cart',
            'content' => '[woocommerce_cart]',
            'template' => '',
            'option' => 'woocommerce_cart_page_id'
        ),
        'checkout' => array(
            'title' => 'Checkout',
            'content' => '[woocommerce_checkout]',
            'template' => '',
            'option' => 'woocommerce_checkout_page_id'
        ),
        'my-account' => array(
            'title' => 'My Account',
            'content' => '[woocommerce_my_account]',
            'template' => '',
            'option' => 'woocommerce_myaccount_page_id'
        ),

        // Custom pages
        'login' => array(
            'title' => 'Login',
            'content' => '',
            'template' => 'login.php',
            'option' => 'aakaari_login_page_id'
        ),
        'register' => array(
            'title' => 'Register',
            'content' => '',
            'template' => 'register.php',
            'option' => 'aakaari_register_page_id'
        ),
        'become-a-reseller' => array(
            'title' => 'Become a Reseller',
            'content' => '[become_a_reseller]',
            'template' => 'become-a-reseller.php',
            'option' => 'aakaari_reseller_page_id'
        ),
        'application-pending' => array(
            'title' => 'Application Pending',
            'content' => '',
            'template' => 'application-pending.php',
            'option' => 'aakaari_pending_page_id'
        ),
        'reseller-dashboard' => array(
            'title' => 'Reseller Dashboard',
            'content' => '',
            'template' => 'reseller-dashboard.php',
            'option' => 'aakaari_dashboard_page_id'
        ),
        'admin-dashboard' => array(
            'title' => 'Admin Dashboard',
            'content' => '',
            'template' => 'admindashboard.php',
            'option' => 'aakaari_admin_dashboard_page_id'
        ),
        'track-order' => array(
            'title' => 'Track Order',
            'content' => '',
            'template' => 'page-track-order.php',
            'option' => 'aakaari_track_order_page_id'
        ),
        'contact' => array(
            'title' => 'Contact',
            'content' => '',
            'template' => 'contact.php',
            'option' => 'aakaari_contact_page_id'
        ),
        'how-it-works' => array(
            'title' => 'How It Works',
            'content' => '',
            'template' => 'how-it-works.php',
            'option' => 'aakaari_how_it_works_page_id'
        ),
        'pricing' => array(
            'title' => 'Pricing',
            'content' => '',
            'template' => 'pricing.php',
            'option' => 'aakaari_pricing_page_id'
        ),
    );

    foreach ($pages as $slug => $page) {
        // Check if page already exists
        $page_id = get_option($page['option']);

        if (!$page_id || !get_post($page_id)) {
            // Create page
            $page_data = array(
                'post_title'   => $page['title'],
                'post_content' => $page['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $slug,
                'post_author'  => 1,
            );

            $page_id = wp_insert_post($page_data);

            // Set template if specified
            if (!empty($page['template'])) {
                update_post_meta($page_id, '_wp_page_template', $page['template']);
            }

            // Store page ID in option
            update_option($page['option'], $page_id);

            error_log("Aakaari: Created page '{$page['title']}' (ID: $page_id)");
        }
    }
}

// Run on theme activation
add_action('after_switch_theme', 'aakaari_create_required_pages');

// Also provide manual trigger for debugging
add_action('admin_init', function() {
    if (isset($_GET['aakaari_create_pages']) && current_user_can('manage_options')) {
        aakaari_create_required_pages();
        wp_redirect(admin_url('themes.php?pages_created=1'));
        exit;
    }
});
```

**Testing:**
1. Activate theme → All pages created automatically
2. Delete pages → Visit `wp-admin?aakaari_create_pages=1` → Pages recreated
3. Database reset → Reactivate theme → Pages created

---

## ISSUE 2: Print Area Coordinate System Fix

### Root Cause

**Print Studio Canvas:** 500x500px (fixed)
**Product Customizer Canvas:** Dynamic size (not 500x500)
**Problem:** Coordinates saved from Print Studio are used directly without scaling

### Example:
```
Print Studio saves: {x: 60, y: 60, width: 380, height: 420} (on 500x500 canvas)
Product Customizer uses: {x: 60, y: 60, width: 380, height: 420} (but canvas might be 800x600!)
Result: Print area appears in wrong location and wrong size
```

### Solution: Coordinate Transformation System

**Step 1: Determine Canvas Sizes**

```javascript
// In product-customizer.js - setupCanvas()
function setupCanvas() {
    state.canvas = document.getElementById('interactive-canvas');
    if (!state.canvas) {
        console.error('Canvas element not found!');
        return;
    }

    state.ctx = state.canvas.getContext('2d');

    // IMPORTANT: Store the canvas dimensions
    state.canvasWidth = state.canvas.width;
    state.canvasHeight = state.canvas.height;

    // Define Print Studio canvas size (constant)
    state.PRINT_STUDIO_WIDTH = 500;
    state.PRINT_STUDIO_HEIGHT = 500;

    // Calculate scaling factors
    state.scaleX = state.canvasWidth / state.PRINT_STUDIO_WIDTH;
    state.scaleY = state.canvasHeight / state.PRINT_STUDIO_HEIGHT;

    console.log('Canvas size:', state.canvasWidth, 'x', state.canvasHeight);
    console.log('Scale factors:', state.scaleX, state.scaleY);

    renderCanvas();
}
```

**Step 2: Transform Print Area Coordinates**

```javascript
// Transform print area from Print Studio coordinates to canvas coordinates
function transformPrintArea(area) {
    if (!area) return null;

    return {
        x: area.x * state.scaleX,
        y: area.y * state.scaleY,
        width: area.width * state.scaleX,
        height: area.height * state.scaleY,
        name: area.name
    };
}

// Get transformed primary print area
function getPrimaryPrintArea() {
    const currentSide = state.product.sides[state.selectedSide];
    if (!currentSide || !currentSide.printAreas || currentSide.printAreas.length === 0) {
        return null;
    }

    // Find largest print area
    let primaryArea = currentSide.printAreas[0];
    let maxSize = primaryArea.width * primaryArea.height;

    currentSide.printAreas.forEach(area => {
        const size = area.width * area.height;
        if (size > maxSize) {
            maxSize = size;
            primaryArea = area;
        }
    });

    // Return transformed coordinates
    return transformPrintArea(primaryArea);
}
```

**Step 3: Fix Constraint Function**

```javascript
function constrainToPrintArea(x, y, width, height) {
    // Get transformed print area
    const printArea = getPrimaryPrintArea();

    if (!printArea) {
        console.warn('No print areas defined');
        return { x, y };
    }

    let constrainedX = x;
    let constrainedY = y;

    // Left boundary
    if (constrainedX < printArea.x) {
        constrainedX = printArea.x;
    }

    // Right boundary
    if (constrainedX + width > printArea.x + printArea.width) {
        constrainedX = printArea.x + printArea.width - width;
    }

    // Top boundary
    if (constrainedY < printArea.y) {
        constrainedY = printArea.y;
    }

    // Bottom boundary
    if (constrainedY + height > printArea.y + printArea.height) {
        constrainedY = printArea.y + printArea.height - height;
    }

    return { x: constrainedX, y: constrainedY };
}
```

**Step 4: Fix Visual Drawing**

```javascript
function drawPrintArea(ctx, area) {
    // Transform to canvas coordinates
    const transformed = transformPrintArea(area);
    if (!transformed) return;

    ctx.save();

    // Draw background tint
    ctx.fillStyle = 'rgba(59, 130, 246, 0.1)';
    ctx.fillRect(transformed.x, transformed.y, transformed.width, transformed.height);

    // Draw border
    ctx.strokeStyle = '#3B82F6';
    ctx.lineWidth = 2;
    ctx.setLineDash([8, 4]);
    ctx.strokeRect(transformed.x, transformed.y, transformed.width, transformed.height);
    ctx.setLineDash([]);

    // Draw labels
    ctx.font = 'bold 14px Arial';
    ctx.fillStyle = '#3B82F6';
    ctx.fillText('✓ DESIGN AREA', transformed.x + 10, transformed.y + 25);

    ctx.font = '12px Arial';
    ctx.fillStyle = '#6B7280';
    ctx.textAlign = 'center';
    ctx.fillText('Place your designs here',
                 transformed.x + transformed.width / 2,
                 transformed.y + transformed.height - 15);

    ctx.restore();
}
```

---

## ISSUE 3: Image Drag Not Working

### Investigation Needed

**Possible Causes:**
1. Design position stored as center but calculated as top-left
2. Mouse offset calculation incorrect
3. Image not being treated same as text designs
4. Constraint function interfering

**Debug Steps:**

```javascript
function handleCanvasMouseDown(event) {
    // ... existing code ...

    // Add extensive logging
    console.log('Mouse down at:', x, y);
    console.log('Clicked design:', clickedDesign);
    console.log('Design position:', clickedDesign?.x, clickedDesign?.y);
    console.log('Design size:', clickedDesign?.width, clickedDesign?.height);
    console.log('Drag offset:', clickedOffset);
}

function handleCanvasMouseMove(event) {
    if (!state.draggingDesign) return;

    // Log during drag
    console.log('Dragging - Mouse:', x, y);
    console.log('Dragging - Design before:', state.draggingDesign.x, state.draggingDesign.y);

    // ... apply constraint ...

    console.log('Dragging - Design after:', state.draggingDesign.x, state.draggingDesign.y);
}
```

**Likely Fix:** Ensure image designs use same coordinate system as text

```javascript
// In drawDesign() function
function drawDesign(ctx, design) {
    ctx.save();

    if (design.type === 'text') {
        // Text positioning (center-based)
        ctx.translate(design.x, design.y);
        // ... draw text centered at 0,0 ...
    }
    else if (design.type === 'image') {
        // Image positioning (also center-based for consistency)
        ctx.translate(design.x, design.y);
        ctx.drawImage(design.image, -design.width/2, -design.height/2, design.width, design.height);
    }

    ctx.restore();
}
```

---

## ISSUE 4: Add to Cart Without Design

### Current Problem

```javascript
// Line ~1141 in product-customizer.js
function addToCart() {
    // Check if we have designs and print type
    if (state.designs.length === 0 || !state.selectedPrintType) {
        alert('Please add at least one design and select a print type before adding to cart.');
        return; // BLOCKS ADD TO CART!
    }
    // ...
}
```

### Solution: Make Customization Optional

```javascript
function addToCart() {
    // Customization is now OPTIONAL

    // If user added designs, validate print type
    if (state.designs.length > 0 && !state.selectedPrintType) {
        alert('Please select a print type for your custom design.');
        return;
    }

    // Show loading state
    const addToCartBtn = $('#add-to-cart-btn');
    const originalText = addToCartBtn.text();
    addToCartBtn.text('Adding...');
    addToCartBtn.prop('disabled', true);

    // Prepare data
    let designsData = [];
    if (state.designs.length > 0) {
        // Has customization
        designsData = state.designs.map(design => ({
            id: design.id,
            type: design.type,
            sideIndex: design.sideIndex,
            x: design.x,
            y: design.y,
            width: design.width,
            height: design.height,
            printType: state.selectedPrintType,
            ...(design.type === 'text' && {
                text: design.text,
                fontSize: design.fontSize,
                fontFamily: design.fontFamily,
                color: design.color
            }),
            ...(design.type === 'image' && {
                src: design.src
            })
        }));
    }

    // Create form data
    const formData = new FormData();
    formData.append('action', 'aakaari_add_to_cart');
    formData.append('security', AAKAARI_SETTINGS.nonce);
    formData.append('product_id', state.product.id);
    formData.append('designs', JSON.stringify(designsData)); // Empty array if no designs

    // Add selected color if available
    if (state.selectedColor !== null) {
        formData.append('selected_color', state.selectedColor);
    }

    // Add image files if any
    if (state.designs.length > 0) {
        state.designs.forEach(design => {
            if (design.type === 'image' && design.file) {
                formData.append('files[]', design.file);
            }
        });
    }

    // Send AJAX request
    $.ajax({
        url: AAKAARI_SETTINGS.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Redirect to cart
                window.location.href = response.data.redirect || '/cart/';
            } else {
                alert('Error: ' + (response.data?.message || 'Could not add to cart'));
                addToCartBtn.text(originalText).prop('disabled', false);
            }
        },
        error: function() {
            alert('Error adding to cart. Please try again.');
            addToCartBtn.text(originalText).prop('disabled', false);
        }
    });
}
```

**Update Button Text:**

```javascript
// In updateProductState() or similar
function updateAddToCartButton() {
    const btn = $('#add-to-cart-btn');

    if (state.designs.length > 0) {
        btn.text('Add Custom Product to Cart');
    } else {
        btn.text('Add to Cart');
    }

    // Always enable button
    btn.prop('disabled', false);
}
```

---

## Implementation Order

### Priority Order:

1. **ISSUE 1** - Automatic page creation (Quick, high impact)
2. **ISSUE 4** - Make add to cart optional (Quick, high impact)
3. **ISSUE 2** - Fix print area coordinates (Medium effort, critical)
4. **ISSUE 3** - Fix image drag (Depends on Issue 2, medium effort)

### Estimated Timeline:

- Issue 1: 30 minutes (create function, test)
- Issue 4: 20 minutes (remove check, test)
- Issue 2: 60 minutes (implement transformation, test thoroughly)
- Issue 3: 30 minutes (debug, fix, test)
- **Total: ~2.5 hours**

---

## Testing Checklist

### Issue 1 (Auto Pages):
- [ ] Activate theme → All pages created
- [ ] Check WooCommerce pages work
- [ ] Check custom pages load correct templates
- [ ] Reset database, reactivate → Pages recreated

### Issue 2 (Print Areas):
- [ ] Print area overlay matches actual boundaries
- [ ] Designs cannot be dragged outside print area
- [ ] New designs placed within print area
- [ ] Works on different canvas sizes

### Issue 3 (Image Drag):
- [ ] Upload image → Can drag smoothly
- [ ] Image drag same as text drag
- [ ] Image respects print area boundaries
- [ ] Selection handles work

### Issue 4 (Optional Cart):
- [ ] Can add to cart without designs
- [ ] Can add to cart with designs
- [ ] Button text updates appropriately
- [ ] No errors in console

---

## Ready to Implement?

**Shall I proceed with:**
1. ✅ Automatic page creation system
2. ✅ Make add to cart optional
3. ✅ Fix print area coordinate transformation
4. ✅ Fix image drag functionality

Or would you like me to tackle them in a different order?
