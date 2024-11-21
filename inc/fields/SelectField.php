<?php

/**
 * formBuilder EditorJS field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class SelectField {
    public static $field_label = 'Select';

    public static $field_type = 'select';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
    }

    public static function validate_select_field(
        $post_data,
        $data,
        $file,
        $post_id,
        $form_id,
        $all_settings
    ) {
        if ( !isset( $data['select'] ) || empty( $data['select'] ) ) {
            return $post_data;
        }
        foreach ( $data['select'] as $name => $value ) {
            $settings = Form::get_form_field_settings_by_name( $name, $form_id, $all_settings['form_builder_json'] );
            $is_value_empty = false;
            if ( empty( $value['ids'] ) || $value['ids'] == 'null' ) {
                $is_value_empty = true;
            }
            if ( $settings['required'] && $is_value_empty ) {
                $message = sprintf( __( 'The %s selection is required', 'front-editor' ), $value['label'] );
                if ( isset( $settings['required_error_text'] ) && !empty( $settings['required_error_text'] ) ) {
                    $message = $settings['required_error_text'];
                }
                /* Translators: filed is required */
                wp_send_json_error( [
                    'field'   => $name,
                    'message' => $message,
                ] );
            }
            update_post_meta( $post_id, $name, $value['ids'] );
        }
        return $post_data;
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
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = ['access'];
        // important array for showing this field in builder
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field'    => sprintf( '<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type ),
            'onRender' => '',
        ];
        /**
         * Adding attribute settings
         */
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'required_error_text' => [
                'label'       => __( 'Required error text', 'front-editor' ),
                'placeholder' => 'Term selection is required',
                'value'       => '',
            ],
            'search_placeholder'  => [
                'label'       => __( 'Search placeholder', 'front-editor' ),
                'placeholder' => 'Search',
                'value'       => '',
            ],
            'search_error_text'   => [
                'label'       => __( 'No result text', 'front-editor' ),
                'placeholder' => 'No Results',
                'value'       => '',
            ],
        ];
        return $data;
    }

    /**
     * This template for showing in front
     *
     * @return void
     */
    public static function add_field_to_front_form( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        require fe_template_path( 'front-editor/select.php' );
    }

    /**
     * This is for validation before saving the post
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form( $post_id ) {
        if ( !isset( $_POST['select'] ) ) {
            return;
        }
        foreach ( $_POST['select'] as $name => $value ) {
            if ( $value['required'] === '1' && $value['ids'] === 'null' ) {
                /* Translators: filed is required */
                wp_send_json_error( [
                    'field'   => self::$field_type,
                    'message' => sprintf( __( '%s is required', 'front-editor' ), $value['label'] ),
                ] );
            }
            update_post_meta( $post_id, $name, $value['ids'] );
        }
    }

}

SelectField::init();