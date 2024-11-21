<div class="fe_custom_field <?= $field['name'] ?>">
    <?php
    $placeholder = __('Write your address');
    if (isset($field['placeholder'])) {
        $placeholder = $field['placeholder'];
    }
    $required = '';
    if (isset($field['required'])) {
        if ($field['required']) {
            $required = '<span class="required">*</span>';
        }
    }

    $country_code = '';
    if (isset($field['country_code'])) {
        $country_code = $field['country_code'];
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
        '<input class="google-map" type="hidden" required="%s" name="google_map[%s]"  value="%s">',
        $field['required'],
        esc_attr($field['name']),
        htmlspecialchars($value),
    );

    printf(
        '<input type="%s" required="%s" name="google_map_input[%s]" class="%s" placeholder="%s" data-country-code="%s">',
        $field['type'],
        $field['required'],
        esc_attr($field['name']),
        isset($field['className']) ? esc_attr($field['className'] . ' autocomplete') : ' autocomplete',
        esc_attr($placeholder),
        $country_code,
    );

    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }
    ?>
</div>