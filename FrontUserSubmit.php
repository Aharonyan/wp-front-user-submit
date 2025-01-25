<?php

namespace BFE;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Core
 */
class FrontUserSubmit
{

  /**
   * The init
   */
  public static function init()
  {

    define('FE_PLUGIN_URL', plugins_url('', __FILE__));
    define('FE_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__));
    define('FE_Template_PATH', plugin_dir_path(__FILE__) . 'templates/');

    add_action('init', [__CLASS__, 'true_load_plugin_textdomain']);

    add_action('plugins_loaded', [__CLASS__, 'add_components']);

    add_filter('post_row_actions', [__CLASS__, 'add_link_to_edit_this_post'], 10, 2);

    add_action('BFE_activate', [__CLASS__, 'activate_user_ability_to_upload_files']);

    add_action('BFE_deactivate', [__CLASS__, 'disable_user_ability_to_upload_files']);
  }


  /**
   * Add Components
   */
  public static function add_components()
  {
    require_once FE_PLUGIN_DIR_PATH . '/vendor/autoload.php';
    require_once FE_PLUGIN_DIR_PATH . '/functions.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/MenuSettings.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/Shortcodes.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/UserAdmin.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/SavePost.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/Blocks.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/Editor.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/EditorWidget.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/Form.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/Notifications.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/WooIntegration.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/LoginRegisterShortcode.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/FormMetaBox.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/FormMetaBox.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/shortcodes/FUSGoogleMapsShortcode.php';
    require_once FE_PLUGIN_DIR_PATH . '/inc/shortcodes/FUSCustomFieldContent.php';
    


    add_action('wp_enqueue_scripts', [__CLASS__, 'add_scripts']);
    add_action('admin_footer', [__CLASS__, 'add_live_chat_to_admin_pages']);
  }


  /**
   * Add languages
   *
   * @return void
   */
  public static function true_load_plugin_textdomain()
  {
    load_plugin_textdomain('front-editor', false, dirname(plugin_basename(__FILE__)) . '/languages/');
  }

  /**
   * Add scripts
   */
  public static function add_scripts()
  {
    $asset = require FE_PLUGIN_DIR_PATH . 'build/front.asset.php';

    wp_register_script('bfee-editor.js', FE_PLUGIN_URL . '/build/front.js', array('jquery'), $asset['version'], true);

    wp_register_style(
      'bfe-block-style',
      FE_PLUGIN_URL . '/build/frontStyle.css',
      [],
      $asset['version']
    );

    $asset = require FE_PLUGIN_DIR_PATH . 'build/useradmin.asset.php';

    wp_register_script(
      'useradmin',
      FUS__PLUGIN_URL . 'build/useradmin.js',
      array(),
      $asset['version']
    );

    wp_localize_script('useradmin', 'useradmin', ['text' => __('Are you sure you want to delete this post?', 'front-editor')]);
  }

  /**
   * added link wp admin post archive
   *
   * @param [type] $actions
   * @param [type] $post
   * @return void
   */
  public static function add_link_to_edit_this_post($actions, $post)
  {
    if ($post->post_type !== 'fe_post_form') {
      if ($edit_link = Editor::get_post_edit_link($post->ID)) {
        $actions['bfe_front_editor_link'] = sprintf(
          '<a target="_blank" style="color:#388ffe;" href="%s">%s</a>',
          $edit_link,
          __('Edit in front editor', 'front-editor')
        );
      }
    }

    /**
     * Changing postForm archive page edit link
     */
    if ($post->post_type === 'fe_post_form') {
      $actions['edit'] = sprintf(
        '<a target="_blank" href="%s">%s</a>',
        home_url(sprintf('/wp-admin/edit.php?page=fe-post-forms&action=edit&id=%s', $post->ID)),
        __('Edit', 'front-editor')
      );

      unset($actions['inline hide-if-no-js']);
    }

    return $actions;
  }

  public static function activate_user_ability_to_upload_files()
  {
    $contributor = get_role('subscriber');
    $contributor->add_cap('upload_files');
  }

  public static function disable_user_ability_to_upload_files()
  {
    $contributor = get_role('subscriber');
    $contributor->remove_cap('upload_files');
  }

  public static function add_live_chat_to_admin_pages()
  {
    $admin_urls = [
      'fe-post-forms',
      'fe-global-settings',
      'front_editor_settings-affiliation',
      'front_editor_settings-contact',
      'front_editor_settings-pricing'
    ];

    $chat_script = "<script>window.replainSettings = { id: 'a9e898d6-1341-4462-8045-ce4eb7392d6c' };(function(u){var s=document.createElement('script');s.async=true;s.src=u;var x=document.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);})('https://widget.replain.cc/dist/client.js');</script>";

    foreach ($admin_urls as $url) {
      $current_page = fus_get_current_admin_url();
      if ($current_page == admin_url(sprintf('admin.php?page=%s', $url))) {
        echo $chat_script;
      }
    }

    if (isset($_GET['page'])) {
      if ($_GET['page'] == 'fe-post-forms') {
        echo $chat_script;
      }
    }
  }
}

FrontUserSubmit::init();
