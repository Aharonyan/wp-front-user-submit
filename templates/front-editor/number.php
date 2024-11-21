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

    $min = '';
    if (isset($field['min'])) {
        $min = $field['min'];
    }

    $max = '';
    if (isset($field['max'])) {
        $max = $field['max'];
    }

    $step = '';
    if (isset($field['step'])) {
        $step = $field['step'];
    }

    if (isset($field['label'])) {
        printf('<label for="%s">%s %s</label>', esc_attr($field['name']), esc_html($field['label']), $required);
    }

    $custom_keys = get_post_custom_keys($post_id);

    if ($custom_keys && in_array($field['name'], $custom_keys)) {
        $value = get_post_meta($post_id, $field['name'], true) ?? '';
    } else {
        $value = isset($field['value']) ? $field['value'] : '';
    }
    
    printf(
        '<input type="%s" required="%s" name="number[%s]" class="%s" value="%s" placeholder="%s" min="%s" max="%s" step="%s">',
        $field['type'],
        $field['required'],
        esc_attr($field['name']),
        esc_attr($field['className']),
        $value,
        esc_attr($placeholder),
        $min,
        $max,
        $step
    );
    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }
    ?>
</div>