<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Form;

defined('ABSPATH') || exit;


class HiddenField
{
    public static $field_label = 'Hidden Field';
    public static $field_type =  'hidden_field';

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        if (fe_fs()->can_use_premium_code__premium_only()) {
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

        $field_label = __('Hidden Field', 'front-editor');

        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] =
            [
                'label' => $field_label,
                'attrs' => [
                    'type' => self::$field_type
                ],
                'icon' => '<span class="dashicons dashicons-hidden"></span>',
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
                'required',
                'description',
                'placeholder',
                'className',
                'access',
            ];

        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['custom_fields']['types'][] = self::$field_type;

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

        require fe_template_path('front-editor/hidden.php');
    }

    /**
     * Save front form hidden field in meta
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form($post_id)
    {
        if (!isset($_POST['hidden_fields'])) {
            return;
        }

        foreach ($_POST['hidden_fields'] as $name => $value) {
            update_post_meta($post_id, $name, $value);
        }
    }
}

HiddenField::init();
