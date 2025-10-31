<?php
/**
 * File Handler Class
 * Handles secure file uploads and attachment management
 *
 * @package Aakaari_Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Aakaari_File_Handler {

    /**
     * Max file size (5MB)
     */
    const MAX_FILE_SIZE = 5242880;

    /**
     * Allowed MIME types
     */
    private $allowed_types = array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    );

    /**
     * AJAX upload design
     */
    public function ajax_upload_design() {
        check_ajax_referer('aakaari_customizer', 'nonce');

        if (empty($_FILES['design_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'aakaari')), 400);
        }

        $file = $_FILES['design_file'];

        // Validate file
        $validation = $this->validate_upload($file);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()), 400);
        }

        // Upload file
        $result = $this->handle_upload($file);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()), 500);
        }

        wp_send_json_success(array(
            'attachment_id' => $result['attachment_id'],
            'url' => $result['url'],
            'thumbnail' => $result['thumbnail'],
            'width' => $result['width'],
            'height' => $result['height'],
            'file_size' => $result['file_size'],
            'message' => __('File uploaded successfully.', 'aakaari')
        ));
    }

    /**
     * Validate upload
     */
    private function validate_upload($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', $this->get_upload_error_message($file['error']));
        }

        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    __('File size exceeds maximum allowed size of %s.', 'aakaari'),
                    size_format(self::MAX_FILE_SIZE)
                )
            );
        }

        // Check MIME type
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file_type['type'], $this->allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                __('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'aakaari')
            );
        }

        // Verify it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return new WP_Error('invalid_image', __('File is not a valid image.', 'aakaari'));
        }

        return true;
    }

    /**
     * Handle upload
     */
    private function handle_upload($file) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Upload file
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            return new WP_Error('upload_failed', $upload['error']);
        }

        // Create attachment
        $attachment_data = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);

        if (is_wp_error($attachment_id)) {
            // Clean up uploaded file if attachment creation fails
            @unlink($upload['file']);
            return $attachment_id;
        }

        // Generate metadata
        $attachment_meta = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_meta);

        // Get image dimensions
        $image_info = getimagesize($upload['file']);

        // Generate thumbnail if needed
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'medium');

        return array(
            'attachment_id' => $attachment_id,
            'url' => $upload['url'],
            'thumbnail' => $thumbnail_url ? $thumbnail_url : $upload['url'],
            'width' => $image_info[0],
            'height' => $image_info[1],
            'file_size' => filesize($upload['file'])
        );
    }

    /**
     * Get upload error message
     */
    private function get_upload_error_message($error_code) {
        $messages = array(
            UPLOAD_ERR_INI_SIZE => __('File exceeds server upload limit.', 'aakaari'),
            UPLOAD_ERR_FORM_SIZE => __('File exceeds form upload limit.', 'aakaari'),
            UPLOAD_ERR_PARTIAL => __('File was only partially uploaded.', 'aakaari'),
            UPLOAD_ERR_NO_FILE => __('No file was uploaded.', 'aakaari'),
            UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder.', 'aakaari'),
            UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'aakaari'),
            UPLOAD_ERR_EXTENSION => __('File upload stopped by extension.', 'aakaari')
        );

        return isset($messages[$error_code]) ? $messages[$error_code] : __('Unknown upload error.', 'aakaari');
    }

    /**
     * Get attachment data
     */
    public function get_attachment_data($attachment_id) {
        $attachment_id = absint($attachment_id);

        if (!wp_attachment_is_image($attachment_id)) {
            return false;
        }

        $metadata = wp_get_attachment_metadata($attachment_id);

        return array(
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'thumbnail' => wp_get_attachment_image_url($attachment_id, 'medium'),
            'width' => isset($metadata['width']) ? $metadata['width'] : 0,
            'height' => isset($metadata['height']) ? $metadata['height'] : 0,
            'file_size' => filesize(get_attached_file($attachment_id))
        );
    }

    /**
     * Create preview image from canvas data
     *
     * @param string $canvas_data Base64 encoded image data
     * @return int|WP_Error Attachment ID or error
     */
    public function create_preview_from_canvas($canvas_data) {
        // Remove data URL prefix
        if (strpos($canvas_data, 'data:image') === 0) {
            $canvas_data = preg_replace('/^data:image\/\w+;base64,/', '', $canvas_data);
        }

        $image_data = base64_decode($canvas_data);
        if ($image_data === false) {
            return new WP_Error('invalid_canvas_data', __('Invalid canvas data.', 'aakaari'));
        }

        // Create temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'preview_' . uniqid() . '.png';
        $filepath = $upload_dir['path'] . '/' . $filename;

        // Save image
        if (file_put_contents($filepath, $image_data) === false) {
            return new WP_Error('file_write_failed', __('Failed to save preview image.', 'aakaari'));
        }

        // Create attachment
        $attachment_data = array(
            'post_mime_type' => 'image/png',
            'post_title' => 'Customization Preview',
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment_data, $filepath);

        if (is_wp_error($attachment_id)) {
            @unlink($filepath);
            return $attachment_id;
        }

        // Generate metadata
        $attachment_meta = wp_generate_attachment_metadata($attachment_id, $filepath);
        wp_update_attachment_metadata($attachment_id, $attachment_meta);

        return $attachment_id;
    }

    /**
     * Delete attachments
     */
    public function delete_attachments($attachment_ids) {
        if (!is_array($attachment_ids)) {
            $attachment_ids = array($attachment_ids);
        }

        foreach ($attachment_ids as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }
}
