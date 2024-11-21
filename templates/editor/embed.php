<!-- wp:core-embed -->
<div class="bfe-embed">
    <iframe src="<?= $data['embed'] ?>" style="width:100%; height:<?= $data['height'] ?>px;" scrolling="no" frameborder="no">
    </iframe>
    <?php if (!empty($data['caption'])) : ?>
        <footer class="bfe-embed-caption"><?= $data['caption'] ?></footer>
    <?php endif; ?>
</div>
<!-- /wp:core-embed -->