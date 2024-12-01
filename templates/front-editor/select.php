<?php
$select_name = sprintf('select[%s][ids]', $field['name']);

$placeholder = $field['label'];
if (isset($field['placeholder'])) {
    $placeholder = $field['placeholder'];
}

$selected = get_post_meta($post_id, $field['name']);
if (empty($selected)) {
    $selected = [];
} elseif (is_array($selected) && count($selected) === 1 && $field['multiple']) {
    $selected = explode(',', $selected[0]);
}

$required = '';
if (isset($field['required'])) {
    if ($field['required']) {
        $required = '<span class="required">*</span>';
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

?>
<div class="select-wrap <?= $field['type'] ?>">
    <label for="<?= $field['type'] ?>"><?php echo esc_html($field['label']); ?> <?= $required ?></label>
    <input type="hidden" name="<?= sprintf('select[%s][required]', $field['name']) ?>" value="<?= $field['required'] ? 1 : 0 ?>">
    <input type="hidden" name="<?= sprintf('select[%s][label]', $field['name']) ?>" value="<?= $field['label'] ?>">
    <select
        id="<?= $field['type'] ?>"
        class="taxonomy-select <?= $field['name'] ?>" name="<?= $select_name ?>"
        <?php echo $field['multiple'] ? 'multiple' : ''; ?>
        data-placeholder="<?php echo esc_attr($placeholder); ?>"
        <?= $field['required'] ? 'required' : '' ?>
        <?php
        ($search_placeholder) ? printf('data-search-placeholder="%s"', $search_placeholder) : '';
        ($search_error_text) ? printf('data-search-text="%s"', $search_error_text) : '';
        ($show_search) ? printf('data-show-search="1"') : '';
        ?>>

        <option data-placeholder="true"></option>
        <?php
        foreach ($field['values'] as $term) {
            $term_id = $term['value'];
            echo sprintf(
                '<option %s value="%s">%s</option>',
                in_array($term_id, $selected) ? 'selected' : '',
                $term_id,
                esc_attr($term['label'])
            );
        }
        ?>
    </select>
    <?php
    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }
    ?>
</div>