<?php

/**
 * Generate form builder fields from a text description
 *
 * @package bfee
 */

namespace BFE\AI;

defined('ABSPATH') || exit;

class AiFormGenerator
{
  const MAX_FIELDS = 30;

  const MAX_DESCRIPTION_LENGTH = 3000;

  /**
   * Types that never have a meta name
   */
  const TYPES_WITHOUT_NAME = ['post_title', 'featured_image', 'header', 'paragraph', 'captcha_field', 'hcaptcha_field', 'turnstile_field'];

  const TYPES_WITH_OPTIONS = ['select', 'radio-group', 'checkbox-group'];

  const CONTENT_EDITORS = ['post_content_editor_js', 'md_editor', 'tinymce', 'textarea'];

  /**
   * Post fields and plugin meta that must not be used as custom field names
   */
  const RESERVED_NAMES = ['post_content', 'post_title', 'post_excerpt', 'post_status', 'post_author', 'post_date', 'post_name', 'post_type', 'post_parent', 'thumbnail_id', 'formbuilderdata', 'fe_form_settings', 'bfe_editor_js_data'];

  public static function init()
  {
    add_action('rest_api_init', [__CLASS__, 'new_endpoints']);
    add_filter('bfe_fe_post_form_backend_block_localize_data', [__CLASS__, 'add_admin_data'], 20);
  }

  public static function new_endpoints()
  {
    register_rest_route('bfe/v1', '/ai_generate_form', [
      'methods' => 'POST',
      'callback' => [__CLASS__, 'generate_form'],
      'permission_callback' => function () {
        return current_user_can(fe_admin_role());
      },
    ]);
  }

  public static function add_admin_data($data)
  {
    $data['ai']['rest_generate_form'] = get_rest_url(null, 'bfe/v1/ai_generate_form');
    $data['ai']['i18n'] = array_merge($data['ai']['i18n'], [
      'title' => __('Generate form with AI', 'front-editor'),
      'placeholder' => __('E.g. A form to submit a recipe: title, photo, ingredients, cooking time, difficulty (easy/medium/hard) and step by step instructions.', 'front-editor'),
      'mode_append' => __('Add fields to the current form', 'front-editor'),
      'mode_replace' => __('Replace current form fields', 'front-editor'),
      'generate' => __('Generate', 'front-editor'),
      'cancel' => __('Cancel', 'front-editor'),
      'empty' => __('Please describe the form', 'front-editor'),
      'success' => __('Form generated. Review the fields and click Save.', 'front-editor'),
      'error' => __('AI request failed', 'front-editor'),
    ]);
    return $data;
  }

  /**
   * Generate form fields
   *
   * @param \WP_REST_Request $request
   * @return \WP_REST_Response|\WP_Error
   */
  public static function generate_form(\WP_REST_Request $request)
  {
    $description = mb_substr(sanitize_textarea_field(wp_unslash((string) $request->get_param('description'))), 0, self::MAX_DESCRIPTION_LENGTH);
    $types = self::sanitize_types(json_decode(wp_unslash((string) $request->get_param('types')), true));
    $existing_names = array_map('sanitize_key', (array) json_decode(wp_unslash((string) $request->get_param('existing_names')), true));

    if ($description === '') {
      return new \WP_Error('fus_ai_description', __('Please describe the form', 'front-editor'), ['status' => 400]);
    }
    if (empty($types)) {
      return new \WP_Error('fus_ai_types', __('No field types available', 'front-editor'), ['status' => 400]);
    }

    $result = AiClient::complete_json(self::build_system_prompt($types), $description, ['max_tokens' => 4096]);
    if (is_wp_error($result)) {
      return new \WP_Error($result->get_error_code(), AiAssistant::public_error_message($result), ['status' => 502]);
    }

    $fields = self::sanitize_fields($result['fields'] ?? [], $types, $existing_names);
    if (empty($fields)) {
      return new \WP_Error('fus_ai_no_fields', __('AI did not return any usable fields, try to describe the form differently', 'front-editor'), ['status' => 502]);
    }

    return rest_ensure_response(['success' => true, 'data' => ['fields' => $fields]]);
  }

