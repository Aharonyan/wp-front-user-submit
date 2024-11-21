<table class="form-table">

    <tr class="setting">
        <th><?= __('Place of control buttons', 'front-editor') ?></th>
        <td>
            <select name="settings[control_buttons]" id="labe_position">
                <?php
                $options = [
                    'default' => __('On Top', 'front-editor'),
                    'bottom' => __('On Bottom', 'front-editor')
                ];

                $options_selected = isset($form_settings['control_buttons']) ? $form_settings['control_buttons'] : 'bottom';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('Select place where the form control buttons will be placed', 'front-editor') ?></p>
        </td>
    </tr>

    <tr class="setting">
        <th><?= __('Place to show success or error messages', 'front-editor') ?></th>
        <td>
            <select name="settings[error_success_messages]" id="labe_position">
                <?php
                $options = [
                    'default' => __('Popup on bottom right', 'front-editor'),
                    'bottom' => __('On bottom after form', 'front-editor'),
                    'top' => __('On top before form', 'front-editor')
                ];

                $options_selected = isset($form_settings['error_success_messages']) ? $form_settings['error_success_messages'] : 'bottom';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('The place where will be displayed error and success messages', 'front-editor') ?></p>
        </td>
    </tr>

    <tr class="setting">
        <th><?= __('Theme Designs', 'front-editor') ?></th>
        <td>
            <select name="settings[form_theme]" id="labe_position">
                <?php
                $options = [
                    'default_2' => __('Simple Design', 'front-editor'),
                    'default' => __('Modern Design', 'front-editor'),
                    'no_style' => __('Minimum Style', 'front-editor'),
                ];

                $options_selected = isset($form_settings['form_theme']) ? $form_settings['form_theme'] : 'default_2';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('Select front form design', 'front-editor') ?></p>
        </td>
    </tr>

    <?php require __DIR__ . '/form-design-color-settings.php'; ?>

    <tr class="setting">
        <th><?= __('Custom css for form', 'front-editor') ?></th>
        <td>
            <textarea id="fus_code_editor_page_css" rows="10" name="settings[form_custom_css]" class="widefat textarea"><?php echo wp_unslash($form_settings['form_theme'] ?? ''); ?></textarea>
        </td>
    </tr>

</table>