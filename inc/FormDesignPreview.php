<?php

namespace BFE;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Form designs and the "Preview on my form" page
 *
 * @package bfee
 */
class FormDesignPreview
{
  const QUERY_VAR = 'fus_design_preview';
  const NONCE_ACTION = 'fus_design_preview_';

  public static function init()
  {
    add_action('template_redirect', [__CLASS__, 'render_preview']);
  }

  /**
   * Designs offered in the form settings, keyed by the form_theme value
   *
   * @return array
   */
  public static function get_designs()
  {
    $img = FE_PLUGIN_URL . '/assets/img/';

    return [
      'clean' => [
        'name' => __('Clean', 'front-editor'),
        'pro' => false,
        'preview' => $img . 'form-clean-preview.png'
      ],
      'default_2' => [
        'name' => __('Simple Design', 'front-editor'),
        'pro' => false,
        'preview' => $img . 'simple-design-preview.png'
      ],
      'default' => [
        'name' => __('Modern Design', 'front-editor'),
        'pro' => false,
        'preview' => $img . 'modern-design-preview.png'
      ],
      'no_style' => [
        'name' => __('Minimum Style', 'front-editor'),
        'pro' => false,
        'preview' => $img . 'minimum-style-preview.png'
      ],
      'modern-minimal' => [
        'name' => __('Modern Minimal', 'front-editor'),
        'pro' => true,
        'preview' => $img . 'form-modern-minimal-preview.png'
      ],
      'card-panel' => [
        'name' => __('Card/Panel Style', 'front-editor'),
        'pro' => true,
        'preview' => $img . 'form-card-panel-preview.png'
      ],
      'corporate-professional' => [
        'name' => __('Corporate/Professional', 'front-editor'),
        'pro' => true,
        'preview' => $img . 'form-corporate-preview.png'
      ],
      'borderless-flat' => [
        'name' => __('Borderless/Flat', 'front-editor'),
        'pro' => true,
        'preview' => $img . 'form-borderless-flat-preview.png'
      ]
    ];
  }

  /**
   * Front-end URL that shows the form in the given design
   *
   * @param int    $form_id
   * @param string $design
   * @return string
   */
  public static function get_preview_url(int $form_id, $design)
  {
    return wp_nonce_url(
      add_query_arg([self::QUERY_VAR => $form_id, 'design' => $design], home_url('/')),
      self::NONCE_ACTION . $form_id
    );
  }

  /**
   * Render the preview page and stop, only for form managers
   */
  public static function render_preview()
  {
    if (!isset($_GET[self::QUERY_VAR])) {
      return;
    }

    $form_id = intval($_GET[self::QUERY_VAR]);
    $design = sanitize_key(wp_unslash($_GET['design'] ?? ''));
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? ''));

    if (!current_user_can(fe_admin_role()) || !wp_verify_nonce($nonce, self::NONCE_ACTION . $form_id)) {
      wp_die(esc_html__('You are not allowed to preview this form.', 'front-editor'), '', ['response' => 403]);
    }

    $designs = self::get_designs();
    if (get_post_type($form_id) !== 'fe_post_form' || !isset($designs[$design])) {
      wp_die(esc_html__('Form or design not found.', 'front-editor'), '', ['response' => 404]);
    }

    add_filter('bfe_form_theme', function () use ($design) {
      return $design;
    });
    add_filter('show_admin_bar', '__return_false');

    $preview_design = $designs[$design];
    $has_pro = fe_fs()->can_use_premium_code__premium_only();
    // Render first, so the form enqueues its scripts and styles before wp_head()
    $form_html = do_shortcode(sprintf('[fe_form id="%d"]', $form_id));

    require fe_template_path('design-preview.php');
    exit;
  }
}

FormDesignPreview::init();
