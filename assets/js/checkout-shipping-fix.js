/**
 * Aakaari Checkout - Shipping Methods Fix v2.0
 *
 * Updated Logic (User Interaction Only):
 *
 * 1. Format shipping UI ONCE when user arrives at step 2
 * 2. Display default selected shipping method (no auto-updates)
 * 3. ONLY trigger WooCommerce update when user clicks a DIFFERENT method
 * 4. Track user interactions to prevent automatic reformatting loops
 *
 * This prevents infinite loops and unnecessary AJAX calls.
 * Updates happen ONLY on actual user interaction.
 */

jQuery(function($) {
    console.log('🚀 Aakaari Checkout Shipping Fix v2.0 - Loaded (User Interaction Only)');

    // State management to prevent loops
    let hasFormattedShipping = false;
    let lastSelectedMethodId = null;
    let isUserInteraction = false;

    /**

     * Function to format shipping options directly from WooCommerce's hidden source.

     */

    function formatShippingMethodsDirectly() {

        // The container for our custom UI

        const $container = $('#aakaari-shipping-methods');

        if (!$container.length) return;

        // The hidden div where WooCommerce updates the original methods

        const $sourceContainer = $('#aakaari-wc-shipping-source');

        if (!$sourceContainer.length) {

            console.log('Aakaari Checkout: Error - Missing source container #aakaari-wc-shipping-source');

            return;

        }

        let hasShippingMethods = false;

        // 1. Check for UL format

        const $shippingMethodList = $sourceContainer.find('#shipping_method');

        if ($shippingMethodList.length && $shippingMethodList.find('li').length > 0) {

            hasShippingMethods = true;

            $container.empty(); // Clear old UI

            $shippingMethodList.find('li').each(function() {

                const $li = $(this);

                const $input = $li.find('input.shipping_method');

                const $label = $li.find('label');

                if ($input.length && $label.length) {

                    const id = $input.attr('id');

                    const isChecked = $input.is(':checked');

                    

                    let methodName = $label.clone();

                    methodName.find('.woocommerce-Price-amount').remove();

                    methodName = methodName.text().trim();

                    

                    let priceHtml = '';

                    const $priceEl = $label.find('.woocommerce-Price-amount');

                    if ($priceEl.length) {

                        priceHtml = $priceEl.parent().html();

                    }

                    

                    const newOption = $(`

                        <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">

                            <div class="radio-visual-input"></div>

                            <div class="radio-option-content">

                                <div class="radio-option-title">${methodName}</div>

                            </div>

                            <span class="radio-option-price">${priceHtml}</span>

                        </div>

                    `);

                    $container.append(newOption);

                }

            });

            console.log('Aakaari Checkout: Found ' + $shippingMethodList.find('li').length + ' shipping methods (List format)');

        }

        // 2. Check for Table format

        else if ($sourceContainer.find('table.woocommerce-shipping-totals').length) {

            const $shippingRows = $sourceContainer.find('table.woocommerce-shipping-totals tr.shipping');

            

            if ($shippingRows.length) {

                hasShippingMethods = true;

                $container.empty(); // Clear old UI

                $shippingRows.each(function() {

                    const $row = $(this);

                    const $methodCell = $row.find('td');

                    const $input = $methodCell.find('input[type="radio"]');

                    if ($input.length) { // Radio button rows

                        const id = $input.attr('id');

                        const isChecked = $input.is(':checked');

                        const $label = $methodCell.find('label[for="' + id + '"]');

                        if ($label.length) {

                            let methodName = $label.clone();

                            methodName.find('.woocommerce-Price-amount').remove();

                            methodName = methodName.text().trim();

                            

                            let priceHtml = '';

                            const $priceEl = $label.find('.woocommerce-Price-amount');

                            if ($priceEl.length) {

                                priceHtml = $priceEl.parent().html();

                            }

                            

                            const newOption = $(`

                                <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">

                                    <div class="radio-visual-input"></div>

                                    <div class="radio-option-content">

                                        <div class="radio-option-title">${methodName}</div>

                                    </div>

                                    <span class="radio-option-price">${priceHtml}</span>

                                </div>

                            `);

                            $container.append(newOption);

                        }

                    } 

                    else { // Single method (no radio button)

                        let methodName = $row.find('th').clone();

                        methodName.find('.woocommerce-Price-amount').remove();

                        methodName = methodName.text().trim();

                        

                        let priceHtml = '';

                        const $priceEl = $methodCell.find('.woocommerce-Price-amount');

                        if ($priceEl.length) {

                            priceHtml = $priceEl.parent().html();

                        } else {

                            priceHtml = $methodCell.clone().children().remove().end().html();

                        }

                        

                        const newOption = $(`

                            <div class="radio-option selected">

                                <div class="radio-visual-input"></div>

                                <div class="radio-option-content">

                                    <div class="radio-option-title">${methodName}</div>

                                </div>

                                <span class="radio-option-price">${priceHtml}</span>

                            </div>

                        `);

                        $container.append(newOption);

                    }

                });

                

                console.log('Aakaari Checkout: Found ' + $shippingRows.length + ' shipping methods (Table format)');

            }

        }

        // 3. Bind click events to the new UI

        $container.find('.radio-option[data-method-id]').off('click').on('click', function() {

            const $this = $(this);

            if ($this.hasClass('selected')) {

                console.log('Aakaari Checkout: Method already selected, no action needed');
                return; // Already selected, do nothing

            }

            const methodId = $this.data('method-id');

            if (methodId) {

                // Check if this is actually a new selection
                if (methodId === lastSelectedMethodId) {
                    console.log('Aakaari Checkout: Same method already selected, skipping update');
                    return;
                }

                console.log('Aakaari Checkout: User clicked different shipping method: ' + methodId);
                lastSelectedMethodId = methodId;
                isUserInteraction = true; // Mark as user interaction



                // Find the original radio button in the hidden source

                const $originalInput = $sourceContainer.find('#' + methodId);



                if ($originalInput.length) {

                    // Update our custom UI first
                    $container.find('.radio-option').removeClass('selected');
                    $this.addClass('selected');

                    // Then trigger WooCommerce update - this will update order summary
                    console.log('Aakaari Checkout: Triggering WooCommerce update for new selection');
                    $originalInput.prop('checked', true).trigger('change');

                }

            }

        });

        // 4. Handle case where no shipping methods are available

        if (!hasShippingMethods) {

            console.log('Aakaari Checkout: No shipping methods found in source.');

            $container.html('<p>No shipping methods available for your address. Please check your address in Step 1.</p>');

        } else {
            // Store the currently selected method ID
            const $selectedInput = $sourceContainer.find('input[name^="shipping_method["]:checked');
            if ($selectedInput.length) {
                lastSelectedMethodId = $selectedInput.attr('id');
                console.log('Aakaari Checkout: Current selected method: ' + lastSelectedMethodId);
            }
        }

        // Mark that we've formatted the shipping methods
        hasFormattedShipping = true;

    }

    // --- Main Event Listeners ---

    // Listen for step changes from the checkout controller
    $(document).on('aak_step_changed', function(event, step) {
        console.log('Aakaari Checkout: Step changed to ' + step);

        // Only format shipping methods when arriving at step 2 for the first time
        if (step === 2 && !hasFormattedShipping) {
            console.log('Aakaari Checkout: First time on step 2, formatting shipping methods');
            setTimeout(formatShippingMethodsDirectly, 200);
        }
    });

    // Only reformat on updated_checkout if it was triggered by user interaction
    $(document.body).on('updated_checkout', function() {
        if (isUserInteraction) {
            console.log('Aakaari Checkout: Order summary updated after user selection');
            isUserInteraction = false;
        } else {
            console.log('Aakaari Checkout: updated_checkout detected (not user interaction, skipping reformat)');
        }
    });

    // Notes:
    // - Only formats shipping methods ONCE when step 2 is first accessed
    // - Only triggers WooCommerce update when user clicks a DIFFERENT shipping method
    // - This prevents infinite loops and unnecessary AJAX calls

});

