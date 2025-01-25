<?php

/**
 * Plugin Name: Front User Submit | Front Editor (Premium)
 * Plugin URI: https://wpfronteditor.com/
 * Description: Have you ever seen websites that allow users to submit posts or other type of content? Do you want to have user-submitted content on your site? Front Editor allow users to submit blog posts to your WordPress site with new frontend block editor EditorJs.
 * Author: Aleksan Aharonyan
 * Author URI: https://github.com/Aharonyan/
 * Developer: Aleksan Aharonyan
 * Developer URI: https://wpfronteditor.com/
 * Text Domain: front-editor
 * Domain Path: /languages
 * PHP requires at least: 7.0
 * WP requires at least: 5.0
 * Tested up to: 6.7.1
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 4.9.0
 * Update URI: https://api.freemius.com
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
    fe_fs()->set_basename( true, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'fe_fs' ) ) {
        // Create a helper function for easy SDK access.
        function fe_fs() {
            global $fe_fs;
            if ( !isset( $fe_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $fe_fs = fs_dynamic_init( array(
                    'id'              => '7886',
                    'slug'            => 'front-editor',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_721b5ebdb9cda3d26691a9fb5c35c',
                    'is_premium'      => true,
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'trial'           => array(
                        'days'               => 3,
                        'is_require_payment' => true,
                    ),
                    'has_affiliation' => 'selected',
                    'menu'            => array(
                        'slug' => 'front_editor_settings',
                    ),
                    'is_live'         => true,
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