<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Form;

defined('ABSPATH') || exit;


class TinyMCE
{
    public static $field_label = 'TinyMCE Editor';
    public static $field_type =  'tinymce';

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
        add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'save_field_to_front_form'], 10, 4);

        // Validate field on front form submit
        add_action('bfe_ajax_before_post_update_or_creation', [__CLASS__, 'validate_field_on_front_form_submit'], 10, 3);
    }



    /**
     * This settings for wp admin form builder
     *
     * @param [type] $data
     * @return void
     */
    public static function add_field_settings($data)
    {
        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] =
            [
                'save_to' => [
                    'label' => 'Save To',
                    'multiple' => false,
                    'options' => [
                        'post_meta' => 'Post Meta',
                        'post_content' => 'Post Content',
                        'post_excerpt' => 'Post Excerpt'
                    ],
                ],
                'media_buttons' => [
                    'label' => __('Media Support', 'front-editor'),
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'teeny' => [
                    'label' => __('Teeny', 'front-editor'),
                    'value' => false,
                    'type' => 'checkbox',
                ],
                'drag_drop_upload' => [
                    'label' => __('Drag & Drop', 'front-editor'),
                    'value' => true,
                    'type' => 'checkbox',
                ],
                'required_error_text' => ['label' =>  __('Required error text', 'front-editor'), 'placeholder' => 'Please add editor field', 'value' => ''],
                'not_correct_post_title_text' => ['label' => __('Not correct content', 'front-editor'), 'placeholder' => 'Please add correct editor', 'value' => ''],
            ];
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] =
            [
                'label' => self::$field_label,
                'attrs' => [
                    'type' => self::$field_type
                ],
                'icon' => '<span class="dashicons dashicons-text"></span>',
            ];

        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['editors']['types'][] = self::$field_type;

        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] =
            [
                'inline',
                'toggle',
                'access',
                'value',
                'type',
                'subtype'
            ];

        // important array for showing this field in builder
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type),
            'onRender' => ''
        ];

        if (!fe_fs()->can_use_premium_code__premium_only()) {
            $data['formBuilder_options']['disable_attr'][] = '.fld-media_buttons';
            $data['formBuilder_options']['disable_attr'][] = '.fld-teeny';
            $data['formBuilder_options']['disable_attr'][] = '.fld-drag_drop_upload';
        }

        $data['formBuilder_options']['attr_descriptions']['media_buttons'] = __('Whether to display media insert/upload buttons', 'front-editor');
        $data['formBuilder_options']['attr_descriptions']['teeny'] = __('Whether to output the minimal editor configuration', 'front-editor');
        $data['formBuilder_options']['attr_descriptions']['drag_drop_upload'] = __('Enable Drag & Drop Upload Support', 'front-editor');

        return $data;
    }

    /**
     * Add post image selection
     *
     * @return void
     */
    public static function add_field_to_front_form($post_id, $attributes, $field)
    {

        if ($field['type'] !== self::$field_type) {
            return;
        }

        require fe_template_path('front-editor/tinymce.php');
    }

    /**
     * Image check
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form($post_id, $form_id,$post_data, $settings)
    {
        if (empty($_POST['tinymce'])) {
            return;
        }

        foreach ($_POST['tinymce'] as $name => $content) {
            $content = wp_kses_post($content);

            $settings = Form::get_form_field_settings_by_name($name, $form_id, $settings['form_builder_json']);

            $post_data = [
                'ID' => $post_id,
            ];

            if (isset($settings['save_to']) && !empty($settings['save_to'])) {
                if ($settings['save_to'] === 'post_content') {
                    $post_data['post_content'] = $content;
                    wp_update_post($post_data);
                } elseif ($settings['save_to'] === 'post_excerpt') {
                    $post_data['post_excerpt'] = sanitize_text_field($content);
                    wp_update_post($post_data);
                } else {
                    update_post_meta($post_id, $name, $content);
                }
            } elseif ($settings['post_content'] === true) {
                $post_data['post_content'] = $content;
                wp_update_post($post_data);
            } else {
                update_post_meta($post_id, $name, $content);
            }
        }
    }

    /**
     * Validate on front for submit
     *
     * @param int $post_id
     * @param int $form_id
     * @return void
     */
    public static function validate_field_on_front_form_submit($post_id, $form_id, $all_settings)
    {
        if (!isset($_POST['tinymce'])) {
            return;
        }

        foreach ($_POST['tinymce'] as $name => $value) {
            $settings = Form::get_form_field_settings_by_name($name, $form_id, $all_settings['form_builder_json']);
            if (empty($value) && $settings['required']) {
                $message = __('Please add editor field', 'front-editor');
                if (isset($settings['required_error_text']) && !empty(isset($settings['required_error_text']))) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error(['field' => $name, 'message' => $message]);
            }
        }
    }
}

TinyMCE::init();
