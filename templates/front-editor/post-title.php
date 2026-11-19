<div class="fus-post-title post_title">
    <?php
    $placeholder = __('Add Title', 'front-editor');

    if (isset($field['placeholder'])) {
        $placeholder = $field['placeholder'];
    }

    $required = '';
    if (isset($field['required'])) {
        if ($field['required']) {
            $required = '<span class="required">*</span>';
        }
    }

    $class = "fus_post_title";

    if (isset($field['className'])) {
        $class .= $field['className'];
    }

    $html_element = 'textarea';
    if (isset($field['title_element'])) {
        $html_element = $field['title_element'];
    }

    $hide_field = false;
    if (isset($field['hide_field'])) {
        $hide_field = $field['hide_field'];
    }

    $text_type = 'text';

    if ($hide_field) {
        $text_type = "hidden";
    }

    if (isset($field['label']) && !$hide_field) {
        printf('<label for="fus_post_title">%s %s</label>', esc_html($field['label']), $required);
    }

    // The saved title as is: get_the_title() runs the_title filters, so themes and plugins
    // could change it (Twenty Twenty-One shows "Untitled" in new forms, "Private: " prefixes)
    $title = 'new' === $post_id ? '' : (string) get_post_field('post_title', $post_id, 'raw');

    if ($html_element === 'input') {
        printf(
            '<%s class="%s" type="%s" id="fus_post_title" name="post_title" placeholder="%s" rows="2" value="%s">',
            $html_element,
            $text_type,
            $class,
            esc_attr($placeholder),
            esc_attr($title)
        );
    } else {
        printf(
            '<%s class="%s" type="%s" id="fus_post_title" name="post_title" placeholder="%s" rows="2">%s</%s>',
            $html_element,
            $text_type,
            $class,
            esc_attr($placeholder),
            esc_html($title),
            $html_element
        );
    }

    ?>
</div>