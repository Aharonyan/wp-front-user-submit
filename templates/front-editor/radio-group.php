<?php
$radio_name = sprintf('radio[%s][ids]', $field['name']);

$placeholder = $field['label'];
if (isset($field['placeholder'])) {
    $placeholder = $field['placeholder'];
}

$selected = get_post_meta($post_id, $field['name']);
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
<div class="radio-wrap <?= $field['type'] ?> <?= $field['name'] ?>">
    <label for="<?= $field['type'] ?>"><?php echo esc_html($field['label']); ?> <?= $required ?></label>
    <input type="hidden" name="<?= sprintf('radio[%s][required]', $field['name']) ?>" value="<?= $field['required'] ? 1 : 0 ?>">
    <input type="hidden" name="<?= sprintf('radio[%s][label]', $field['name']) ?>" value="<?= $field['label'] ?>">

    <?php
    foreach ($field['values'] as $radio_item) {
        $radio_item_id = $radio_item['value'];
    ?>
        <div class="radio-item">
            <?php if (!empty($selected)) { ?>
                <label for="<?= esc_attr($radio_item_id) ?>"><input type="radio" id="<?= esc_attr($radio_item_id) ?>" class="radio-<?= esc_attr($field['name']) ?>" name="<?= $radio_name ?>" value="<?= esc_attr($radio_item_id) ?>" <?php echo in_array($radio_item_id, $selected) ? 'checked' : ''; ?> <?= $field['required'] ? 'required' : '' ?>>
                    <?= esc_html($radio_item['label']) ?></label>
            <?php } else { ?>
                <label for="<?= esc_attr($radio_item_id) ?>"><input type="radio" id="<?= esc_attr($radio_item_id) ?>" class="radio-<?= esc_attr($field['name']) ?>" name="<?= $radio_name ?>" value="<?= esc_attr($radio_item_id) ?>" <?php echo !empty($radio_item['selected']) ? 'checked' : ''; ?> <?= $field['required'] ? 'required' : '' ?>>
                    <?= esc_html($radio_item['label']) ?></label>
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