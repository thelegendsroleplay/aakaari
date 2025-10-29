/**
 * Aakaari Checkout - Shipping Methods UI Formatter
 *
 * This script does ONE thing only:
 * - Reads shipping methods from WooCommerce's hidden container
 * - Formats them into custom radio buttons
 *
 * That's it. No event listeners, no auto-updates, nothing.
 */

jQuery(function($) {

    // Track if we've already formatted (do it only once)
    let hasFormatted = false;

    /**
     * Format shipping methods from WooCommerce HTML into custom UI
     */
    function formatShippingMethods() {
        // Prevent multiple formats
        if (hasFormatted) {
            return;
        }

        const $container = $('#aakaari-shipping-methods');
        const $source = $('#aakaari-wc-shipping-source');

        if (!$container.length || !$source.length) {
            return;
        }

        // Clear loading message
        $container.empty();

        // Look for shipping methods in the source
        const $shippingList = $source.find('#shipping_method');

        // Format from UL/LI structure
        if ($shippingList.length && $shippingList.find('li').length > 0) {
            $shippingList.find('li').each(function() {
                const $li = $(this);
                const $input = $li.find('input.shipping_method');
                const $label = $li.find('label');

                if ($input.length && $label.length) {
                    const id = $input.attr('id');
                    const isChecked = $input.is(':checked');

                    // Extract method name (without price)
                    let methodName = $label.clone();
                    methodName.find('.woocommerce-Price-amount').remove();
                    methodName = methodName.text().trim();

                    // Extract price HTML
                    let priceHtml = '';
                    const $priceEl = $label.find('.woocommerce-Price-amount');
                    if ($priceEl.length) {
                        priceHtml = $priceEl.parent().html();
                    }

                    // Create custom radio option
                    const $option = $(`
                        <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">
                            <div class="radio-visual-input"></div>
                            <div class="radio-option-content">
                                <div class="radio-option-title">${methodName}</div>
                            </div>
                            <span class="radio-option-price">${priceHtml}</span>
                        </div>
                    `);

                    $container.append($option);
                }
            });

            hasFormatted = true;
            return;
        }

        // Format from table structure
        const $shippingTable = $source.find('table.woocommerce-shipping-totals');
        if ($shippingTable.length) {
            const $rows = $shippingTable.find('tr.shipping');

            $rows.each(function() {
                const $row = $(this);
                const $cell = $row.find('td');
                const $input = $cell.find('input[type="radio"]');

                if ($input.length) {
                    // Multiple shipping methods (radio buttons)
                    const id = $input.attr('id');
                    const isChecked = $input.is(':checked');
                    const $label = $cell.find('label[for="' + id + '"]');

                    if ($label.length) {
                        let methodName = $label.clone();
                        methodName.find('.woocommerce-Price-amount').remove();
                        methodName = methodName.text().trim();

                        let priceHtml = '';
                        const $priceEl = $label.find('.woocommerce-Price-amount');
                        if ($priceEl.length) {
                            priceHtml = $priceEl.parent().html();
                        }

                        const $option = $(`
                            <div class="radio-option ${isChecked ? 'selected' : ''}" data-method-id="${id}">
                                <div class="radio-visual-input"></div>
                                <div class="radio-option-content">
                                    <div class="radio-option-title">${methodName}</div>
                                </div>
                                <span class="radio-option-price">${priceHtml}</span>
                            </div>
                        `);

                        $container.append($option);
                    }
                } else {
                    // Single shipping method (no radio)
                    let methodName = $row.find('th').clone();
                    methodName.find('.woocommerce-Price-amount').remove();
                    methodName = methodName.text().trim();

                    let priceHtml = '';
                    const $priceEl = $cell.find('.woocommerce-Price-amount');
                    if ($priceEl.length) {
                        priceHtml = $priceEl.parent().html();
                    } else {
                        priceHtml = $cell.clone().children().remove().end().html();
                    }

                    const $option = $(`
                        <div class="radio-option selected">
                            <div class="radio-visual-input"></div>
                            <div class="radio-option-content">
                                <div class="radio-option-title">${methodName}</div>
                            </div>
                            <span class="radio-option-price">${priceHtml}</span>
                        </div>
                    `);

                    $container.append($option);
                }
            });

            hasFormatted = true;
            return;
        }

        // No shipping methods found
        $container.html('<p>No shipping methods available.</p>');
    }

    // Listen for step 2 - format shipping methods when user arrives
    $(document).on('aak_step_changed', function(event, step) {
        if (step === 2) {
            setTimeout(formatShippingMethods, 100);
        }
    });

});
