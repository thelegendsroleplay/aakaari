/**
 * Aakaari Checkout V2 - Clean JavaScript
 * NO LOOPS, NO AUTO-UPDATES
 */

jQuery(function($) {
    'use strict';

    let currentStep = 1;
    let hasFormattedShipping = false;

    // ========== STEP NAVIGATION ==========

    function goToStep(step) {
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

        // Step 2: Format shipping methods (ONCE)
        if (step === 2 && !hasFormattedShipping) {
            formatShippingMethods();
        }
    }

    // ========== BUTTON HANDLERS ==========

    // Next buttons
    $('.btn-next').on('click', function() {
        const nextStep = parseInt($(this).data('next'));

        if (validateStep(currentStep)) {
            goToStep(nextStep);
        }
    });

    // Back buttons
    $('.btn-back').on('click', function() {
        const prevStep = parseInt($(this).data('prev'));
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

        // Step 2: Validate shipping selection
        if (step === 2) {
            const hasShippingSelected = $('#wc-shipping input[type="radio"]:checked').length > 0;
            if (!hasShippingSelected) {
                alert('Please select a shipping method');
                isValid = false;
            }
        }

        // Step 3: Validate payment selection
        if (step === 3) {
            const hasPaymentSelected = $('input[name="payment_method"]:checked').length > 0;
            if (!hasPaymentSelected) {
                alert('Please select a payment method');
                isValid = false;
            }
        }

        return isValid;
    }

    // Clear validation on input
    $('.input').on('input change', function() {
        $(this).css('border-color', '#d1d5db');
    });

    // ========== SHIPPING METHODS ==========

    function formatShippingMethods() {
        const $source = $('#wc-shipping');
        const $container = $('#shipping-options');

        // Find shipping methods
        const $methods = $source.find('input[type="radio"]');

        if ($methods.length === 0) {
            $container.html('<p style="text-align:center; color:#6b7280;">No shipping methods available</p>');
            hasFormattedShipping = true;
            return;
        }

        // Clear container
        $container.empty();

        // Build custom UI
        $methods.each(function() {
            const $input = $(this);
            const id = $input.attr('id');
            const isChecked = $input.is(':checked');
            const $label = $source.find('label[for="' + id + '"]');

            // Extract name and price
            let name = $label.clone().children().remove().end().text().trim();
            let price = $label.find('.woocommerce-Price-amount').parent().html() || '';

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
        $container.on('click', '.shipping-option', function() {
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
        if (currentStep < 3) {
            e.preventDefault();
            return false;
        }

        if (!validateStep(3)) {
            e.preventDefault();
            return false;
        }

        // Show loading
        $('.btn-submit').html('<div class="spinner" style="width:20px;height:20px;margin:0 auto;"></div>').prop('disabled', true);
    });

    // ========== INITIALIZE ==========

    goToStep(1);

});
