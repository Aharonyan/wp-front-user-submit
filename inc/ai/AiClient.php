<?php

/**
 * AI providers client (OpenAI, Anthropic, Google Gemini, OpenAI compatible endpoints)
 *
 * @package bfee
 */

namespace BFE\AI;

defined('ABSPATH') || exit;

class AiClient
{
  const OPTION_NAME = 'bfe_ai_settings';

  const REQUEST_TIMEOUT = 60;

  const DEFAULT_MAX_TOKENS = 2048;

  const REASONING_BUDGET_MULTIPLIER = 4;

  /**
   * Providers list with defaults
   *
   * @return array
   */
  public static function get_providers()
  {
    return [
      'openai' => [
        'label' => 'OpenAI',
        'default_model' => 'gpt-5-mini',
        'models' => ['gpt-5-mini', 'gpt-5', 'gpt-5-nano', 'gpt-4.1-mini'],
        'key_url' => 'https://platform.openai.com/api-keys',
        'key_constant' => 'FUS_OPENAI_API_KEY',
      ],
      'anthropic' => [
        'label' => 'Anthropic Claude',
        'default_model' => 'claude-haiku-5-5',
        'models' => ['claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5'],
        'key_url' => 'https://console.anthropic.com/settings/keys',
        'key_constant' => 'FUS_ANTHROPIC_API_KEY',
      ],
      'gemini' => [
        'label' => 'Google Gemini',
        'default_model' => 'gemini-2.5-flash',
        'models' => ['gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.5-pro'],
        'key_url' => 'https://aistudio.google.com/app/apikey',
        'key_constant' => 'FUS_GEMINI_API_KEY',
      ],
      'custom' => [
        'label' => __('OpenRouter / OpenAI compatible URL', 'front-editor'),
        'default_model' => 'openai/gpt-5-mini',
        'models' => ['openai/gpt-5-mini', 'anthropic/claude-haiku-5-5', 'google/gemini-2.5-flash', 'llama3.1'],
        'key_url' => 'https://openrouter.ai/keys',
        'key_constant' => 'FUS_CUSTOM_AI_API_KEY',
      ],
    ];
  }

  /**
   * Saved AI settings
   *
   * @return array
   */
  public static function get_settings()
  {
    $settings = get_option(self::OPTION_NAME, []);
    return is_array($settings) ? $settings : [];
  }

  /**
   * API key for provider, wp-config constant has priority over saved option
   *
   * @param string $provider
   * @param array $settings
   * @return string
   */
  public static function get_api_key($provider, array $settings = [])
  {
    $providers = self::get_providers();
    if (!isset($providers[$provider])) {
      return '';
    }

    $constant = $providers[$provider]['key_constant'];
    if (defined($constant) && constant($constant)) {
      return (string) constant($constant);
    }

    $settings = $settings ?: self::get_settings();
    return (string) ($settings[$provider . '_key'] ?? '');
  }

  /**
   * Is key defined in wp-config.php
   *
   * @param string $provider
   * @return bool
   */
  public static function is_key_from_constant($provider)
  {
    $providers = self::get_providers();
    $constant = $providers[$provider]['key_constant'] ?? '';
    return $constant && defined($constant) && constant($constant);
  }

  /**
   * Resolved provider config: provider, key, model, url
   *
   * @param array $overrides
   * @return array
   */
  public static function get_config(array $overrides = [])
  {
    $settings = array_merge(self::get_settings(), array_filter($overrides, function ($value) { return $value !== '' && $value !== null; }));
    $providers = self::get_providers();
    $provider = isset($providers[$settings['provider'] ?? '']) ? $settings['provider'] : 'openai';

    return [
      'provider' => $provider,
      'api_key' => self::get_api_key($provider, $settings),
      'model' => !empty($settings[$provider . '_model']) ? $settings[$provider . '_model'] : $providers[$provider]['default_model'],
      'custom_url' => $settings['custom_url'] ?? '',
    ];
  }

