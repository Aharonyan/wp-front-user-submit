<?php

/**
 * Daily AI request limit per user (or per IP for guests)
 *
 * @package bfee
 */

namespace BFE\AI;

defined('ABSPATH') || exit;

class AiUsageLimit
{
  const CACHE_GROUP = 'fus_ai_usage';

  /**
   * Identity of the visitor: user ID, or IP for guests (IPv6 by /64 network)
   *
   * @param int $user_id Use this user instead of the current one (background jobs)
   * @return string
   */
  public static function identity($user_id = 0)
  {
    $user_id = $user_id ?: get_current_user_id();
    if ($user_id) {
      return 'u' . $user_id;
    }

    // Sites behind a proxy or CDN can pass the real visitor IP
    $ip = (string) apply_filters('bfe_ai_client_ip', $_SERVER['REMOTE_ADDR'] ?? '');
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $ip = implode(':', array_slice(explode(':', inet_ntop(inet_pton($ip)) ?: $ip), 0, 4)) . '::/64';
    }
    return 'ip' . $ip;
  }

  /**
   * Is the user free from the limit
   *
   * @param int $user_id
   * @return bool
   */
  public static function is_unlimited($user_id = 0)
  {
    $limit = intval(AiSettings::get()['daily_limit']);
    $user_id = $user_id ?: get_current_user_id();
    return !$limit || ($user_id && user_can($user_id, 'edit_others_posts'));
  }

  /**
   * Take one request from the daily budget before calling the provider
   *
   * @param int $user_id
   * @return true|\WP_Error
   */
  public static function reserve($user_id = 0)
  {
    if (self::is_unlimited($user_id)) {
      return true;
    }

    $key = self::key($user_id);
    $used = self::increment($key, 1);
    if ($used > intval(AiSettings::get()['daily_limit'])) {
      self::increment($key, -1);
      return new \WP_Error('fus_ai_limit', __('You have reached the daily AI limit. Please try again tomorrow.', 'front-editor'), ['status' => 429]);
    }
    return true;
  }

  /**
   * Give the request back when the provider call failed
   *
   * @param int $user_id
   * @return void
   */
  public static function release($user_id = 0)
  {
    if (!self::is_unlimited($user_id)) {
      self::increment(self::key($user_id), -1);
    }
  }

  private static function key($user_id)
  {
    return 'fus_ai_usage_' . md5(self::identity($user_id) . gmdate('Y-m-d'));
  }

  /**
   * Atomic with a persistent object cache, transient fallback otherwise
   *
   * @param string $key
   * @param int $step
   * @return int new value
   */
  private static function increment($key, $step)
  {
    if (wp_using_ext_object_cache()) {
      wp_cache_add($key, 0, self::CACHE_GROUP, DAY_IN_SECONDS);
      $value = $step > 0 ? wp_cache_incr($key, $step, self::CACHE_GROUP) : wp_cache_decr($key, -$step, self::CACHE_GROUP);
      return intval($value);
    }

    $value = max(0, intval(get_transient($key)) + $step);
    set_transient($key, $value, DAY_IN_SECONDS);
    return $value;
  }
}
