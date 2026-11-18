<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Form;

defined('ABSPATH') || exit;


class TextField
{
    public static $field_label = 'Text Field';
    public static $field_type =  'text';

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        // Validate field on front form submit
        add_action('bfe_ajax_before_post_update_or_creation', [__CLASS__, 'validate_field_on_front_form_submit'], 10, 3);
        add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
        add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'save_field_to_front_form'], 10, 2);
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
        if (!isset($_POST['text_fields']) || !is_array($_POST['text_fields'])) {
            return;
        }

        foreach ($_POST['text_fields'] as $name => $value) {
            // only fields of this form are checked (and saved)
            $settings = Form::get_submitted_field($form_id, (string) $name, self::$field_type);
            if (!$settings) {
                continue;
            }
            if (empty($value) && !empty($settings['required'])) {
                $message = __('Please add text field', 'front-editor');
                if (isset($settings['required_error_text']) && !empty($settings['required_error_text'])) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error(['field' => $name, 'message' => $message]);
            }
        }
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
                'subtype' => [
                    'label' => __('Type', 'front-editor'),
                    'options' => [
                        'text' => 'Text Field',
                        'password' => 'Password',
                        'email' => 'Email',
                        'color' => 'Color',
                        'tel' => 'Tel',
                        'hidden' => 'Hidden',
                    ],
                    'type' => 'select',
                ],
                'required_error_text' => ['label' =>  __('Required error text', 'front-editor'), 'placeholder' => 'Please add text field', 'value' => ''],
                'not_correct_post_title_text' => ['label' => __('Not correct content', 'front-editor'), 'placeholder' => 'Please add correct text field', 'value' => ''],
            ];

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

        require fe_template_path('front-editor/text.php');
    }

    /**
     * Image check
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form($post_id, $form_id = 0)
    {
        if (!isset($_POST['text_fields'])) {
            return;
        }

        // only fields of this form, so a visitor cannot write other post meta
        Form::save_submitted_meta($post_id, Form::submitted_form_id($form_id), $_POST['text_fields'], self::$field_type, function ($value, $field) {
            if (!is_scalar($value)) {
                return null;
            }
            return (isset($field['subtype']) && $field['subtype'] === 'email') ? sanitize_email((string) $value) : sanitize_text_field((string) $value);
        });
    }
}

TextField::init();
