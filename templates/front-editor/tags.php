<div class="select-wrap">
    <label for="tags-select"><?php echo esc_html(__('Select tags', 'front-editor')); ?></label>
    <select id="bfe-tags" name="tags-select" data-placeholder="<?php echo esc_html(__('Select tags', 'front-editor')); ?>" multiple>
        <?php
        $post_cat_id = 0;
        $tags_array = [];
        $has_tags = wp_get_post_tags($post_id);

        foreach ($has_tags as $tag) {
            $tags_array[] = $tag->slug;
        }
        $tags = get_tags([
            'hide_empty'   => 0,
        ]);
        foreach ($tags as $tag) {
            $tag_id = (int) $tag->term_id;
            $option =  sprintf(
                '<option value="%s" %s>%s</option>',
                $tag->slug,
                in_array($tag->slug, $tags_array) ? 'selected' : '',
                $tag->name
            );

            echo $option;
        }
        ?>
    </select>
    <?php
    if (isset($field['description']) && $field['subtype'] !== 'hidden') {
        printf('<p class="fus-custom-field-description %s">%s</p>', $field_name, esc_attr($field['description']));
    }
    ?>
</div>