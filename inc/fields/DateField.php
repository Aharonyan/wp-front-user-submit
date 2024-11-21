<?php

/**
 * formBuilder Date field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class DateField {
    public static $field_label = 'Date Field';

    public static $field_type = 'date';

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
        if ( !isset( $_POST['date'] ) ) {
            return;
        }
        foreach ( $_POST['date'] as $name => $value ) {
            $settings = Form::get_form_field_settings_by_name( $name, $form_id, $all_settings['form_builder_json'] );
            if ( empty( $value ) && $settings['required'] ) {
                $message = __( 'Please add date field', 'front-editor' );
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
                $message = __( 'Please add correct date', 'front-editor' );
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
        $field_label = __( 'Date Field', 'front-editor' );
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] = [
            'label' => $field_label,
            'attrs' => [
                'type' => self::$field_type,
            ],
        ];
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field'    => sprintf( '<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type ),
            'onRender' => '',
        ];
        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = ['placeholder', 'access'];
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'subtype'                     => [
                'options' => [
                    'date'           => 'date',
                    'datetime-local' => 'datetime-local',
                    'time'           => 'time',
                ],
            ],
            'min'                         => [
                'label' => sprintf( '%s', __( 'Min', 'front-editor' ) ),
                'value' => '',
            ],
            'max'                         => [
                'label' => sprintf( '%s', __( 'Max', 'front-editor' ) ),
                'value' => '',
            ],
            'step'                        => [
                'label' => sprintf( '%s', __( 'Step', 'front-editor' ) ),
                'value' => '',
            ],
            'required_error_text'         => [
                'label'       => __( 'Required error text', 'front-editor' ),
                'placeholder' => 'Please add date field',
                'value'       => '',
            ],
            'not_correct_post_title_text' => [
                'label'       => __( 'Not correct content', 'front-editor' ),
                'placeholder' => 'Please add correct date',
                'value'       => '',
            ],
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
    public static function add_field_to_front_form( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        require fe_template_path( 'front-editor/date.php' );
    }

    /**
     * Save front form hidden field in meta
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form( $post_id ) {
        if ( !isset( $_POST['date'] ) ) {
            return;
        }
        foreach ( $_POST['date'] as $name => $value ) {
            update_post_meta( $post_id, $name, sanitize_text_field( $value ) );
        }
    }

}

DateField::init();