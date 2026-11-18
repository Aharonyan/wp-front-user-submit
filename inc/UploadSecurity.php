<?php

/**
 * Upload validation and safe serving of uploaded files
 *
 * @package BFE;
 */

namespace BFE;

defined('ABSPATH') || exit;

class UploadSecurity
{
    /**
     * Types a browser can execute scripts in, never stored or served from front uploads
     */
    const UNSAFE_MIME_TYPES = [
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'text/xml',
        'application/xml',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'application/x-shockwave-flash',
    ];

    const FALLBACK_MIME_TYPE = 'application/octet-stream';

    /**
     * Guest uploads per visitor and hour (filter bfe_guest_upload_limit_per_hour, 0 = no limit)
     */
    const GUEST_UPLOADS_PER_HOUR = 50;

    /**
     * Guest uploads per IP address and hour (filter bfe_guest_upload_limit_per_ip_hour, 0 = no limit).
     * Higher than the per-visitor limit: behind a proxy many visitors can share one address.
     */
    const GUEST_UPLOADS_PER_IP_HOUR = 500;

    /**
     * Cookie with a random id of a guest browser, ties guest uploads to the guest who made them
     */
    const VISITOR_COOKIE = 'fus_visitor';

    /**
     * Random id of the current guest browser, created (and sent as cookie) when missing
     *
     * @return string
     */
    public static function visitor_token()
    {
        $token = isset($_COOKIE[self::VISITOR_COOKIE]) ? (string) $_COOKIE[self::VISITOR_COOKIE] : '';
        if (preg_match('/^[a-f0-9]{32}$/', $token)) {
            return $token;
        }

        $token = bin2hex(random_bytes(16));
        $_COOKIE[self::VISITOR_COOKIE] = $token;
        if (!headers_sent()) {
            setcookie(self::VISITOR_COOKIE, $token, [
                'expires'  => time() + DAY_IN_SECONDS,
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN ?: '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return $token;
    }

    /**
     * Value stored with a guest upload: a hash of the guest browser id (the id itself stays secret)
     *
     * @return string
     */
    public static function guest_upload_marker()
    {
        return wp_hash('fus_guest_upload|' . self::visitor_token());
    }

    /**
     * Count a guest upload; false when the visitor or their IP reached the hourly limit
     *
     * @return bool
     */
    public static function guest_upload_allowed()
    {
        $limits = [
            'fus_guest_uploads_v_' . md5(self::visitor_token()) => (int) apply_filters('bfe_guest_upload_limit_per_hour', self::GUEST_UPLOADS_PER_HOUR),
            'fus_guest_uploads_' . md5(FormMetaBox::get_user_ip_address()) => (int) apply_filters('bfe_guest_upload_limit_per_ip_hour', self::GUEST_UPLOADS_PER_IP_HOUR),
        ];

        foreach ($limits as $key => $limit) {
            if ($limit > 0 && (int) get_transient($key) >= $limit) {
                return false;
            }
        }

        foreach ($limits as $key => $limit) {
            if ($limit > 0) {
                set_transient($key, (int) get_transient($key) + 1, HOUR_IN_SECONDS);
            }
        }

        return true;
    }

    /**
     * Check the real file content, not only the name or the client sent type
     *
     * @param string $tmp_path uploaded file path.
     * @param string $file_name original file name.
     * @return array|\WP_Error ['ext' => string, 'type' => string, 'name' => string]
     */
    public static function validate_file($tmp_path, $file_name)
    {
        if (empty($tmp_path) || !is_file($tmp_path)) {
            return new \WP_Error('upload_error', __('File is not exist', 'front-editor'), ['status' => 400]);
        }

        $file_name = sanitize_file_name($file_name);
        $checked   = wp_check_filetype_and_ext($tmp_path, $file_name);

        if (empty($checked['ext']) || empty($checked['type'])) {
            return new \WP_Error('upload_error', __('Sorry, this file type is not permitted.', 'front-editor'), ['status' => 415]);
        }

        if (self::is_unsafe_mime_type($checked['type']) || self::is_unsafe_mime_type(self::sniff_mime_type($tmp_path))) {
            return new \WP_Error('upload_error', __('Sorry, this file type is not permitted.', 'front-editor'), ['status' => 415]);
        }

        return [
            'ext'  => $checked['ext'],
            'type' => $checked['type'],
            'name' => empty($checked['proper_filename']) ? $file_name : $checked['proper_filename'],
        ];
    }

    /**
     * @param string $mime_type
     * @return bool
     */
    public static function is_unsafe_mime_type($mime_type)
    {
        $mime_type = strtolower(trim(explode(';', (string) $mime_type)[0]));

        return in_array($mime_type, self::UNSAFE_MIME_TYPES, true);
    }

    /**
     * Content-Type to serve a stored file with: taken from its extension like the web
     * server does for the uploads directory, never from the file bytes
     *
     * @param string $path stored file path.
     * @return string
     */
    public static function serve_mime_type($path)
    {
        $type = wp_check_filetype(basename($path))['type'];

        if (empty($type) || self::is_unsafe_mime_type($type) || self::is_unsafe_mime_type(self::sniff_mime_type($path))) {
            return self::FALLBACK_MIME_TYPE;
        }

        return $type;
    }

    /**
     * Send a stored file as a download that the browser never renders
     *
     * @param string $path stored file path.
     * @return void
     */
    public static function send_file($path)
    {
        $file_name = str_replace(['"', '\\', "\r", "\n"], '', sanitize_file_name(basename($path)));

        header('Access-Control-Expose-Headers: Content-Disposition');
        header('Content-Type: ' . self::serve_mime_type($path));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: sandbox; default-src 'none'");
        readfile($path);
    }

    /**
     * @param string $path
     * @return string
     */
    private static function sniff_mime_type($path)
    {
        if (!function_exists('finfo_open')) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type  = finfo_file($finfo, $path);
        finfo_close($finfo);

        return (string) $type;
    }
}