  /**
   * Allowed types sent by form builder: [type => label]
   *
   * @param mixed $types
   * @return array
   */
  private static function sanitize_types($types)
  {
    $allowed = [];
    foreach ((array) $types as $type) {
      if (!is_array($type) || empty($type['type'])) {
        continue;
      }
      $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($type['type']));
      // captchas need keys from the site owner, so AI does not add them
      if ($slug && !in_array($slug, ['button', 'hidden_field', 'action_hook_field', 'captcha_field', 'hcaptcha_field', 'turnstile_field'], true)) {
        $allowed[$slug] = sanitize_text_field($type['label'] ?? $slug);
      }
    }
    return $allowed;
  }

  private static function build_system_prompt(array $types)
  {
    $type_lines = implode("\n", array_map(function ($type, $label) { return sprintf('- %s: %s', $type, $label); }, array_keys($types), $types));

    return implode("\n", [
      'You build forms for a WordPress front-end post submission plugin.',
      'The user describes the form. Return JSON: {"fields": [ ... ]} in the order the fields should appear.',
      'Each field: {"type": string, "label": string, "name": string, "required": bool, "placeholder": string, "description": string, "values": [{"label": string, "value": string}], "subtype": string, "save_to_post_content": bool}.',
      'Only use these field types:',
      $type_lines,
      'Rules:',
      '- Include post_title once if available, it is the post title.',
      '- Use one main content editor (post_content_editor_js, md_editor, tinymce or textarea) with "save_to_post_content": true for the post body.',
      '- tax_* types are taxonomies (categories, tags); use them for categorisation.',
      '- "name" is the post meta key in snake_case English, unique, only for custom fields.',
      '- "values" only for select, radio-group and checkbox-group.',
      '- "subtype" for text: text, email, tel, url; for header: h2 or h3.',
      '- Use header and paragraph to split long forms into sections with hints.',
      '- Labels, placeholders and descriptions in the language of the user description.',
      sprintf('- At most %d fields.', self::MAX_FIELDS),
    ]);
  }

  /**
   * Convert AI fields into formBuilder data
   *
   * @param mixed $fields
   * @param array $types
   * @param array $existing_names
   * @return array
   */
  public static function sanitize_fields($fields, array $types, array $existing_names = [])
  {
    $result = [];
    $used_names = array_flip($existing_names);
    $has_content_editor = false;

    foreach (array_slice((array) $fields, 0, self::MAX_FIELDS) as $index => $field) {
      if (!is_array($field) || !isset($types[$field['type'] ?? ''])) {
        continue;
      }
      $type = $field['type'];
      $item = [
        'type' => $type,
        'required' => !empty($field['required']),
        'label' => sanitize_text_field($field['label'] ?? $types[$type]),
      ];

      foreach (['placeholder', 'description'] as $attr) {
        if (!empty($field[$attr]) && is_string($field[$attr])) {
          $item[$attr] = sanitize_text_field($field[$attr]);
        }
      }

      if ($type === 'text') {
        $item['subtype'] = in_array($field['subtype'] ?? '', ['text', 'email', 'tel', 'url'], true) ? $field['subtype'] : 'text';
      }
      if ($type === 'header') {
        $item['subtype'] = in_array($field['subtype'] ?? '', ['h2', 'h3'], true) ? $field['subtype'] : 'h2';
        unset($item['required']);
      }
      if ($type === 'paragraph') {
        $item['subtype'] = 'p';
        unset($item['required']);
      }

      if (in_array($type, self::TYPES_WITH_OPTIONS, true)) {
        $item['values'] = self::sanitize_options($field['values'] ?? []);
        if (empty($item['values'])) {
          continue;
        }
      }

      if (in_array($type, self::CONTENT_EDITORS, true) && !empty($field['save_to_post_content']) && !$has_content_editor) {
        $has_content_editor = true;
        $item['post_content'] = true;
        if ($type === 'tinymce') {
          $item['save_to'] = 'post_content';
        }
      }

      if (!in_array($type, self::TYPES_WITHOUT_NAME, true) && strpos($type, 'tax_') !== 0) {
        $item['name'] = self::unique_name($field['name'] ?? '', $type, $index, $used_names);
        $item['className'] = 'form-control';
      }

      $result[] = $item;
    }

    return $result;
  }

  private static function sanitize_options($values)
  {
    $options = [];
    foreach (array_slice((array) $values, 0, 50) as $index => $value) {
      $label = sanitize_text_field(is_array($value) ? ($value['label'] ?? '') : (string) $value);
      if ($label === '') {
        continue;
      }
      $option_value = sanitize_title(is_array($value) && !empty($value['value']) ? $value['value'] : $label);
      $options[] = [
        'label' => $label,
        'value' => $option_value ?: 'option-' . ($index + 1),
        'selected' => false,
      ];
    }
    return $options;
  }

  private static function unique_name($name, $type, $index, array &$used_names)
  {
    $name = trim(preg_replace('/[^a-z0-9_]+/', '_', strtolower(remove_accents((string) $name))), '_');
    if ($name === '') {
      $name = sprintf('%s_%d', str_replace('-', '_', $type), $index + 1);
    }

    if (in_array($name, self::RESERVED_NAMES, true) || strpos($name, '_') === 0) {
      $name = 'field_' . ltrim($name, '_');
    }

    $unique = $name;
    $suffix = 2;
    while (isset($used_names[$unique])) {
      $unique = sprintf('%s_%d', $name, $suffix++);
    }
    $used_names[$unique] = true;
    return $unique;
  }
}

AiFormGenerator::init();
