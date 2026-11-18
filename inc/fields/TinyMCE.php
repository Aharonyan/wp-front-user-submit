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
    public static function save_field_to_front_form($post_id, $form_id, $post_data = [], $all_settings = [])
    {
        if (empty($_POST['tinymce']) || !is_array($_POST['tinymce'])) {
            return;
        }

        foreach ($_POST['tinymce'] as $name => $content) {
            // only TinyMCE fields of this form, so a visitor cannot write other post meta
            $settings = Form::get_submitted_field($form_id, (string) $name, self::$field_type);
            if (!$settings || !is_scalar($content)) {
                continue;
            }

            self::save_editor_content($post_id, $name, wp_kses_post(wp_unslash((string) $content)), $settings);
        }
    }

    /**
     * Save editor HTML to post content, excerpt or meta as the field is set up
     *
     * @param int    $post_id
     * @param string $name     field name (meta key).
     * @param string $content  clean, unslashed HTML.
     * @param array  $settings field settings.
     * @return void
     */
    public static function save_editor_content($post_id, $name, $content, array $settings)
    {
        $save_to = !empty($settings['save_to']) ? $settings['save_to'] : (!empty($settings['post_content']) ? 'post_content' : 'post_meta');

        if ($save_to === 'post_content') {
            wp_update_post(wp_slash(['ID' => $post_id, 'post_content' => $content]));
        } elseif ($save_to === 'post_excerpt') {
            wp_update_post(wp_slash(['ID' => $post_id, 'post_excerpt' => sanitize_text_field($content)]));
        } else {
            update_post_meta($post_id, $name, wp_slash($content));
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
        if (!isset($_POST['tinymce']) || !is_array($_POST['tinymce'])) {
            return;
        }

        foreach ($_POST['tinymce'] as $name => $value) {
            // only fields of this form are checked (and saved)
            $settings = Form::get_submitted_field($form_id, (string) $name, self::$field_type);
            if (!$settings) {
                continue;
            }
            if (empty($value) && !empty($settings['required'])) {
                $message = __('Please add editor field', 'front-editor');
                if (isset($settings['required_error_text']) && !empty($settings['required_error_text'])) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error(['field' => $name, 'message' => $message]);
            }
        }
    }
}

TinyMCE::init();
