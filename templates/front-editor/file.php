<div class="file-field file-<?= $field['name'] ?>-wrap">
    <input type="hidden" class="file_field" name="<?= sprintf('file_field[%s]', $field['name']) ?>">
    <?php
    $all_file_props = [];
    foreach ($field as $key => $prop) {
        if (str_contains($key, 'label')) {
            $all_file_props[$key] = $prop;
        }
    }

    $attach_ids = get_post_meta($post_id, $field['name'], true);

    $files = [];
    if (!empty($attach_ids)) {
        foreach ($attach_ids as $attach_id) {
            $files[] = [
                'source' => $attach_id,
                'options' => [
                    'type' => 'local'
                ]
            ];
        }
    }
    $required = '';
    if (isset($field['required'])) {
        if ($field['required']) {
            $required = '<span class="required">*</span>';
        }
    }

    $all_file_props['files'] = $files;

    printf('<script type="application/json">%s</script>', json_encode($all_file_props));

    printf('<label for="%s">%s %s</label>', esc_attr($field['name']), esc_html($field['label']), $required);
    printf(
        '<input type="file" required="%s" id="%s" name="files_%s" class="file_upload %s" value="%s" accept="%s" %s %s %s %s>',
        $field['required'] ? 'required' : '',
        esc_attr($field['name']),
        esc_attr($field['name']),
        esc_attr($field['className']),
        '',
        esc_attr($field['accept']),
        sprintf('data-max-file-size="%s"', esc_html($field['max_size']) . 'KB'),
        sprintf('data-max-files="%s"', esc_html($field['max_files'])),
        $field['multiple'] ? 'multiple' : '',
        sprintf('data-label-idle="%s"', esc_html($field['placeholder']))
    );
    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }

    ?>
</div>