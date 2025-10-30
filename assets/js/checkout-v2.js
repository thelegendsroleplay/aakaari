/**
 * Checkout V2 - Simplified 2-Step
 * Step 1: Information | Step 2: Payment
 */

jQuery(function($) {
    'use strict';

    let currentStep = 1;

    console.log('Checkout V2: 2-step simplified initialized');

    // ========== SYNC BILLING TO SHIPPING ==========

    function syncBillingToShipping() {
        const fields = ['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'];

        fields.forEach(function(field) {
            const billingValue = $('#billing_' + field).val();
            $('#shipping_' + field).val(billingValue);
        });
    }

    // Sync on field change
    $(document).on('change input', '#step-1 input, #step-1 select', function() {
        syncBillingToShipping();
    });

    // ========== STEP NAVIGATION ==========

    function goToStep(step) {
        if (step < 1 || step > 2) return;

        console.log('Navigating to step ' + step);

        // Sync before moving to step 2
        if (step === 2) {
            syncBillingToShipping();
        }

        // Hide all steps
        $('.step-content').hide();

        // Show target step
        $('#step-' + step).fadeIn(300);

        // Update progress bar
        $('.checkout-progress .step').removeClass('active completed');
        if (step === 2) {
            $('.checkout-progress .step[data-step="1"]').addClass('completed');
        }
        $('.checkout-progress .step[data-step="' + step + '"]').addClass('active');

        // Update current step
        currentStep = step;

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);

        // Step 2: Trigger order review update
        if (step === 2) {
            $(document.body).trigger('update_checkout');
        }
    }

    // ========== BUTTON HANDLERS ==========

    // Next button
    $(document).on('click', '.btn-next', function(e) {
        e.preventDefault();
        const nextStep = parseInt($(this).attr('data-next'));

        if (validateStep(currentStep)) {
            goToStep(nextStep);
        }
    });

    // Back button
    $(document).on('click', '.btn-back', function(e) {
        e.preventDefault();
        const prevStep = parseInt($(this).attr('data-prev'));
        goToStep(prevStep);
    });

    // ========== VALIDATION ==========

    function validateStep(step) {
        let isValid = true;

        // Step 1: Validate required fields
        if (step === 1) {
            const $step = $('#step-1');

            $step.find('.field').each(function() {
                const $input = $(this).find('input[required], select[required]').first();

                if ($input.length) {
                    const value = $input.val();

                    if (!value || value.trim() === '') {
                        $(this).addClass('field-error');
                        $input.css('border-color', '#ef4444');
                        isValid = false;
                    } else {
                        $(this).removeClass('field-error');
                        $input.css('border-color', '#d1d5db');
                    }
                }
            });

            if (!isValid) {
                alert('Please fill in all required fields');
                // Scroll to first error
                const $firstError = $step.find('.field-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
            }
        }

        return isValid;
    }

    // Clear validation on input
    $(document).on('input change', 'input, select', function() {
        $(this).css('border-color', '#d1d5db');
        $(this).closest('.field').removeClass('field-error');
    });

    // ========== FORM SUBMISSION ==========

    // Form submission - only allow on step 2
    $('form.checkout').on('submit', function(e) {
        console.log('Form submission - Step ' + currentStep);

        // Only allow submission on step 2
        if (currentStep < 2) {
            e.preventDefault();
            console.log('Prevented submission - not on final step');
            return false;
        }

        // Sync one more time
        syncBillingToShipping();

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

        // Add hidden field for ship_to_different_address (set to no)
        if (!$('input[name="ship_to_different_address"]').length) {
            $('<input type="hidden" name="ship_to_different_address" value="0">').appendTo('form.checkout');
        }

        // Initial sync
        syncBillingToShipping();
    });

});
