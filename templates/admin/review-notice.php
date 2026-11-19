<?php

/**
 * Review request for the site admin (BFE\ReviewNotice::render)
 *
 * @var int $posts_count published posts that came through the forms
 * @var callable $choice_url URL that saves review / later / never
 *
 * @package bfee
 */

defined('ABSPATH') || exit;
?>
<div class="notice notice-info fus-review-notice">
  <p>
    <strong>
      <?php
      printf(
        /* translators: %s: number of posts */
        esc_html(_n('Your Front User Submit forms have brought in %s published post so far.', 'Your Front User Submit forms have brought in %s published posts so far.', $posts_count, 'front-editor')),
        esc_html(number_format_i18n($posts_count))
      );
      ?>
    </strong>
  </p>
  <p>
    <?php esc_html_e('If the plugin saves you time, could you leave a short review on WordPress.org? Reviews are how other site owners find it, and I read every one.', 'front-editor'); ?>
    <br>
    <?php esc_html_e('Aleksan, developer of Front User Submit', 'front-editor'); ?>
  </p>
  <p style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
    <a class="button button-primary" href="<?php echo esc_url($choice_url('review')); ?>" target="_blank" rel="noopener"><?php esc_html_e('Leave a review', 'front-editor'); ?></a>
    <a class="button" href="<?php echo esc_url($choice_url('later')); ?>"><?php esc_html_e('Maybe later', 'front-editor'); ?></a>
    <a href="<?php echo esc_url($choice_url('never')); ?>"><?php esc_html_e("Don't show again", 'front-editor'); ?></a>
  </p>
</div>
<script>
  // the review opens in a new tab, hide the request in this one
  document.querySelectorAll('.fus-review-notice a[target="_blank"]').forEach(function (link) {
    link.addEventListener('click', function () {
      link.closest('.fus-review-notice').style.display = 'none';
    });
  });
</script>
