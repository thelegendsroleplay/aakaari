/**
 * Aakaari Multi-step Checkout (WooCommerce)
 * - Keeps Woo hooks intact
 * - Validates step-by-step (no native blocking)
 * - Triggers update_checkout when shipping changes / step changes
 */

jQuery(function ($) {
  let currentStep = 1;

  // Elements
  const $form   = $('#checkout-form');
  const $step1  = $('#step-1-content');
  const $step2  = $('#step-2-content');
  const $step3  = $('#step-3-content');

  const $mobileBack  = $('#mobile-back-btn');
  const $mobileNext  = $('#mobile-next-btn');
  const $deskBack    = $('#desktop-back-btn');
  const $deskNext    = $('#desktop-next-btn');

  const $progress1 = $('#progress-step-1');
  const $progress2 = $('#progress-step-2');
  const $progress3 = $('#progress-step-3');

  // Disable native validation – we validate in JS
  $form.attr('novalidate','novalidate');

  // Buttons
  $('#back-to-cart, #back-to-cart-top').on('click', goBackToCart);
  $mobileBack.on('click', onBack);
  $deskBack.on('click', onBack);
  $mobileNext.on('click', () => $form.trigger('submit'));
  $deskNext.on('click', () => $form.trigger('submit'));

  // Submit (advance step or place order)
  $form.on('submit', function(e){
    // On final step we let Woo submit normally
    if (currentStep < 3) e.preventDefault();

    if (currentStep === 1) {
      if (!validateStep1()) return;
      showStep(2);
      $('body').trigger('update_checkout');
      return;
    }

    if (currentStep === 2) {
      if (!validateStep2()) return;
      showStep(3);
      $('body').trigger('update_checkout');
      return;
    }

    // Step 3: let Woo handle order submit
  });

  // Shipping method change should refresh totals
  $(document).on('change', 'input[name^="shipping_method"]', function(){
    $('body').trigger('update_checkout');
  });

  // Payment refresh: Woo handles gateway rendering; we just ensure icons refresh
  $(document.body).on('updated_checkout', function(){
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });

  function onBack() {
    if (currentStep > 1) showStep(currentStep - 1);
  }

  function goBackToCart() {
    const url = (window.aakaariCheckout && aakaariCheckout.cartUrl) ? aakaariCheckout.cartUrl : (wc_checkout_params?.cart_url || '/cart/');
    window.location.href = url;
  }

  function showStep(n) {
    currentStep = n;
    $step1.addClass('hidden'); $step2.addClass('hidden'); $step3.addClass('hidden');
    if (n === 1) $step1.removeClass('hidden');
    if (n === 2) $step2.removeClass('hidden');
    if (n === 3) $step3.removeClass('hidden');

    updateProgress();
    updateNavLabels();

    // Scroll the form into view
    $('html,body').animate({scrollTop: $form.offset().top - 50}, 250);

    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  function updateProgress() {
    $progress1.removeClass('active completed');
    $progress2.removeClass('active completed');
    $progress3.removeClass('active completed');

    if (currentStep === 1) $progress1.addClass('active');
    if (currentStep === 2) { $progress1.addClass('completed'); $progress2.addClass('active'); }
    if (currentStep === 3) { $progress1.addClass('completed'); $progress2.addClass('completed'); $progress3.addClass('active'); }
  }

  function updateNavLabels() {
    const next = currentStep === 3 ? '<i data-lucide="lock" width="16" height="16"></i> Place Order' : 'Continue';
    $mobileNext.html(next);
    $deskNext.html(next);

    const showBack = currentStep > 1 ? 'inline-flex' : 'none';
    $mobileBack.css('display', showBack);
    $deskBack.css('display', showBack);
  }

  // ——— Validations (keep it light; Woo will also validate server-side) ———
  function validateStep1() {
    // Check a few common billing fields if present
    const ids = ['billing_email','billing_phone','billing_first_name','billing_last_name','billing_address_1','billing_city','billing_state','billing_postcode'];
    for (const id of ids) {
      const $f = $('#'+id);
      if ($f.length && !$f.val()) { toast('Please complete: ' + ( $('label[for="'+id+'"]').text() || id )); $f.focus(); return false; }
    }
    return true;
  }

  function validateStep2() {
    if (!$('input[name^="shipping_method"]:checked').length && $('body').hasClass('shipping-enabled')) {
      toast('Please select a shipping method.');
      return false;
    }
    return true;
  }

  function toast(msg){ if (window.console) console.warn(msg); }
  
  // Init
  showStep(1);
  
  // Initialize Lucide icons if available
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
  
  // Add selected class to radio options on click (for browsers without :has support)
  $(document).on('change', '.radio-option input[type="radio"]', function() {
    $('.radio-option').removeClass('selected');
    $(this).closest('.radio-option').addClass('selected');
  });
});