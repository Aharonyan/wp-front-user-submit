<!-- wp:core-embed -->
<div class="bfe-embed">
    <iframe src="<?= esc_url($data['embed'] ?? '') ?>" style="width:100%; height:<?= absint($data['height'] ?? 320) ?>px;" scrolling="no" frameborder="no">
    </iframe>
    <?php if (!empty($data['caption'])) : ?>
        <footer class="bfe-embed-caption"><?= wp_kses_post($data['caption']) ?></footer>
    <?php endif; ?>
</div>
<!-- /wp:core-embed -->