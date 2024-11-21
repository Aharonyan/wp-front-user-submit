<?php

/**
 * Editor blocks for gutenberg and front part
 *
 * Long Description.
 *
 * @link URL
 * @since x.x.x (if available)
 *
 * @package bfee
 */

namespace BFE;

class Block
{

    public static function init()
    {
        add_action('init', [__CLASS__, 'gutenberg_add_editor_block']);
        add_action('enqueue_block_editor_assets', [__CLASS__, 'gutenberg_editor_block_editor_scripts']);
    }

    /**
     * Gutenberg block scripts
     *
     * @return void
     */
    public static function gutenberg_editor_block_editor_scripts()
    {
        $asset = require FE_PLUGIN_DIR_PATH . 'build/gutenberg.asset.php';

        wp_register_script(
            'bfe-block-script',
            plugins_url('build/gutenberg.js', dirname(__FILE__)),
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_register_style(
            'bfe-block-style',
            plugins_url('build/adminStyle.css', dirname(__FILE__)),
            [],
            $asset['version']
        );

        $data = [
            'fe_edit_link' => Editor::get_post_edit_link(get_the_ID()),
            'translations' => [
                'title' => __('Select Post Form', 'front-editor'),
                'fe_edit_link_text' => __('Edit in front editor', 'front-editor'),
                'fe_edit_message' => __('This post created with the Front Editor plugin. Please edit it using Front Editor to not have issues with the plugin!', 'front-editor'),
                'form_builder_id' => __('Choose form', 'front-editor'),
                'create_new_form' => __('Create new form', 'front-editor')
            ],
            'create_new_post_form_link' => admin_url('admin.php?page=fe-post-forms&action=add-new')
        ];

        $data['post_form_list'] = self::get_list_of_post_forms();

        /**
         * If post edited with Front Editor
         */
        if (get_post_meta(get_the_ID(), 'bfe_editor_js_data', true)) {
            $data['fe_show_warning_message'] = 1;
        }


        wp_enqueue_script('bfe-block-script');

        wp_localize_script('bfe-block-script', 'editor_block_data', apply_filters('bfe_front_editor_backend_block_localize_data', $data));
    }


    /**
     * Get form lists
     *
     * @return void
     */
    public static function get_list_of_post_forms()
    {
        $posts = get_posts(['numberposts' => -1, 'post_type'   => 'fe_post_form']);
        $array = [];

        $array[] = [
            'value' => '0',
            'label' => __('Choose form', 'front-editor')
        ];

        foreach ($posts as $post) {
            $array[] = [
                'value' => $post->ID,
                'label' => sprintf('%s (%s)', $post->post_title, $post->ID)
            ];
        }

        return $array;
    }

    /**
     * Rendering block in front
     *
     * @param [type] $attributes
     * @param [type] $content
     * @return void
     */
    public static function bfe_content_block($attributes, $content)
    {
        // Start capture.
        ob_start();
        echo Editor::show_front_editor($attributes, $content);
        return ob_get_clean();
    }

    /**
     * Registering block
     *
     * @return void
     */
    public static function gutenberg_add_editor_block()
    {

        if (!function_exists('register_block_type')) {
            // Gutenberg is not active.
            return;
        }

        register_block_type('bfe/bfe-block', [
            'editor_script' => 'bfe-block-script',
            'style' => 'bfe-block-style',
            'editor_style' => 'bfe-block-style',
            'render_callback' => [__CLASS__, 'bfe_content_block']
        ]);
    }
}

Block::init();
