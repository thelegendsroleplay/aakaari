/**
 * Aakaari Checkout – Modern Step Controller
 * ENHANCED VERSION V3 - Uses jQuery to move fields
 * - This avoids PHP template conflicts with themes.
 */
jQuery(function($) {
  // State management
  let currentStep = 1;
  const totalSteps = 3;
  
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
  
  // Navigation buttons
  const $backBtns = $('#mobile-back-btn, #desktop-back-btn');
  const $nextBtns = $('#mobile-next-btn, #desktop-next-btn');
  const $placeOrderBtn = $('#place-order');
  
  /**
   * NEW FUNCTION: Moves WooCommerce fields into the correct cards
   * This is the main fix for the broken layout.
   */
  function moveCheckoutFields() {
    // 1. Get the containers
    const $fieldWrapper = $('#aak-fields-wrapper');
    const $contactCard = $('#aak-contact-card');
    const $addressCard = $('#aak-address-card');
    
    // 2. Move Contact Fields
    $fieldWrapper.find('#billing_email_field').appendTo($contactCard);
    $fieldWrapper.find('#billing_phone_field').appendTo($contactCard);
    
    // Find any checkbox-wrapper directly inside the original billing fields
    // (This is for "Email me with news..." if a plugin adds it)
    $fieldWrapper.find('.woocommerce-billing-fields > .checkbox-wrapper').appendTo($contactCard);
    
    // 3. Move Address Fields
    // This moves the *entire* billing and shipping field wrappers
    $fieldWrapper.find('.woocommerce-billing-fields').appendTo($addressCard);
    $fieldWrapper.find('.woocommerce-shipping-fields').appendTo($addressCard);
    
    // 4. Move Order Notes & "Save this info"
    $fieldWrapper.find('#order_comments_field').appendTo($addressCard);
    $fieldWrapper.find('p.woocommerce-form-row.save-info').appendTo($addressCard);

    // 5. Clean up the placeholder
    $fieldWrapper.remove();
  }

  // Initialize
  moveCheckoutFields(); // <-- RUN THE NEW FUNCTION ON LOAD
  setupForm();
  formatShippingOptions();
  formatPaymentOptions();
  enhanceFormFields(); // This function will now find the fields in their new locations
  
  /**
   * Setup form and event listeners
   */
  function setupForm() {
    $form.attr('novalidate', 'novalidate');
    $backBtns.on('click', navigateBack);
    $nextBtns.on('click', navigateForward);
    
    $form.on('submit', function(e) {
      if (currentStep < totalSteps) {
        e.preventDefault();
        navigateForward();
      }
    });
    
    $(document).on('change', 'input[name^="shipping_method"]', function() {
      $('body').trigger('update_checkout');
      updateShippingDisplay();
    });
    
    // When Woo updates the checkout, it rebuilds the fields.
    // We must move them AGAIN.
    $(document.body).on('updated_checkout', function() {
      moveCheckoutFields(); 
      formatShippingOptions();
      formatPaymentOptions();
      enhanceFormFields(); // Re-run field enhancements
      updateButtonLabels();
    });
    
    goToStep(1);
    updateButtonLabels();
  }
  
  /**
   * Format shipping method options to match design
   */
  function formatShippingOptions() {
    const $shippingRows = $('ul#shipping_method li');
    if ($shippingRows.length === 0) return;
    
    $('#aakaari-shipping-methods').html('');
    
    $shippingRows.each(function() {
      const $original = $(this);
      const $input = $original.find('input[type="radio"]');
      const id = $input.attr('id');
      const isChecked = $input.is(':checked');
      const label = $original.find('label').text().trim();
      
      const priceMatch = label.match(/:\s*(.*?)$/);
      const pricePart = priceMatch ? priceMatch[1].trim() : '';
      const methodName = label.replace(pricePart, '').replace(':', '').trim();
      
      let deliveryTime = '';
      if (methodName.toLowerCase().includes('express')) {
        deliveryTime = '2-3 business days';
      } else if (methodName.toLowerCase().includes('standard')) {
        deliveryTime = '5-7 business days';
      } else if (methodName.toLowerCase().includes('free')) {
        deliveryTime = '7-10 business days';
      } else if (methodName.toLowerCase().includes('overnight') || methodName.toLowerCase().includes('priority')) {
        deliveryTime = 'Next business day';
      }
      
      let badgeHtml = '';
      if (methodName.toLowerCase().includes('express')) {
        badgeHtml = '<span class="badge">Recommended</span>';
      } else if (methodName.toLowerCase().includes('overnight') || methodName.toLowerCase().includes('priority')) {
        badgeHtml = '<span class="badge fastest">Fastest</span>';
      }
      
      let savingsHtml = '';
      if (methodName.toLowerCase().includes('free')) {
        savingsHtml = '<span class="radio-option-savings">Save $15</span>';
      }
      
      const newOption = `
        <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">
          ${$input[0].outerHTML}
          <div class="radio-option-content">
            <div class="radio-option-title">
              ${methodName} ${badgeHtml}
            </div>
            <p class="radio-option-description">${deliveryTime}</p>
          </div>
          <span class="radio-option-price ${pricePart.toLowerCase().includes('free') ? 'free' : ''}">
            ${pricePart} ${savingsHtml}
          </span>
        </div>
      `;
      
      $('#aakaari-shipping-methods').append(newOption);
    });
    
    // Re-bind click handler
    $('#aakaari-shipping-methods .radio-option').off('click').on('click', function() {
      const methodId = $(this).data('method-id');
      $(`#${methodId}`).prop('checked', true).trigger('change');
      $('#aakaari-shipping-methods .radio-option').removeClass('selected');
      $(this).addClass('selected');
    });
  }
  
  /**
   * Format payment method options to match design
   */
  function formatPaymentOptions() {
    const $paymentMethods = $('.wc_payment_methods li.payment_method');
    if ($paymentMethods.length === 0) return;
    
    $paymentMethods.each(function() {
      const $method = $(this);
      if ($method.hasClass('processed-payment-method')) return;
      
      const $input = $method.find('input.input-radio');
      const $label = $method.find('label');
      const methodName = $label.clone().children().remove().end().text().trim();
      const $description = $method.find('.payment_box');
      
      let cardIconsHtml = '';
      if (methodName.toLowerCase().includes('credit') || methodName.toLowerCase().includes('card')) {
        cardIconsHtml = `
          <div class="radio-option-card-icons">
            <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons@latest/icons/visa.svg" alt="Visa">
            <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons@latest/icons/mastercard.svg" alt="Mastercard">
          </div>
        `;
      }
      
      let iconHtml = '';
      if (methodName.toLowerCase().includes('credit') || methodName.toLowerCase().includes('card')) {
        iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>';
      } else if (methodName.toLowerCase().includes('paypal')) {
        iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="#00457C"><path d="M19.5 5.5h-1.28c-.3 0-.55.24-.6.54l-1.71 10.41-.01.06c-.05.3-.31.54-.61.54H9.72c-.32 0-.57-.24-.62-.55l-.7-3.2v.04c-.04-.3.17-.58.47-.63l6.7-1.11h.05c.29-.05.45-.35.35-.63l-.55-1.42v-.01c-.11-.29.04-.61.33-.7l1.7-.41c.3-.07.61.11.69.41l.75 2.39c.08.3.4.47.7.39l1.21-.24c.3-.06.59.13.65.43l.41 1.84c.07.31-.13.61-.43.67l-2.02.42c-.3.07-.5.35-.42.65l.88 3.58c.07.3-.12.6-.42.67L8.27 21c-.3.06-.6-.13-.67-.43l-2.48-11.38C4.5 6.35 6.66 4 9.5 4h10c1.38 0 2.5 1.12 2.5 2.5v.5c0 .69-.56 1.25-1.25 1.25zM6.19 9.12L5.5 5.75C5.1 4.46 6.03 3 7.35 3h2.28c.31 0 .56.25.5.56L7.82 9.64c-.05.31-.36.49-.66.43l-.47-.11c-.31-.06-.5-.36-.5-.67v-.17z"/></svg>';
      } else if (methodName.toLowerCase().includes('bank')) {
        iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-6 9 6v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
      }
      
      const $newOption = $(`
        <div class="radio-option ${$input.is(':checked') ? 'selected' : ''}">
          ${$input[0].outerHTML}
          <div class="radio-option-content">
            <div class="radio-option-title">
              ${iconHtml} ${methodName}
            </div>
          </div>
          ${cardIconsHtml}
        </div>
      `);
      
      $method.addClass('processed-payment-method').html($newOption);
      
      if ($description.length) {
        $method.find('.radio-option-content').append($description);
        $description.removeClass('payment_box').addClass('card-fields');
      }
    });
    
    // Re-bind click handler
    $('.wc_payment_methods .radio-option').off('click').on('click', function() {
      const $radio = $(this).find('input.input-radio');
      $radio.prop('checked', true).trigger('click');
      $('.wc_payment_methods .radio-option').removeClass('selected');
      $(this).addClass('selected');
    });
  }
  
  /**
   * Update shipping method display when selection changes
   */
  function updateShippingDisplay() {
    $('#aakaari-shipping-methods .radio-option').removeClass('selected');
    $('input[name^="shipping_method"]:checked').each(function() {
      const id = $(this).attr('id');
      $(`#aakaari-shipping-methods .radio-option[data-method-id="${id}"]`).addClass('selected');
    });
  }
  
  /**
   * Navigate to previous step
   */
  function navigateBack() {
    if (currentStep > 1) {
      goToStep(currentStep - 1);
    }
  }
  
  /**
   * Navigate to next step if current step is valid
   */
  function navigateForward() {
    if (validateStep(currentStep) && currentStep < totalSteps) {
      goToStep(currentStep + 1);
    }
  }
  
  /**
   * Go to specific step
   */
  function goToStep(step) {
    if (step < 1 || step > totalSteps) return;
    
    currentStep = step;
    Object.values($stepContents).forEach(el => el.addClass('hidden'));
    $stepContents[step].removeClass('hidden');
    
    updateProgressBar();
    updateButtonLabels();
    scrollToTop();
    $('body').trigger('update_checkout');
  }
  
  /**
   * Update progress bar to reflect current step
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
    
    if (currentStep === totalSteps) {
      nextBtnText = 'Place Order';
      nextBtnHtml = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg> 
        ${nextBtnText}
      `;
    }
    
    $nextBtns.html(nextBtnHtml);
    $('#desktop-btn-text').text(nextBtnText);
    
    const showBack = currentStep > 1 ? 'flex' : 'none';
    $backBtns.css('display', showBack);
  }
  
  /**
   * Validate the current step
   */
  function validateStep(step) {
    switch (step) {
      case 1:
        return validateContactInfo();
      case 2:
        return validateShippingMethod();
      case 3:
        return true;
      default:
        return true;
    }
  }
  
  /**
   * Validate contact and address information
   */
  function validateContactInfo() {
    const requiredFields = [
      'billing_email',
      'billing_first_name',
      'billing_last_name',
      'billing_phone',
      'shipping_first_name',
      'shipping_last_name',
      'shipping_address_1',
      'shipping_city',
      'shipping_state',
      'shipping_postcode'
    ];
    
    let isValid = true;
    
    for (const fieldId of requiredFields) {
      const $field = $(`#${fieldId}`);
      if ($field.length === 0 || !$field.is(':visible')) continue;
      
      if (!$field.val()) {
        highlightInvalidField($field);
        isValid = false;
      } else {
        removeInvalidHighlight($field);
      }
    }
    
    const $email = $('#billing_email');
    if ($email.length && $email.val() && !isValidEmail($email.val())) {
      highlightInvalidField($email);
      isValid = false;
    }
    
    return isValid;
  }
  
  /**
   * Validate shipping method selection
   */
  function validateShippingMethod() {
    if (!$('body').hasClass('woocommerce-shipping-calculator-enabled') &&
        !$('#shipping_method').length) {
      return true;
    }
    
    const isSelected = $('input[name^="shipping_method"]:checked').length > 0;
    
    if (!isSelected) {
      const $firstOption = $('#aakaari-shipping-methods .radio-option').first();
      highlightInvalidField($firstOption);
      return false;
    }
    
    return true;
  }
  
  /**
   * Highlight invalid field with animation
   */
  function highlightInvalidField($field) {
    if ($field.hasClass('select2-hidden-accessible')) {
      $field.next('.select2-container').addClass('aak-shake');
      $field.next('.select2-container').find('.select2-selection').css('border-color', 'var(--err)');
    } else {
      $field.addClass('aak-shake');
      $field.css('border-color', 'var(--err)');
    }
    
    setTimeout(() => {
      $field.removeClass('aak-shake');
      if ($field.hasClass('select2-hidden-accessible')) {
         $field.next('.select2-container').removeClass('aak-shake');
      }
    }, 600);
  }
  
  /**
   * Remove invalid field highlighting
   */
  function removeInvalidHighlight($field) {
    if ($field.hasClass('select2-hidden-accessible')) {
      $field.next('.select2-container').find('.select2-selection').css('border-color', 'transparent'); // for grey bg
    } else {
      $field.css('border-color', 'transparent'); // for grey bg
    }
  }
  
  /**
   * Validate email format
   */
  function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }
  
  /**
   * Scroll to top of form container
   */
  function scrollToTop() {
    $('html, body').animate({
      scrollTop: $('#checkout-container').offset().top - 30
    }, 400);
  }

  /**
   * Enhances form fields without applying conflicting CSS.
   */
  function enhanceFormFields() {
    $('input, select, textarea').each(function() {
      const $input = $(this);
      if (!$input.attr('id') && $input.attr('name')) {
        $input.attr('id', $input.attr('name'));
      }
    });
    
    $('.woocommerce-billing-fields__field-wrapper, .woocommerce-shipping-fields__field-wrapper')
      .find('.form-row').each(function() {
        const $row = $(this);
        const $label = $row.find('label');
        const $input = $row.find('input, select, textarea');
        
        if ($label.length && $input.length) {
          if (!$label.attr('for') && $input.attr('id')) {
            $label.attr('for', $input.attr('id'));
          }
        }
      });
    
    // Add billing details header if missing
    if ($('.billing-details-header').length === 0 && $('.woocommerce-billing-fields').length > 0) {
        // Only add if it doesn't exist
        if( $('.woocommerce-billing-fields > h3').length === 0 ) {
             $('.woocommerce-billing-fields').prepend('<h3 class="billing-details-header">Billing details</h3>');
        }
    }
    
    // Fix checkboxes by adding a wrapper class for styling
    $('.input-checkbox').each(function() {
      const $checkbox = $(this);
      if (!$checkbox.parent().hasClass('checkbox-wrapper')) {
         $checkbox.parent('label').addClass('checkbox-wrapper');
      }
    });
  }
});