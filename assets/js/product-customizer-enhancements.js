/**
 * Product Customizer Enhancements
 * Adds print area restrictions, color selection with preview, and makes customization optional
 */

(function($) {
    'use strict';

    // State management
    let printAreaBounds = null;
    let selectedColor = null;
    let customizationRequired = false; // Make customization optional by default

    $(document).ready(function() {
        init();
    });

    function init() {
        console.log('Product Customizer Enhancements: Initializing...');

        // Setup print area restrictions
        setupPrintAreaRestrictions();

        // Setup color selection with preview
        setupColorSelection();

        // Make customization optional
        makeCustomizationOptional();

        // Enhance add to cart
        enhanceAddToCart();

        console.log('Product Customizer Enhancements: Initialized');
    }

    /**
     * Setup print area restrictions
     * Prevents designs from being placed outside defined print areas
     */
    function setupPrintAreaRestrictions() {
        if (typeof AAKAARI_PRODUCTS === 'undefined' || !AAKAARI_PRODUCTS[0]) {
            return;
        }

        const product = AAKAARI_PRODUCTS[0];
        const currentSide = product.sides && product.sides[0] ? product.sides[0] : null;

        if (!currentSide || !currentSide.printAreas || !currentSide.printAreas[0]) {
            console.log('No print areas defined for this product');
            return;
        }

        // Store print area bounds
        printAreaBounds = currentSide.printAreas[0];

        console.log('Print area bounds set:', printAreaBounds);

        // Override existing drag/drop handlers to enforce boundaries
        setupBoundaryEnforcement();

        // Overlay disabled per UX request
    }

    /**
     * Setup boundary enforcement for designs
     */
    function setupBoundaryEnforcement() {
        // Hook into existing customizer canvas interactions
        const canvas = document.getElementById('interactive-canvas');
        if (!canvas) return;

        // Store original handlers
        const originalMouseMove = canvas.onmousemove;
        const originalMouseUp = canvas.onmouseup;

        // Wrap handlers to enforce boundaries
        canvas.addEventListener('mousemove', function(e) {
            if (originalMouseMove) {
                originalMouseMove.call(this, e);
            }
            enforceDesignBoundaries();
        }, true);

        canvas.addEventListener('mouseup', function(e) {
            if (originalMouseUp) {
                originalMouseUp.call(this, e);
            }
            enforceDesignBoundaries();
        }, true);

        // Also enforce on touch events for mobile
        canvas.addEventListener('touchmove', function(e) {
            enforceDesignBoundaries();
        }, true);

        canvas.addEventListener('touchend', function(e) {
            enforceDesignBoundaries();
        }, true);
    }

    /**
     * Enforce design boundaries
     */
    function enforceDesignBoundaries() {
        if (!printAreaBounds) return;

        // Access the global state object if available
        if (typeof window.customizerState !== 'undefined' && window.customizerState.designs) {
            window.customizerState.designs.forEach(function(design) {
                // Check if design is outside print area
                if (design.x < printAreaBounds.x) {
                    design.x = printAreaBounds.x;
                }
                if (design.y < printAreaBounds.y) {
                    design.y = printAreaBounds.y;
                }
                if (design.x + design.width > printAreaBounds.x + printAreaBounds.width) {
                    design.x = printAreaBounds.x + printAreaBounds.width - design.width;
                }
                if (design.y + design.height > printAreaBounds.y + printAreaBounds.height) {
                    design.y = printAreaBounds.y + printAreaBounds.height - design.height;
                }
            });

            // Trigger canvas redraw if function exists
            if (typeof window.renderCanvas === 'function') {
                window.renderCanvas();
            }
        }
    }

    /**
     * Show print area overlay on canvas
     */
    function showPrintAreaOverlay() {
        // disabled
        return;
    }

    /**
     * Update print area overlay (when switching colors)
     */
    function updatePrintAreaOverlay() {
        if (!printAreaBounds) return;

        const canvas = document.getElementById('interactive-canvas');
        if (!canvas) return;

        const overlay = document.getElementById('print-area-overlay');
        // Overlay disabled: remove if exists
        if (overlay) {
            overlay.parentElement && overlay.parentElement.removeChild(overlay);
        }
        const helper = document.getElementById('print-area-helper-text');
        if (helper) {
            helper.parentElement && helper.parentElement.removeChild(helper);
        }
    }

    /**
     * Setup color selection with preview
     */
    function setupColorSelection() {
        // Listen for color swatch clicks
        $(document).on('click', '.color-swatch', function() {
            const $swatch = $(this);
            const colorIndex = $swatch.data('color');

            // Update selected state
            $('.color-swatch').removeClass('selected');
            $swatch.addClass('selected');

            // Get color data
            if (typeof AAKAARI_PRODUCTS !== 'undefined' && AAKAARI_PRODUCTS[0]) {
                const product = AAKAARI_PRODUCTS[0];
                const colors = product.colors || [];

                if (colors[colorIndex]) {
                    selectedColor = colors[colorIndex];
                    updatePreviewImage(selectedColor);
                }
            }
        });
    }

    /**
     * Update preview image based on selected color
     */
    function updatePreviewImage(colorData) {
        // Check if there's a color-specific mockup with print area
        if (typeof AAKAARI_COLOR_MOCKUPS !== 'undefined' && colorData.color) {
            const colorMockup = AAKAARI_COLOR_MOCKUPS[colorData.color];

            if (colorMockup && colorMockup.url) {
                // Update canvas with color-specific mockup
                updateCanvasBackground(colorMockup.url);

                // Update print area bounds for this color
                if (colorMockup.print_area) {
                    printAreaBounds = {
                        x: colorMockup.print_area.x,
                        y: colorMockup.print_area.y,
                        width: colorMockup.print_area.width,
                        height: colorMockup.print_area.height
                    };

                    // Update the print area overlay
                    updatePrintAreaOverlay();

                    // Re-enforce boundaries with new print area
                    enforceDesignBoundaries();

                    console.log('Updated to color-specific mockup with print area:', printAreaBounds);
                }
                return;
            }
        }

        // If color has a specific image, use it
        if (colorData.image && colorData.image !== '') {
            updateCanvasBackground(colorData.image);
            return;
        }

        // Otherwise, apply color tint to base product image
        const canvas = document.getElementById('interactive-canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Store current canvas content
        const currentContent = ctx.getImageData(0, 0, canvas.width, canvas.height);

        // Apply color overlay
        if (colorData.color) {
            applyColorOverlay(ctx, canvas, colorData.color);
        }

        console.log('Preview updated with color:', colorData.name);
    }

    /**
     * Update canvas background image
     */
    function updateCanvasBackground(imageUrl) {
        const canvas = document.getElementById('interactive-canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            // Redraw designs on top
            if (typeof window.renderCanvas === 'function') {
                window.renderCanvas();
            }
        };

        img.src = imageUrl;
    }

    /**
     * Apply color overlay to canvas
     */
    function applyColorOverlay(ctx, canvas, hexColor) {
        // Simple color tint overlay
        ctx.globalCompositeOperation = 'multiply';
        ctx.fillStyle = hexColor;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.globalCompositeOperation = 'source-over';

        // Redraw designs
        if (typeof window.renderCanvas === 'function') {
            window.renderCanvas();
        }
    }

    /**
     * Make customization optional
     */
    function makeCustomizationOptional() {
        // Check if customization is required (from product meta or settings)
        customizationRequired = false; // Default to optional

        // Update add to cart button to work without customization
        const $addToCartBtn = $('.single_add_to_cart_button, #add-to-cart-button');

        if ($addToCartBtn.length) {
            // Remove any disabled state
            $addToCartBtn.prop('disabled', false);

            // Add helper text
            if (!$('#customization-optional-notice').length) {
                $addToCartBtn.before(
                    '<p id="customization-optional-notice" style="font-size:13px; color:#666; margin-bottom:10px;">' +
                    'Customization is optional. You can add this product to cart with or without custom designs.' +
                    '</p>'
                );
            }
        }
    }

    /**
     * Enhance add to cart functionality
     */
    function enhanceAddToCart() {
        $(document).on('click', '.single_add_to_cart_button, #add-to-cart-button', function(e) {
            // Check if there are any designs
            let hasDesigns = false;

            if (typeof window.customizerState !== 'undefined' && window.customizerState.designs) {
                hasDesigns = window.customizerState.designs.length > 0;
            }

            // If no designs and customization is not required, allow adding to cart normally
            if (!hasDesigns && !customizationRequired) {
                console.log('Adding product to cart without customization');
                // Let default WooCommerce handle it
                return true;
            }

            // If designs exist, include them in the cart data
            if (hasDesigns) {
                console.log('Adding product to cart with customization');
                // Custom add to cart with designs will be handled by existing customizer
            }
        });

        // Capture design preview before adding to cart
        $(document).on('click', '.single_add_to_cart_button, #add-to-cart-button', function(e) {
            captureDesignPreview();
        });
    }

    /**
     * Capture design preview as image data URL
     */
    function captureDesignPreview() {
        const canvas = document.getElementById('interactive-canvas');
        if (!canvas) return;

        try {
            const previewDataUrl = canvas.toDataURL('image/png');

            // Store preview in a hidden field or send with AJAX
            let $previewInput = $('#customization-preview-input');
            if (!$previewInput.length) {
                $previewInput = $('<input type="hidden" id="customization-preview-input" name="customization_preview" />');
                $('form.cart').append($previewInput);
            }
            $previewInput.val(previewDataUrl);

            console.log('Design preview captured');
        } catch (error) {
            console.error('Failed to capture design preview:', error);
        }
    }

    // Expose functions globally for external access
    window.customizerEnhancements = {
        enforceDesignBoundaries: enforceDesignBoundaries,
        updatePreviewImage: updatePreviewImage,
        captureDesignPreview: captureDesignPreview,
    };

})(jQuery);
