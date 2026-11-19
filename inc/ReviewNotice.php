<?php

namespace BFE;

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Asks the site admin for a WordPress.org review once the forms have received a few posts
 *
 * @package bfee
 */
class ReviewNotice
{
  const OPTION_NAME = 'fus_review_notice';
  const ACTION = 'fus_review_notice';

  /**
   * Posts that went through a form before we ask: by then the plugin works on the site
   */
  const MIN_POSTS = 5;

  /**
   * "Maybe later" hides the notice for this long
   */
  const SNOOZE = 30 * DAY_IN_SECONDS;

  const REVIEW_URL = 'https://wordpress.org/support/plugin/front-editor/reviews/#new-post';

  /**
   * Screens with the notice: dashboard, post lists, plugins and the forms list.
   * "Maybe later" and "Don't show again" reload the page, so never where unsaved work can be lost:
   * editors, settings pages, and the form builder (the forms page with action=edit or add-new).
   */
  const SCREENS = ['dashboard', 'edit', 'plugins'];
  const FORMS_PAGE = 'fe-post-forms';

  public static function init()
  {
    add_action('admin_notices', [__CLASS__, 'render']);
    add_action('admin_post_' . self::ACTION, [__CLASS__, 'handle_choice']);
  }

  /**
   * Published posts that came through a form (they keep the form ID in BFE_the_post_edited_by).
   * Pending, draft and trashed ones do not count: spam a site had to delete is no reason to ask.
   *
   * @return int
   */
  public static function submitted_posts_count()
  {
    global $wpdb;

    return (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_status = 'publish'",
      'BFE_the_post_edited_by'
    ));
  }

  /**
   * The admin reviewed, said "Don't show again", or asked to be reminded later and that time has not come
   *
   * @return bool
   */
  public static function is_dismissed()
  {
    $state = get_option(self::OPTION_NAME);
    if (!is_array($state)) {
      return false;
    }

    $status = $state['status'] ?? '';
    if ($status === 'done') {
      return true;
    }

    return $status === 'later' && (int) ($state['until'] ?? 0) > time();
  }

  /**
   * @param string $screen_base WP_Screen base of the current admin page
   * @param string $page value of ?page=
   * @param string $action value of ?action=
   * @return bool
   */
  public static function is_notice_screen($screen_base, $page, $action)
  {
    return in_array($screen_base, self::SCREENS, true) || ($page === self::FORMS_PAGE && $action === '');
  }

  public static function render()
  {
    if (!current_user_can('manage_options') || !apply_filters('bfe_show_review_notice', true)) {
      return;
    }

    $screen = get_current_screen();
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
    if (!$screen || !self::is_notice_screen($screen->base, $page, $action) || self::is_dismissed()) {
      return;
    }

    $posts_count = self::submitted_posts_count();
    if ($posts_count < self::MIN_POSTS) {
      return;
    }

    $choice_url = function ($choice) {
      return wp_nonce_url(admin_url('admin-post.php?action=' . self::ACTION . '&choice=' . $choice), self::ACTION);
    };

    require fe_template_path('admin/review-notice.php');
  }

  /**
   * admin-post.php?action=fus_review_notice&choice=review|later|never
   *
   * @return void
   */
  public static function handle_choice()
  {
    check_admin_referer(self::ACTION);
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('Sorry, you are not allowed to do that.', 'front-editor'), '', 403);
    }

    $choice = isset($_GET['choice']) ? sanitize_key(wp_unslash($_GET['choice'])) : '';
    if (!self::save_choice($choice)) {
      wp_die(esc_html__('Unknown choice.', 'front-editor'), '', 400);
    }

    if ($choice === 'review') {
      // our own constant URL on wordpress.org, wp_safe_redirect() would send it to the dashboard
      wp_redirect(self::REVIEW_URL);
      exit;
    }

    wp_safe_redirect(wp_get_referer() ?: admin_url());
    exit;
  }

  /**
   * @param string $choice review and never hide the notice for good, later hides it for SNOOZE
   * @return bool false for an unknown choice
   */
  public static function save_choice($choice)
  {
    if ($choice === 'review' || $choice === 'never') {
      update_option(self::OPTION_NAME, ['status' => 'done']);
      return true;
    }

    if ($choice !== 'later') {
      return false;
    }

    // "Maybe later" in a tab opened before the review link was clicked must not bring the notice back
    $state = get_option(self::OPTION_NAME);
    if (!is_array($state) || ($state['status'] ?? '') !== 'done') {
      update_option(self::OPTION_NAME, ['status' => 'later', 'until' => time() + self::SNOOZE]);
    }
    return true;
  }
}

ReviewNotice::init();
