<div class="fe_custom_field <?= $field['name'] ?>">
    <?php
    $custom_keys = get_post_custom_keys($post_id);

    if ($custom_keys && in_array($field['name'], $custom_keys)) {
        $value = get_post_meta($post_id, $field['name'], true) ?? '';
    } else {
        $value = isset($field['value']) ? $field['value'] : '';
    }

    printf(
        '<button type="%s" name="button[%s]" class="%s" value="%s">%s</button>',
        $field['type'],
        esc_attr($field['name']),
        esc_attr($field['className']. ' ' . $field['style']),
        $value,
        isset($field['label']) ? $field['label'] : '',
    );
    ?>
</div>