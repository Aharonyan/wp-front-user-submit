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

$innerHTML = sprintf(
    '<figure class="wp-block-image size-large"><img src="%s" alt="" class="wp-image-%s"/><figcaption>%s</figcaption></figure>',
    esc_url((string) wp_get_attachment_image_url($image_id, 'large')),
    (int) $image_id,
    $caption
);

do_action('fe_before_simple_image_block_images_html_render', $image_id, \BFE\SavePost::$saving_post_id);

$block = [
    'blockName' => 'core/image',
    'attrs' =>
    [
        'id' => $image_id,
        'sizeSlug' => $size_slug,
    ],
    'innerBlocks' => [
        'innerHTML' => $innerHTML,
    ],
    'innerContent' => [$innerHTML]
];

echo serialize_block($block);
?>

