<?php

/**
 * formBuilder Google Map field
 *
 * @package BFE;
 */
namespace BFE\Field;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class GoogleMapField {
    public static $field_label = 'Google Map';

    public static $field_type = 'google_map';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
        add_filter( 'bfe_front_editor_localize_data', [__CLASS__, 'front_editor_localize_data'] );
    }

    /**
     * Validate on front for submit
     *
     * @param int $post_id
     * @param int $form_id
     * @return void
     */
    public static function validate_field_on_front_form_submit( $post_id, $form_id, $all_settings ) {
        if ( !isset( $_POST[self::$field_type] ) || !is_array( $_POST[self::$field_type] ) ) {
            return;
        }
        foreach ( $_POST[self::$field_type] as $name => $value ) {
            // only fields of this form are checked (and saved)
            $settings = Form::get_submitted_field( $form_id, (string) $name, self::$field_type );
            if ( !$settings ) {
                continue;
            }
            if ( empty( $value ) && !empty( $settings['required'] ) ) {
                $message = __( 'Please add google map field', 'front-editor' );
                if ( isset( $settings['required_error_text'] ) && !empty( $settings['required_error_text'] ) ) {
                    $message = $settings['required_error_text'];
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
        $google_map_api = get_option( 'bfe_front_editor_google_map_api' );
        if ( empty( $google_map_api ) ) {
            $data['formBuilder_options']['disableExtraSettingsFields'][] = self::$field_type;
        }
        $field_label = __( 'Google Map', 'front-editor' );
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] = [
            'label' => $field_label,
            'attrs' => [
                'type' => self::$field_type,
            ],
            'icon'  => '<span class="dashicons dashicons-location"></span>',
        ];
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field'    => sprintf( '<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type ),
            'onRender' => '',
        ];
        $data['formBuilder_options']['typeUserAttrs'][self::$field_type] = [
            'country_code'                => [
                'label'       => sprintf( '%s', __( 'Country Code', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => 'en',
            ],
            'required_error_text'         => [
                'label'       => __( 'Required error text', 'front-editor' ),
                'placeholder' => 'Please add google map field',
                'value'       => '',
            ],
            'not_correct_post_title_text' => [
                'label'       => __( 'Not correct content', 'front-editor' ),
                'placeholder' => 'Please add google map field',
                'value'       => '',
            ],
        ];
        /**
         * Disabling default settings
         */
        $data['formBuilder_options']['typeUserDisabledAttrs'][self::$field_type] = ['value', 'access'];
        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['custom_fields']['types'][] = self::$field_type;
        /**
         * Adding messages to localize
         */
        $data['formBuilder_options']['messages']['for_google_map_message'] = sprintf( __( 'Before using this field, please add your Google API Key in %s.', 'front-editor' ), '<a target="_blank" href="' . esc_url( home_url( '/wp-admin/admin.php?page=fe-global-settings' ) ) . '">' . __( 'Settings', 'front-editor' ) . '</a>' );
        $data['formBuilder_options']['messages']['for_google_map_button_text'] = __( 'Add API Key', 'front-editor' );
        $data['formBuilder_options']['messages']['for_google_map_button_link'] = esc_url( home_url( '/wp-admin/admin.php?page=fe-global-settings' ) );
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
        require fe_template_path( 'front-editor/google-map.php' );
    }

    /**
     * Save front form hidden field in meta
     *
     * @param [type] $post_data
     * @param [type] $data
     * @param [type] $file
     * @return void
     */
    public static function save_field_to_front_form( $post_id, $form_id = 0 ) {
        if ( !isset( $_POST['google_map'] ) ) {
            return;
        }
        // only fields of this form, so a visitor cannot write other post meta
        Form::save_submitted_meta(
            $post_id,
            Form::submitted_form_id( $form_id ),
            $_POST['google_map'],
            self::$field_type,
            function ( $value ) {
                $location = ( is_string( $value ) ? json_decode( $value, true ) : null );
                if ( !is_array( $location ) ) {
                    return ( is_string( $value ) && trim( $value ) === '' ? '' : null );
                }
                $location = map_deep( $location, function ( $item ) {
                    return ( is_string( $item ) ? sanitize_text_field( $item ) : $item );
                } );
                return wp_json_encode( $location );
            }
        );
    }

    /**
     * Localizes additional data for the front editor.
     * 
     * @param array $localize_data Data to be localized.
     * @return array Modified localized data with Google Map API key.
     */
    public static function front_editor_localize_data( $localize_data ) {
        $google_map_api = get_option( 'bfe_front_editor_google_map_api' );
        $localize_data['google_map_api'] = ( $google_map_api ? $google_map_api : '' );
        return $localize_data;
    }

}

GoogleMapField::init();