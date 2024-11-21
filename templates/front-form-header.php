<?php
$design = [];
if (isset($form_settings['design'])) {
    $settings = $form_settings['design'];
}

$buttons_design = [];

if (isset($settings['buttons'])) {
    $buttons_design = $settings['buttons'];
}

$inline_style = '';
if (is_array($buttons_design) && !empty($buttons_design)) {
    $inline_style = 'style="';
    foreach ($buttons_design as $key => $value) {
        switch ($key) {
            case 'border-radius':
                $value .= 'px';
                break;
            case 'border':
                $inline_style .= sprintf('%s:1px solid %s; ', $key, $value);
                break;
            default:
                $inline_style .= sprintf('%s:%s; ', $key, $value);
                break;
        }
    }

    $inline_style .= '"';
}

?>
<div class="fus-form-block-header" id="bfe-editor-block-header">
    <div class="sub-header top">
        <button class="editor-button big form-submit" <?= $inline_style ?> title="<?php echo $button_text ?>"><?php echo $button_text ?></button>
        <?php
        $save_draft_text = (isset($form_settings['save_draft_button_text'])&&!empty($form_settings['save_draft_button_text'])) ? $form_settings['save_draft_button_text'] : __('Save Draft', 'front-editor');
        $show_save_draft = isset($form_settings['save_draft']) ? $form_settings['save_draft'] : 'display';
        if ($show_save_draft === 'display') :
        ?>
            <button class="editor-button big form-save-draft" <?= $inline_style ?> title="<?php echo $save_draft_text ?>"><?php echo $save_draft_text ?></button>
        <?php
        endif;
        $add_new_button = $form_settings['fe_add_new_button'] ?? false;
        $add_new_text = (isset($form_settings['add_new_button_text'])&&!empty($form_settings['add_new_button_text'])) ? $form_settings['add_new_button_text'] : __('Add new', 'front-editor');
        if (($post_id !== 'new' && $add_new_button !== 'disable') || $add_new_button === 'always_display') : ?>
            <button class="editor-button" <?= $inline_style ?>>
                <a target="_blank" href="<?= $new_post_link ?>" <?= $inline_style ?> title="<?= $add_new_text ?>"><?= $add_new_text ?></a>
            </button>
        <?php endif; ?>
        <?php
        $preview_button = $form_settings['preview_button'] ?? false;
        $preview_button_text = (isset($form_settings['preview_button_text']) && !empty($form_settings['preview_button_text'])) ? $form_settings['preview_button_text'] : __('Preview', 'front-editor');
        ?>
        <?php if (($post_id !== 'new' && $preview_button !== 'disable')) : ?>
            <button type="button" <?= $inline_style ?> class="editor-button fus-view-page view-page <?php echo $post_id === 'new' ? 'hidden' : ''; ?>">
                <a target="_blank" class="view-page editor-button" href="<?php the_permalink($post_id) ?? ''; ?>" title="<?= $preview_button_text ?>">

                    <span class="fus-button-text" <?= $inline_style ?>><?= $preview_button_text ?></span>

                    <img src="<?= FE_PLUGIN_URL . '/assets/img/see.svg' ?>" class="button-icon">
                </a>
            </button>
        <?php endif; ?>
        <div id="fus-loader" class="fus-loader"></div>
    </div>
</div>