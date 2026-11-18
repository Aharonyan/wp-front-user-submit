<?php if (!empty($field['label'])) { ?>
<div class="fe_custom_field fe-paragraph">
    <?php
    $tag = in_array($field['subtype'] ?? '', ['p', 'address', 'blockquote', 'canvas', 'output'], true) ? $field['subtype'] : 'p';
    printf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        $tag,
        isset($field['className']) ? esc_attr($field['className']) : '',
        wp_kses_post($field['label']),
    );
    ?>
</div>
<?php } ?>
