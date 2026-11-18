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
