<?php

/**
 * Plugin Name: Front User Submit – Frontend Post Submission, Guest Posting & AI Writing Assistant
 * Plugin URI: https://wpfronteditor.com/
 * Description: Let users submit and edit posts from the front end: guest posting, drag-and-drop form builder, EditorJS / Markdown / TinyMCE editors and an AI writing assistant (OpenAI, Claude, Gemini, OpenRouter) with your own API key.
 * Author: Aleksan Aharonyan
 * Author URI: https://github.com/Aharonyan/
 * Developer: Aleksan Aharonyan
 * Developer URI: https://wpfronteditor.com/
 * Text Domain: front-editor
 * Domain Path: /languages
 * WP requires at least: 5.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 5.1.0
 */
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
define( 'FUS__PLUGIN_FILE', __FILE__ );
define( 'FUS__PLUGIN_DIR', plugin_dir_path( FUS__PLUGIN_FILE ) );
define( 'FUS__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once FUS__PLUGIN_DIR . '/inc/DemoData.php';
/**
 * On plugin activate
 *
 * @return void
 */
register_activation_hook( __FILE__, function () {
    do_action( 'BFE_activate' );
} );
/**
 * On plugin deactivate
 *
 * @return void
 */
register_deactivation_hook( __FILE__, function () {
    do_action( 'BFE_deactivate' );
} );
if ( function_exists( 'fe_fs' ) ) {
    fe_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'fe_fs' ) ) {
        // Create a helper function for easy SDK access.
        function fe_fs() {
            global $fe_fs;
            if ( !isset( $fe_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';
                $fe_fs = fs_dynamic_init( array(
                    'id'               => '7886',
                    'slug'             => 'front-editor',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_721b5ebdb9cda3d26691a9fb5c35c',
                    'is_premium'       => false,
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'trial'            => array(
                        'days'               => 3,
                        'is_require_payment' => true,
                    ),
                    'has_affiliation'  => 'selected',
                    'menu'             => array(
                        'slug' => 'front_editor_settings',
                    ),
                    'is_live'          => true,
                    'is_org_compliant' => true,
                ) );
            }
            return $fe_fs;
        }

        // Init Freemius.
        fe_fs();
        // Signal that SDK was initiated.
        do_action( 'fe_fs_loaded' );
    }
}
require_once __DIR__ . '/FrontUserSubmit.php';