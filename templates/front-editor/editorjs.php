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
    $editor_js_data = wp_json_encode(\BFE\Field\EditorJsField::sanitize_editor_data(get_post_meta($post_id, $field['name'], true)));
    ?>

    <div class="fus-wrap fus-editor-js-field-wrap <?= esc_attr($field['name']) ?>">
        <?php if (isset($field['label'])): ?>
            <label for="<?= esc_attr($field['name']) ?>" class="md-editor-label"><?= esc_html($field['label']) ?> <?= $required ?></label>
        <?php endif; ?>
        <div class="EditorJS-editor" id="<?= esc_attr($field['name']) ?>"></div>
        <input value="<?= esc_attr($editor_js_data) ?>" id="<?= esc_attr($field['name'] . '-textarea') ?>" type="hidden" class="editor-textarea hidden" name="<?= esc_attr(sprintf('editor_js[%s]', $field['name'])) ?>" required="<?= !empty($field['required']) ? 'required' : '' ?>">
            
        <?php if (isset($field['description'])): ?>
            <p class="fus-custom-field-description <?= esc_attr($field['name']) ?>"><?= esc_attr($field['description']) ?></p>
        <?php endif; ?>
    </div>
</div>