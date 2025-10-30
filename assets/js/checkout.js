/**
 * Aakaari Checkout – Modern Step Controller
 * Complete rewrite for reliable multi-step checkout
 */

jQuery(function($) {
    // Make goToStep global for debugging
    window.goToStep = null;
    
    // State management
    let currentStep = 1;
    const totalSteps = 3;
    let isSubmitting = false;
    let fieldsMovedInitially = false;
    
    // Expose currentStep to other scripts
    window.currentStep = currentStep;

    // DOM elements
    const $form = $('#checkout-form');
    const $stepContents = {
        1: $('#step-1-content'),
        2: $('#step-2-content'),
        3: $('#step-3-content')
    };
    const $progressSteps = {
        1: $('#progress-step-1'),
        2: $('#progress-step-2'),
        3: $('#progress-step-3')
    };
    const $backBtns = $('#mobile-back-btn, #desktop-back-btn');
    const $nextBtns = $('#mobile-next-btn, #desktop-next-btn');
    
    // Debug helper
    const isDebug = aakaariCheckout && aakaariCheckout.isDebug;
    function debug(message, data) {
        if (isDebug && window.console) {
            if (data) {
                console.log('Aakaari Checkout: ' + message, data);
            } else {
                console.log('Aakaari Checkout: ' + message);
            }
        }
        if (typeof window.debugStep === 'function') {
            window.debugStep(currentStep, message);
        }
    }

    /**
     * Initialize the checkout form
     */
    function init() {
        debug('Initializing multi-step checkout');
        
        // Setup form
        $form.attr('novalidate', 'novalidate');
        
        // Move checkout fields to their proper places
        moveCheckoutFields();
        
        // Setup button handlers
        setupButtonHandlers();
        
        // Listen for WooCommerce events
        setupWooCommerceEvents();
        
        // Setup step-specific logic
        goToStep(1);
        updateButtonLabels();
        ensureNonceField();
    }
    
    /**
     * Ensure the WooCommerce nonce field is present
     */
    function ensureNonceField() {
        const $nonceField = $('input[name="woocommerce-process-checkout-nonce"]');
        const $wpNonce = $('input[name="_wpnonce"]');
        
        // If we're missing the nonce, add it
        if (!$nonceField.length && aakaariCheckout.processNonce) {
            debug('Adding missing checkout nonce');
            $form.append('<input type="hidden" name="woocommerce-process-checkout-nonce" value="' + aakaariCheckout.processNonce + '">');
        }
        
        // If we're missing the _wpnonce, add it
        if (!$wpNonce.length && aakaariCheckout.processNonce) {
            debug('Adding missing _wpnonce');
            $form.append('<input type="hidden" name="_wpnonce" value="' + aakaariCheckout.processNonce + '">');
        }
        
        // Check for these fields again before form submission
        $form.on('submit', function() {
            ensureNonceField();
        });
    }
    
    /**
     * Setup button event handlers
     */
    function setupButtonHandlers() {
        // Back button handler
        $backBtns.on('click', function(e) {
            e.preventDefault();
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        });
        
        // Next/Continue button handler
        $nextBtns.on('click', function(e) {
            // Don't do anything if already submitting
            if (isSubmitting) {
                debug('Preventing action - already submitting');
                e.preventDefault();
                return false;
            }
            
            // If not on the final step, navigate forward
            if (currentStep < totalSteps) {
                e.preventDefault();
                debug('Continue button clicked on step ' + currentStep);
                
                if (validateStep(currentStep)) {
                    goToStep(currentStep + 1);
                } else {
                    debug('Validation failed for step ' + currentStep);
                }
            }
            // On the final step, handle form submission
            else if (currentStep === totalSteps) {
                debug('Place order button clicked');

                // AAKAARI FIX: Intercept for COD OTP Verification
                const paymentMethod = $('input[name="payment_method"]:checked').val();
                const isOtpVerified = window.otpVerified || false; // Check global OTP status

                if (paymentMethod === 'cod' && !isOtpVerified) {
                    e.preventDefault(); // Prevent form submission
                    $('#send-cod-otp').trigger('click'); // Trigger the OTP sending process
                    debug('COD selected but not verified. Triggering OTP flow.');
                    return false;
                }
                
                if (!validateStep(currentStep)) {
                    e.preventDefault();
                    isSubmitting = false;
                    debug('Final validation failed - preventing submission');
                    return false;
                }
                
                // Show loading state
                $(this).addClass('aak-button--loading');
                isSubmitting = true;
                
                // Ensure nonce is present before submission
                ensureNonceField();
                
                // Let form submission continue
                debug('Form validation passed, proceeding with submission');
                return true;
            }
        });
        
        // Handle shipping option clicks
        $(document).on('click', '#aakaari-shipping-methods .radio-option', function() {
            const methodId = $(this).data('method-id');
            if (methodId) {
                const $input = $('#' + methodId);
                if ($input.length) {
                    $input.prop('checked', true).trigger('change');
                    $('#aakaari-shipping-methods .radio-option').removeClass('selected');
                    $(this).addClass('selected');
                    debug('Shipping method selected: ' + methodId);
                }
            }
        });
        
        // Handle payment option clicks
        $(document).on('click', '.payment-method-option', function() {
            const $li = $(this).closest('li.payment_method');
            const $input = $li.find('input.input-radio').first();
            
            if ($input.length && !$input.is(':checked')) {
                $input.prop('checked', true).trigger('click');
                
                setTimeout(function() {
                    $(document.body).trigger('payment_method_selected');
                }, 50);
                
                $('.payment-method-option').removeClass('selected');
                $(this).addClass('selected');
                debug('Payment method selected: ' + $input.val());
            }
        });
        
        // Ship to different address toggle
        $(document).on('change', '#ship-to-different-address-checkbox', function() {
            $('.shipping_address').slideToggle('fast', function() {
                $(document.body).trigger('country_to_state_changed');
            });
        });
    }
    
    /**
     * Setup WooCommerce event handlers
     */
    function setupWooCommerceEvents() {
        // After WooCommerce updates the checkout via AJAX
        $(document.body).on('updated_checkout', function() {
            debug('WooCommerce updated_checkout event triggered');
            
            // Make sure fields are in right places
            moveCheckoutFields(true);
            
            // Reinitialize payment methods on step 3
            if (currentStep === 3) {
                setTimeout(function() {
                    $(document.body).trigger('payment_method_selected');
                    $('input[name="payment_method"]:checked').trigger('click');
                }, 300);
            }
            
            // Update button labels
            updateButtonLabels();
        });
        
        // Form submission handler for WooCommerce integration
        $form.on('submit', function(e) {
            debug('Form submission triggered');
            
            // If already being processed by WooCommerce, let it through
            if ($form.is('.processing')) {
                debug('Form already processing, allowing submission');
                return true;
            }
            
            // Final validation check
            if (currentStep === totalSteps && !validateStep(totalSteps)) {
                e.preventDefault();
                isSubmitting = false;
                debug('Form submission blocked - validation failed');
                return false;
            }
            
            // Get selected payment method
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            
            // If no payment method selected but payment is needed, prevent submission
            if ($('.wc_payment_methods').length > 0 && (!paymentMethod || paymentMethod === '')) {
                e.preventDefault();
                isSubmitting = false;
                alert('Please select a payment method.');
                debug('Form submission blocked - no payment method selected');
                return false;
            }
            
            // Ensure necessary fields are present
            ensureNonceField();
            
            // Let WooCommerce payment gateway handle submission
            try {
                debug('Triggering WooCommerce checkout handlers');
                
                if ($form.triggerHandler('checkout_place_order') !== false) {
                    if (!paymentMethod || $form.triggerHandler('checkout_place_order_' + paymentMethod) !== false) {
                        $form.addClass('processing');
                        $nextBtns.addClass('aak-button--loading');
                        debug('Form submission proceeding with payment method: ' + paymentMethod);
                        return true;
                    }
                }
            } catch (error) {
                debug('Error in form submission handler', error);
            }
            
            e.preventDefault();
            isSubmitting = false;
            debug('Form submission blocked by WooCommerce handlers');
            return false;
        });
        
        // Handle checkout errors
        $(document.body).on('checkout_error', function() {
            isSubmitting = false;
            $nextBtns.removeClass('aak-button--loading');
        });
    }

    /**
     * Move checkout fields into their proper containers
     */
    function moveCheckoutFields(isUpdate = false) {
        if (fieldsMovedInitially && !isUpdate) return;
        
        const $fieldWrapper = $('#aak-fields-wrapper');
        const $contactCard = $('#aak-contact-card');
        const $addressCard = $('#aak-address-card');
        
        if (!$contactCard.length || !$addressCard.length) {
            debug('Target cards not found');
            return;
        }
        
        debug('Moving checkout fields' + (isUpdate ? ' (update)' : ''));
        
        // Define field selectors
        const contactSelectors = [
            '#billing_email_field',
            '#billing_phone_field',
            '.woocommerce-billing-fields > .form-row[class*="aak-field-key-marketing_opt_in"]',
            '.woocommerce-billing-fields > .checkbox-wrapper:has(#marketing_opt_in)'
        ];
        const addressSelectors = [
            '.woocommerce-billing-fields',
            '.woocommerce-shipping-fields',
            '#order_comments_field',
            'p.woocommerce-form-row.save-info',
            '.create-account'
        ];
        
        // Determine source container
        const $source = isUpdate && !$fieldWrapper.length ? $form : $fieldWrapper;
        if (!$source.length) return;
        
        // Store original field values before moving
        const formData = $form.serialize();
        
        // Move Contact Fields
        contactSelectors.forEach(selector => {
            const $field = $source.find(selector);
            if ($field.length && !$contactCard.find(selector).length) {
                $field.appendTo($contactCard);
            }
        });
        
        // Move Address Fields
        addressSelectors.forEach(selector => {
            const $section = $source.find(selector);
            if ($section.length && !$addressCard.find(selector).length) {
                $section.appendTo($addressCard);
            }
        });
        
        // Cleanup and finalize
        if (!isUpdate && $fieldWrapper.length) {
            $fieldWrapper.remove();
            fieldsMovedInitially = true;
        }
        
        // Re-initialize form elements after moving
        setTimeout(function() {
            // Re-init Select2 if present
            if ($.fn.select2) {
                $addressCard.find('select.country_select, select.state_select').filter(':visible').each(function() {
                    try {
                        $(this).select2('destroy');
                    } catch(e) { /* may not be initialized yet */ }
                    $(this).select2();
                });
            }
            
            // Trigger WooCommerce events
            $(document.body).trigger('country_to_state_changed');
            $(document.body).trigger('wc_address_i18n_ready');
            
            // Check if form data changed unexpectedly
            if ($form.serialize() !== formData) {
                debug('Field values changed during move - monitoring form integrity');
            }
        }, 150);
    }
    
    /**
     * Go to a specific checkout step
     */
    function goToStep(step) {
        if (step < 1 || step > totalSteps) return;
        
        debug('Navigating to step ' + step);
        
        // Reset submission state
        isSubmitting = false;
        $nextBtns.removeClass('aak-button--loading');
        
        // Update current step
        currentStep = step;
        window.currentStep = step; // Make available globally
        
        // Hide all steps and show current
        Object.values($stepContents).forEach(el => el.addClass('hidden'));
        $stepContents[step].removeClass('hidden');
        
        // Update UI
        updateProgressBar();
        updateButtonLabels();
        scrollToTop();
        
        // Trigger updates based on step
// Special handling for shipping address and calculation
if (step === 2) {
    // Make sure shipping address is copied from billing if needed
    if ($('#ship-to-different-address-checkbox').length && !$('#ship-to-different-address-checkbox').is(':checked')) {
        // Copy billing fields to shipping fields
        $('input[name^="billing_"]').each(function() {
            const fieldName = $(this).attr('name');
            const shippingFieldName = fieldName.replace('billing_', 'shipping_');
            
            // Check if shipping field exists
            if ($('input[name="' + shippingFieldName + '"]').length) {
                $('input[name="' + shippingFieldName + '"]').val($(this).val());
            }
        });
        
        // Copy select fields
        $('select[name^="billing_"]').each(function() {
            const fieldName = $(this).attr('name');
            const shippingFieldName = fieldName.replace('billing_', 'shipping_');
            
            if ($('select[name="' + shippingFieldName + '"]').length) {
                $('select[name="' + shippingFieldName + '"]').val($(this).val());
            }
        });
    }
}
        
        // Trigger a custom event that other scripts can listen for
        $(document).trigger('aak_step_changed', [step]);
        
        // Close any open Select2 dropdowns
        if ($.fn.select2) {
            $('select.select2-hidden-accessible').select2('close');
        }
    }
    
    /**
     * Update progress bar UI
     */
    function updateProgressBar() {
        Object.values($progressSteps).forEach(el => el.removeClass('active completed'));
        
        for (let i = 1; i <= totalSteps; i++) {
            if (i < currentStep) {
                $progressSteps[i].addClass('completed');
            } else if (i === currentStep) {
                $progressSteps[i].addClass('active');
            }
        }
    }
    
    /**
     * Update button labels based on current step
     */
    function updateButtonLabels() {
        let nextBtnText = 'Continue';
        let nextBtnHtml = nextBtnText;
        const placeOrderText = $nextBtns.eq(0).data('value') || $nextBtns.eq(0).attr('value') || 'Place Order';
        
        if (currentStep === totalSteps) {
            nextBtnText = placeOrderText;
            nextBtnHtml = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> ${nextBtnText}`;
            
            // Change button type and name for submission
            $nextBtns.attr('type', 'submit').attr('name', 'woocommerce_checkout_place_order');
        } else {
            // Revert to button type for navigation
            $nextBtns.attr('type', 'button').removeAttr('name');
        }
        
        // Apply text/HTML updates
        $nextBtns.html(nextBtnText);
        
        // Desktop button with special structure
        const $desktopBtnText = $('#desktop-btn-text');
        if ($desktopBtnText.length) {
            if (currentStep === totalSteps) {
                $desktopBtnText.parent().html(nextBtnHtml);
            } else {
                $desktopBtnText.text(nextBtnText);
            }
        }
        
        // Show/hide back button
        $backBtns.css('display', currentStep > 1 ? 'flex' : 'none');
    }
    
    /**
     * Validate the current step
     */
    function validateStep(step) {
        let isValid = true;
        const $currentStepContent = $stepContents[step];
        
        debug('Validating step ' + step);
        
        // Find required fields in current step
        const $requiredRows = $currentStepContent.find('.validate-required, .validate-email');
        
        $requiredRows.each(function() {
            const $row = $(this);
            
            // Find the input element
            const $field = $row.find('input, select, textarea').filter(':visible').first();
            
            // Skip if no visible field
            if (!$field.length) {
                // Check for Select2
                if ($row.find('select.select2-hidden-accessible').length) {
                    const $select2 = $row.find('select.select2-hidden-accessible');
                    if (!$select2.val()) {
                        highlightInvalidField($row);
                        isValid = false;
                    } else {
                        removeInvalidHighlight($row);
                    }
                }
                return;
            }
            
            // Get field value
            let fieldValue = $field.val();
            
            // Special handling for checkboxes
            if ($field.is(':checkbox') && !$field.is(':checked')) {
                fieldValue = '';
            }
            
            // Validate field value
            if (fieldValue === null || fieldValue.trim() === '') {
                highlightInvalidField($row);
                isValid = false;
            } else {
                removeInvalidHighlight($row);
                
                // Email validation
                if ($row.hasClass('validate-email') && !isValidEmail(fieldValue)) {
                    highlightInvalidField($row);
                    isValid = false;
                }
            }
        });
        
        // Scroll to first error
        if (!isValid) {
            const $firstError = $currentStepContent.find('.woocommerce-invalid').first();
            if ($firstError.length) {
                scrollToElement($firstError);
            }
        }
        
        // Step-specific validation
        if (step === 2 && isValid) {
            isValid = validateShippingMethod();
        } else if (step === 3 && isValid) {
            isValid = validatePaymentMethod();
        }
        
        debug('Validation ' + (isValid ? 'passed' : 'failed') + ' for step ' + step);
        return isValid;
    }
    
    /**
     * Validate shipping method selection
     */
    function validateShippingMethod() {
        // Only validate if shipping is required
        const $container = $('#aakaari-shipping-methods');
        if (!$container.length || $container.children().length === 0) {
            return true;
        }
        
        const isSelected = $('input[name^="shipping_method["]:checked').length > 0;
        const $shippingCard = $container.closest('.card');
        
        if (!isSelected) {
            highlightInvalidField($shippingCard);
            scrollToElement($shippingCard);
            return false;
        } else {
            removeInvalidHighlight($shippingCard);
        }
        
        return true;
    }
    
    /**
     * Validate payment method selection
     */
    function validatePaymentMethod() {
        // Skip if no payment needed
        if (!$('.wc_payment_methods').length) {
            return true;
        }
        
        const isSelected = $('input[name="payment_method"]:checked').length > 0;
        const $paymentCard = $('#aakaari-payment').closest('.card');
        
        if (!isSelected) {
            highlightInvalidField($paymentCard);
            scrollToElement($paymentCard);
            return false;
        } else {
            removeInvalidHighlight($paymentCard);
        }
        
        return true;
    }
    
    /**
     * Highlight an invalid field
     */
    function highlightInvalidField($element) {
        if (!$element.hasClass('woocommerce-invalid')) {
            $element.addClass('woocommerce-invalid aak-shake');
            setTimeout(() => {
                $element.removeClass('aak-shake');
            }, 600);
        }
    }
    
    /**
     * Remove invalid field highlighting
     */
    function removeInvalidHighlight($element) {
        $element.removeClass('woocommerce-invalid');
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * Scroll to an element
     */
    function scrollToElement($element) {
        if ($element.length) {
            $('html, body').animate({
                scrollTop: $element.offset().top - 50
            }, 400);
        }
    }
    
    /**
     * Scroll to top of checkout container
     */
    function scrollToTop() {
        const $container = $('#checkout-container');
        if ($container.length && $(window).scrollTop() > $container.offset().top + 10) {
            $('html, body').animate({
                scrollTop: $container.offset().top - 30
            }, 400);
        }
    }
    
    // Make goToStep available for debugging
    window.goToStep = goToStep;
    
    // Initialize when document is ready
    $(document).ready(function() {
        init();
    });
});