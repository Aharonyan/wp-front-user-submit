<?php

namespace BFE;

class DemoData
{

    public static $file_json_demo_data = FUS__PLUGIN_DIR . 'templates/default_form_data.json';
    public static  $post_type = 'fe_post_form';
    public static  $post_name = 'front-user-submit-form';


    public static function init()
    {

        add_action('BFE_activate', [__CLASS__, 'activation']);
        add_action('BFE_deactivate', [__CLASS__, 'deactivate']);
        add_action('init', [__CLASS__, 'json_generate']);

        add_action('init', function () {
            if (isset($_GET['ddd'])) {

                self::create_demo_page();
            }
        });

        /**
         * Add post state for auto created pages
         */
        add_filter('display_post_states', [__CLASS__, 'display_post_states'], 10, 2);
    }



    public static function activation()
    {

        self::create_demo_form();
        self::create_demo_page();
        self::create_user_admin_page();
    }

    public static function deactivate()
    {
        // delete_option('fus_activated');
    }


    public static function create_demo_page()
    {


        $args = [
            'post_type' => 'page',
            'pagename' => self::$post_name,
        ];

        $pages = get_posts($args);

        if (isset($pages[0])) {
            return;
        }

        $demo_post = self::get_demo_post();
        if (empty($demo_post->ID)) {

            return;
        }

        $content = sprintf('[fe_form id="%s"]', $demo_post->ID);
        $demo_post = [
            'post_title'    => 'Front User Submit Form',
            'post_type'    => 'page',
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_name'   => self::$post_name,
        ];

        $post_id = wp_insert_post($demo_post);
    }

    public static function create_user_admin_page()
    {
        $args = [
            'post_type' => 'page',
            'pagename' => 'fe-fs-user-admin',
        ];

        $pages = get_posts($args);

        if (isset($pages[0])) {
            return;
        }

        $demo_post = self::get_demo_user_admin_page();
        if (empty($demo_post->ID)) {

            return;
        }

        $content = sprintf('[fe_fs_user_admin]', $demo_post->ID);
        $demo_post = [
            'post_title'    => 'Front User Submit User Admin',
            'post_type'    => 'page',
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_name'   => 'fe-fs-user-admin',
        ];

        $post_id = wp_insert_post($demo_post);
    }

    public static function get_demo_user_admin_page()
    {
        $args = [
            'post_type' => 'page',
            'post_name' => 'fe-fs-user-admin',
        ];

        $posts = get_posts($args);

        if (isset($posts[0])) {
            return $posts[0];
        }

        return false;
    }

    public static function get_demo_post()
    {
        $args = [
            'post_type' => self::$post_type,
            'post_name' => self::$post_name,
        ];

        $posts = get_posts($args);

        if (isset($posts[0])) {
            return $posts[0];
        }

        return false;
    }

    public static function create_demo_form()
    {

        if (self::get_demo_post()) {
            return;
        }

        $demo_post = [
            'post_title'    => 'First demo form',
            'post_type'    => self::$post_type,
            'post_content'  => 'Test post content',
            'post_status'   => 'publish',
            'post_name'   => self::$post_name,
        ];

        // $post_id = 23887;
        $post_id = wp_insert_post($demo_post);
        $file_data = file_get_contents(self::$file_json_demo_data);
        $array = json_decode($file_data, true);

        $data = [
            'fe_post_updated_from_admin' => get_post_meta($post_id, 'fe_post_updated_from_admin', true),
            'formBuilderData' => get_post_meta($post_id, 'formBuilderData', true),
            'fe_form_settings' => get_post_meta($post_id, 'fe_form_settings', true),
        ];

        update_post_meta($post_id, 'fe_post_updated_from_admin', $array['fe_post_updated_from_admin']);
        update_post_meta($post_id, 'formBuilderData', json_encode($array['formBuilderData']));
        update_post_meta($post_id, 'fe_form_settings', $array['fe_form_settings']);

        //todo - make import data
        do_action('qm/debug', $array);
        do_action('qm/debug', $data);
    }


    /**
     * generate json file
     */
    public static function json_generate()
    {

        if (!isset($_GET['default_form_data'])) {
            return;
        }

        $args = [
            'post_type' => self::$post_type,
            'post_name' => self::$post_name,
        ];

        $posts = get_posts($args);
        $data = [
            'fe_post_updated_from_admin' => get_post_meta($posts[0]->ID, 'fe_post_updated_from_admin', true),
            'formBuilderData' => get_post_meta($posts[0]->ID, 'formBuilderData', true),
            'fe_form_settings' => get_post_meta($posts[0]->ID, 'fe_form_settings', true),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);

        $file_json = self::$file_json_demo_data;
        $fp = fopen($file_json, 'w');
        fwrite($fp, $json);
        fclose($fp);

        do_action('qm/debug', $json);
    }


    public static function display_post_states($post_states, $post)
    {
        if ('fe-fs-user-admin' === $post->post_name) {
            $post_states['fe-fs-user-admin'] = __('Front Editor', 'front-editor');
        }

        if ('front-user-submit-form' === $post->post_name) {
            $post_states['front-user-submit-form'] = __('Front Editor', 'front-editor');
        }

        return $post_states;
    }
}



DemoData::init();
