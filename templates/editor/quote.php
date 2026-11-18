<?php
    $centerClass = (isset($data['alignment']) && $data['alignment'] === 'center') ? 'bfe-quote--center' : '';
?>
<!-- wp:quote -->
<blockquote class="wp-block-quote bfe-quote <?= esc_attr($centerClass) ?>"><cite><?= wp_kses_post($data['text'] ?? '') ?></cite><p><?= wp_kses_post($data['caption'] ?? '') ?></p></blockquote>
<!-- /wp:quote -->