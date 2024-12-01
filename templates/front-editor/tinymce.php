<div class="textarea-field <?= $field['name'] ?> tinymce">
    <?php
    $field_name = $field['name'];
    $field_label = $field['label'];

    $placeholder = $field_label;
    $content = BFE\Editor::get_field_content($post_id, $field);
    $max_length = '';
    $rows = 10;
    if (isset($field['rows'])) {
        $rows = $field['rows'];
    }
    if (isset($field['placeholder'])) {
        $placeholder = esc_html($field['placeholder']);
    }
    if (isset($field['maxlength'])) {
        $max_length = sprintf('maxlength="%s"', $field['maxlength']);
    }
    $required = '';
    if (isset($field['required'])) {
        if ($field['required']) {
            $required = '<span class="required">*</span>';
        }
    }

    $media_buttons = (isset($field['media_buttons']) && !empty($field['media_buttons'])) ? $field['media_buttons'] : false;
    $teeny = (isset($field['teeny']) && !empty($field['teeny'])) ? $field['teeny'] : false;
    $drag_drop_upload = (isset($field['drag_drop_upload']) && !empty($field['drag_drop_upload'])) ? $field['drag_drop_upload'] : false;

    printf('<label for="%s">%s %s</label>', esc_attr($field_name), esc_attr($field_label), $required);

    $settings  = [
        'wpautop'          => true,   // Whether to use wpautop for adding in paragraphs. Note that the paragraphs are added automatically when wpautop is false.
        'media_buttons'    => $media_buttons,   // Whether to display media insert/upload buttons
        'textarea_name'    => sprintf('tinymce[%s]', $field_name),   // The name assigned to the generated textarea and passed parameter when the form is submitted.
        'textarea_rows'    => $rows,  // The number of rows to display for the textarea
        'tabindex'         => '',     // The tabindex value used for the form field
        'editor_css'       => '',     // Additional CSS styling applied for both visual and HTML editors buttons, needs to include <style> tags, can use "scoped"
        'editor_class'     => $field['className'] ?? '',     // Any extra CSS Classes to append to the Editor textarea
        'teeny'            => $teeny,  // Whether to output the minimal editor configuration used in PressThis
        'dfw'              => false,  // Whether to replace the default fullscreen editor with DFW (needs specific DOM elements and CSS)
        'tinymce'          => true,   // Load TinyMCE, can be used to pass settings directly to TinyMCE using an array
        'quicktags'        => false,   // Load Quicktags, can be used to pass settings directly to Quicktags using an array. Set to false to remove your editor's Visual and Text tabs.
        'drag_drop_upload' => $drag_drop_upload    // Enable Drag & Drop Upload Support (since WordPress 3.9)
    ];

    // display the editor
    wp_editor($content, $field_name, $settings);


    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field_name), esc_html($field['description']));
    }
    ?>
</div>