  /**
   * Is AI configured and ready to use
   *
   * @return bool
   */
  public static function is_configured()
  {
    $config = self::get_config();
    if ($config['provider'] === 'custom') {
      return !empty($config['custom_url']);
    }
    return !empty($config['api_key']);
  }

  /**
   * Send prompt to the selected provider
   *
   * @param string $system System instructions
   * @param string $prompt User message
   * @param array $args json (bool), max_tokens (int), config (array) overrides
   * @return string|\WP_Error
   */
  public static function complete($system, $prompt, array $args = [])
  {
    $config = $args['config'] ?? self::get_config();
    $args = array_merge(['json' => false, 'max_tokens' => self::DEFAULT_MAX_TOKENS, 'timeout' => self::REQUEST_TIMEOUT], $args);

    if ($config['provider'] !== 'custom' && empty($config['api_key'])) {
      return new \WP_Error('fus_ai_no_key', __('AI API key is not configured', 'front-editor'));
    }

    switch ($config['provider']) {
      case 'anthropic':
        $request = self::anthropic_request($config, $system, $prompt, $args);
        break;
      case 'gemini':
        $request = self::gemini_request($config, $system, $prompt, $args);
        break;
      case 'custom':
        $request = self::openai_request($config, $system, $prompt, $args, true);
        break;
      default:
        $request = self::openai_request($config, $system, $prompt, $args, false);
    }

    if (is_wp_error($request)) {
      return $request;
    }

    $response = wp_remote_post($request['url'], [
      'timeout' => $args['timeout'],
      // Never follow redirects: an external URL must not lead to internal hosts
      'redirection' => 0,
      'headers' => array_merge(['Content-Type' => 'application/json'], $request['headers']),
      'body' => wp_json_encode($request['body']),
    ]);

    if (is_wp_error($response)) {
      return new \WP_Error('fus_ai_http', sprintf(__('AI request failed: %s', 'front-editor'), $response->get_error_message()));
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300) {
      return new \WP_Error('fus_ai_api', sprintf(__('AI provider error (%1$s): %2$s', 'front-editor'), $code, self::extract_error_message($body)));
    }

    $text = self::extract_text($config['provider'], is_array($body) ? $body : []);
    if ($text === '') {
      return new \WP_Error('fus_ai_empty', __('AI provider returned an empty response', 'front-editor'));
    }

    return $text;
  }

  /**
   * Request that must return JSON, decoded to array
   *
   * @param string $system
   * @param string $prompt
   * @param array $args
   * @return array|\WP_Error
   */
  public static function complete_json($system, $prompt, array $args = [])
  {
    $text = self::complete($system . "\n\nRespond with valid JSON only, no markdown fences.", $prompt, array_merge($args, ['json' => true]));
    if (is_wp_error($text)) {
      return $text;
    }

    $data = self::decode_json($text);
    if (!is_array($data)) {
      return new \WP_Error('fus_ai_json', __('AI response is not valid JSON', 'front-editor'));
    }
    return $data;
  }

  /**
   * Decode JSON even when model wrapped it into fences or text
   *
   * @param string $text
   * @return array|null
   */
  public static function decode_json($text)
  {
    $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
    $data = json_decode($text, true);
    if (is_array($data)) {
      return $data;
    }

    if (preg_match('/[\{\[].*[\}\]]/s', $text, $matches)) {
      $data = json_decode($matches[0], true);
      return is_array($data) ? $data : null;
    }
    return null;
  }

