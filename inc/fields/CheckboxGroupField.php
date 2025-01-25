<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Form;

defined('ABSPATH') || exit;


class CheckboxGroupField
{
    public static $field_label = 'Checkbox Group';
    public static $field_type =  'checkbox-group';

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        if (fe_fs()->can_use_premium_code__premium_only()) {
            // Validate field on front form submit
            add_action('bfe_ajax_before_post_update_or_creation', [__CLASS__, 'validate_field_on_front_form_submit'], 10, 3);
            add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
            add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'save_field_to_front_form'], 10);
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
        if (!fe_fs()->can_use_premium_code__premium_only()) {
            $data['formBuilder_options']['disableProFields'][] = self::$field_type;
        }

        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = ['access', 'inline', 'other', 'toggle'];

        // important array for showing this field in builder
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type),
            'onRender' => ''
        ];

        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] =
            [
                'required_error_text' => ['label' =>  __('Required error text', 'front-editor'), 'placeholder' => 'Please add checkbox field', 'value' => ''],
            ];

        return $data;
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
        if (!isset($_POST['checkbox'])) {
            return;
        }

        foreach ($_POST['checkbox'] as $name => $value) {
            $settings = Form::get_form_field_settings_by_name($name, $form_id, $all_settings['form_builder_json']);
            if ((!isset($value['ids']) || empty($value['ids'])) && $settings['required']) {
                $message = __('Please add checkbox field', 'front-editor');
                if (isset($settings['required_error_text']) && !empty(isset($settings['required_error_text']))) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error(['field' => $name, 'message' => $message]);
            }
        }
    }

    /**
     * This template for showing in front
     *
     * @return void
     */
    public static function add_field_to_front_form($post_id, $attributes, $field)
    {

        if ($field['type'] !== self::$field_type) {
            return;
        }

        require fe_template_path('front-editor/checkbox-group.php');
    }

    /**
     * This is for validation before saving the post
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form($post_id)
    {
        if (!isset($_POST['checkbox'])) {
            return;
        }

        foreach ($_POST['checkbox'] as $name => $value) {
            if ($value['required'] === '1' && $value['ids'] === 'null') {
                /* Translators: filed is required */
                wp_send_json_error(['field' => $name, 'message' => sprintf(__('%s is required', 'front-editor'), $value['label'])]);
            }
            update_post_meta($post_id, $name, $value['ids']);
        }
    }
}

CheckboxGroupField::init();
