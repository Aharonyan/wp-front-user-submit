<?php


/**
 * Gutenberg block to display Post Form.
 *
 * @package BFE;
 */

namespace BFE;

defined('ABSPATH') || exit;

/**
 * Class Post Form - registers custom gutenberg block.
 */
class LoginRegisterShortcodes
{
    public static function init()
    {
        add_shortcode('fus_form_login', [__CLASS__, 'fus_form_login_shortcode']);

        add_shortcode('fus_form_register', [__CLASS__, 'fus_form_register_shortcode']);

        add_action('init', [__CLASS__, 'fus_handle']);
    }

    public static function fus_form_login_shortcode($atts, $content = false)
    {
        $atts = shortcode_atts(array(
            'redirect' => false
        ), $atts);
        return self::get_fus_form_login($atts['redirect']);
    }

    public static function fus_form_register_shortcode($atts, $content = false)
    {
        $atts = shortcode_atts(array(
            'redirect' => false
        ), $atts);
        return self::get_fus_form_register($atts['redirect']);
    }

    /**
     * Get login form
     *
     * @param boolean $redirect
     * @return void
     */
    public static function get_fus_form_login($redirect = false)
    {
        global $fus_form_count;
        ++$fus_form_count;
        if (!is_user_logged_in()) {
            ob_start();
            require fe_template_path('login-form.php');

            return ob_get_clean();
        }
    }

    public static function get_fus_form_register($redirect = false)
    {
        global $fus_form_count;
        ++$fus_form_count;
        if (!is_user_logged_in()) {
            ob_start();
            require fe_template_path('register-form.php');
            
            return ob_get_clean();
        }

        return __('User is logged in.', 'front-editor');
    }

    public static function fus_handle()
    {
        if (!isset($_POST['fus_register_nonce']) || !wp_verify_nonce($_POST['fus_register_nonce'], 'fus_register_nonce')) {
            self::set_fus_error(__('Security error, please update page', 'front-editor'));
        }

        $success = false;
        if (isset($_REQUEST['fus_action'])) {
            switch ($_REQUEST['fus_action']) {
                case 'login':
                    if (!$_POST['fus_username']) {
                        self::set_fus_error(__('<strong>ERROR</strong>: Empty username', 'front-editor'), $_REQUEST['fus_form']);
                    } else if (!$_POST['fus_password']) {
                        self::set_fus_error(__('<strong>ERROR</strong>: Empty password', 'front-editor'), $_REQUEST['fus_form']);
                    } else {
                        $creds = array();
                        $creds['user_login'] = $_POST['fus_username'];
                        $creds['user_password'] = $_POST['fus_password'];
                        if (isset($_POST['rememberme'])) {
                            $creds['remember'] = true;
                        }
                        $user = wp_signon($creds);
                        if (is_wp_error($user)) {
                            self::set_fus_error($user->get_error_message(), $_REQUEST['fus_form']);
                        } else {
                            self::set_fus_success(__('Log in successful', 'front-editor'), $_REQUEST['fus_form']);
                            $success = true;
                        }
                    }
                    break;
                case 'register':
                    if (!$_POST['fus_username']) {
                        self::set_fus_error(__('<strong>ERROR</strong>: Empty username', 'front-editor'), $_REQUEST['fus_form']);
                    } else if (!$_POST['fus_email']) {
                        self::set_fus_error(__('<strong>ERROR</strong>: Empty email', 'front-editor'), $_REQUEST['fus_form']);
                    } else {
                        $creds = [];
                        $creds['user_login'] = sanitize_text_field($_POST['fus_username']);
                        $creds['user_email'] = sanitize_email($_POST['fus_email']);
                        $creds['username'] = $creds['user_login'];
                        $creds['user_password'] = wp_generate_password();
                        $creds['user_pass'] = $creds['user_password'];
                        $creds['role'] = get_option('default_role');
                        if (isset($_POST['first_name'])) {
                            $creds['first_name'] = sanitize_text_field($_POST['first_name']);
                        }
                        if (isset($_POST['last_name'])) {
                            $creds['last_name'] = sanitize_text_field($_POST['last_name']);
                        }
                        if (isset($_POST['website'])) {
                            $creds['user_url'] = sanitize_url($_POST['website']);
                        }

                        $creds['remember'] = true;
                        $user = wp_insert_user($creds);
                        if (is_wp_error($user)) {
                            self::set_fus_error($user->get_error_message(), $_REQUEST['fus_form']);
                            break;
                        }

                        self::set_fus_success(__('Registration successful. Your password will be sent via email shortly.', 'front-editor'), $_REQUEST['fus_form']);
                        $settings = get_option('bfe_general_settings_login_register_group_options');
                        $creds['subject'] = '[blog_name] Registration successful';
                        $creds['message'] =  sprintf('Hi [username],%sLogin: [user_login]%sPassword: [user_password]', PHP_EOL, PHP_EOL);
                        if(isset($settings['registration_email_content_field']) && !empty($settings['registration_email_content_field'])){
                            $creds['subject'] = $settings['registration_email_content_field']['subject'];
                            $creds['message'] = $settings['registration_email_content_field']['message'];
                        }

                        self::send_email($user,$creds);
                        
                        
                        $success = true;

                        self::login_user_by_id($user);
                    }
                    break;
            }

            // if redirect is set and action was successful
            if ($success) {
                if (isset($_REQUEST['redirect']) && $_REQUEST['redirect']) {
                    wp_redirect($_REQUEST['redirect']);
                    exit;
                }
            }
        }
    }

    public static function login_user_by_id($user_id)
    {
        $user = get_user_by('id', $user_id);

        if ($user) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Optional: Update the user's last login time
            update_user_meta($user_id, 'last_login', current_time('mysql'));

            return true;
        }

        return false;
    }

    public static function send_email($user_id, $settings)
    {
        $settings['from_email'] = get_bloginfo('admin_email') ?? '';
        $settings['from_name'] = get_bloginfo('name');
        $settings['siteurl'] = home_url();
        $settings['blog_name'] = home_url();

        $message = $settings['message'];
        // Replace variables in text
        foreach ($settings as $key => $value) {
            $message = preg_replace(sprintf('/\[%s]/', $key), $value, $message);
        }

        $subject = $settings['subject'];
        // the same for subject
        foreach ($settings as $key => $value) {
            $subject = preg_replace(sprintf('/\[%s]/', $key), $value, $subject);
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        if (!empty($settings['from_email'])) {
            $headers[] = sprintf('From: %s <%s>', $settings['from_name'], $settings['from_email']);
        }

        wp_mail($settings['user_email'], $subject, nl2br($message), $headers);
    }

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
