<?php

namespace BFE\fields;

use BFE\Form;

defined('ABSPATH') || exit;

class HCaptcha
{
    public static $field_label = 'hCaptcha';
    public static $field_type = 'hcaptcha_field';

    public static function init()
    {
        add_filter('admin_post_form_formBuilder_settings', [__CLASS__, 'add_field_settings']);

        add_action('bfe_editor_on_front_field_adding', [__CLASS__, 'add_field_to_front_form'], 10, 3);
        add_action('bfe_ajax_before_post_update_or_creation', [__CLASS__, 'hcaptcha_validate'], 10, 2);
    }
    

    public static function hcaptcha_validate($post_id, $form_id)
    {
        $settings = Form::get_form_field_settings(self::$field_type, $form_id);
        if (empty($settings)) {
            return;
        }

        // the form has a captcha: a request without an answer is refused
        $captcha = isset($_POST['h-captcha-response']) && is_string($_POST['h-captcha-response']) ? sanitize_text_field(wp_unslash($_POST['h-captcha-response'])) : '';
        if ($captcha === '') {
            $message = !empty($settings['hcaptcha_error_message']) ? $settings['hcaptcha_error_message'] : 'Please complete the captcha properly.';
            wp_send_json_error(['field' => self::$field_type, 'message' => $message]);
        }
        $secret_key = (string) ($settings['hcaptcha_secret_key'] ?? '');

        $verify_data = [
            'secret' => $secret_key,
            'response' => $captcha
        ];

        $verify_url = 'https://hcaptcha.com/siteverify';
        
        $response = wp_remote_post($verify_url, [
            'timeout' => 15,
            'body' => $verify_data
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['field' => self::$field_type, 'message' => 'Failed to verify captcha']);
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        $error_message = 'Please complete the captcha properly.';
        if (isset($settings['hcaptcha_error_message']) && !empty($settings['hcaptcha_error_message'])) {
            $error_message = $settings['hcaptcha_error_message'];
        }

        if (empty($result['success'])) {
            wp_send_json_error(['field' => self::$field_type, 'message' => $error_message]);
        }
    }

    public static function add_field_settings($data)
    {
        $data['formBuilder_options']['fields'][] = [
            'label' => self::$field_label,
            'attrs' => [
                'type' => self::$field_type
            ],
            'icon' => '<span class="dashicons dashicons-shield"></span>',
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
            'hcaptcha_site_key' => ['label' => sprintf('%s', __('Site Key', 'front-editor')), 'value' => '', 'placeholder' => ''],
            'hcaptcha_secret_key' => ['label' => sprintf('%s', __('Secret Key', 'front-editor')), 'value' => '', 'placeholder' => ''],
            'hcaptcha_error_message' => ['label' => sprintf('%s', __('Error Message', 'front-editor')), 'value' => '', 'placeholder' => 'Please complete the captcha properly.'],
            'hcaptcha_theme' => [
                'label' => 'Theme',
                'multiple' => false,
                'options' => [
                    'light' => 'Light',
                    'dark' => 'Dark'
                ],
            ],
            'hcaptcha_size' => [
                'label' => 'Size',
                'multiple' => false,
                'options' => [
                    'normal' => 'Normal',
                    'compact' => 'Compact'
                ],
            ],
            'hcaptcha_language' => ['label' => sprintf('%s', __('Language', 'front-editor')), 'value' => '', 'placeholder' => 'en']
        ];

        $desc_for_keys = sprintf('You need to get keys from hCaptcha. Visit: <a href="https://dashboard.hcaptcha.com/signup" target="_blank">hCaptcha Dashboard</a>');
        $data['formBuilder_options']['attr_descriptions']['hcaptcha_site_key'] = $desc_for_keys;
        $data['formBuilder_options']['attr_descriptions']['hcaptcha_secret_key'] = $desc_for_keys;
        $data['formBuilder_options']['attr_descriptions']['hcaptcha_language'] = __('Check the list of languages <a href="https://docs.hcaptcha.com/languages" target="_blank">here</a>', 'front-editor');

        $data['formBuilder_options']['temp_back'][self::$field_type] = [
            'field' => sprintf('<input type="text" class="%s" name="%s">', self::$field_type, self::$field_type),
            'onRender' => '',
            'max_in_form' => 1
        ];

        return $data;
    }

    public static function add_field_to_front_form($post_id, $attributes, $field)
    {
        if ($field['type'] !== self::$field_type) {
            return;
        }

        if (!isset($field['hcaptcha_site_key'])) {
            return;
        }

        $theme = isset($field['hcaptcha_theme']) ? $field['hcaptcha_theme'] : 'light';
        $size = isset($field['hcaptcha_size']) ? $field['hcaptcha_size'] : 'normal';

        $language = isset($field['hcaptcha_language']) ? $field['hcaptcha_language'] : 'en';
        // recaptchacompat=off: otherwise hCaptcha also renders into reCAPTCHA widgets on the page
        $script = sprintf('<script src="%s" async defer></script>', esc_url(add_query_arg(['hl' => rawurlencode($language), 'recaptchacompat' => 'off'], 'https://js.hcaptcha.com/1/api.js')));
        printf(
            '%s<div class="h-captcha %s" data-sitekey="%s" data-theme="%s" data-size="%s"></div>',
            $script,
            esc_attr(self::$field_type),
            esc_attr($field['hcaptcha_site_key']),
            esc_attr($theme),
            esc_attr($size)
        );
        // the normal widget is 303px wide: narrow forms on phones get the compact one
        if ($size === 'normal') {
            echo fus_compact_captcha_script(303);
        }

        if (!empty($field['description'])) {
            printf('<p class="fus-custom-field-description">%s</p>', esc_html($field['description']));
        }
    }
}

HCaptcha::init();