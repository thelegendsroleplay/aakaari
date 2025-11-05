<?php
/**
 * Admin Interface for Color Variant Images
 * Allows admin to upload different product images for each color variant
 *
 * @package Aakaari
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Aakaari_Color_Variant_Images {

    /**
     * Initialize
     */
    public static function init() {
        $instance = new self();
        $instance->hooks();
    }

    /**
     * Setup hooks
     */
    private function hooks() {
        // Add color variant images meta box to products
        add_action('add_meta_boxes', [$this, 'add_color_variant_meta_box']);

        // Save color variant images
        add_action('save_post_product', [$this, 'save_color_variant_images']);

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // AJAX handler for uploading images
        add_action('wp_ajax_upload_color_variant_image', [$this, 'ajax_upload_image']);
    }

    /**
     * Add meta box for color variant images
     */
    public function add_color_variant_meta_box() {
        add_meta_box(
            'aakaari_color_variant_images',
            __('Color Variant Images', 'aakaari'),
            [$this, 'render_color_variant_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render color variant images meta box
     */
    public function render_color_variant_meta_box($post) {
        wp_nonce_field('aakaari_color_variant_images', 'aakaari_color_variant_images_nonce');

        // Get print studio data to get colors
        $studio_data = get_post_meta($post->ID, '_aakaari_print_studio_data', true);
        $colors = isset($studio_data['colors']) && is_array($studio_data['colors']) ? $studio_data['colors'] : array();

        // Get saved color variant images
        $variant_images = get_post_meta($post->ID, '_aakaari_color_variant_images', true);
        if (!is_array($variant_images)) {
            $variant_images = array();
        }

        ?>
        <div id="color-variant-images-container">
            <p><?php _e('Upload different product images for each color variant. These images will be shown when customers select different colors.', 'aakaari'); ?></p>

            <?php if (empty($colors)) : ?>
                <p class="description">
                    <?php _e('No colors configured for this product. Please configure colors in Print Studio first.', 'aakaari'); ?>
                </p>
            <?php else : ?>
                <table class="widefat" style="margin-top:15px;">
                    <thead>
                        <tr>
                            <th style="width:30%;"><?php _e('Color', 'aakaari'); ?></th>
                            <th style="width:40%;"><?php _e('Image', 'aakaari'); ?></th>
                            <th style="width:30%;"><?php _e('Actions', 'aakaari'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colors as $index => $hex_color) :
                            $color_name = $this->hex_to_color_name($hex_color);
                            $image_id = isset($variant_images[$hex_color]) ? $variant_images[$hex_color] : '';
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                        ?>
                            <tr data-color="<?php echo esc_attr($hex_color); ?>">
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span style="display:inline-block; width:30px; height:30px; border-radius:4px; border:1px solid #ddd; background-color:<?php echo esc_attr($hex_color); ?>;"></span>
                                        <strong><?php echo esc_html($color_name); ?></strong>
                                        <br><small><?php echo esc_html($hex_color); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="color-variant-image-preview">
                                        <?php if ($image_url) : ?>
                                            <img src="<?php echo esc_url($image_url); ?>" style="max-width:100px; max-height:100px; border:1px solid #ddd; border-radius:4px;" />
                                        <?php else : ?>
                                            <span class="no-image" style="color:#999;">
                                                <?php _e('No image set', 'aakaari'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="color_variant_images[<?php echo esc_attr($hex_color); ?>]" value="<?php echo esc_attr($image_id); ?>" class="color-variant-image-id" />
                                </td>
                                <td>
                                    <button type="button" class="button upload-color-variant-image">
                                        <?php _e('Upload Image', 'aakaari'); ?>
                                    </button>
                                    <?php if ($image_id) : ?>
                                        <button type="button" class="button remove-color-variant-image">
                                            <?php _e('Remove', 'aakaari'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="description" style="margin-top:15px;">
                    <?php _e('Tip: Upload high-quality images showing the product in each color. These images will automatically be displayed when customers select a color.', 'aakaari'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save color variant images
     */
    public function save_color_variant_images($post_id) {
        // Check nonce
        if (!isset($_POST['aakaari_color_variant_images_nonce']) ||
            !wp_verify_nonce($_POST['aakaari_color_variant_images_nonce'], 'aakaari_color_variant_images')) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save color variant images
        if (isset($_POST['color_variant_images']) && is_array($_POST['color_variant_images'])) {
            $variant_images = array();
            foreach ($_POST['color_variant_images'] as $color => $image_id) {
                if (!empty($image_id)) {
                    $variant_images[sanitize_text_field($color)] = absint($image_id);
                }
            }
            update_post_meta($post_id, '_aakaari_color_variant_images', $variant_images);
        } else {
            delete_post_meta($post_id, '_aakaari_color_variant_images');
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;

        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'product') {
            wp_enqueue_media();

            wp_enqueue_script(
                'aakaari-color-variant-images-admin',
                get_stylesheet_directory_uri() . '/assets/js/admin-color-variant-images.js',
                array('jquery'),
                '1.0.0',
                true
            );

            wp_localize_script('aakaari-color-variant-images-admin', 'aakaariColorVariant', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aakaari_color_variant'),
                'upload_title' => __('Choose Color Variant Image', 'aakaari'),
                'upload_button' => __('Use this image', 'aakaari'),
            ));

            // Add inline styles
            wp_add_inline_style('wp-admin', '
                #color-variant-images-container .color-variant-image-preview {
                    min-height: 100px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                #color-variant-images-container button.remove-color-variant-image {
                    margin-left: 5px;
                    color: #d63638;
                }
            ');
        }
    }

    /**
     * Convert hex to color name
     */
    private function hex_to_color_name($hex) {
        $hex = ltrim($hex, '#');

        $color_map = array(
            'FF0000' => 'Red', 'FFA500' => 'Orange', 'FFFF00' => 'Yellow',
            '00FF00' => 'Lime', '008000' => 'Green', '00FFFF' => 'Cyan',
            '0000FF' => 'Blue', '800080' => 'Purple', 'FF00FF' => 'Magenta',
            'FFC0CB' => 'Pink', 'FFFFFF' => 'White', '000000' => 'Black',
            'C0C0C0' => 'Silver', '808080' => 'Gray', 'A52A2A' => 'Brown',
        );

        $hex_upper = strtoupper($hex);
        return isset($color_map[$hex_upper]) ? $color_map[$hex_upper] : '#' . $hex;
    }
}

// Initialize
add_action('admin_init', ['Aakaari_Color_Variant_Images', 'init']);
