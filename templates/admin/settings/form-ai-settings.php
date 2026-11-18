<?php

use BFE\AI\AiClient;
use BFE\AI\AiSettings;

$ai_global = AiSettings::get();
$ai_is_pro = AiSettings::is_pro();
$ai_settings_url = admin_url('admin.php?page=fe-global-settings&tab=' . AiSettings::TAB);

$ai_options = [
    'enabled' => __('Enabled', 'front-editor'),
    'disabled' => __('Disabled', 'front-editor'),
];

$ai_rows = [
    'assistant' => [
        'title' => __('AI assistant', 'front-editor'),
        'description' => __('Show the "✨ AI" button in the fields of this form.', 'front-editor'),
        'global_enabled' => !empty($ai_global['assistant_enabled']),
        'is_pro' => false,
    ],
    'moderation' => [
        'title' => __('AI moderation', 'front-editor'),
        'description' => __('Check posts submitted with this form before publishing.', 'front-editor'),
        'global_enabled' => !empty($ai_global['moderation_enabled']),
        'is_pro' => true,
    ],
    'auto_meta' => [
        'title' => __('Auto-fill after submit', 'front-editor'),
        'description' => __('Generate excerpt, tags and SEO description for posts from this form.', 'front-editor'),
        'global_enabled' => !empty($ai_global['auto_excerpt']) || !empty($ai_global['auto_tags']) || !empty($ai_global['auto_seo_description']),
        'is_pro' => true,
    ],
];
?>
<div class="fus-form-ai-settings">
    <?php if (!AiClient::is_configured()) : ?>
        <p class="fus-ai-status is-missing">
            <span aria-hidden="true">●</span>
            <?= esc_html__('AI is not configured yet.', 'front-editor') ?>
            <a href="<?= esc_url($ai_settings_url) ?>"><?= esc_html__('Add an API key', 'front-editor') ?></a>
        </p>
    <?php endif; ?>

    <p class="description"><?= esc_html__('AI features are off in every form until you enable them here.', 'front-editor') ?></p>
    <table class="form-table">
        <?php foreach (AiSettings::form_feature_keys() as $ai_feature => $ai_key) :
            $ai_row = $ai_rows[$ai_feature];
            $ai_locked = $ai_row['is_pro'] && !$ai_is_pro;
            $ai_value = ($form_settings[$ai_key] ?? 'disabled') === 'enabled' ? 'enabled' : 'disabled';
        ?>
            <tr class="setting">
                <th>
                    <label for="fe_settings_<?= esc_attr($ai_key) ?>"><?= esc_html($ai_row['title']) ?></label>
                    <?php if ($ai_row['is_pro']) : ?>
                        <span class="fus-ai-pro-badge">PRO</span>
                    <?php endif; ?>
                </th>
                <td>
                    <select name="settings[<?= esc_attr($ai_key) ?>]" id="fe_settings_<?= esc_attr($ai_key) ?>" <?= $ai_locked ? 'disabled' : '' ?>>
                        <?php foreach ($ai_options as $ai_option => $ai_label) {
                            printf('<option value="%s"%s>%s</option>', esc_attr($ai_option), selected($ai_value, $ai_option, false), esc_html($ai_label));
                        } ?>
                    </select>
                    <?php if ($ai_locked) : ?>
                        <input type="hidden" name="settings[<?= esc_attr($ai_key) ?>]" value="<?= esc_attr($ai_value) ?>">
                    <?php endif; ?>
                    <p class="description"><?= esc_html($ai_row['description']) ?></p>
                    <?php if (!$ai_locked && !$ai_row['global_enabled']) : ?>
                        <p class="description fus-ai-global-off">
                            <?= esc_html__('Turned off in the global AI settings, so it does not work in any form now.', 'front-editor') ?>
                            <a href="<?= esc_url($ai_settings_url) ?>"><?= esc_html__('Open AI settings', 'front-editor') ?></a>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (!$ai_is_pro) : ?>
        <p class="fus-ai-pro-notice">
            <?= esc_html__('AI form generation, translation and tone change in the assistant, post moderation and automatic excerpt, tags and SEO description are available in the Pro version.', 'front-editor') ?>
            <a href="<?= esc_url(admin_url('admin.php?page=front_editor_settings-pricing')) ?>"><?= esc_html__('Get the Pro version', 'front-editor') ?></a>
        </p>
    <?php endif; ?>
</div>
