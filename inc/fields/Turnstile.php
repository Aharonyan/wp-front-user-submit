<?php

namespace BFE\fields;

use BFE\Form;

defined('ABSPATH') || exit;

/**
 * Cloudflare Turnstile captcha field
 *
 * @package bfee
 */
class Turnstile
{
    public static $field_label = 'Cloudflare Turnstile';
    public static $field_type = 'turnstile_field';

    const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Turnstile tokens are at most 2048 characters long
     */
    const MAX_TOKEN_LENGTH = 2048;

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);
        add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
        add_action('bfe_ajax_before_post_update_or_creation', [__CLASS__, 'turnstile_validate'], 10, 2);
    }

    /**
     * A form with a Turnstile field is saved only after Cloudflare confirms the token
     *
     * @param int|string $post_id
     * @param int $form_id
     * @return void
     */
    public static function turnstile_validate($post_id, $form_id)
    {
        $settings = Form::get_form_field_settings(self::$field_type, $form_id);
        if (empty($settings)) {
            return;
        }

        $error_message = !empty($settings['turnstile_error_message']) ? $settings['turnstile_error_message'] : __('Please complete the captcha properly.', 'front-editor');

        $token = isset($_POST['cf-turnstile-response']) && is_string($_POST['cf-turnstile-response']) ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response'])) : '';
        if ($token === '' || strlen($token) > self::MAX_TOKEN_LENGTH) {
            wp_send_json_error(['field' => self::$field_type, 'message' => $error_message]);
        }

        $response = wp_remote_post(self::VERIFY_URL, [
            'timeout' => 15,
            'body' => [
                'secret' => (string) ($settings['turnstile_secret_key'] ?? ''),
                'response' => $token,
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['field' => self::$field_type, 'message' => __('Failed to verify captcha', 'front-editor')]);
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($result['success'])) {
            wp_send_json_error(['field' => self::$field_type, 'message' => $error_message]);
        }
    }

    /**
     * Form builder settings
     *
     * @param array $data
     * @return array
     */
    public static function add_field_settings($data)
    {
        $data['formBuilder_options']['fields'][] = [
            'label' => self::$field_label,
            'attrs' => [
                'type' => self::$field_type
            ],
            'icon' => '<span class="dashicons dashicons-cloud"></span>',
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
            'turnstile_site_key' => ['label' => __('Site Key', 'front-editor'), 'value' => '', 'placeholder' => ''],
            'turnstile_secret_key' => ['label' => __('Secret Key', 'front-editor'), 'value' => '', 'placeholder' => ''],
            'turnstile_error_message' => ['label' => __('Error Message', 'front-editor'), 'value' => '', 'placeholder' => 'Please complete the captcha properly.'],
            'turnstile_theme' => [
                'label' => __('Theme', 'front-editor'),
                'multiple' => false,
                'options' => [
                    'auto' => 'Auto',
                    'light' => 'Light',
                    'dark' => 'Dark'
                ],
            ],
            'turnstile_size' => [
                'label' => __('Size', 'front-editor'),
                'multiple' => false,
                'options' => [
                    'normal' => 'Normal',
                    'flexible' => 'Flexible',
                    'compact' => 'Compact'
                ],
            ],
            'turnstile_language' => ['label' => __('Language', 'front-editor'), 'value' => '', 'placeholder' => 'auto'],
        ];

        $desc_for_keys = __('Create a widget in the Cloudflare dashboard (Turnstile) to get the keys. Turnstile is free: <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">Cloudflare Turnstile</a>', 'front-editor');
        $data['formBuilder_options']['attr_descriptions']['turnstile_site_key'] = $desc_for_keys;
        $data['formBuilder_options']['attr_descriptions']['turnstile_secret_key'] = $desc_for_keys;
        $data['formBuilder_options']['attr_descriptions']['turnstile_language'] = __('Language code like "de" or "auto" for the visitor language. <a href="https://developers.cloudflare.com/turnstile/reference/supported-languages/" target="_blank">Supported languages</a>', 'front-editor');

        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type),
            'onRender' => '',
            'max_in_form' => 1
        ];

        return $data;
    }

    /**
     * Turnstile widget on the front form
     *
     * @param int|string $post_id
     * @param array $attributes
     * @param array $field
     * @return void
     */
    public static function add_field_to_front_form($post_id, $attributes, $field)
    {
        if ($field['type'] !== self::$field_type) {
            return;
        }

        // without keys nobody can pass the check: tell the form owner, visitors see nothing
        if (empty($field['turnstile_site_key'])) {
            if (current_user_can(fe_admin_role())) {
                printf('<div class="fus-info">%s</div>', esc_html__('Cloudflare Turnstile: add the site key and the secret key in the field settings.', 'front-editor'));
            }
            return;
        }

        $theme = in_array($field['turnstile_theme'] ?? '', ['light', 'dark'], true) ? $field['turnstile_theme'] : 'auto';
        $size = in_array($field['turnstile_size'] ?? '', ['flexible', 'compact'], true) ? $field['turnstile_size'] : 'normal';
        $language = !empty($field['turnstile_language']) ? $field['turnstile_language'] : 'auto';

        printf(
            '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script><div class="cf-turnstile %s" data-sitekey="%s" data-theme="%s" data-size="%s" data-language="%s"></div>',
            esc_attr(self::$field_type),
            esc_attr($field['turnstile_site_key']),
            esc_attr($theme),
            esc_attr($size),
            esc_attr($language)
        );

        // normal and flexible widgets need 300px: narrow forms on phones get the compact one
        if ($size !== 'compact') {
            echo fus_compact_captcha_script(300);
        }

        if (!empty($field['description'])) {
            printf('<p class="fus-custom-field-description">%s</p>', esc_html($field['description']));
        }
    }
}

Turnstile::init();
