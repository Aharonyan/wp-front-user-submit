<?php if (!empty($field['label'])) { ?>
<div class="fe_custom_field fe-header">
    <?php
    $tag = in_array($field['subtype'] ?? '', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $field['subtype'] : 'h2';
    printf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        $tag,
        isset($field['className']) ? esc_attr($field['className']) : '',
        wp_kses_post($field['label']),
    );
    ?>
</div>
<?php } ?>
