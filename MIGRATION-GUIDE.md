# Migration Guide: Print Studio → Product Customizer v2.0

This guide helps you transition existing products from the legacy Print Studio system to the new Product Customizer v2.0.

## Overview

The new customizer system provides:
- ✅ Better data persistence (cart → order)
- ✅ Server-side validation blocking invalid designs
- ✅ Variation-aware mockup system
- ✅ Relative coordinate system for print areas
- ✅ Proper attachment handling (no base64 bloat)
- ✅ Enhanced security with nonce validation

## Breaking Changes

### Meta Key Changes

| Old Print Studio | New Customizer | Notes |
|------------------|----------------|-------|
| `_enable_print_studio` | `_customizer_enabled` | Enable flag |
| `_print_studio_mockups` | `_customizer_mockups` | Color-based mockups |
| `_print_area` | `_customizer_print_areas` | Now array of areas |
| `_variation_mockup` | `_variation_mockup_attachment_id` | Variation mockups |
| N/A | `_variation_print_area` | Per-variation print area |

### Cart Item Data Structure

**Old Print Studio:**
```php
'aakaari_designs' => array(
    'design_data' => base64_encoded_string,
    'canvas_json' => json_string,
    'print_type' => 'direct',
    // ... loosely structured
)
```

**New Customizer:**
```php
'custom_design' => array(
    'attachment_ids' => array(123, 456),  // WP attachment IDs
    'preview_url' => 'https://...',       // PNG preview URL
    'applied_transform' => array(
        'scale' => 1.0,
        'x' => 0.5,
        'y' => 0.5,
        'rotation' => 0
    ),
    'print_area_meta' => array(
        'x' => 0.1,
        'y' => 0.2,
        'w' => 0.8,
        'h' => 0.6
    ),
    'print_type' => 'direct',
    'fabric_type' => 'polyester',
    'color' => 'white',
    'variation_id' => 123
)
```

### Order Meta Changes

**Old Print Studio:**
- Order meta scattered across multiple keys
- Base64 images causing database bloat
- Limited admin visibility

**New Customizer:**
- `_custom_design` - Complete design data
- `_custom_design_attachments` - Array of attachment IDs
- `_customizer_print_type` - Print type label
- `_customizer_fabric_type` - Fabric type label
- `_customizer_color` - Color selection
- `_is_customized` - Flag for quick filtering

## Migration Steps

### Step 1: Backup Your Database

```bash
# Via WP-CLI
wp db export backup-before-customizer-migration.sql

# Or via phpMyAdmin
# Export entire database with "Complete inserts" option checked
```

### Step 2: Audit Existing Products

Run this query to see which products have Print Studio data:

```sql
SELECT
    p.ID,
    p.post_title,
    pm1.meta_value as enable_print_studio,
    pm2.meta_value as mockups
FROM wp_posts p
LEFT JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_enable_print_studio'
LEFT JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_print_studio_mockups'
WHERE p.post_type = 'product'
AND pm1.meta_value IS NOT NULL;
```

### Step 3: Migrate Product Meta (Manual Method)

For each product with Print Studio enabled:

1. **Enable New Customizer:**
   - Edit product in WP Admin
   - Scroll to "Product Customization" meta box
   - Check "Enable Customization"

2. **Upload Mockups:**
   - Find existing mockup images (check Print Studio meta)
   - Upload to "Product Mockups" section
   - Map to appropriate colors/variations

3. **Configure Print Area:**
   - Use the visual print area editor
   - Drag and resize to match old print area
   - Save print area configuration

4. **Test Product:**
   - View product on frontend
   - Verify mockup loads correctly
   - Test customization and add to cart
   - Check cart and checkout display

### Step 4: Migrate Product Meta (Automated Script)

**⚠️ Test on staging site first!**

Create file `migrate-customizer.php` in theme root:

