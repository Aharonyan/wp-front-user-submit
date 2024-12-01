<?php
$checkbox_name = sprintf('checkbox[%s][ids][]', $field['name']);

$placeholder = $field['label'];
if (isset($field['placeholder'])) {
    $placeholder = $field['placeholder'];
}

$selected = get_post_meta($post_id, $field['name'], true);
if (empty($selected)) {
    $selected = [];
}

$required = '';
if (isset($field['required'])) {
    if ($field['required']) {
        $required = '<span class="required">*</span>';
    }
}
?>
<div class="checkbox-wrap <?= $field['type'] ?>  <?= $field['name'] ?>">
    <label for="<?= $field['type'] ?>"><?php echo esc_html($field['label']); ?> <?= $required ?></label>
    <input type="hidden" name="<?= sprintf('checkbox[%s][required]', $field['name']) ?>" value="<?= $field['required'] ? 1 : 0 ?>">
    <input type="hidden" name="<?= sprintf('checkbox[%s][label]', $field['name']) ?>" value="<?= $field['label'] ?>">

    <?php
    foreach ($field['values'] as $checkbox_item) {
        $checkbox_item_id = $checkbox_item['value'];
    ?>
        <div class="checkbox-item">
            <?php if (!empty($selected)) { ?>
                <label for="<?= esc_attr($checkbox_item_id) ?>"><input type="checkbox" id="<?= esc_attr($checkbox_item_id) ?>" class="checkbox-<?= esc_attr($field['name']) ?>" name="<?= $checkbox_name ?>" value="<?= esc_attr($checkbox_item_id) ?>" <?php echo in_array($checkbox_item_id, $selected) ? 'checked' : ''; ?> <?= $field['required'] ? 'required' : '' ?>>
                    <?= esc_html($checkbox_item['label']) ?></label>
            <?php } else { ?>
                <label for="<?= esc_attr($checkbox_item_id) ?>"><input type="checkbox" id="<?= esc_attr($checkbox_item_id) ?>" class="checkbox-<?= esc_attr($field['name']) ?>" name="<?= $checkbox_name ?>" value="<?= esc_attr($checkbox_item_id) ?>" <?php echo !empty($checkbox_item['selected']) ? 'checked' : ''; ?> <?= $field['required'] ? 'required' : '' ?>>
                    <?= esc_html($checkbox_item['label']) ?></label>
            <?php } ?>
        </div>
    <?php
    }
    ?>

    <?php
    if (isset($field['description'])) {
        printf('<p class="fus-custom-field-description %s">%s</p>', esc_attr($field['name']), esc_attr($field['description']));
    }
    ?>
</div>