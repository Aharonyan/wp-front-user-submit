<?php
$editor_type = 'textarea';

if (isset($field['editor_type'])) {
    $editor_type = $field['editor_type'];
}
?>
<div class="textarea-field <?= esc_attr($field['name']) ?> <?= $editor_type ?>">
    <?php
    $placeholder = $field['label'];
    $content = get_post_meta($post_id, $field['name'], true);

    $max_length = '';
    $rows = 10;
    if (isset($field['rows'])) {
        $rows = $field['rows'];
    }
    if ($field['post_content']) {
        $content = get_post_field('post_content', $post_id);
    }
    if (isset($field['placeholder'])) {
        $placeholder = $field['placeholder'];
    }
    if (isset($field['maxlength'])) {
        $max_length = sprintf('maxlength="%s"', $field['maxlength']);
    }
    $required = '';
    if (isset($field['required'])) {
        if($field['required']){
            $required = '<span class="required">*</span>';
        }
    }
    printf('<label for="%s">%s %s</label>', esc_attr($field['name']), esc_html($field['label']),$required);

    $simple_textarea = sprintf(
        '<textarea id="%s" type="%s" required="%s" rows="%s" name="textarea[%s]" class="%s" placeholder="%s" %s>%s</textarea>',
        esc_attr($field['name']),
        $field['subtype'] ?? 'textarea',
        $field['required'],
        $rows,
        esc_attr($field['name']),
        esc_attr($field['className']),
        esc_attr($placeholder),
        $max_length,
        $content
    );

    echo $simple_textarea;


    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }
    ?>
</div>