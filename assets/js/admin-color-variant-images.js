/**
 * Admin Color Variant Images
 * Handles uploading and managing color variant images in admin
 */

(function($) {
    'use strict';

    let mediaUploader;

    $(document).ready(function() {
        init();
    });

    function init() {
        // Handle upload button click
        $(document).on('click', '.upload-color-variant-image', handleUploadClick);

        // Handle remove button click
        $(document).on('click', '.remove-color-variant-image', handleRemoveClick);
    }

    function handleUploadClick(e) {
        e.preventDefault();

        const $button = $(this);
        const $row = $button.closest('tr');
        const color = $row.data('color');

        // If the media uploader instance exists, reopen it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create a new media uploader instance
        mediaUploader = wp.media({
            title: aakaariColorVariant.upload_title,
            button: {
                text: aakaariColorVariant.upload_button
            },
            multiple: false
        });

        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();

            // Update the preview
            const $preview = $row.find('.color-variant-image-preview');
            $preview.html(
                '<img src="' + attachment.url + '" style="max-width:100px; max-height:100px; border:1px solid #ddd; border-radius:4px;" />'
            );

            // Update the hidden input
            $row.find('.color-variant-image-id').val(attachment.id);

            // Show remove button if not already visible
            if (!$row.find('.remove-color-variant-image').length) {
                $button.after(
                    '<button type="button" class="button remove-color-variant-image">' +
                    'Remove' +
                    '</button>'
                );
            }

            console.log('Color variant image uploaded for color:', color, 'Image ID:', attachment.id);
        });

        // Open the media uploader
        mediaUploader.open();
    }

    function handleRemoveClick(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to remove this image?')) {
            return;
        }

        const $button = $(this);
        const $row = $button.closest('tr');

        // Clear the preview
        $row.find('.color-variant-image-preview').html(
            '<span class="no-image" style="color:#999;">No image set</span>'
        );

        // Clear the hidden input
        $row.find('.color-variant-image-id').val('');

        // Remove the remove button
        $button.remove();

        console.log('Color variant image removed');
    }

})(jQuery);
