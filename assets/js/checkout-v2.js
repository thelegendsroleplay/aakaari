/**
 * Checkout V2 - Clean JavaScript
 * NO LOOPS, proper WooCommerce integration
 */

jQuery(function($) {
    'use strict';

    let currentStep = 1;
    let hasFormattedShipping = false;

    // ========== SYNC BILLING TO SHIPPING ==========

    function syncBillingToShipping() {
        const billingFields = [
            'first_name', 'last_name', 'address_1', 'address_2',
            'city', 'state', 'postcode', 'country'
        ];

        billingFields.forEach(function(field) {
            const billingValue = $('#billing_' + field).val();
            $('#shipping_' + field).val(billingValue);
        });
    }

    // ========== STEP NAVIGATION ==========

    function goToStep(step) {
        // Sync billing to shipping before moving
        if (step >= 2) {
            syncBillingToShipping();
        }

        // Hide all steps
        $('.step-content').hide();

        // Show target step
        $('#step-' + step).fadeIn(300);

        // Update progress
        $('.checkout-progress .step').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $('.checkout-progress .step[data-step="' + i + '"]').addClass('completed');
        }
        $('.checkout-progress .step[data-step="' + step + '"]').addClass('active');

        // Update current step
        currentStep = step;

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);

        // Step 2: Format shipping methods (ONCE) and trigger WC update
        if (step === 2 && !hasFormattedShipping) {
            // Let WooCommerce calculate shipping
            setTimeout(function() {
                $(document.body).trigger('update_checkout');
            }, 300);

            setTimeout(function() {
                formatShippingMethods();
            }, 800);
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

        // Validate required fields
        $step.find('.input[required]').each(function() {
            const $input = $(this);

            if (!$input.val() || $input.val().trim() === '') {
                $input.css('border-color', '#ef4444');
                isValid = false;
            } else {
                $input.css('border-color', '#d1d5db');
            }
        });

        if (!isValid && step === 1) {
            alert('Please fill in all required fields');
        }

        // Step 2: Validate shipping selection
        if (step === 2) {
            const hasShippingSelected = $('#wc-shipping input[type="radio"]:checked').length > 0;
            if (!hasShippingSelected) {
                alert('Please select a shipping method');
                isValid = false;
            }
        }

        // Note: Step 3 validation is handled by WooCommerce

        return isValid;
    }

    // Clear validation on input
    $(document).on('input change', '.input', function() {
        $(this).css('border-color', '#d1d5db');
    });

    // ========== SHIPPING METHODS ==========

    function formatShippingMethods() {
        const $source = $('#wc-shipping');
        const $container = $('#shipping-options');

        // Find shipping methods
        const $methods = $source.find('input[type="radio"], input[name^="shipping_method"]');

        if ($methods.length === 0) {
            $container.html('<p style="text-align:center; color:#6b7280; padding: 20px;">Loading shipping methods...</p>');

            // Retry after a delay
            setTimeout(function() {
                const $retryMethods = $source.find('input[type="radio"], input[name^="shipping_method"]');
                if ($retryMethods.length > 0) {
                    buildShippingUI($retryMethods, $source, $container);
                } else {
                    $container.html('<p style="text-align:center; color:#6b7280;">No shipping methods available</p>');
                }
            }, 1000);

            return;
        }

        buildShippingUI($methods, $source, $container);
    }

    function buildShippingUI($methods, $source, $container) {
        // Clear container
        $container.empty();

        // Build custom UI
        $methods.each(function() {
            const $input = $(this);
            const id = $input.attr('id');
            const isChecked = $input.is(':checked');
            const $label = $source.find('label[for="' + id + '"]');

            if (!$label.length) return;

            // Extract name and price
            let name = $label.clone().children().remove().end().text().trim();
            let price = $label.find('.woocommerce-Price-amount').parent().html() || '';

            // Clean up name
            if (name.includes(':')) {
                name = name.split(':')[0].trim();
            }

            // Create custom option
            const $option = $('<div class="shipping-option' + (isChecked ? ' selected' : '') + '" data-id="' + id + '"></div>');
            $option.html(`
                <div class="info">
                    <div class="radio"></div>
                    <div class="name">${name}</div>
                </div>
                <div class="price">${price}</div>
            `);

            $container.append($option);
        });

        // Click handler
        $container.off('click').on('click', '.shipping-option', function() {
            const id = $(this).data('id');

            // Update UI
            $('.shipping-option').removeClass('selected');
            $(this).addClass('selected');

            // Check the actual radio
            $('#' + id).prop('checked', true).trigger('change');
        });

        hasFormattedShipping = true;
    }

    // ========== FORM SUBMISSION ==========

    $('#checkout-form').on('submit', function(e) {
        // Only prevent submission if not on step 3
        if (currentStep < 3) {
            e.preventDefault();
            return false;
        }

        // Sync billing to shipping before WooCommerce processes
        syncBillingToShipping();

        // Let WooCommerce handle validation and submission
        // WooCommerce will show its own loading states and error messages
    });

    // ========== INITIALIZE ==========

    // Wait for DOM ready
    $(document).ready(function() {
        goToStep(1);
    });

});
