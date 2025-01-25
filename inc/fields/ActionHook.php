<?php

namespace BFE\fields;

use BFE\Form;

defined('ABSPATH') || exit;

class ActionHook
{
    public static $field_label = 'Action Hook';
    public static $field_type = 'action_hook_field';

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        if (fe_fs()->can_use_premium_code__premium_only()) {
            add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
        }
    }

    public static function add_field_settings($data)
    {
        if (!fe_fs()->can_use_premium_code__premium_only()) {
            $data['formBuilder_options']['disableProFields'][] = self::$field_type;
        }
        
        $data['formBuilder_options']['fields'][] = [
            'label' => self::$field_label,
            'attrs' => [
                'type' => self::$field_type
            ],
            'icon' => '<span class="dashicons dashicons-admin-plugins"></span>',
        ];

        $data['formBuilder_options']['controls_group']['custom_fields']['types'][] = self::$field_type;

        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = [
            'inline',
            'toggle',
            'access',
            'value',
            'type',
            'subtype',
            'placeholder',
            'required',
            'className'
        ];

        $data['formBuilder_options']['disabledFieldButtons'][self::$field_type] = ['copy'];

        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'action_hook_name' => ['label' => sprintf('%s', __('Action Hook Name', 'front-editor')), 'value' => '', 'placeholder' => 'my_custom_action'],
            'action_hook_description' => ['label' => sprintf('%s', __('Description', 'front-editor')), 'value' => '', 'placeholder' => 'Add description for this action hook']
        ];

        $data['formBuilder_options']['attr_descriptions']['action_hook_name'] = __('Enter the name of your custom action hook. This will be called when form is rendered.', 'front-editor');
        $data['formBuilder_options']['attr_descriptions']['action_hook_description'] = __('Add a description to help other developers understand the purpose of this hook.', 'front-editor');

        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<div class="%s" name="%s"></div>', self::$field_type, self::$field_type),
            'onRender' => '',
        ];

        return $data;
    }

    public static function add_field_to_front_form($post_id, $attributes, $field)
    {
        if ($field['type'] !== self::$field_type || empty($field['action_hook_name'])) {
            return;
        }

        $form_settings = Form::get_form_settings($attributes['id']);
        do_action($field['action_hook_name'], $post_id, $attributes, $field, $form_settings);
    }
}

ActionHook::init();