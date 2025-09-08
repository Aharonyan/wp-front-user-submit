<?php

namespace BFE;

defined('ABSPATH') || exit;

/**
 * Enhanced LoginRegisterShortcodes with AJAX support
 */
class LoginRegisterShortcodes
{
    public static function init()
    {
        // Existing shortcodes
        add_shortcode('fus_form_login', [__CLASS__, 'fus_form_login_shortcode']);
        add_shortcode('fus_form_register', [__CLASS__, 'fus_form_register_shortcode']);

        // Existing form handler (for fallback)
        add_action('init', [__CLASS__, 'fus_handle']);

        // NEW: AJAX handlers
        add_action('wp_ajax_fus_login', [__CLASS__, 'ajax_login_handler']);
        add_action('wp_ajax_nopriv_fus_login', [__CLASS__, 'ajax_login_handler']);

        add_action('wp_ajax_fus_register', [__CLASS__, 'ajax_register_handler']);
        add_action('wp_ajax_nopriv_fus_register', [__CLASS__, 'ajax_register_handler']);

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_ajax_scripts']);
    }

    /**
     * Enqueue AJAX scripts (CORRECTED VERSION)
     */
    public static function enqueue_ajax_scripts()
    {
        // Don't enqueue in admin
        if (is_admin()) {
            return;
        }

        // Get asset file (with error handling)
        $asset_file = FE_PLUGIN_DIR_PATH . 'build/loginRegister.asset.php';
        $asset = file_exists($asset_file) ? require $asset_file : ['version' => '1.0.0', 'dependencies' => []];

        // Register and enqueue JavaScript
        wp_register_script(
            'fus-login-register',
            plugins_url('build/loginRegister.js', dirname(__FILE__)), // Fixed path to build folder
            $asset['dependencies'], // Use dependencies from asset file
            $asset['version'],
            true
        );

        // Register and enqueue CSS
        wp_register_style(
            'fus-login-register-style',
            plugins_url('build/loginRegisterStyle.css', dirname(__FILE__)),
            [],
            $asset['version']
        );

        // Localize script (fixed handle name to match registered script)
        wp_localize_script('fus-login-register', 'fusAjaxConfig', [ // Changed from 'fus-ajax' to 'fus-login-register'
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fus_ajax_nonce'),
            'messages' => [
                'processing' => __('Processing...', 'front-editor'),
                'error' => __('An error occurred. Please try again.', 'front-editor'),
                'networkError' => __('Network error. Please check your connection.', 'front-editor'),
                'success' => __('Success!', 'front-editor')
            ]
        ]);

        // IMPORTANT: Actually enqueue both script AND style
        wp_enqueue_script('fus-login-register');
        wp_enqueue_style('fus-login-register-style'); // ← YOU WERE MISSING THIS!
    }

    // Existing shortcode methods (unchanged)
    public static function fus_form_login_shortcode($atts, $content = false)
    {
        $atts = shortcode_atts(array(
            'redirect' => false,
            'ajax' => true // NEW: Default to AJAX
        ), $atts);
        return self::get_fus_form_login($atts['redirect'], $atts['ajax']);
    }

    public static function fus_form_register_shortcode($atts, $content = false)
    {
        $atts = shortcode_atts(array(
            'redirect' => false,
            'ajax' => true // NEW: Default to AJAX
        ), $atts);
        return self::get_fus_form_register($atts['redirect'], $atts['ajax']);
    }

    /**
     * Enhanced login form with AJAX support
     */
    public static function get_fus_form_login($redirect = false, $ajax = true)
    {
        global $fus_form_count;
        ++$fus_form_count;

        if (!is_user_logged_in()) {
            ob_start();

            // Pass AJAX flag to template
            $fus_ajax_enabled = $ajax;
            $fus_form_id = $fus_form_count;
            $fus_redirect = $redirect;

            require fe_template_path('login-form.php');
            return ob_get_clean();
        }
    }

    /**
     * Enhanced register form with AJAX support
     */
    public static function get_fus_form_register($redirect = false, $ajax = true)
    {
        global $fus_form_count;
        ++$fus_form_count;

        if (!is_user_logged_in()) {
            ob_start();

            // Pass AJAX flag to template
            $fus_ajax_enabled = $ajax;
            $fus_form_id = $fus_form_count;
            $fus_redirect = $redirect;

            require fe_template_path('register-form.php');
            return ob_get_clean();
        }

        return __('User is logged in.', 'front-editor');
    }

