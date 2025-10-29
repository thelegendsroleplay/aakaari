/**

 * Aakaari Checkout - Shipping Methods Fix (Simple, Reactive Version)

 *

 * Logic (as requested):

 * 1. Read shipping methods from the hidden '#aakaari-wc-shipping-source' div.

 * 2. Build the custom UI in '#aakaari-shipping-methods'.

 * 3. When a user clicks a custom option, check the original hidden radio button and trigger 'change'.

 * 4. WooCommerce's 'change' handler triggers 'update_checkout' AJAX.

 * 5. After AJAX completes ('updated_checkout'), re-run this script to rebuild the UI.

 *

 * This version removes all 'forceShippingUpdate' and 'setupBillingFieldMonitoring'

 * to prevent recalculation loops.

 */

jQuery(function($) {

    // State management to prevent loops
    let isUpdating = false;
    let updateTimeout = null;
    let lastSelectedMethodId = null;

    /**

     * Function to format shipping options directly from WooCommerce's hidden source.

     */

    function formatShippingMethodsDirectly() {

        // Prevent concurrent updates
        if (isUpdating) {
            console.log('Aakaari Checkout: Update already in progress, skipping...');
            return;
        }

        // The container for our custom UI

        const $container = $('#aakaari-shipping-methods');

        if (!$container.length) return;

        // The hidden div where WooCommerce updates the original methods

        const $sourceContainer = $('#aakaari-wc-shipping-source');

        if (!$sourceContainer.length) {

            console.log('Aakaari Checkout: Error - Missing source container #aakaari-wc-shipping-source');

            return;

        }

        isUpdating = true;
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

                return; // Already selected, do nothing

            }

            const methodId = $this.data('method-id');

            if (methodId) {

                // Check if this is actually a new selection
                if (methodId === lastSelectedMethodId) {
                    console.log('Aakaari Checkout: Same method already selected, skipping update');
                    return;
                }

                console.log('Aakaari Checkout: User selected shipping ID: ' + methodId);
                lastSelectedMethodId = methodId;



                // Find the original radio button in the hidden source

                const $originalInput = $sourceContainer.find('#' + methodId);



                if ($originalInput.length) {

                    // Check it and trigger 'change'

                    // This is what tells WooCommerce to update the summary

                    $originalInput.prop('checked', true).trigger('change');



                    // Update our custom UI

                    $container.find('.radio-option').removeClass('selected');

                    $this.addClass('selected');

                }

            }

        });

        // 4. Handle case where no shipping methods are available

        if (!hasShippingMethods) {

            console.log('Aakaari Checkout: No shipping methods found in source.');

            $container.html('<p>No shipping methods available for your address. Please check your address in Step 1.</p>');

        }

        // Mark update as complete
        isUpdating = false;

    }

    // --- Main Event Listeners ---

    // 1. Run on document ready

    // We add a small delay to ensure all DOM elements, including the source div, are ready.

    setTimeout(formatShippingMethodsDirectly, 100);

    // 2. Run after WooCommerce updates the checkout

    // This is the *most important* listener.

    // When WC finishes its AJAX, it rebuilds the hidden source div.

    // We must then re-run our function to rebuild our custom UI.
    // Added debouncing to prevent rapid successive updates

    $(document.body).on('updated_checkout', function() {

        console.log('Aakaari Checkout: "updated_checkout" detected, scheduling reformat...');

        // Clear any pending update
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }

        // Debounce the update with a 300ms delay
        updateTimeout = setTimeout(function() {
            console.log('Aakaari Checkout: Executing debounced reformat');
            formatShippingMethodsDirectly();
            updateTimeout = null;
        }, 300);

    });

    // ALL 'forceShippingUpdate', 'setupBillingFieldMonitoring', 

    // and 'aak_step_changed' listeners have been REMOVED to stop loops.

});

