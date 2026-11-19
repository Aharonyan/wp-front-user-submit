<?php

/**
 * Forms on pages served by a page cache (cache plugins, host and CDN caches)
 *
 * A cached page keeps the nonces it was built with. After 12 to 24 hours they expire,
 * and guests get "Cookie check failed" when they upload or send the form. Cached pages,
 * and pages that show the form outside the post content (page builders, widgets), also
 * come without the guest browser cookie: several files uploaded at once then get different
 * browser ids, and only the files of one id are attached to the post. When the nonce of the page
 * may have expired, or a form with uploads finds no browser id, the browser asks this endpoint for fresh
 * nonces and the cookie before the form is used. Pages younger than half the nonce life
 * still have valid nonces, so most views of a cached page need no request.
 *
 * @package BFE;
 */

namespace BFE;

defined('ABSPATH') || exit;

class CachedPages
{
    public static function init()
    {
        add_action('rest_api_init', [__CLASS__, 'register_route']);
    }

    public static function register_route()
    {
        // Called without a nonce: the nonce of the page is the one that may have expired
        register_rest_route('bfe/v1', '/fresh_nonces', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'fresh_nonces'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Nonces for the current visitor, and the guest browser cookie the cached page did not send.
     * They are the same nonces every visitor gets with the page, nothing more.
     *
     * @return \WP_REST_Response
     */
    public static function fresh_nonces()
    {
        if (!is_user_logged_in()) {
            UploadSecurity::visitor_token(true);
        }

        $response = new \WP_REST_Response([
            'rest'  => wp_create_nonce('wp_rest'),
            'login' => wp_create_nonce('fus_ajax_nonce'),
        ]);
        $response->set_headers(wp_get_nocache_headers());

        return $response;
    }

    /**
     * Data the browser needs to see that the page came from a cache
     *
     * @return array
     */
    public static function page_data()
    {
        return [
            'page_time'        => time(),
            // a nonce stays valid between half and all of this time (security plugins may shorten it)
            'nonce_life'       => (int) apply_filters('nonce_life', DAY_IN_SECONDS, 'wp_rest'),
            'fresh_nonces_url' => get_rest_url(null, 'bfe/v1/fresh_nonces'),
            'visitor_cookie'   => UploadSecurity::VISITOR_READY_COOKIE,
        ];
    }
}

CachedPages::init();