```php
<?php
/**
 * Migration Script: Print Studio → Customizer v2.0
 *
 * Usage: Run via WP-CLI or load in functions.php with admin_init hook
 */

function aakaari_migrate_print_studio_to_customizer() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_enable_print_studio',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));

    $migrated = 0;
    $skipped = 0;
    $errors = array();

    foreach ($products as $product) {
        $product_id = $product->ID;

        try {
            // 1. Enable customizer
            update_post_meta($product_id, '_customizer_enabled', 'yes');

            // 2. Migrate mockups
            $old_mockups = get_post_meta($product_id, '_print_studio_mockups', true);
            if (is_array($old_mockups) && !empty($old_mockups)) {
                $new_mockups = array();

                foreach ($old_mockups as $color => $mockup_data) {
                    // If mockup_data is attachment ID
                    if (is_numeric($mockup_data)) {
                        $new_mockups[$color] = array(
                            'attachment_id' => intval($mockup_data),
                            'url' => wp_get_attachment_url($mockup_data)
                        );
                    }
                    // If mockup_data is array with attachment_id
                    elseif (is_array($mockup_data) && isset($mockup_data['attachment_id'])) {
                        $new_mockups[$color] = $mockup_data;
                    }
                }

                if (!empty($new_mockups)) {
                    update_post_meta($product_id, '_customizer_mockups', $new_mockups);
                }
            }

            // 3. Migrate print area
            $old_print_area = get_post_meta($product_id, '_print_area', true);
            if (is_array($old_print_area) && !empty($old_print_area)) {
                // Convert absolute pixels to relative coordinates if needed
                // This requires knowing the mockup dimensions - adjust as needed
                $new_print_areas = array(
                    'default' => array(
                        'x' => isset($old_print_area['x']) ? floatval($old_print_area['x']) : 0.1,
                        'y' => isset($old_print_area['y']) ? floatval($old_print_area['y']) : 0.2,
                        'w' => isset($old_print_area['w']) ? floatval($old_print_area['w']) : 0.8,
                        'h' => isset($old_print_area['h']) ? floatval($old_print_area['h']) : 0.6
                    )
                );
                update_post_meta($product_id, '_customizer_print_areas', $new_print_areas);
            } else {
                // Set default print area if none exists
                $new_print_areas = array(
                    'default' => array(
                        'x' => 0.1,
                        'y' => 0.2,
                        'w' => 0.8,
                        'h' => 0.6
                    )
                );
                update_post_meta($product_id, '_customizer_print_areas', $new_print_areas);
            }

            // 4. Mark as required if it was required before
            $required = get_post_meta($product_id, '_print_studio_required', true);
            if ($required === 'yes') {
                update_post_meta($product_id, '_customizer_required', 'yes');
            }

            $migrated++;

        } catch (Exception $e) {
            $errors[] = "Product ID {$product_id}: " . $e->getMessage();
            $skipped++;
        }
    }

    // Output results
    echo "<div class='notice notice-success'>";
    echo "<p><strong>Migration Complete!</strong></p>";
    echo "<p>Migrated: {$migrated} products</p>";
    echo "<p>Skipped: {$skipped} products</p>";
    echo "</div>";

    if (!empty($errors)) {
        echo "<div class='notice notice-error'>";
        echo "<p><strong>Errors:</strong></p><ul>";
        foreach ($errors as $error) {
            echo "<li>" . esc_html($error) . "</li>";
        }
        echo "</ul></div>";
    }
}

// Run migration (uncomment to execute)
// add_action('admin_init', 'aakaari_migrate_print_studio_to_customizer');
```

**To run the migration:**

```bash
# Via WP-CLI (recommended)
wp eval-file migrate-customizer.php

# Or uncomment the add_action line and visit WP Admin once
```

### Step 5: Migrate Pending Orders (Optional)

If you have pending orders with Print Studio customizations:

