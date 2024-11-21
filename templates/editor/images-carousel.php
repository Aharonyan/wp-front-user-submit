<?php
$classes = array();

if (!empty($data['stretched']) && $data['stretched'] == 'true') {
    $classes[] = 'bfe-image--stretched';
}

if (!empty($data['withBorder']) && $data['withBorder'] == 'true') {
    $classes[] = 'bfe-image--bordered';
}

if (!empty($data['withBackground']) && $data['withBackground'] == 'true') {
    $classes[] = 'bfe-image--backgrounded';
}

$size_slug = "large";
$clean_img_ids = [];

ob_start();
?>
<figure class="wp-block-gallery columns-2">
    <ul class="blocks-gallery-grid">
        <?php foreach ($images as $image) :
            $image_id = attachment_url_to_postid( $image['url'] );
            $clean_img_ids[] = $image_id;
            $attachment_url = wp_get_attachment_image_url($image_id, 'large');

            do_action('fe_before_gallery_block_images_html_render', $image_id, $_POST['post_id']);
        ?>
            <li class="blocks-gallery-item">
                <?php
                printf(
                    '<figure><img src="%s" alt="" class="wp-image-%s" data-id="%s" data-full-url="%s" data-link="http://front-editor.local/?attachment_id=%s"></figure>',
                    $attachment_url,
                    $image_id,
                    $image_id,
                    $attachment_url,
                    $image_id
                );
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
</figure>
<?php
$innerHTML = ob_get_clean();

$block = [
    'blockName' => 'core/gallery',
    'attrs' =>
    [
        'ids' => $clean_img_ids,
    ],
    'innerBlocks' => [
        'innerHTML' => $innerHTML,
    ],
    'innerContent' => [$innerHTML]
];

//$block = filter_block_kses($block, 'post');

echo serialize_block($block);
?>