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
        
        $post_id = empty($atts['post_id']) ? get_the_ID() : $atts['post_id'];
        $meta_data = get_post_meta($post_id, $atts['meta_name'], true);
        
        return esc_html($meta_data);
    }
}

add_action('init', array('FUSCustomFieldContent', 'init'));