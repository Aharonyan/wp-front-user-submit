<?php

namespace BFE;

class UserAdmin
{

    /**
     * creating shortcode post list
     */
    public static function init($atts)
    {

        if (!is_user_logged_in()) {
            $option = get_option('bfe_general_user_admin_settings_group_options');
            $without_login = __('This page is restricted. Please Login to view this page.', 'front-editor');
            $without_login = sprintf('<div class="fus-info">%s</div>', $without_login);
            if (!empty($option['without_login_front_user_admin']['checked'])) {
                $without_login = do_shortcode('[fus_form_login]');
            } elseif (!empty($option['without_login_front_user_admin']['text'])) {
                $without_login = $option['without_login_front_user_admin']['text'];
                $without_login = sprintf('<div class="fus-info">%s</div>', $without_login);
            }
            return $without_login;
        }

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);

        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $count = 2;

        // SECURITY FIX: Validate post_status against allowed values
        $allowed_post_statuses = ['publish', 'draft', 'pending', 'private', 'trash'];
        $post_status_input = isset($_GET['post_status']) ? sanitize_text_field($_GET['post_status']) : 'publish';
        $post_status = in_array($post_status_input, $allowed_post_statuses, true) ? $post_status_input : 'publish';

        // GET ALLOWED POST TYPES FROM SETTINGS
        $allowed_post_types = \BFE\MenuSettings::get_allowed_post_types_for_user_admin();

        // Get the current post type filter from URL
        $current_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

        // Validate the post type is in allowed list
        if (!empty($current_post_type) && !in_array($current_post_type, $allowed_post_types)) {
            $current_post_type = $allowed_post_types[0]; // Use first allowed type as fallback
        }

        // If no specific post type requested, use all allowed types
        $query_post_types = !empty($current_post_type) ? $current_post_type : $allowed_post_types;

        // SECURITY FIX: Validate any_user parameter
        $any_user_input = isset($_GET['any_user']) ? sanitize_text_field($_GET['any_user']) : 'false';
        $any_user = ($any_user_input === 'true') ? 'true' : 'false';

        $user_can = current_user_can('edit_others_posts') ? true : false;
        $current_url = self::get_current_url();

        if (!empty($atts)) {
            $count = ($atts['count']) ? intval($atts['count']) : 6;
        }

        if (empty($atts)) {
            if (!isset($atts['count'])) {
                $count = 6;
            }
        }

        $args = [
            'posts_per_page' => $count,
            'paged' => $paged,
            'post_type' => $query_post_types,
            'post_status' => $post_status,
            'author' => get_current_user_id()
        ];

        // Show all post if user can see all posts
        if ($any_user === 'true' && $user_can) {
            unset($args['author']);
        }

        if (isset($_GET['delete_post'])) {
            // SECURITY FIX: Properly validate and sanitize delete_post parameter
            $delete_post_id = intval($_GET['delete_post']);
            if ($delete_post_id > 0) {
                // SECURITY FIX: Verify user can delete this specific post
                if (current_user_can('delete_post', $delete_post_id)) {
                    $deleted = wp_delete_post($delete_post_id);
                    if (!$deleted) {
                        fe_fs_add_sentry_error(sprintf('Can not delete post with id = %s', $delete_post_id), __FUNCTION__, ['func_args' => func_get_args()]);
                    }
                } else {
                    // Log unauthorized deletion attempt
                    fe_fs_add_sentry_error(sprintf('Unauthorized deletion attempt for post id = %s by user = %s', $delete_post_id, get_current_user_id()), __FUNCTION__);
                }
            }
        }

        $post_lists = new \WP_Query($args);
        $body_class = self::get_body_class();

        ob_start();

        require FE_Template_PATH . '/user-admin.php';

        return ob_get_clean();
    }

    /**
     * Add scripts
     */
    public static function enqueue_scripts()
    {
        $asset = require FE_PLUGIN_DIR_PATH . 'build/front.asset.php';


        wp_enqueue_style(
            'bfe-block-style',
            FE_PLUGIN_URL . '/build/frontStyle.css',
            [],
            $asset['version']
        );
    }

    public static function get_body_class()
    {
        $options = get_option('bfe_general_user_admin_settings_group_options');
        $design = !empty($options['fe_admin_design']) ? $options['fe_admin_design'] : 'no_style';
        return 'fe-admin-design-' . esc_attr($design);
    }

    public static function get_current_url()
    {
        global $wp;
        $args = !empty($_GET) ? $_GET : [];
        if (!empty($args)) {
            $args['any_user'] = 'true';
            $url = home_url(add_query_arg($args, $wp->request));
        } else {
            $url = home_url($wp->request) . '?any_user=true';
        }
        return $url;
    }
}
