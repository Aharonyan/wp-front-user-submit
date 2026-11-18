<?php

/**
 * AI settings tab: provider, API keys, models and features
 *
 * @package bfee
 */

namespace BFE\AI;

defined('ABSPATH') || exit;

class AiSettings
{
  const OPTION_GROUP = 'bfe_ai_settings_group';

  const PAGE = 'bfe_ai_settings_page';

  const TAB = 'ai_settings';

  const DEFAULT_DAILY_LIMIT = 30;

  public static function init()
  {
    add_filter('bfe_settings_tabs', [__CLASS__, 'add_tab']);
    add_action('admin_init', [__CLASS__, 'register_settings']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
  }

  /**
   * Default settings
   *
   * @return array
   */
  public static function defaults()
  {
    return [
      'provider' => 'openai',
      'custom_url' => '',
      'assistant_enabled' => '1',
      'assistant_access' => 'logged_in',
      'daily_limit' => self::DEFAULT_DAILY_LIMIT,
      'moderation_enabled' => '',
      'moderation_action' => 'pending',
      'moderation_rules' => '',
      'auto_excerpt' => '',
      'auto_tags' => '',
      'auto_seo_description' => '',
    ];
  }

  /**
   * Settings merged with defaults
   *
   * @return array
   */
  public static function get()
  {
    return array_merge(self::defaults(), AiClient::get_settings());
  }

  /**
   * Per form switches, stored in fe_form_settings
   *
   * @return array feature => setting key
   */
  public static function form_feature_keys()
  {
    return [
      'assistant' => 'ai_assistant',
      'moderation' => 'ai_moderation',
      'auto_meta' => 'ai_auto_meta',
    ];
  }

  /**
   * Is AI feature switched on in the form settings. Off by default, so existing forms are not changed
   *
   * @param int $form_id
   * @param string $feature assistant|moderation|auto_meta
   * @return bool
   */
  public static function is_enabled_for_form($form_id, $feature)
  {
    $keys = self::form_feature_keys();
    if (!$form_id || !isset($keys[$feature])) {
      return false;
    }
    $form_settings = get_post_meta(intval($form_id), 'fe_form_settings', true);
    return is_array($form_settings) && ($form_settings[$keys[$feature]] ?? 'disabled') === 'enabled';
  }

  public static function is_pro()
  {
    return function_exists('fe_fs') && fe_fs()->can_use_premium_code__premium_only();
  }

  public static function add_tab($tabs)
  {
    $tabs[self::TAB] = [
      'title' => __('✨ AI', 'front-editor'),
      'heading' => __('AI Assistant Settings', 'front-editor'),
      'fields_group' => self::OPTION_GROUP,
      'sections' => self::PAGE,
      'shortcode' => '',
    ];
    return $tabs;
  }

  public static function register_settings()
  {
    register_setting(self::OPTION_GROUP, AiClient::OPTION_NAME, [
      'sanitize_callback' => [__CLASS__, 'sanitize'],
    ]);

    add_settings_section('bfe_ai_provider_section', __('AI Provider', 'front-editor'), [__CLASS__, 'render_provider_section'], self::PAGE);
    add_settings_section('bfe_ai_assistant_section', __('Editor Assistant', 'front-editor'), [__CLASS__, 'render_assistant_section'], self::PAGE);
    add_settings_section('bfe_ai_pro_section', __('Pro AI Features', 'front-editor'), [__CLASS__, 'render_pro_section'], self::PAGE);
  }

  /**
   * Sanitize settings, keep saved API keys when the input is left empty
   *
   * @param array $input
   * @return array
   */
  public static function sanitize($input)
  {
    $input = is_array($input) ? wp_unslash($input) : [];
    $saved = AiClient::get_settings();
    $providers = AiClient::get_providers();
    $output = [];

    $output['provider'] = isset($providers[$input['provider'] ?? '']) ? $input['provider'] : 'openai';

    foreach (array_keys($providers) as $provider) {
      $key = trim(sanitize_text_field($input[$provider . '_key'] ?? ''));
      if (!empty($input[$provider . '_key_remove'])) {
        $key = '';
      } elseif ($key === '') {
        $key = $saved[$provider . '_key'] ?? '';
      }
      $output[$provider . '_key'] = $key;
      $output[$provider . '_model'] = preg_replace('/[^A-Za-z0-9._:\/@-]/', '', $input[$provider . '_model'] ?? '');
    }

    $output['custom_url'] = esc_url_raw(trim($input['custom_url'] ?? ''), ['http', 'https']);
    // On multisite only network admins may point requests to custom (possibly internal) hosts
    if (is_multisite() && !is_super_admin()) {
      $output['custom_url'] = $saved['custom_url'] ?? '';
    }
    $output['assistant_enabled'] = !empty($input['assistant_enabled']) ? '1' : '';
    $output['assistant_access'] = ($input['assistant_access'] ?? '') === 'everyone' ? 'everyone' : 'logged_in';
    $output['daily_limit'] = max(0, intval($input['daily_limit'] ?? self::DEFAULT_DAILY_LIMIT));
    $output['moderation_enabled'] = !empty($input['moderation_enabled']) ? '1' : '';
    $output['moderation_action'] = in_array($input['moderation_action'] ?? '', ['pending', 'draft'], true) ? $input['moderation_action'] : 'pending';
    $output['moderation_rules'] = sanitize_textarea_field($input['moderation_rules'] ?? '');
    $output['auto_excerpt'] = !empty($input['auto_excerpt']) ? '1' : '';
    $output['auto_tags'] = !empty($input['auto_tags']) ? '1' : '';
    $output['auto_seo_description'] = !empty($input['auto_seo_description']) ? '1' : '';

    // Pro fields are disabled (not submitted) without license, keep saved values
    if (!self::is_pro()) {
      foreach (['moderation_enabled', 'moderation_action', 'moderation_rules', 'auto_excerpt', 'auto_tags', 'auto_seo_description'] as $key) {
        if (!array_key_exists($key, $input) && array_key_exists($key, $saved)) {
          $output[$key] = $saved[$key];
        }
      }
    }

    return $output;
  }

  public static function render_provider_section()
  {
    $settings = self::get();
    $providers = AiClient::get_providers();
    $name = AiClient::OPTION_NAME;

    printf('<p>%s</p>', esc_html__('Choose the AI provider and add your own API key. Requests are sent from your server, the key is never shown to site visitors.', 'front-editor'));
    self::render_status();
    echo '<table class="form-table fus-ai-settings" role="presentation"><tbody>';

    printf('<tr><th scope="row"><label for="fus-ai-provider">%s</label></th><td><select id="fus-ai-provider" name="%s[provider]">', esc_html__('Provider', 'front-editor'), esc_attr($name));
    foreach ($providers as $slug => $provider) {
      printf('<option value="%s" %s>%s</option>', esc_attr($slug), selected($settings['provider'], $slug, false), esc_html($provider['label']));
    }
    echo '</select></td></tr>';

    foreach ($providers as $slug => $provider) {
      self::render_provider_rows($slug, $provider, $settings);
    }

    printf(
      '<tr><th scope="row">%s</th><td><button type="button" class="button" id="fus-ai-test-connection">%s</button> <span id="fus-ai-test-result" class="fus-ai-test-result" aria-live="polite"></span><p class="description">%s</p></td></tr>',
      esc_html__('Connection', 'front-editor'),
      esc_html__('Test connection', 'front-editor'),
      esc_html__('Sends a short test request with the values entered above.', 'front-editor')
    );
    echo '</tbody></table>';
  }

  private static function render_status()
  {
    $config = AiClient::get_config();
    if (AiClient::is_configured()) {
      printf(
        '<p class="fus-ai-status is-ready"><span aria-hidden="true">●</span> %s</p>',
        esc_html(sprintf(__('Ready: %1$s · %2$s', 'front-editor'), AiClient::get_providers()[$config['provider']]['label'], $config['model']))
      );
      return;
    }
    printf('<p class="fus-ai-status is-missing"><span aria-hidden="true">●</span> %s</p>', esc_html__('Not configured: add an API key below and save.', 'front-editor'));
  }

  private static function render_provider_rows($slug, $provider, $settings)
  {
    $name = AiClient::OPTION_NAME;
    $saved_key = $settings[$slug . '_key'] ?? '';
    $row_attr = sprintf('class="fus-ai-provider-row" data-provider="%s"', esc_attr($slug));

    if ($slug === 'custom') {
      printf(
        '<tr %s><th scope="row"><label for="fus-ai-custom-url">%s</label></th><td><input type="url" class="regular-text" id="fus-ai-custom-url" name="%s[custom_url]" value="%s" placeholder="https://openrouter.ai/api/v1"><p class="description">%s</p></td></tr>',
        $row_attr,
        esc_html__('API base URL', 'front-editor'),
        esc_attr($name),
        esc_attr($settings['custom_url']),
        esc_html__('Any OpenAI compatible API: OpenRouter, Groq, Together, local Ollama (http://localhost:11434/v1)…', 'front-editor')
      );
    }

    if (AiClient::is_key_from_constant($slug)) {
      $key_html = sprintf('<p><code>%s</code> %s</p>', esc_html($provider['key_constant']), esc_html__('is defined in wp-config.php and used as the API key.', 'front-editor'));
    } else {
      $key_html = sprintf(
        '<input type="password" autocomplete="new-password" class="regular-text" id="fus-ai-%1$s-key" name="%2$s[%1$s_key]" value="" placeholder="%3$s">',
        esc_attr($slug),
        esc_attr($name),
        esc_attr($saved_key ? sprintf(__('Saved key ••••%s — leave empty to keep', 'front-editor'), substr($saved_key, -4)) : __('Paste your API key', 'front-editor'))
      );
      if ($saved_key) {
        $key_html .= sprintf(' <label><input type="checkbox" name="%s[%s_key_remove]" value="1"> %s</label>', esc_attr($name), esc_attr($slug), esc_html__('Remove key', 'front-editor'));
      }
      $key_html .= sprintf(
        '<p class="description"><a href="%s" target="_blank" rel="noopener">%s</a> · %s <code>%s</code></p>',
        esc_url($provider['key_url']),
        esc_html__('Get an API key', 'front-editor'),
        esc_html__('or define it in wp-config.php as', 'front-editor'),
        esc_html($provider['key_constant'])
      );
    }

    printf('<tr %s><th scope="row"><label for="fus-ai-%s-key">%s</label></th><td>%s</td></tr>', $row_attr, esc_attr($slug), esc_html__('API key', 'front-editor'), $key_html);

    $options = implode('', array_map(function ($model) { return sprintf('<option value="%s">', esc_attr($model)); }, $provider['models']));
    printf(
      '<tr %1$s><th scope="row"><label for="fus-ai-%2$s-model">%3$s</label></th><td><input type="text" class="regular-text" list="fus-ai-%2$s-models" id="fus-ai-%2$s-model" name="%4$s[%2$s_model]" value="%5$s" placeholder="%6$s"><datalist id="fus-ai-%2$s-models">%7$s</datalist><p class="description">%8$s</p></td></tr>',
      $row_attr,
      esc_attr($slug),
      esc_html__('Model', 'front-editor'),
      esc_attr($name),
      esc_attr($settings[$slug . '_model'] ?? ''),
      esc_attr($provider['default_model']),
      $options,
      esc_html(sprintf(__('Leave empty to use %s.', 'front-editor'), $provider['default_model']))
    );
  }

  public static function render_assistant_section()
  {
    $settings = self::get();
    $name = AiClient::OPTION_NAME;

    printf('<p>%s</p>', esc_html__('Adds an "✨ AI" button to the title, textarea and editor fields of your front-end forms: write from a prompt, improve, fix grammar, shorten or expand text.', 'front-editor'));
    printf('<p class="description">%s</p>', esc_html__('AI is off in every form by default. Turn it on per form: Post Forms → edit form → Settings → ✨ AI.', 'front-editor'));
    echo '<table class="form-table" role="presentation"><tbody>';

    printf(
      '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="%s[assistant_enabled]" value="1" %s> %s</label></td></tr>',
      esc_html__('Enable assistant', 'front-editor'),
      esc_attr($name),
      checked($settings['assistant_enabled'], '1', false),
      esc_html__('Show the AI button in front-end forms', 'front-editor')
    );

    printf('<tr><th scope="row"><label for="fus-ai-access">%s</label></th><td><select id="fus-ai-access" name="%s[assistant_access]">', esc_html__('Who can use it', 'front-editor'), esc_attr($name));
    printf('<option value="logged_in" %s>%s</option>', selected($settings['assistant_access'], 'logged_in', false), esc_html__('Logged-in users only', 'front-editor'));
    printf('<option value="everyone" %s>%s</option>', selected($settings['assistant_access'], 'everyone', false), esc_html__('Everyone, including guests', 'front-editor'));
    printf('</select><p class="description">%s</p></td></tr>', esc_html__('Requests are paid from your API key, so guest access is not recommended.', 'front-editor'));

    printf(
      '<tr><th scope="row"><label for="fus-ai-daily-limit">%s</label></th><td><input type="number" min="0" class="small-text" id="fus-ai-daily-limit" name="%s[daily_limit]" value="%d"><p class="description">%s</p></td></tr>',
      esc_html__('Daily limit per user', 'front-editor'),
      esc_attr($name),
      intval($settings['daily_limit']),
      esc_html__('Maximum AI requests per user (or IP for guests) per day. 0 = unlimited. Administrators and editors are not limited.', 'front-editor')
    );
    echo '</tbody></table>';
  }

  public static function render_pro_section()
  {
    $settings = self::get();
    $name = AiClient::OPTION_NAME;
    $is_pro = self::is_pro();
    $disabled = $is_pro ? '' : 'disabled';

    if (!$is_pro) {
      printf(
        '<p class="fus-ai-pro-notice">%s <a href="%s">%s</a></p>',
        esc_html__('AI form generation, translation and tone change in the assistant, post moderation and automatic excerpt, tags and SEO description are available in the Pro version.', 'front-editor'),
        esc_url(admin_url('admin.php?page=front_editor_settings-pricing')),
        esc_html__('Get the Pro version', 'front-editor')
      );
    } else {
      printf('<p>%s</p>', esc_html__('Use the "✨ Generate with AI" button in the form builder to create forms from a description.', 'front-editor'));
    }

    echo '<table class="form-table" role="presentation"><tbody>';

    printf(
      '<tr><th scope="row">%s</th><td><label><input type="checkbox" name="%s[moderation_enabled]" value="1" %s %s> %s</label><br><br>',
      esc_html__('AI moderation', 'front-editor'),
      esc_attr($name),
      checked($settings['moderation_enabled'], '1', false),
      $disabled,
      esc_html__('Check submitted posts for spam, abuse and unsafe content before publishing', 'front-editor')
    );
    printf('<label>%s <select name="%s[moderation_action]" %s>', esc_html__('Flagged posts are saved as', 'front-editor'), esc_attr($name), $disabled);
    printf('<option value="pending" %s>%s</option>', selected($settings['moderation_action'], 'pending', false), esc_html__('Pending review', 'front-editor'));
    printf('<option value="draft" %s>%s</option>', selected($settings['moderation_action'], 'draft', false), esc_html__('Draft', 'front-editor'));
    printf(
      '</select></label><p><textarea name="%s[moderation_rules]" rows="3" class="large-text" placeholder="%s" %s>%s</textarea></p><p class="description">%s</p></td></tr>',
      esc_attr($name),
      esc_attr__('Extra rules, e.g. "No links to other shops. Only posts about gardening."', 'front-editor'),
      $disabled,
      esc_textarea($settings['moderation_rules']),
      esc_html__('Administrators and editors are not moderated. If the AI provider is unavailable, posts are saved as usual.', 'front-editor')
    );

    $auto_fields = [
      'auto_excerpt' => __('Generate excerpt when it is empty', 'front-editor'),
      'auto_tags' => __('Suggest and add tags', 'front-editor'),
      'auto_seo_description' => __('Generate SEO meta description (Yoast, Rank Math, SEOPress or built-in)', 'front-editor'),
    ];
    printf('<tr><th scope="row">%s</th><td>', esc_html__('Auto-fill after submit', 'front-editor'));
    foreach ($auto_fields as $field => $label) {
      printf(
        '<label><input type="checkbox" name="%s[%s]" value="1" %s %s> %s</label><br>',
        esc_attr($name),
        esc_attr($field),
        checked($settings[$field], '1', false),
        $disabled,
        esc_html($label)
      );
    }
    printf('<p class="description">%s</p></td></tr>', esc_html__('Runs in the background right after a new post is submitted.', 'front-editor'));
    echo '</tbody></table>';
  }

  public static function enqueue_scripts()
  {
    if (($_GET['page'] ?? '') !== 'fe-global-settings' || ($_GET['tab'] ?? '') !== self::TAB) {
      return;
    }

    wp_enqueue_script('fus-ai-settings', FUS__PLUGIN_URL . 'assets/js/ai-settings.js', [], filemtime(FUS__PLUGIN_DIR . 'assets/js/ai-settings.js'), true);
    wp_localize_script('fus-ai-settings', 'fusAiSettings', [
      'rest_url' => get_rest_url(null, 'bfe/v1/ai_test_connection'),
      'nonce' => wp_create_nonce('wp_rest'),
      'option_name' => AiClient::OPTION_NAME,
      'i18n' => [
        'testing' => __('Testing…', 'front-editor'),
        'error' => __('Connection failed', 'front-editor'),
      ],
    ]);
  }
}

AiSettings::init();
