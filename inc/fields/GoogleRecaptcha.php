<?php

namespace BFE\fields;

use BFE\Form;
defined( 'ABSPATH' ) || exit;
class GoogleRecaptcha {
    public static $field_label = 'reCaptcha v2';

    public static $field_type = 'captcha_field';

    public static function init() {
        add_filter( 'admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings'] );
    }

    public static function recaptcha_validate( $post_id, $form_id ) {
        $settings = Form::get_form_field_settings( self::$field_type, $form_id );
        if ( empty( $settings ) ) {
            return;
        }
        if ( !isset( $_POST['g-recaptcha-response'] ) ) {
            return;
        }
        $captcha = $_POST['g-recaptcha-response'];
        $private_key = $settings['recaptcha_secret_key'];
        $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret={$private_key}&response={$captcha}" );
        $responseKeys = json_decode( $response, true );
        $error_message = 'Please complete the captcha properly.';
        if ( isset( $settings['recaptcha_error_message'] ) && !empty( $settings['recaptcha_error_message'] ) ) {
            $error_message = $settings['recaptcha_error_message'];
        }
        if ( intval( $responseKeys["success"] ) !== 1 ) {
            wp_send_json_error( [
                'field'   => self::$field_type,
                'message' => $error_message,
            ] );
        }
    }

    /**
     *
     *
     * @param [type] $data
     * @return void
     */
    public static function add_field_settings( $data ) {
        /**
         * Adding field
         */
        $data['formBuilder_options']['fields'][] = [
            'label' => self::$field_label,
            'attrs' => [
                'type' => self::$field_type,
            ],
            'icon'  => '<span class="dashicons dashicons-image-rotate"></span>',
        ];
        /**
         * Adding field to group
         */
        $data['formBuilder_options']['controls_group']['custom_fields']['types'][] = self::$field_type;
        /**
         * Disabling default settings
         */
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
            'recaptcha_language'      => [
                'label'       => sprintf( '%s', __( 'Language', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => 'en',
            ],
            'recaptcha_site_key'      => [
                'label'       => sprintf( '%s', __( 'Site key', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => '',
            ],
            'recaptcha_secret_key'    => [
                'label'       => sprintf( '%s', __( 'Secret key', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => '',
            ],
            'recaptcha_error_message' => [
                'label'       => sprintf( '%s', __( 'Error Message', 'front-editor' ) ),
                'value'       => '',
                'placeholder' => 'Please complete the captcha properly.',
            ],
            'recaptcha_theme'         => [
                'label'    => 'Theme',
                'multiple' => false,
                'options'  => [
                    'light' => 'Light',
                    'dark'  => 'Dark',
                ],
            ],
        ];
        $desc_for_keys = sprintf( 'You need to get keys from your google project. Check docs: <a href="https://developers.google.com/recaptcha/intro" target="_blank">Click here</a>' );
        $data['formBuilder_options']['attr_descriptions']['recaptcha_language'] = __( 'Check the list of languages <a href="https://developers.google.com/recaptcha/docs/language" target="_blank">here</a>', 'front-editor' );
        $data['formBuilder_options']['attr_descriptions']['recaptcha_site_key'] = $desc_for_keys;
        $data['formBuilder_options']['attr_descriptions']['recaptcha_secret_key'] = $desc_for_keys;
        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field'       => sprintf( '<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type ),
            'onRender'    => '',
            'max_in_form' => 1,
        ];
        return $data;
    }

    /**
     *
     *
     * @return void
     */
    public static function add_field_to_front_form( $post_id, $attributes, $field ) {
        if ( $field['type'] !== self::$field_type ) {
            return;
        }
        if ( !isset( $field['recaptcha_site_key'] ) ) {
            return;
        }
        $lng = 'en';
        if ( isset( $field['recaptcha_language'] ) ) {
            $lng = $field['recaptcha_language'];
        }
        $theme = 'light';
        if ( isset( $field['recaptcha_theme'] ) ) {
            $theme = $field['recaptcha_theme'];
        }
        $script = sprintf( '<script src="https://www.google.com/recaptcha/api.js?hl=%s" async defer></script>', $lng );
        printf(
            '%s<div class="g-recaptcha" data-sitekey="%s" data-theme="%s"></div>',
            $script,
            $field['recaptcha_site_key'],
            $theme
        );
        if ( isset( $field['name'] ) && isset( $field['description'] ) ) {
            printf( '<p class="fus-custom-field-description %s">%s</p>', esc_attr( $field['name'] ), esc_attr( $field['description'] ) );
        }
    }

}

GoogleRecaptcha::init();