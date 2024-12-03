<?php

namespace BFE;

class Shortcodes
{

    public static function init()
    {
        add_shortcode('bfe-front-editor', [__CLASS__, 'editor_js']);

        add_shortcode('user_posts_list', [__CLASS__, 'user_admin']);

        add_shortcode('fe_fs_user_admin', [__CLASS__, 'user_admin']);

        add_shortcode('fe_form', [__CLASS__, 'fe_form']);

        add_shortcode('fus_display_field', [__CLASS__, 'fus_display_field']);
    }
    /**
     * Shortcode to display field value
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function fus_display_field($atts)
    {
        $atts = shortcode_atts(
            array(
                'name' => '',
                'post_id' => get_the_ID(),
            ),
            $atts,
            'fus_display_field'
        );

        if (empty($atts['name'])) {
            return '';
        }

        $post_id = intval($atts['post_id']);
        $field_name = sanitize_text_field($atts['name']);

        $field_value = get_post_meta($post_id, $field_name, true);

        // Apply filters to allow further customization
        $field_value = apply_filters('fus_display_field_value', $field_value, $field_name, $post_id);

        return esc_html($field_value);
    }
    
    /**
     * creating shortcode
     *
     * @param [type] $atts
     * @return void
     */
    public static function editor_js($atts)
    {

        /**
         * If exist true and false string it is changing it to the boolean
         */
        if (!empty($atts)) {
            foreach ($atts as $att_name => $attribute) {
                if (
                    filter_var($attribute, FILTER_VALIDATE_BOOLEAN) !== null
                    && $attribute !== "display"
                    && $attribute !== "require"
                    && $attribute !== "disable"
                    && $attribute !== "always_display"
                ) {
                    $atts[$att_name] = filter_var($attribute, FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        return Editor::show_front_editor($atts);
    }

    /**
     * fe form
     *
     * @param [type] $atts
     * @return void
     */
    public static function fe_form($atts)
    {

        /**
         * If exist true and false string it is changing it to the boolean
         */
        if (empty($atts['id'])) {
            return '';
        }

        return Editor::show_front_editor($atts, '', 'form_builder');
    }



    /**
     * creating shortcode 
     *
     * @param [type] $atts
     * @return void
     */
    public static function user_admin($atts)
    {
        wp_enqueue_style('bfe-block-style');

        return UserAdmin::init($atts);
    }
}

Shortcodes::init();
