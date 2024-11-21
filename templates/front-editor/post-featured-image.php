<?php
$thumb_id = 0;
if (has_post_thumbnail($post_id)) {
    $thumb_id = get_post_thumbnail_id($post_id);
    $style = sprintf('style="background:url(%s)"', wp_get_attachment_url($thumb_id));
    $class = 'chosen';
    $thumb_exist = 1;
}
?>
<div class="fus-editor-js-field-wrap thumb-img-wrap featured_image">
    <?php
    $required = '';
    if (isset($field['required'])) {
        if ($field['required']) {
            $required = '<span class="required">*</span>';
        }
    }
    if (isset($field['label'])) {
        printf('<label for="img_inp" class="editor-js-label">%s %s</label>', esc_html($field['label']),$required);
    }
    ?>
    <input type="hidden" name="post_image_required" value="<?= $field['required'] ? 1 : 0; ?>">
    <input type="hidden" id="post_image_wp_media_uploader" name="post_image_wp_media_uploader" value="<?= $field['wp_media_uploader'] ? 1 : 0; ?>">
    <input type="hidden" id="thumb_img_id" name="thumb_img_id" value="<?= $thumb_id ?? 0 ?>">
    <div class="image_loader editor-button <?= $class ?? '' ?>" thumb_exist="<?= $thumb_exist ?? 0 ?>">
        <input name="post_thumbnail" type='file' id="img_inp" accept="image/*" title="<?php echo __('Set featured image', 'front-editor'); ?>" />
        <label class="thumbnail" for="img_inp">
            <img src="<?= FE_PLUGIN_URL . '/assets/img/photo.svg' ?>" class="button-icon">
        </label>
        <img <?= $style ?? '' ?> id="post_thumbnail_image" src="data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" />
        <img src="<?= FE_PLUGIN_URL . '/assets/img/cancel.svg' ?>" class="bfe-remove-image">
        <div class="fus-loader"></div>
    </div>
    <div class="thumb-error-message"></div>
</div>