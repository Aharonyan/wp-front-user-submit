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
