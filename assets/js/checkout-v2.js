/**
 * Checkout V2 - WooCommerce Integrated
 * Full backend integration with WooCommerce checkout system
 */

jQuery(function($) {
    'use strict';

    let currentStep = 1;
    let hasFormattedShipping = false;

    console.log('Checkout V2: Initialized with WooCommerce integration');

    // ========== STEP NAVIGATION ==========

    function goToStep(step) {
        if (step < 1 || step > 3) return;

        console.log('Navigating to step ' + step);

        // Hide all steps
        $('.step-content').hide();

        // Show target step
        $('#step-' + step).fadeIn(300);

        // Update progress bar
        $('.checkout-progress .step').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $('.checkout-progress .step[data-step="' + i + '"]').addClass('completed');
        }
        $('.checkout-progress .step[data-step="' + step + '"]').addClass('active');

        // Update current step
        currentStep = step;

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);

        // Step-specific actions
        if (step === 2) {
            // Trigger WooCommerce to calculate shipping
            $(document.body).trigger('update_checkout');

            // Format shipping methods after a delay
            if (!hasFormattedShipping) {
                setTimeout(function() {
                    formatShippingMethods();
                }, 1000);
            }
        }

        if (step === 3) {
            // Trigger order review update
            $(document.body).trigger('update_checkout');
        }
    }

    // ========== BUTTON HANDLERS ==========

    // Next buttons
    $(document).on('click', '.btn-next', function(e) {
        e.preventDefault();
        const nextStep = parseInt($(this).attr('data-next'));

        if (validateStep(currentStep)) {
            goToStep(nextStep);
        }
    });

    // Back buttons
    $(document).on('click', '.btn-back', function(e) {
        e.preventDefault();
        const prevStep = parseInt($(this).attr('data-prev'));
        goToStep(prevStep);
    });

    // ========== VALIDATION ==========

    function validateStep(step) {
        const $step = $('#step-' + step);
        let isValid = true;

        // Step 1: Validate billing fields
        if (step === 1) {
            $step.find('.validate-required').each(function() {
                const $field = $(this).find('input, select, textarea').first();

                if ($field.length) {
                    const value = $field.val();

                    if (!value || value.trim() === '') {
                        $(this).addClass('woocommerce-invalid');
                        isValid = false;
                    } else {
                        $(this).removeClass('woocommerce-invalid');
                    }
                }
            });

            if (!isValid) {
                alert('Please fill in all required fields');
            }
        }

        // Step 2: Validate shipping method selection
        if (step === 2) {
            const hasShipping = $('input[name^="shipping_method"]:checked').length > 0;

            if (!hasShipping && $('#shipping-options .shipping-option').length > 0) {
                alert('Please select a shipping method');
                isValid = false;
            }
        }

        // Step 3: Let WooCommerce handle payment validation
        // WooCommerce will validate on form submission

        return isValid;
    }

    // Clear validation errors on input
    $(document).on('input change', 'input, select, textarea', function() {
        $(this).closest('.form-row').removeClass('woocommerce-invalid');
    });

    // ========== SHIPPING METHODS ==========

    function formatShippingMethods() {
        const $container = $('#shipping-options');
        const $wcShipping = $('#wc-shipping');

        // Find shipping method inputs
        const $methods = $wcShipping.find('input[name^="shipping_method"]');

        if ($methods.length === 0) {
            $container.html('<p style="text-align:center; color:#6b7280; padding: 20px;">Loading shipping methods...</p>');

            // Retry once
            setTimeout(function() {
                const $retryMethods = $('#wc-shipping').find('input[name^="shipping_method"]');
                if ($retryMethods.length > 0) {
                    buildShippingUI($retryMethods, $container);
                } else {
                    $container.html('<p style="text-align:center; color:#6b7280;">No shipping methods available for your location.</p>');
                }
            }, 1500);

            return;
        }

        buildShippingUI($methods, $container);
    }

    function buildShippingUI($methods, $container) {
        $container.empty();

        $methods.each(function() {
            const $input = $(this);
            const id = $input.attr('id');
            const value = $input.val();
            const isChecked = $input.is(':checked');

            // Find the label
            const $label = $('label[for="' + id + '"]');
            if (!$label.length) return;

            // Extract method name and price
            let methodName = $label.clone().children().remove().end().text().trim();
            const $price = $label.find('.woocommerce-Price-amount');
            let priceHtml = $price.length ? $price.parent().html() : '';

            // Clean up method name
            if (methodName.includes(':')) {
                methodName = methodName.split(':')[0].trim();
            }

            // Create custom shipping option
            const $option = $('<div class="shipping-option' + (isChecked ? ' selected' : '') + '" data-id="' + id + '"></div>');
            $option.html(`
                <div class="info">
                    <div class="radio"></div>
                    <div class="name">${methodName}</div>
                </div>
                <div class="price">${priceHtml}</div>
            `);

            $container.append($option);
        });

        // Click handler for shipping options
        $container.off('click').on('click', '.shipping-option', function() {
            const id = $(this).data('id');

            // Update UI
            $('.shipping-option').removeClass('selected');
            $(this).addClass('selected');

            // Trigger the actual radio button
            $('#' + id).prop('checked', true).trigger('change');
        });

        hasFormattedShipping = true;
        console.log('Shipping methods formatted: ' + $methods.length + ' methods');
    }

    // ========== WOOCOMMERCE INTEGRATION ==========

    // Listen for WooCommerce checkout updates
    $(document.body).on('updated_checkout', function() {
        console.log('WooCommerce checkout updated');

        // Reformat shipping if on step 2
        if (currentStep === 2) {
            setTimeout(function() {
                formatShippingMethods();
            }, 300);
        }
    });

    // Form submission - let WooCommerce handle everything
    $('#checkout-form, form.checkout').on('submit', function(e) {
        console.log('Form submission - Step ' + currentStep);

        // Only allow submission on step 3
        if (currentStep < 3) {
            e.preventDefault();
            console.log('Prevented submission - not on final step');
            return false;
        }

        // Let WooCommerce handle the rest
        console.log('Allowing WooCommerce to process checkout');
    });

    // Handle checkout errors
    $(document.body).on('checkout_error', function() {
        console.log('Checkout error occurred');
        $('html, body').animate({ scrollTop: 0 }, 400);
    });

    // ========== INITIALIZE ==========

    $(document).ready(function() {
        console.log('Starting checkout on step 1');
        goToStep(1);

        // Add ship_to_different_address hidden field set to no (use billing for shipping)
        if (!$('input[name="ship_to_different_address"]').length) {
            $('<input type="hidden" name="ship_to_different_address" value="0">').appendTo('form.checkout');
        }
    });

});