  private static function openai_request($config, $system, $prompt, $args, $is_custom)
  {
    $url = 'https://api.openai.com/v1/chat/completions';
    if ($is_custom) {
      // Admin defined URL, local hosts are allowed for self-hosted models (Ollama, LM Studio)
      $scheme = wp_parse_url($config['custom_url'] ?? '', PHP_URL_SCHEME);
      if (!filter_var($config['custom_url'] ?? '', FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
        return new \WP_Error('fus_ai_url', __('AI endpoint URL is not valid', 'front-editor'));
      }
      $url = untrailingslashit($config['custom_url']);
      if (!preg_match('#/chat/completions$#', $url)) {
        $url .= '/chat/completions';
      }
    }

    $body = [
      'model' => $config['model'],
      'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $prompt],
      ],
    ];

    // OpenAI reasoning models accept only max_completion_tokens, compatible APIs expect max_tokens.
    // Reasoning models (gpt-5, o-series, many OpenRouter models) spend part of the budget on thinking
    $body[$is_custom ? 'max_tokens' : 'max_completion_tokens'] = $args['max_tokens'] * self::REASONING_BUDGET_MULTIPLIER;
    if (preg_match('#(^|/)(gpt-5|o[1-9])#', $config['model'])) {
      $body['reasoning_effort'] = 'low';
    }

    if ($args['json'] && !$is_custom) {
      $body['response_format'] = ['type' => 'json_object'];
    }

    $headers = [];
    if (!empty($config['api_key'])) {
      $headers['Authorization'] = 'Bearer ' . $config['api_key'];
    }
    if ($is_custom) {
      $headers['HTTP-Referer'] = home_url();
      $headers['X-Title'] = get_bloginfo('name');
    }

    return ['url' => $url, 'headers' => $headers, 'body' => $body];
  }

  private static function anthropic_request($config, $system, $prompt, $args)
  {
    return [
      'url' => 'https://api.anthropic.com/v1/messages',
      'headers' => [
        'x-api-key' => $config['api_key'],
        'anthropic-version' => '2023-06-01',
      ],
      'body' => [
        'model' => $config['model'],
        'max_tokens' => $args['max_tokens'],
        'system' => $system,
        'messages' => [['role' => 'user', 'content' => $prompt]],
      ],
    ];
  }

  private static function gemini_request($config, $system, $prompt, $args)
  {
    // Gemini 2.5 spends part of the output budget on thinking
    $generation_config = ['maxOutputTokens' => $args['max_tokens'] * self::REASONING_BUDGET_MULTIPLIER];
    if ($args['json']) {
      $generation_config['responseMimeType'] = 'application/json';
    }

    return [
      'url' => sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', rawurlencode($config['model'])),
      'headers' => ['x-goog-api-key' => $config['api_key']],
      'body' => [
        'systemInstruction' => ['parts' => [['text' => $system]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'generationConfig' => $generation_config,
      ],
    ];
  }

  private static function extract_text($provider, array $body)
  {
    switch ($provider) {
      case 'anthropic':
        $parts = array_filter($body['content'] ?? [], function ($part) { return ($part['type'] ?? '') === 'text'; });
        return trim(implode('', array_column($parts, 'text')));
      case 'gemini':
        $parts = $body['candidates'][0]['content']['parts'] ?? [];
        $parts = array_filter($parts, function ($part) { return empty($part['thought']); });
        return trim(implode('', array_column($parts, 'text')));
      default:
        return trim((string) ($body['choices'][0]['message']['content'] ?? ''));
    }
  }

  private static function extract_error_message($body)
  {
    if (!is_array($body)) {
      return __('Unknown error', 'front-editor');
    }
    $message = $body['error']['message'] ?? $body['error'] ?? $body['message'] ?? __('Unknown error', 'front-editor');
    return self::redact_secrets(sanitize_text_field(is_string($message) ? $message : wp_json_encode($message)));
  }

  /**
   * Hide API keys that providers echo back in error messages
   *
   * @param string $text
   * @return string
   */
  public static function redact_secrets($text)
  {
    return preg_replace('/\b(sk|ak|pk|rk|key|AIza)[-_A-Za-z0-9]{12,}\b/', '[redacted]', (string) $text);
  }
}
