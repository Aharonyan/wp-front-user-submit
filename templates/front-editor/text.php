<div class="fe_custom_field <?= $field['name'] ?>">
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

    if ($field['subtype'] !== 'hidden') {
        printf('<label for="%s">%s %s</label>', esc_attr($field['name']), esc_html($field['label']), $required);
    }

    printf(
        '<input type="%s" required="%s" name="text_fields[%s]" class="%s" value="%s" placeholder="%s">',
        $field['subtype'],
        $field['required'],
        esc_attr($field['name']),
        esc_attr($field['className']),
        get_post_meta($post_id, $field['name'], true) ?? '',
        esc_attr($placeholder)
    );
    if (isset($field['description']) && $field['subtype'] !== 'hidden') {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }
    ?>
</div>