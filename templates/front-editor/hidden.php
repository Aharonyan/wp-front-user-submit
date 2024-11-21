<div class="fe_custom_field <?= $field['name'] ?>">
    <?php
    
    $custom_keys = get_post_custom_keys($post_id);

    if ($custom_keys && in_array($field['name'], $custom_keys)) {
        $value = get_post_meta($post_id, $field['name'], true) ?? '';
    } else {
        $value = isset($field['value']) ? $field['value'] : '';
    }

    printf(
        '<input type="hidden" name="hidden_fields[%s]" value="%s">',
        esc_attr($field['name']),
        $value,
    );
    ?>
</div>