    /**
     * NEW: AJAX Login Handler
     */
    public static function ajax_login_handler()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'fus_ajax_nonce')) {
            wp_send_json_error([
                'message' => __('Security error, please refresh page', 'front-editor')
            ]);
        }

        $response = self::process_login($_POST);

        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }

    /**
     * NEW: AJAX Register Handler
     */
    public static function ajax_register_handler()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'fus_ajax_nonce')) {
            wp_send_json_error([
                'message' => __('Security error, please refresh page', 'front-editor')
            ]);
        }

        $response = self::process_register($_POST);

        if ($response['success']) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error($response);
        }
    }

    /**
     * Extracted login processing logic
     */
    private static function process_login($data)
    {
        if (empty($data['fus_username'])) {
            return [
                'success' => false,
                'message' => __('<strong>ERROR</strong>: Empty username', 'front-editor')
            ];
        }

        if (empty($data['fus_password'])) {
            return [
                'success' => false,
                'message' => __('<strong>ERROR</strong>: Empty password', 'front-editor')
            ];
        }

        $creds = array();
        $creds['user_login'] = $data['fus_username'];
        $creds['user_password'] = $data['fus_password'];

        if (isset($data['rememberme'])) {
            $creds['remember'] = true;
        }

        $user = wp_signon($creds);

        if (is_wp_error($user)) {
            return [
                'success' => false,
                'message' => $user->get_error_message()
            ];
        }

        return [
            'success' => true,
            'message' => __('Login successful', 'front-editor'),
            'redirect' => !empty($data['redirect']) ? $data['redirect'] : home_url()
        ];
    }

     /**
     * NEW: Get current form design from settings
     */
    public static function get_current_form_design()
    {
        $options = get_option('bfe_general_settings_login_register_group_options', []);
        $selected_design = $options['form_design'] ?? 'modern-minimal';
        
        // Check if user has pro version for premium designs
        $has_pro = function_exists('fe_fs') && fe_fs()->can_use_premium_code__premium_only();
        
        // Pro designs list
        $pro_designs = [
            'card-panel', 'split-screen', 'gradient-bg', 'glassmorphism', 
            'borderless-flat', 'corporate', 'playful-colorful'
        ];
        
        // If selected design is pro but user doesn't have pro, fallback to default
        if (in_array($selected_design, $pro_designs) && !$has_pro) {
            return 'modern-minimal';
        }
        
        return $selected_design;
    }

    /**
     * NEW: Get design CSS class name
     */
    public static function get_design_css_class($design = null)
    {
        if (!$design) {
            $design = self::get_current_form_design();
        }
        
        // Convert design key to CSS class
        return 'fus-design-' . $design;
    }

    /**
     * NEW: Extracted register processing logic
     */
    private static function process_register($data)
    {
        if (empty($data['fus_username'])) {
            return [
                'success' => false,
                'message' => __('<strong>ERROR</strong>: Empty username', 'front-editor')
            ];
        }

        if (empty($data['fus_email'])) {
            return [
                'success' => false,
                'message' => __('<strong>ERROR</strong>: Empty email', 'front-editor')
            ];
        }

        $creds = [];
        $creds['user_login'] = sanitize_text_field($data['fus_username']);
        $creds['user_email'] = sanitize_email($data['fus_email']);
        $creds['username'] = $creds['user_login'];
        $creds['user_password'] = wp_generate_password();
        $creds['user_pass'] = $creds['user_password'];
        $creds['role'] = get_option('default_role');

        if (isset($data['first_name'])) {
            $creds['first_name'] = sanitize_text_field($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $creds['last_name'] = sanitize_text_field($data['last_name']);
        }
        if (isset($data['website'])) {
            $creds['user_url'] = sanitize_url($data['website']);
        }

        $user = wp_insert_user($creds);

        if (is_wp_error($user)) {
            return [
                'success' => false,
                'message' => $user->get_error_message()
            ];
        }

        return [
            'success' => true,
            'message' => __('Registration successful. Your password will be sent via email shortly.', 'front-editor'),
            'redirect' => !empty($data['redirect']) ? $data['redirect'] : home_url()
        ];
    }

    // Keep existing methods for backward compatibility
    public static function fus_handle()
    {
        if (!isset($_POST['fus_register_nonce']) || !wp_verify_nonce($_POST['fus_register_nonce'], 'fus_register_nonce')) {
            self::set_fus_error(__('Security error, please update page', 'front-editor'));
            return;
        }

        $success = false;
        if (isset($_REQUEST['fus_action'])) {
            switch ($_REQUEST['fus_action']) {
                case 'login':
                    $result = self::process_login($_POST);
                    if ($result['success']) {
                        self::set_fus_success($result['message'], $_REQUEST['fus_form']);
                        $success = true;
                    } else {
                        self::set_fus_error($result['message'], $_REQUEST['fus_form']);
                    }
                    break;

                case 'register':
                    $result = self::process_register($_POST);
                    if ($result['success']) {
                        self::set_fus_success($result['message'], $_REQUEST['fus_form']);
                        $success = true;
                    } else {
                        self::set_fus_error($result['message'], $_REQUEST['fus_form']);
                    }
                    break;
            }
        }

        // Handle redirects (existing logic)
        if ($success && !empty($_REQUEST['redirect'])) {
            wp_safe_redirect($_REQUEST['redirect']);
            exit;
        }
    }

    // Keep existing error/success methods unchanged
    public static function set_fus_error($error, $id = 0)
    {
        $_SESSION['fus_error_' . $id] = $error;
    }

    public static function the_fus_error($id = 0)
    {
        echo self::get_fus_error($id);
    }

    public static function get_fus_error($id = 0)
    {
        if (isset($_SESSION['fus_error_' . $id])) {
            if ($_SESSION['fus_error_' . $id]) {
                $return = $_SESSION['fus_error_' . $id];
                unset($_SESSION['fus_error_' . $id]);
                return $return;
            }
        }
        return false;
    }

    public static function set_fus_success($error, $id = 0)
    {
        $_SESSION['fus_success_' . $id] = $error;
    }

    public static function the_fus_success($id = 0)
    {
        echo self::get_fus_success($id);
    }

    public static function get_fus_success($id = 0)
    {
        if (isset($_SESSION['fus_success_' . $id])) {
            if ($_SESSION['fus_success_' . $id]) {
                $return = $_SESSION['fus_success_' . $id];
                unset($_SESSION['fus_success_' . $id]);
                return $return;
            }
        }
        return false;
    }
}

LoginRegisterShortcodes::init();
