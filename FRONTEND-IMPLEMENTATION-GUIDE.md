# Frontend Implementation Guide - Fabric.js Canvas

This guide provides complete, copy-paste ready code for implementing the Fabric.js canvas customizer.

## Overview

The frontend consists of:
1. Product page template integration
2. Fabric.js canvas with print area constraints
3. Color/variation switching
4. File upload handling
5. Add to cart with design data

## File: assets/js/customizer-canvas.js

```javascript
/**
 * Product Customizer Canvas
 * Fabric.js implementation with print area constraints
 */

(function($) {
    'use strict';

    let canvas;
    let currentMockup = null;
    let currentPrintArea = null;
    let designObjects = [];

    $(document).ready(function() {
        if (typeof aakaariCustomizer === 'undefined') {
            return;
        }

        init();
    });

    function init() {
        // Initialize Fabric canvas
        initializeCanvas();

        // Setup event handlers
        $('#upload-design').on('change', handleFileUpload);
        $('#add-text').on('click', addTextElement);
        $('#choose-plain').on('click', addPlainToCart);
        $('#add-customized').on('click', addCustomizedToCart);
        $('[name="variation_id"]').on('change', handleVariationChange);
        $('.color-swatch').on('click', handleColorChange);

        // Load initial mockup
        loadInitialMockup();
    }

    /**
     * Initialize Fabric canvas
     */
    function initializeCanvas() {
        canvas = new fabric.Canvas('customizer-canvas', {
            width: 600,
            height: 600,
            backgroundColor: '#f5f5f5'
        });

        // Add canvas event listeners
        canvas.on('object:moving', enforceConstraints);
        canvas.on('object:scaling', enforceConstraints);
        canvas.on('object:rotating', enforceConstraints);
    }

    /**
     * Load initial mockup
     */
    function loadInitialMockup() {
        let mockupUrl;
        let printArea;

        if (aakaariCustomizer.is_variable && aakaariCustomizer.variations) {
            // Get first variation
            const firstVar = Object.values(aakaariCustomizer.variations)[0];
            mockupUrl = firstVar.mockup_url;
            printArea = firstVar.print_area;
        } else if (aakaariCustomizer.mockups) {
            // Get first mockup
            const firstMockup = Object.values(aakaariCustomizer.mockups)[0];
            mockupUrl = firstMockup.url;
            printArea = aakaariCustomizer.print_areas.default;
        }

        if (mockupUrl) {
            loadMockup(mockupUrl, printArea);
        }
    }

    /**
     * Load mockup image
     */
    function loadMockup(url, printArea) {
        fabric.Image.fromURL(url, function(img) {
            // Scale mockup to fit canvas
            const scale = Math.min(
                canvas.width / img.width,
                canvas.height / img.height
            );

            img.scale(scale);
            img.set({
                left: 0,
                top: 0,
                selectable: false,
                evented: false
            });

            // Clear existing mockup
            if (currentMockup) {
                canvas.remove(currentMockup);
            }

            currentMockup = img;
            canvas.add(img);
            canvas.sendToBack(img);

            // Store and draw print area
            currentPrintArea = printArea || {x: 0.1, y: 0.1, w: 0.8, h: 0.8};
            drawPrintAreaOverlay();

            canvas.renderAll();
        });
    }

    /**
     * Draw print area overlay
     */
    function drawPrintAreaOverlay() {
        // Remove existing overlay
        const existing = canvas.getObjects().find(obj => obj.name === 'print-area-overlay');
        if (existing) {
            canvas.remove(existing);
        }

        if (!currentPrintArea || !currentMockup) {
            return;
        }

        const scale = currentMockup.scaleX;
        const mockupWidth = currentMockup.width * scale;
        const mockupHeight = currentMockup.height * scale;

        const rect = new fabric.Rect({
            name: 'print-area-overlay',
            left: mockupWidth * currentPrintArea.x,
            top: mockupHeight * currentPrintArea.y,
            width: mockupWidth * currentPrintArea.w,
            height: mockupHeight * currentPrintArea.h,
            fill: 'rgba(33, 150, 243, 0.1)',
            stroke: '#2196F3',
            strokeWidth: 2,
            strokeDashArray: [5, 5],
            selectable: false,
            evented: false
        });

        canvas.add(rect);
        canvas.sendToBack(rect);
        canvas.sendToBack(currentMockup);
    }

    /**
     * Enforce print area constraints
     */
    function enforceConstraints(e) {
        const obj = e.target;

        if (!currentPrintArea || !currentMockup || obj.name === 'print-area-overlay') {
            return;
        }

        const scale = currentMockup.scaleX;
        const mockupWidth = currentMockup.width * scale;
        const mockupHeight = currentMockup.height * scale;

        const printAreaLeft = mockupWidth * currentPrintArea.x;
        const printAreaTop = mockupHeight * currentPrintArea.y;
        const printAreaRight = printAreaLeft + (mockupWidth * currentPrintArea.w);
        const printAreaBottom = printAreaTop + (mockupHeight * currentPrintArea.h);

        // Get object bounds
        const objBounds = obj.getBoundingRect();

        // Constrain to print area
        if (objBounds.left < printAreaLeft) {
            obj.left = printAreaLeft + (obj.width * obj.scaleX) / 2;
        }
        if (objBounds.top < printAreaTop) {
            obj.top = printAreaTop + (obj.height * obj.scaleY) / 2;
        }
        if (objBounds.left + objBounds.width > printAreaRight) {
            obj.left = printAreaRight - (obj.width * obj.scaleX) / 2;
        }
        if (objBounds.top + objBounds.height > printAreaBottom) {
            obj.top = printAreaBottom - (obj.height * obj.scaleY) / 2;
        }

        obj.setCoords();
    }

    /**
     * Handle file upload
     */
    function handleFileUpload(e) {
        const file = e.target.files[0];

        if (!file) {
            return;
        }

        // Validate file
        if (!file.type.match('image.*')) {
            showError('Please upload an image file.');
            return;
        }

        if (file.size > aakaariCustomizer.max_upload_size) {
            showError('File size exceeds maximum allowed size.');
            return;
        }

        // Upload to server
        const formData = new FormData();
        formData.append('action', 'aakaari_upload_design');
        formData.append('nonce', aakaariCustomizer.nonce);
        formData.append('design_file', file);

        $.ajax({
            url: aakaariCustomizer.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    addImageToCanvas(response.data.url, response.data.attachment_id);
                    showSuccess('Image uploaded successfully.');
                } else {
                    showError(response.data.message || 'Upload failed.');
                }
            },
            error: function() {
                showError('Upload failed. Please try again.');
            }
        });
    }

    /**
     * Add image to canvas
     */
    function addImageToCanvas(url, attachmentId) {
        fabric.Image.fromURL(url, function(img) {
            img.scale(0.3);

            // Center in print area
            if (currentPrintArea && currentMockup) {
                const scale = currentMockup.scaleX;
                const mockupWidth = currentMockup.width * scale;
                const mockupHeight = currentMockup.height * scale;

                img.set({
                    left: mockupWidth * (currentPrintArea.x + currentPrintArea.w / 2),
                    top: mockupHeight * (currentPrintArea.y + currentPrintArea.h / 2),
                    originX: 'center',
                    originY: 'center'
                });
            }

            img.attachmentId = attachmentId;
            canvas.add(img);
            canvas.setActiveObject(img);
            designObjects.push(img);
        });
    }

    /**
     * Add text element
     */
    function addTextElement() {
        const text = new fabric.IText('Your Text Here', {
            fontSize: 30,
            fill: '#000000',
            fontFamily: 'Arial',
            originX: 'center',
            originY: 'center'
        });

        // Center in canvas
        text.set({
            left: canvas.width / 2,
            top: canvas.height / 2
        });

        canvas.add(text);
        canvas.setActiveObject(text);
        designObjects.push(text);
    }

    /**
     * Handle variation change
     */
    function handleVariationChange(e) {
        const variationId = $(this).val();

        if (!variationId || !aakaariCustomizer.variations) {
            return;
        }

        const variation = aakaariCustomizer.variations['variation_' + variationId];

        if (variation && variation.mockup_url) {
            loadMockup(variation.mockup_url, variation.print_area);
        }
    }

    /**
     * Handle color change
     */
    function handleColorChange(e) {
        const color = $(this).data('color');

        if (!color || !aakaariCustomizer.mockups) {
            return;
        }

        const mockup = aakaariCustomizer.mockups[color];

        if (mockup && mockup.url) {
            loadMockup(mockup.url, currentPrintArea);
        }
    }

    /**
     * Add plain product to cart
     */
    function addPlainToCart(e) {
        e.preventDefault();

        // Use standard WooCommerce add to cart
        const form = $('form.cart');
        form.find('[name="is_customized"]').remove();
        form.append('<input type="hidden" name="is_customized" value="false">');
        form.submit();
    }

    /**
     * Add customized product to cart
     */
    function addCustomizedToCart(e) {
        e.preventDefault();

        // Validate that there are designs
        if (designObjects.length === 0) {
            showError('Please add at least one design element.');
            return;
        }

        // Validate all designs are within print area
        if (!validateDesignBounds()) {
            showError('Some design elements are outside the print area. Please adjust.');
            return;
        }

        // Collect design data
        const designData = collectDesignData();

        // Send to server
        $.ajax({
            url: aakaariCustomizer.ajax_url,
            type: 'POST',
            data: {
                action: 'aakaari_add_customized_to_cart',
                nonce: aakaariCustomizer.nonce,
                product_id: aakaariCustomizer.product_id,
                variation_id: $('[name="variation_id"]').val() || 0,
                is_customized: true,
                design_data: JSON.stringify(designData)
            },
            success: function(response) {
                if (response.success) {
                    showSuccess('Product added to cart!');
                    setTimeout(function() {
                        window.location = response.data.cart_url;
                    }, 1000);
                } else {
                    showError(response.data.message || 'Could not add to cart.');
                }
            },
            error: function() {
                showError('An error occurred. Please try again.');
            }
        });
    }

    /**
     * Validate design bounds
     */
    function validateDesignBounds() {
        if (!currentPrintArea || !currentMockup) {
            return true;
        }

        const scale = currentMockup.scaleX;
        const mockupWidth = currentMockup.width * scale;
        const mockupHeight = currentMockup.height * scale;

        const printAreaLeft = mockupWidth * currentPrintArea.x;
        const printAreaTop = mockupHeight * currentPrintArea.y;
        const printAreaRight = printAreaLeft + (mockupWidth * currentPrintArea.w);
        const printAreaBottom = printAreaTop + (mockupHeight * currentPrintArea.h);

        for (let obj of designObjects) {
            const bounds = obj.getBoundingRect();

            if (bounds.left < printAreaLeft ||
                bounds.top < printAreaTop ||
                bounds.left + bounds.width > printAreaRight ||
                bounds.top + bounds.height > printAreaBottom) {
                return false;
            }
        }

        return true;
    }

    /**
     * Collect design data
     */
    function collectDesignData() {
        const scale = currentMockup ? currentMockup.scaleX : 1;
        const mockupWidth = currentMockup ? currentMockup.width * scale : canvas.width;
        const mockupHeight = currentMockup ? currentMockup.height * scale : canvas.height;

        // Collect attachment IDs from uploaded images
        const attachmentIds = [];
        for (let obj of designObjects) {
            if (obj.attachmentId) {
                attachmentIds.push(obj.attachmentId);
            }
        }

        // Get canvas as data URL for preview
        const previewDataUrl = canvas.toDataURL('image/png');

        // Calculate transform (simplified - using first object)
        const firstObj = designObjects[0];
        const transform = {
            scale: firstObj.scaleX,
            x: firstObj.left / mockupWidth,
            y: firstObj.top / mockupHeight,
            rotation: firstObj.angle || 0
        };

        return {
            attachment_ids: attachmentIds,
            preview_url: previewDataUrl,
            applied_transform: transform,
            print_area_meta: currentPrintArea,
            print_type: $('#print-type').val(),
            fabric_type: $('#fabric-type').val(),
            color: $('.color-swatch.active').data('color'),
            variation_id: $('[name="variation_id"]').val() || 0,
            width: mockupWidth,
            height: mockupHeight
        };
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('.customizer-messages').html(
            '<div class="error-message">' + message + '</div>'
        );
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        $('.customizer-messages').html(
            '<div class="success-message">' + message + '</div>'
        );
    }

})(jQuery);
```

