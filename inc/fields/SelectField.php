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
        if ( empty( $data['select'] ) || !is_array( $data['select'] ) ) {
            return $post_data;
        }
        foreach ( $data['select'] as $name => $value ) {
            // only fields of this form are checked (and saved)
            $settings = Form::get_submitted_field( $form_id, (string) $name, self::$field_type );
            if ( !$settings ) {
                continue;
            }
            $is_value_empty = false;
            if ( empty( $value['ids'] ) || $value['ids'] == 'null' ) {
                $is_value_empty = true;
            }
            if ( !empty( $settings['required'] ) && $is_value_empty ) {
                $message = sprintf( __( 'The %s selection is required', 'front-editor' ), $settings['label'] ?? $name );
                if ( isset( $settings['required_error_text'] ) && !empty( $settings['required_error_text'] ) ) {
                    $message = $settings['required_error_text'];
                }
                /* Translators: filed is required */
                wp_send_json_error( [
                    'field'   => $name,
                    'message' => $message,
                ] );
            }
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
            'show_search'         => [
                'label' => __( 'Show Search Input', 'front-editor' ),
                'value' => false,
                'type'  => 'checkbox',
            ],
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
    public static function save_field_to_front_form( $post_id, $form_id = 0 ) {
        if ( !isset( $_POST['select'] ) ) {
            return;
        }
        // only fields of this form, so a visitor cannot write other post meta
        Form::save_submitted_meta(
            $post_id,
            Form::submitted_form_id( $form_id ),
            $_POST['select'],
            self::$field_type,
            ['\\BFE\\Form', 'sanitize_choice_value']
        );
    }

}

SelectField::init();