1. **Leave old system active temporarily** by keeping Print Studio functions in `/inc/` directory
2. Orders already placed will continue to display correctly
3. **After all pending orders are fulfilled**, you can:
   - Remove old Print Studio files
   - Clean up old meta keys (optional - they won't interfere)

**To clean up old meta keys:**

```sql
-- CAUTION: Only run after all orders are fulfilled and you've verified everything works!

DELETE FROM wp_postmeta WHERE meta_key IN (
    '_enable_print_studio',
    '_print_studio_mockups',
    '_print_area',
    '_print_studio_required'
);
```

### Step 6: Update Frontend Templates

If you have custom product templates:

1. **Replace Print Studio canvas** with new customizer canvas:

```php
// Old Print Studio template
<?php if (get_post_meta($product_id, '_enable_print_studio', true) === 'yes'): ?>
    <div id="print-studio-canvas"></div>
<?php endif; ?>

// New Customizer template
<?php if (get_post_meta($product_id, '_customizer_enabled', true) === 'yes'): ?>
    <div id="customizer-canvas"></div>
<?php endif; ?>
```

2. **Enqueue new assets:**

The new customizer automatically enqueues assets via `wp_enqueue_scripts` hook. If you manually enqueued Print Studio assets, remove those.

### Step 7: Test Everything

Use the QA runbook (`QA-RUNBOOK.md`) to test:

1. ✅ Product page displays mockup correctly
2. ✅ Color selector updates mockup
3. ✅ Canvas constraints work properly
4. ✅ Add to cart validation blocks invalid designs
5. ✅ Cart displays customization correctly
6. ✅ Order email includes preview image
7. ✅ Admin can download design files

## Rollback Plan

If you need to rollback:

1. **Restore database backup:**
```bash
wp db import backup-before-customizer-migration.sql
```

2. **Disable new customizer:**
   - Comment out line in `functions.php`:
   ```php
   // require_once get_stylesheet_directory() . '/inc/customizer/init.php';
   ```

3. **Re-enable old Print Studio functions** (if removed)

## Backwards Compatibility

The new customizer is designed to coexist with old orders:

- ✅ Old order meta (`aakaari_designs`) continues to display in admin
- ✅ New orders use new meta structure (`_custom_design`)
- ✅ Both systems can run simultaneously during transition
- ✅ Old product meta won't break anything

## Coordinate System Migration

**Important:** Print areas use different coordinate systems:

**Old Print Studio:**
- Absolute pixels: `{x: 100, y: 150, w: 300, h: 400}`
- Tied to specific mockup size

**New Customizer:**
- Relative percentages: `{x: 0.2, y: 0.25, w: 0.6, h: 0.5}`
- Device-independent, scales with any mockup

**To convert:**
```javascript
// If you know mockup dimensions:
const mockupWidth = 600;  // px
const mockupHeight = 800; // px

const relativeX = absoluteX / mockupWidth;
const relativeY = absoluteY / mockupHeight;
const relativeW = absoluteW / mockupWidth;
const relativeH = absoluteH / mockupHeight;
```

## Support

**Common Issues:**

1. **"Mockup not loading"**
   - Check that mockup attachment ID exists
   - Verify file hasn't been deleted from media library
   - Check browser console for 404 errors

2. **"Print area too small/wrong position"**
   - Re-configure print area using visual editor
   - Ensure coordinates are in 0-1 range
   - Check mockup image dimensions

3. **"Old orders not displaying"**
   - Keep old Print Studio functions active temporarily
   - Old meta keys are still read by legacy system

4. **"Validation blocking valid designs"**
   - Check print area configuration
   - Verify coordinate system (relative vs absolute)
   - Check browser console for validation errors

## Timeline Recommendation

- **Week 1:** Test migration on staging site
- **Week 2:** Migrate 5-10 test products on production, monitor for issues
- **Week 3:** Migrate remaining products in batches
- **Week 4:** Clean up old meta keys (optional)

## Checklist

Before going live:

- [ ] Database backup created
- [ ] Migration script tested on staging
- [ ] At least 3 test products migrated and verified
- [ ] Frontend templates updated
- [ ] QA tests passed
- [ ] Team trained on new admin UI
- [ ] Rollback plan documented
- [ ] Support tickets prepared for customer questions

## Need Help?

Contact the development team with:
1. Product ID experiencing issues
2. Browser console errors
3. Steps to reproduce
4. Expected vs actual behavior