## Product Page Template Integration

Add to your product page template (woocommerce/single-product.php or custom template):

```php
<?php if (aakaari_is_customizer_product()): ?>
    <div class="product-customizer">
        <div class="customizer-header">
            <h3><?php _e('Customize Your Product', 'aakaari'); ?></h3>
        </div>

        <div class="customizer-options">
            <button type="button" id="choose-plain" class="button">
                <?php _e('Buy Plain Product', 'aakaari'); ?>
            </button>
            <span><?php _e('or', 'aakaari'); ?></span>
            <button type="button" id="customize-it" class="button button-primary">
                <?php _e('Customize It', 'aakaari'); ?>
            </button>
        </div>

        <div class="customizer-interface" style="display:none;">
            <div class="customizer-canvas-area">
                <canvas id="customizer-canvas"></canvas>
                <div class="print-area-info">
                    <?php _e('Blue dashed area = print area. Design must stay within this area.', 'aakaari'); ?>
                </div>
            </div>

            <div class="customizer-controls">
                <h4><?php _e('Design Tools', 'aakaari'); ?></h4>

                <div class="control-group">
                    <label><?php _e('Upload Image', 'aakaari'); ?></label>
                    <input type="file" id="upload-design" accept="image/*">
                </div>

                <div class="control-group">
                    <button type="button" id="add-text" class="button">
                        <?php _e('Add Text', 'aakaari'); ?>
                    </button>
                </div>

                <div class="control-group">
                    <label for="print-type"><?php _e('Print Type', 'aakaari'); ?></label>
                    <select id="print-type">
                        <?php foreach (aakaari_customizer()->get_print_types() as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="control-group">
                    <label for="fabric-type"><?php _e('Fabric Type', 'aakaari'); ?></label>
                    <select id="fabric-type">
                        <?php foreach (aakaari_customizer()->get_fabric_types() as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="customizer-messages"></div>

                <button type="button" id="add-customized" class="button button-primary button-large">
                    <?php _e('Add to Cart', 'aakaari'); ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#customize-it').on('click', function() {
                $('.customizer-options').hide();
                $('.customizer-interface').show();
            });
        });
    </script>
<?php endif; ?>
```

## CSS (assets/css/customizer-frontend.css)

```css
.product-customizer {
    margin: 20px 0;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.customizer-options {
    text-align: center;
    padding: 20px;
}

.customizer-interface {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.customizer-canvas-area {
    position: relative;
}

#customizer-canvas {
    border: 1px solid #ddd;
    border-radius: 4px;
}

.print-area-info {
    margin-top: 10px;
    padding: 10px;
    background: #e3f2fd;
    border-radius: 4px;
    font-size: 12px;
    color: #1976d2;
}

.customizer-controls {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
}

.control-group {
    margin-bottom: 15px;
}

.control-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.customizer-messages {
    margin: 15px 0;
}

.error-message {
    padding: 10px;
    background: #ffebee;
    color: #c62828;
    border-radius: 4px;
}

.success-message {
    padding: 10px;
    background: #e8f5e9;
    color: #2e7d32;
    border-radius: 4px;
}
```

## Next Steps

1. Copy the JavaScript code to `/assets/js/customizer-canvas.js`
2. Copy the CSS to `/assets/css/customizer-frontend.css`
3. Add the template code to your product page
4. Test with a product that has customizer enabled
5. Upload a design, move it around, and verify constraints work
6. Test add to cart and verify data persists through checkout

