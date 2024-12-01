<?php
$select_name = sprintf('tax[%s][ids]', $tax_name);

$placeholder = esc_html($field['label']);
if (isset($field['placeholder'])) {
    $placeholder = $field['placeholder'];
}

$hierarchically = false;
if (isset($field['hierarchically'])) {
    if ($field['hierarchically']) {
        $hierarchically = true;
    }
}

$add_new = false;
if (isset($field['add_new'])) {
    if ($field['add_new']) {
        $add_new = true;
    }
}

$show_search = false;
if (isset($field['show_search'])) {
    if ($field['show_search']) {
        $show_search = true;
    }
}

$search_placeholder = false;
if (isset($field['search_placeholder']) && !empty($field['search_placeholder'])) {
    $search_placeholder = $field['search_placeholder'];
}

$search_error_text = false;
if (isset($field['search_error_text']) && !empty($field['search_error_text'])) {
    $search_error_text = $field['search_error_text'];
}

$has_terms = wp_get_post_terms($post_id, $tax_name, ['fields' => 'ids']);
$args = [
    'hide_empty'   => $field['show_empty'] ? 0 : 1,
    'order'         => strtoupper($field['order']),
];

if (isset($field['exclude'])) {
    if (!empty($field['exclude'])) {
        $args['exclude'] = $field['exclude'];
    }
}

$terms = get_terms($tax_name, $args);
$term_hierarchy = [];
sort_terms_hierarchically($terms, $term_hierarchy);

$required = '';
if (isset($field['required'])) {
    if ($field['required']) {
        $required = '<span class="required">*</span>';
    }
}

?>
<div class="select-wrap <?= $field['type'] ?>">
    <input type="hidden" name="<?= sprintf('tax[%s][required]', $tax_name) ?>" value="<?= $field['required'] ? 1 : 0 ?>">
    <input type="hidden" name="<?= sprintf('tax[%s][default_terms]', $tax_name) ?>" value="<?= !empty($field['default_terms']) ? $field['default_terms'] : '' ?>">
    <label for="<?= $field['type'] ?>"><?php echo esc_html($field['label']); ?> <?= $required ?></label>
    <?php
    printf(
        '<select name="%s" id="%s" class="taxonomy-select %s" %s data-placeholder="%s" %s %s %s %s>',
        $select_name,
        $field['type'],
        $tax_name,
        $field['multiple'] ? 'multiple' : '',
        esc_attr($placeholder),
        ($add_new) ? 'data-add-new="1"' : '',
        ($search_placeholder)?sprintf('data-search-placeholder="%s"',$search_placeholder):'',
        ($search_error_text)?sprintf('data-search-text="%s"',$search_error_text):'',
        ($show_search) ? 'data-show-search="1"' : '',
    )
    ?>
    <option data-placeholder="true"></option>
    <?php

    foreach ($term_hierarchy as $optgroup) {
        $term_id = (int) $optgroup->term_id;
        printf(
            '<option %s value="%s" %s>%s</option>',
            in_array($term_id, $has_terms) ? 'selected' : '',
            $term_id,
            $hierarchically ? 'class="optionGroup"' : '',
            $optgroup->name
        );
        foreach ($optgroup->children as $term) {
            $term_id = (int) $term->term_id;

            printf(
                '<option %s value="%s" %s>%s</option>',
                in_array($term_id, $has_terms) ? 'selected' : '',
                $term_id,
                $hierarchically ? 'class="optionChild"' : '',
                $term->name
            );
        }
    }

    ?>
    </select>
    <?php
    if (isset($field['description']) && $field['subtype'] !== 'hidden') {
        printf('<p class="fus-custom-field-description %s">%s</p>', $field_name, esc_attr($field['description']));
    }
    ?>
</div>