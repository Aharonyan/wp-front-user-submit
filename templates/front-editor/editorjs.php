<div class="fe_custom_field">
    <?php

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
    $editor_js_data = json_encode(get_post_meta($post_id, $field['name'], true));
    ?>

    <div class="fus-wrap fus-editor-js-field-wrap <?= $field['name'] ?>">
        <?php if (isset($field['label'])): ?>
            <label for="<?= $field['name'] ?>" class="md-editor-label"><?= $field['label'] ?> <?= $required ?></label>
        <?php endif; ?>
        <div class="EditorJS-editor" id="<?= $field['name'] ?>"></div>
        <input value='<?= $editor_js_data ?>' id="<?php printf('%s-textarea', $field['name']) ?>" type="hidden" class="editor-textarea hidden" name="<?php printf('editor_js[%s]', $field['name']) ?>" required="<?= $field['required'] ? 'required' : '' ?>">
            
        <?php if (isset($field['description'])): ?>
            <p class="fus-custom-field-description <?= esc_attr($field['name']) ?>"><?= esc_attr($field['description']) ?></p>
        <?php endif; ?>
    </div>
</div>