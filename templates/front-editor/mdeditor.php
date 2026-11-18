<?php

use League\HTMLToMarkdown\HtmlConverter;
use BFE\Editor;

$placeholder = $field['label'];
if (isset($field['placeholder'])) {
    $placeholder = $field['placeholder'];
}
$required = '';
if (isset($field['required'])) {
    if ($field['required']) {
        $required = '<span class="required">*</span>';
    }
}

$content = Editor::get_field_content($post_id,$field);

$converter = new HtmlConverter();
$content = $converter->convert($content);

?>
<div class="fus-wrap fus-md-editor-field-wrap <?= esc_attr($field['name']) ?>">
    <?php if (isset($field['label'])): ?>
        <label for="<?= esc_attr($field['name']) ?>" class="md-editor-label"><?= esc_html($field['label']) ?> <?= $required ?></label>
    <?php endif; ?>
    <div class="md-editor" id="<?= esc_attr($field['name']) ?>" locale="<?= esc_attr(get_locale()) ?>"></div>
    <textarea id="<?= esc_attr($field['name'] . '-textarea') ?>" type="hidden" class="editor-textarea" name="<?= esc_attr(sprintf('md_editor[%s]', $field['name'])) ?>"><?= esc_textarea((string) $content) ?></textarea>
    <?php if (isset($field['description'])): ?>
        <p class="fus-custom-field-description <?= esc_attr($field['name']) ?>"><?= esc_attr($field['description']) ?></p>
    <?php endif; ?>
</div>