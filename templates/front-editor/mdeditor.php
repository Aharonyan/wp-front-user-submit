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
<div class="fus-wrap fus-md-editor-field-wrap <?= $field['name'] ?>">
    <?php if (isset($field['label'])): ?>
        <label for="<?= $field['name'] ?>" class="md-editor-label"><?= $field['label'] ?> <?= $required ?></label>
    <?php endif; ?>
    <div class="md-editor" id="<?= $field['name'] ?>" locale="<?= get_locale() ?>"></div>
    <textarea id="<?= $field['name'] . '-textarea' ?>" type="hidden" class="editor-textarea" name="<?php printf('md_editor[%s]', $field['name']) ?>"><?= $content ?></textarea>
    <?php if (isset($field['description'])): ?>
        <p class="fus-custom-field-description <?= esc_attr($field['name']) ?>"><?= esc_attr($field['description']) ?></p>
    <?php endif; ?>
</div>