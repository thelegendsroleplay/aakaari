/**
 * Fixed WooCommerce Cart JavaScript
 * This version fixes the quantity button issue
 */
jQuery(function($) {
  // Remove any existing handlers to prevent duplicates
  $(document).off('click', '.quantity-btn.minus, .quantity-btn.plus, .qty-btn');
  $('.quantity-btn.minus, .quantity-btn.plus, .qty-btn').off('click');
  
  // Initialize only once
  var isInitialized = false;
  
  function initializeCart() {
    if (isInitialized) return;
    isInitialized = true;
    
    // Setup quantity controls
    setupQuantityButtons();
    
    // Update the savings progress bar
    updateSavingsProgress();
    
    // Set up other event handlers
    setupEventHandlers();
  }
  
  // ---- Quantity Button Handlers ----
  function setupQuantityButtons() {
    // Setup minus buttons - using ONE-TIME binding with .one()
    $(document).on('click', '.quantity-btn.minus', function(e) {
      e.preventDefault();
      e.stopPropagation(); // Stop event propagation
      
      // Find the input field
      var $input = $(this).siblings('.quantity-input, .qty');
      
      // Get current value
      var currentVal = parseInt($input.val());
      
      // Only decrease if value is greater than 1
      if (!isNaN(currentVal) && currentVal > 1) {
        // Explicitly set the new value
        $input.val(currentVal - 1);
        
        // Add a short delay before updating cart
        setTimeout(function() {
          $('[name="update_cart"]').prop('disabled', false).trigger('click');
        }, 50);
      }
      
      return false; // Prevent default and stop propagation
    });
    
    // Setup plus buttons - using ONE-TIME binding to prevent multiple handlers
    $(document).on('click', '.quantity-btn.plus', function(e) {
      e.preventDefault();
      e.stopPropagation(); // Stop event propagation
      
      // Find the input field
      var $input = $(this).siblings('.quantity-input, .qty');
      
      // Get current value
      var currentVal = parseInt($input.val());
      
      // Increase by EXACTLY 1
      if (!isNaN(currentVal)) {
        // Store original value to enforce +1 increment
        var originalVal = currentVal;
        
        // Explicitly set to original + 1
        $input.val(originalVal + 1);
        
        // Add a short delay before updating cart
        setTimeout(function() {
          $('[name="update_cart"]').prop('disabled', false).trigger('click');
        }, 50);
      } else {
        $input.val(1);
        
        // Add a short delay before updating cart
        setTimeout(function() {
          $('[name="update_cart"]').prop('disabled', false).trigger('click');
        }, 50);
      }
      
      return false; // Prevent default and stop propagation
    });
    
    // Remove any automatic update on input change
    $(document).off('change', '.quantity-input, .qty');
  }
  
  // ---- Event Handlers ----
  function setupEventHandlers() {
    // Cart quantity change handler with debounce
    var updateTimer;
    var $cartForm = $('.woocommerce-cart-form');
    
    // Remove existing handler and add a new one
    $cartForm.off('change', '.quantity-input, .qty');
    $cartForm.on('change', '.quantity-input, .qty', function() {
      clearTimeout(updateTimer);
      blockCartUI();
      
      // Debounce to prevent multiple rapid updates
      updateTimer = setTimeout(function() {
        // Use WooCommerce's native update cart button
        $('[name="update_cart"]').prop('disabled', false).trigger('click');
      }, 500);
    });
    
    // Remove item handler (with AJAX)
    $(document).off('click', '.remove-item');
    $(document).on('click', '.remove-item', function(e) {
      e.preventDefault();
      blockCartUI();
      
      var $thisLink = $(this);
      var url = $thisLink.attr('href');
      
      $.ajax({
        type: 'GET',
        url: url,
        dataType: 'html',
        success: function(response) {
          var $html = $(response);
          var $updatedCartForm = $html.find('.woocommerce-cart-form');
          
          if ($updatedCartForm.length) {
            // Update the cart form with the new HTML
            $('.woocommerce-cart-form').replaceWith($updatedCartForm);
            
            // Update fragments
            var fragments = {
              '.woocommerce-cart-form': $updatedCartForm.html()
            };
            
            $(document.body).trigger('wc_fragment_refresh');
            unblockCartUI();
            // Don't reinitialize here - it will happen via updated_cart_totals event
          } else {
            // No cart form found, page might be redirected to empty cart
            window.location.reload();
          }
        },
        error: function() {
          // Fallback: redirect to the URL
          window.location.href = url;
        }
      });
    });
    
    // Apply coupon button handler
    $(document).off('click', '.apply-btn');
    $(document).on('click', '.apply-btn', function(e) {
      // Don't need to prevent default as this is a submit button
      // Just make sure coupon field has a value
      var couponCode = $('#coupon_code').val().trim();
      
      if (couponCode === '') {
        // If empty, prevent submission and focus the field
        e.preventDefault();
        $('#coupon_code').focus();
      }
      // Otherwise, let the form submit normally
    });
    
    // Allow Enter key to submit coupon
    $(document).off('keypress', '#coupon_code');
    $(document).on('keypress', '#coupon_code', function(e) {
      if (e.which === 13) {
        e.preventDefault();
        $('.apply-btn').click();
      }
    });
  }
  
  // ---- Update Discount Savings Bar ----
  function updateSavingsProgress() {
    // Get the current subtotal from hidden field
    var $subtotalInput = $('#cart_subtotal');
    if (!$subtotalInput.length) return;
    
    var subtotal = parseFloat($subtotalInput.data('value'));
    if (isNaN(subtotal)) subtotal = 0;
    
    // Get all discount tiers
    var $tiers = $('.discount-tier');
    if (!$tiers.length) return;
    
    // Determine current and next tier
    var currentTier = null;
    var nextTier = null;
    var activeTierIndex = -1;
    
    $tiers.each(function(index) {
      var $tier = $(this);
      var threshold = parseFloat($tier.data('threshold'));
      
      if (subtotal >= threshold) {
        $tier.find('.tier-radio').addClass('active');
        currentTier = $tier;
        activeTierIndex = index;
      } else {
        $tier.find('.tier-radio').removeClass('active');
        if (nextTier === null) {
          nextTier = $tier;
        }
      }
    });
    
    // Update progress bar
    var $progressBar = $('.progress-bar');
    var $progressText = $('.savings-text');
    var $savingsBadge = $('.savings-badge');
    
    // Calculate progress
    var progressPercent;
    
    if (nextTier) {
      // Calculate progress to next tier
      var nextThreshold = parseFloat(nextTier.data('threshold'));
      var baseThreshold = currentTier ? parseFloat(currentTier.data('threshold')) : 0;
      var range = nextThreshold - baseThreshold;
      var progressToNext = (subtotal - baseThreshold) / range;
      
      // Scale for segmented progress bar
      var segmentSize = 100 / $tiers.length;
      progressPercent = (activeTierIndex + 1) * segmentSize * progressToNext;
      
      // Update text
      var nextDiscount = nextTier.text().match(/(\d+)%/)[1];
      var remaining = nextThreshold - subtotal;
      
      $progressText.html('Add <strong>' + formatMoney(remaining) + '</strong> more to unlock <strong>' + nextDiscount + '% discount</strong>');
      
      // Update badge
      if (currentTier) {
        var currentDiscount = currentTier.text().match(/(\d+)%/)[1];
        $savingsBadge.text(currentDiscount + '% OFF');
      } else {
        $savingsBadge.text('0% OFF');
      }
    } else if (currentTier) {
      // Max tier reached
      progressPercent = 100;
      
      // Update text and badge
      var maxDiscount = currentTier.text().match(/(\d+)%/)[1];
      
      $progressText.html('<strong>Congrats!</strong> You unlocked the maximum <strong>' + maxDiscount + '% discount</strong>!');
      $savingsBadge.text(maxDiscount + '% OFF');
    } else {
      // No tier reached, first tier not met
      progressPercent = (subtotal / parseFloat($tiers.first().data('threshold'))) * (100 / $tiers.length);
      
      // Update text and badge
      var firstThreshold = parseFloat($tiers.first().data('threshold'));
      var firstDiscount = $tiers.first().text().match(/(\d+)%/)[1];
      
      $progressText.html('Add <strong>' + formatMoney(firstThreshold - subtotal) + '</strong> to unlock your first discount!');
      $savingsBadge.text('0% OFF');
    }
    
    // Update progress bar width
    $progressBar.css('width', progressPercent + '%');
  }
  
  // ---- Helper Functions ----
  
  function formatMoney(amount) {
    if (typeof woocommerce_params !== 'undefined' && woocommerce_params.currency_symbol) {
      return woocommerce_params.currency_symbol + parseFloat(amount).toFixed(2);
    }
    return '$' + parseFloat(amount).toFixed(2);
  }
  
  function blockCartUI() {
    var $targets = $('.cart_totals, .cart-items-list');
    
    if ($targets.length && $.fn.block) {
      $targets.block({
        message: null,
        overlayCSS: {
          background: '#fff',
          opacity: 0.6
        }
      });
    }
  }
  
  function unblockCartUI() {
    var $targets = $('.cart_totals, .cart-items-list');
    
    if ($targets.length && $.fn.unblock) {
      $targets.unblock();
    }
  }
  
  // Make sure update cart button is enabled
  $('[name="update_cart"]').prop('disabled', false);
  
  // Check if update cart is completely missing and add it if needed
  if ($('[name="update_cart"]').length === 0) {
    $('.woocommerce-cart-form').append('<button type="submit" class="button" name="update_cart" value="Update cart" style="display:none">Update cart</button>');
  }
  
  // Handle single initialization on page load
  initializeCart();
  
  // On cart updates, only re-initialize necessary parts
  $(document.body).on('updated_cart_totals wc_fragments_refreshed', function() {
    unblockCartUI();
    updateSavingsProgress();
    setupQuantityButtons(); // Re-setup quantity buttons
    $('[name="update_cart"]').prop('disabled', false); // Make sure button is enabled
  });
});