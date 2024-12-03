<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */

namespace BFE\Field;

use BFE\Editor;

use BFE\Form;

defined('ABSPATH') || exit;


class MdEditor
{
    public static $field_label = 'MD Editor';
    public static $field_type =  'md_editor';

    public static function init()
    {
        /**
         * Adding setting to admin
         */
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        //add_filter('bfe_front_editor_localize_data', [__CLASS__, 'field_setting_for_frontend'], 10, 3);

        /**
         * Add or update editor content
         */
        add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'add_or_update_editor_content_front'], 10, 4);

        add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);

        // Validate field on front form submit
        add_action('bfe_ajax_before_post_update_or_creation', [__CLASS__, 'validate_field_on_front_form_submit'], 10, 3);
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
        if (!isset($_POST['md_editor'])) {
            return;
        }

        foreach ($_POST['md_editor'] as $name => $value) {
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

    /**
     * Show hidden mdeditor
     *
     * @return void
     */
    public static function add_field_to_front_form($post_id, $attributes, $field)
    {

        if ($field['type'] !== self::$field_type) {
            return;
        }

        require fe_template_path('front-editor/mdeditor.php');
    }


    /**
     * Add or update editor content
     */
    public static function add_or_update_editor_content_front($post_id, $form_id,$post_data, $settings)
    {
        if (empty($_POST['md_editor'])) {
            return;
        }
        $Parsedown = new \Parsedown();

        foreach ($_POST['md_editor'] as $name => $content) {
            $content = wp_kses_post($Parsedown->text($content));

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
     * Validate field on wp admin form save
     *
     * @param [type] $data
     * @return void
     */
    public static function validate_field_before_wp_admin_form_save($data)
    {
        $settings = Form::get_form_field_settings(self::$field_type, 0, $_POST['formBuilderData']);

        if (!$settings) {
            wp_send_json_success([
                'message' => [
                    'title' => __('Oops', 'front-editor'),
                    'message' => __('Post Content Field is missing', 'front-editor'),
                    'status' => 'warning'
                ]
            ]);
        }
    }

    /**
     * Adding setting to admin
     */
    public static function add_field_settings($data)
    {
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] =
            [
                'label' => self::$field_label,
                'attrs' => [
                    'type' => self::$field_type
                ],
                'icon' => '<svg version="1.0" xmlns="http://www.w3.org/2000/svg"
                width="1664.000000pt" height="1024.000000pt" viewBox="0 0 1664.000000 1024.000000"
                preserveAspectRatio="xMidYMid meet">
               
               <g transform="translate(0.000000,1024.000000) scale(0.100000,-0.100000)"
               fill="#000000" stroke="none">
               <path d="M1045 10229 c-546 -78 -965 -503 -1035 -1049 -8 -60 -10 -1250 -8
               -4115 3 -3854 4 -4033 22 -4105 118 -489 466 -833 946 -937 82 -17 360 -18
               7350 -18 6990 0 7268 1 7350 18 484 105 842 463 947 947 17 81 18 255 18 4150
               0 3895 -1 4069 -18 4150 -104 480 -448 828 -937 946 -72 18 -351 19 -7325 20
               -3987 1 -7277 -2 -7310 -7z m3751 -3384 c438 -547 800 -995 804 -995 4 0 366
               448 804 995 l796 995 800 0 800 0 0 -2720 0 -2720 -800 0 -800 0 -2 1557 -3
               1557 -793 -992 c-437 -546 -798 -992 -802 -992 -4 0 -365 446 -802 992 l-793
               992 -3 -1557 -2 -1557 -800 0 -800 0 0 2720 0 2720 800 0 800 0 796 -995z
               m8324 -365 l0 -1360 800 0 800 0 -1196 -1395 c-657 -767 -1199 -1395 -1204
               -1395 -5 0 -547 628 -1204 1395 l-1196 1395 800 0 800 0 0 1360 0 1360 800 0
               800 0 0 -1360z"/>
               </g>
               </svg>',
            ];

        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<div class="%s editor" name="%s"></div>', self::$field_type, self::$field_type),
            'onRender' => '',
            //'max_in_form' => 1,
            //'required' => 0
        ];

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
                'required_error_text' => ['label' =>  __('Required error text', 'front-editor'), 'placeholder' => 'Please add editor field', 'value' => ''],
                'not_correct_post_title_text' => ['label' => __('Not correct content', 'front-editor'), 'placeholder' => 'Please add correct editor', 'value' => ''],
            ];

        /**
         * Adding as default
         */
        // $data['formBuilder_options']['defaultFields'][] = [
        //     'label' => self::$field_label,
        //     'type' => self::$field_type
        // ];

        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['editors']['types'][] = self::$field_type;

        /**
         * Disable attr if there is no pro version
         */
        $is_premium = fe_fs()->can_use_premium_code__premium_only();
        if (!$is_premium) {
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_gallery_plugin';
            $data['formBuilder_options']['disable_attr'][] = '.fld-editor_table_plugin';
            $data['formBuilder_options']['disable_attr'][] = 'fld-editor_warning_plugin';
        }

        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] =
            [
                'inline',
                'toggle',
                'access',
                'value',
            ];

        return $data;
    }
}

MdEditor::init();
