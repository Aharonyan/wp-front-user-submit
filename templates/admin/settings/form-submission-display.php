<table class="form-table">

    <tr class="setting">
        <th><?= __('Place of control buttons', 'front-editor') ?></th>
        <td>
            <select name="settings[control_buttons]" id="labe_position">
                <?php
                $options = [
                    'default' => __('On Top', 'front-editor'),
                    'bottom' => __('On Bottom', 'front-editor'),
                    'both' => __('On Top and Bottom', 'front-editor')
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
    <th><?= __('Form Designs', 'front-editor') ?></th>
    <td>
        <?php
        // Get current selection
        $current_design = isset($form_settings['form_theme']) ? $form_settings['form_theme'] : 'default_2';
        
        // Check if user has pro license
        $has_pro = fe_fs()->can_use_premium_code__premium_only();
        
        // Define available designs with preview images
        $designs = [
            'default_2' => [
                'name' => __('Simple Design', 'front-editor'),
                'pro' => false,
                'preview' => FE_PLUGIN_URL . '/assets/img/simple-design-preview.png'
            ],
            'default' => [
                'name' => __('Modern Design', 'front-editor'),
                'pro' => false,
                'preview' => FE_PLUGIN_URL . '/assets/img/modern-design-preview.png'
            ],
            'no_style' => [
                'name' => __('Minimum Style', 'front-editor'),
                'pro' => false,
                'preview' => FE_PLUGIN_URL . '/assets/img/minimum-style-preview.png'
            ],
            'modern-minimal' => [
                'name' => __('Modern Minimal', 'front-editor'),
                'pro' => true,
                'preview' => FE_PLUGIN_URL . '/assets/img/form-modern-minimal-preview.png'
            ],
            'card-panel' => [
                'name' => __('Card/Panel Style', 'front-editor'),
                'pro' => true,
                'preview' => FE_PLUGIN_URL . '/assets/img/form-card-panel-preview.png'
            ],
            'corporate-professional' => [
                'name' => __('Corporate/Professional', 'front-editor'),
                'pro' => true,
                'preview' => FE_PLUGIN_URL . '/assets/img/form-corporate-preview.png'
            ],
            'borderless-flat' => [
                'name' => __('Borderless/Flat', 'front-editor'),
                'pro' => true,
                'preview' => FE_PLUGIN_URL . '/assets/img/form-borderless-flat-preview.png'
            ]
        ];

        // Prepare template variables
        $template_vars = [
            'designs' => $designs,
            'current_design' => $current_design,
            'has_pro' => $has_pro,
            'field_name' => 'form_theme',
            'field_args' => [
                'name' => 'settings'
            ],
            'description' => __('Choose the visual design for your form. Pro designs require a premium license.', 'front-editor')
        ];

        // Load the design selector template
        \BFE\MenuSettings::load_admin_template('design-selector', $template_vars);
        ?>
        
        <p class="description"><?= __('Select front form design. Each design offers a unique visual style and user experience.', 'front-editor') ?></p>
    </td>
</tr>

    <?php require __DIR__ . '/form-design-color-settings.php'; ?>

    <tr class="setting">
        <th><?= __('Custom css for form', 'front-editor') ?></th>
        <td>
            <textarea id="fus_code_editor_page_css" rows="10" name="settings[form_custom_css]" class="widefat textarea"><?php echo wp_unslash($form_settings['form_custom_css'] ?? '.class{color:black;}'); ?></textarea>
        </td>
    </tr>

</table>