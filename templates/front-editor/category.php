<div class="select-wrap category">
    <?php
    $required = '';
    if (isset($field['required'])) {
        if ($field['required']) {
            $required = '<span class="required">*</span>';
        }
    }
    ?>
    <label for="category-select"><?php echo esc_html(__('Select category', 'front-editor')); ?> <?= $required ?></label>
    <select id="bfe-category" name="category-select" <?php echo isset($attributes['category_multiple']) ? 'multiple' : ''; ?> data-placeholder="<?php echo esc_attr(__('Select category', 'front-editor')); ?>">
        <option data-placeholder="true"></option>
        <?php
        $post_cat_id = 0;
        $has_categories = wp_get_post_categories($post_id);

        $categories = get_categories([
            'hide_empty'   => isset($attributes['category_show_empty']) ? 1 : 0,
        ]);
        foreach ($categories as $category) {
            $cat_id = (int) $category->cat_ID;
            echo sprintf(
                '<option %s value="%s">%s</option>',
                in_array($cat_id, $has_categories) ? 'selected' : '',
                $cat_id,
                $category->cat_name
            );
        }
        ?>
    </select>
</div>