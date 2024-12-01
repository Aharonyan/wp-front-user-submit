<?php

/**
 * If users have not selected the form
 */
if (!$attributes['id'] && current_user_can('manage_options')) {
    printf(
        '<h2>%s <a href="%s">%s</a></h2>',
        __('Post form is not selected please select existing one or', 'front-editor'),
        admin_url('admin.php?page=fe-post-forms&action=add-new'),
        __('Create New One', 'front-editor')
    );
}
$fields_list = json_decode(get_post_meta($attributes['id'], 'formBuilderData', true), true) ?? BFE\Form::get_form_builder_demo_data();
$form_id = $attributes['id'] ?? 0;
$form_theme = $form_settings['form_theme'] ?? 'default_2';
$form_control_buttons = $form_settings['control_buttons'] ?? 'bottom';
$message_place = $form_settings['error_success_messages'] ?? 'bottom';
$form_css = sprintf('<style>%s</style>', esc_html($form_settings['form_custom_css'] ?? ''));
echo $form_css;
?>

<?php if ($message_place == 'top') {
    printf('<div id="fus-message-wrap"></div>');
} ?>

<form class="fus-form bfe-editor <?= $form_theme ?>" id="fus-form-<?= $form_id ?>" post_id="<?= $post_id ?>">
    <?php if ($form_control_buttons == 'default' || $form_control_buttons == 'both') {
        require fe_template_path('front-form-header.php');
    } ?>
    <div class="hidden-fields">
        <input type="text" name="post_id" class="fus_post_id" value="<?= $post_id ?>">
        <input type="text" name="page_id" class="fus_post_id" value="<?= get_the_ID() ?>">
        <?php if ($form_id) : ?>
            <input type="text" name="form_id" value="<?= $form_id ?>">
        <?php endif; ?>
    </div>

    <div class="wrapper">
        <div class="column">
            <?php
            if (!empty($fields_list)) {
                foreach ($fields_list as $field) {
                    // field can hook here check the field and add custom templates for custom fields
                    do_action('bfe_editor_on_front_field_adding', $post_id, $attributes, $field);
                }
            }
            ?>
        </div>
    </div>
    <?php if ($form_control_buttons == 'bottom' || $form_control_buttons == 'both') {
        require fe_template_path('front-form-header.php');
    } ?>
</form>

<?php if ($message_place == 'bottom') {
    printf('<div id="fus-message-wrap"></div>');
} ?>