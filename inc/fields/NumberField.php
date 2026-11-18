<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Form;

defined('ABSPATH') || exit;


class NumberField
{
    public static $field_label = 'Number Field';
    public static $field_type =  'number';

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
        if (!isset($_POST['number']) || !is_array($_POST['number'])) {
            return;
        }

        foreach ($_POST['number'] as $name => $value) {
            // only fields of this form are checked (and saved)
            $settings = Form::get_submitted_field($form_id, (string) $name, self::$field_type);
            if (!$settings) {
                continue;
            }
            if (empty($value) && !empty($settings['required'])) {
                $message = __('Please add number field', 'front-editor');
                if (isset($settings['required_error_text']) && !empty($settings['required_error_text'])) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error(['field' => $name, 'message' => $message]);
            }

            if((empty($value) && !empty($settings['required'])) || (!is_numeric($value) && !empty($settings['required']))){
                $message = __('Please add correct number field', 'front-editor');
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
        $field_label = __('Number Field', 'front-editor');

        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] =
            [
                'label' => $field_label,
                'attrs' => [
                    'type' => self::$field_type
                ],
                //'icon' => '<span class="dashicons dashicons-hidden"></span>',
            ];

        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type),
            'onRender' => '',
        ];

        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] =
            [
                'access',
            ];

        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['custom_fields']['types'][] = self::$field_type;

        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] =
            [
                'required_error_text' => ['label' =>  __('Required error text', 'front-editor'), 'placeholder' => 'Please add number field', 'value' => ''],
                'not_correct_post_title_text' => ['label' => __('Not correct content', 'front-editor'), 'placeholder' => 'Please add correct number field', 'value' => ''],
            ];

        return $data;
    }

    /**
     * Show hidden field in form
     *
     * @return void
     */
    public static function add_field_to_front_form($post_id, $attributes, $field)
    {

        if ($field['type'] !== self::$field_type) {
            return;
        }

        require fe_template_path('front-editor/number.php');
    }

    /**
     * Save front form hidden field in meta
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form($post_id, $form_id = 0)
    {
        if (!isset($_POST['number'])) {
            return;
        }

        // only fields of this form, so a visitor cannot write other post meta
        Form::save_submitted_meta($post_id, Form::submitted_form_id($form_id), $_POST['number'], self::$field_type, function ($value) {
            if (!is_scalar($value)) {
                return null;
            }
            $value = trim((string) $value);
            return ($value === '' || is_numeric($value)) ? $value : null;
        });
    }
}

NumberField::init();
