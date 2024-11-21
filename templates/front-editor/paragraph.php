<?php if (!empty($field['label'])) { ?>
<div class="fe_custom_field fe-paragraph">
    <?php
    printf(
        '<%1$s class="%2$s">%3$s</%1$s>',
        $field['subtype'],
        isset($field['className']) ? esc_attr($field['className']) : '',
        isset($field['label']) ? $field['label'] : '',
    );
    ?>
</div>
<?php } ?>