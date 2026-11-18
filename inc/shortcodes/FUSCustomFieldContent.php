<?php

defined('ABSPATH') || exit;
class FUSCustomFieldContent {
    public static function init() {
        add_shortcode('fus_custom_field_content', array(__CLASS__, 'render_content'));
    }

    public static function render_content($atts) {
        $defaults = array(
            'meta_name' => '',
            'post_id' => ''
        );

        $atts = shortcode_atts($defaults, $atts, 'fus_custom_field_content');
        
        $post_id = empty($atts['post_id']) ? get_the_ID() : (int) $atts['post_id'];
        $meta_name = sanitize_text_field($atts['meta_name']);

        if (!\BFE\Shortcodes::can_show_post_meta($post_id, $meta_name)) {
            return '';
        }

        $meta_data = get_post_meta($post_id, $meta_name, true);

        return is_scalar($meta_data) ? esc_html($meta_data) : '';
    }
}

// this file is loaded on init, so the shortcode is registered right away
FUSCustomFieldContent::init();
