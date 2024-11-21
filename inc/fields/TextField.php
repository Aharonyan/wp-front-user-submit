<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class TextField {
    public static $field_label = 'Text Field';

    public static $field_type = 'text';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
    }

    /**
     * Validate on front for submit
     *
     * @param int $post_id
     * @param int $form_id
     * @return void
     */
    public static function validate_field_on_front_form_submit( $post_id, $form_id, $all_settings ) {
        if ( !isset( $_POST['text_fields'] ) ) {
            return;
        }
        foreach ( $_POST['text_fields'] as $name => $value ) {
            $settings = Form::get_form_field_settings_by_name( $name, $form_id, $all_settings['form_builder_json'] );
            if ( empty( $value ) && $settings['required'] ) {
                $message = __( 'Please add text field', 'front-editor' );
                if ( isset( $settings['required_error_text'] ) && !empty( isset( $settings['required_error_text'] ) ) ) {
                    $message = $settings['required_error_text'];
                }
                wp_send_json_error( [
                    'field'   => $name,
                    'message' => $message,
                ] );
            }
            $post_title = sanitize_text_field( $value );
            if ( empty( $post_title ) ) {
                $message = __( 'Please add correct text field', 'front-editor' );
                if ( isset( $settings['not_correct_post_title_text'] ) && !empty( isset( $settings['not_correct_post_title_text'] ) ) ) {
                    $message = $settings['not_correct_post_title_text'];
                }
                wp_send_json_error( [
                    'field'   => $name,
                    'message' => $message,
                ] );
            }
        }
    }

    /**
     * This settings for wp admin form builder
     *
     * @param [type] $data
     * @return void
     */
    public static function add_field_settings( $data ) {
        $data['formBuilder_options']['disableProFields'][] = self::$field_type;
        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'subtype'                     => [
                'label'   => __( 'Type', 'front-editor' ),
                'options' => [
                    'text'     => 'Text Field',
                    'password' => 'Password',
                    'email'    => 'Email',
                    'color'    => 'Color',
                    'tel'      => 'Tel',
                    'hidden'   => 'Hidden',
                ],
                'type'    => 'select',
            ],
            'required_error_text'         => [
                'label'       => __( 'Required error text', 'front-editor' ),
                'placeholder' => 'Please add text field',
                'value'       => '',
            ],
            'not_correct_post_title_text' => [
                'label'       => __( 'Not correct content', 'front-editor' ),
                'placeholder' => 'Please add correct text field',
                'value'       => '',
            ],
        ];
        return $data;
    }

    /**
     * Add post image selection
     *
     * @return void
     */
    public static function add_field_to_front_form( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        require fe_template_path( 'front-editor/text.php' );
    }

    /**
     * Image check
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form( $post_id ) {
        if ( !isset( $_POST['text_fields'] ) ) {
            return;
        }
        foreach ( $_POST['text_fields'] as $name => $value ) {
            update_post_meta( $post_id, $name, $value );
        }
    }

}

TextField::init();