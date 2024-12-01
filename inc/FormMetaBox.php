<?php

/**
 * Custom metabox for updating meta values.
 *
 * @package BFE
 */

namespace BFE;

defined('ABSPATH') || exit;

/**
 * Class FormMetaBox
 */
class FormMetaBox
{
    private static $form_settings = [];

    /**
     * Initialize hooks to add meta box and save meta data.
     */
    public static function init()
    {
        add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'add_user_ip_address'], 999, 1);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_box']);
        add_action('save_post', [__CLASS__, 'save_meta_box_data'], 999);
    }

    /**
     * Sets form settings.
     *
     * @param array $settings Form settings array.
     */
    public static function set_form_settings(array $settings)
    {
        self::$form_settings = $settings;
    }

    /**
     * Gets form settings.
     *
     * @param int $form_id Form ID.
     * @return array Form settings.
     */
    public static function get_form_settings($form_id)
    {
        if (empty(self::$form_settings)) {
            $all_form_settings = Form::get_form_settings($form_id);
            $form_settings = $all_form_settings['form_builder_json'] ?? [];
            self::set_form_settings(json_decode($form_settings, true));
        }
        return self::$form_settings;
    }

    /**
     * Registers the custom meta box for the post editing screen.
     */
    public static function add_meta_box()
    {
        global $post;

        // Get the current post type
        $post_type = get_post_type($post->ID);

        // Check for the 'BFE_the_post_edited_by' meta key to decide if the meta box should be added
        $form_id = get_post_meta($post->ID, 'BFE_the_post_edited_by', true);
        $icon = sprintf('<img src="%s" />', FE_PLUGIN_URL . '/assets/img/logo_24.png');
        $style = 'display: flex;align-items: center;gap: 10px;';
        $title = sprintf('<div style="%s">%s %s</div>', $style, $icon, __('FUS Form Data', 'front-editor'));
        // Only add the meta box if the form_id exists and is not empty
        if (!empty($form_id)) {
            add_meta_box(
                'fus_form_meta_box',                  // Meta box ID
                $title, // Meta box title
                [__CLASS__, 'display_meta_box'],     // Callback function to display the meta box
                $post_type,                         // Use the dynamic post type
                'normal',                           // Position of the meta box (normal, side, advanced)
                'high'                           // Priority of the meta box (default, high, low)
            );
        }
    }


    /**
     * Displays the meta box fields.
     *
     * @param \WP_Post $post The current post object.
     */
    public static function display_meta_box($post)
    {
        $post_id = $post->ID;

        // Security nonce for verification
        wp_nonce_field('custom_meta_box_nonce_action', 'custom_meta_box_nonce');

        $form_id = get_post_meta($post_id, 'BFE_the_post_edited_by', true);
        $form_fields = self::get_form_settings($form_id);
        $user_ip_address = get_post_meta($post_id, 'fus_user_ip_address',true);
        $post_data = ['Form ID' => $form_id];

        if ($user_ip_address) {
            $post_data['Author IP'] = $user_ip_address;
        }

        $style = "border: 2px solid #ffcc00;background:#ffcc000f;border-radius:5px;padding:20px;text-align-center";
        printf(
            __('<div style="%s">We recommend using the front form for post editing. <a href="%s">Edit -></a></div>', 'front-editor'),
            $style,
            Editor::get_post_edit_link($post_id)
        );

        if (!empty($form_fields)) {
            foreach ($form_fields as $field) {
                self::render_field($field, $post_id);
            }
        }

        foreach ($post_data as $name => $data) {
            printf('<p><strong>%s:</strong> %s</p>', $name, $data);
        }
    }

    /**
     * Renders a single field in the meta box.
     *
     * @param array $field The field data.
     * @param int   $post_id The post ID.
     */
    private static function render_field(array $field, $post_id)
    {
        if (empty($field['name'])) {
            return;
        }

        $value = get_post_meta($post_id, $field['name'], true);

        if (empty($value)) {
            return;
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
        }

        $label = $field['label'] ?? __('Untitled Field', 'front-editor');
        $style = 'display: flex;align-items: center;gap: 10px;';

        printf(
            '<div style="%s"><div style="width: 200px;"><p><strong>%s:</strong></p><small>%s</small></div> <textarea class="large-text" name="%s" id="%s">%s</textarea></div>',
            $style,
            esc_html($label),
            esc_attr($field['name']),
            esc_attr($field['name']),
            esc_attr($field['name']),
            esc_textarea($value)
        );
    }

    /**
     * Saves the meta box data.
     *
     * @param int $post_id The post ID.
     */
    public static function save_meta_box_data($post_id)
    {
        // Verify nonce to check if request is valid
        if (!isset($_POST['custom_meta_box_nonce']) || !wp_verify_nonce($_POST['custom_meta_box_nonce'], 'custom_meta_box_nonce_action')) {
            return;
        }

        // Prevent saving during autosave or if user lacks permissions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Process each form field in settings
        $form_fields = self::$form_settings;

        if (!empty($form_fields)) {
            foreach ($form_fields as $field) {
                $field_name = $field['name'] ?? null;

                // Skip if field name is not set or empty
                if (empty($field_name) || !isset($_POST[$field_name])) {
                    continue;
                }

                $new_value = $_POST[$field_name];
                $old_value = get_post_meta($post_id, $field_name, true);

                // Convert array to JSON if the old value was JSON
                if (is_array($old_value) && !is_array($new_value)) {
                    $new_value = json_decode($new_value, true);
                }

                // Update meta only if the new value is different from the old value
                if ($new_value !== $old_value) {
                    update_post_meta($post_id, $field_name, $new_value);
                }
            }
        }
    }


    /**
     * Adds the user's IP address as post meta.
     *
     * @param int $post_id The post ID.
     */
    public static function add_user_ip_address($post_id)
    {
        $ip_address = self::get_user_ip_address();

        update_post_meta($post_id, 'fus_user_ip_address', $ip_address);
    }

    static function get_user_ip_address() {
        // Check for shared internet/proxy user
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            // IP from shared internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // IP passed from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            // Handle multiple IPs in the forwarded list
            $ip = explode( ',', $ip )[0];
        } else {
            // Remote IP address
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    
        // Validate IP address format
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '-';
    }
    
}

FormMetaBox::init();
