<?php
$url = (string) ($data['file']['url'] ?? '');
$title = (string) ($data['title'] ?? '');
?>
<!-- wp:file {"href":<?php echo wp_json_encode(esc_url_raw($url)); ?>} -->
<div class="wp-block-file"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a><a href="<?php echo esc_url($url); ?>" class="wp-block-file__button" download><?php esc_html_e("Download", 'front-editor') ?></a></div>
<!-- /wp:file -->