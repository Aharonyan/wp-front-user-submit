<?php
$comment_status        = isset($form_settings['comment_status']) ? $form_settings['comment_status'] : 'open';
$submit_text           = isset($form_settings['submit_text']) ? $form_settings['submit_text'] : __('Submit', 'front-editor');
$post_added_message = isset($form_settings['post_added_message']) ? $form_settings['post_added_message'] : __('New post created', 'front-editor');
?>
<table class="form-table">
    <tr class="setting">
        <th><?= __('Post Status', 'front-editor') ?></th>
        <td>
            <select name="settings[fe_post_status]" id="fe_settings_post_status">
                <?php
                $post_statuses = [
                    'publish' => __('Publish', 'front-editor'),
                    'pending' => __('Pending', 'front-editor')
                ];

                $post_status_selected    = isset($form_settings['fe_post_status']) ? $form_settings['fe_post_status'] : 'publish';

                foreach ($post_statuses as $status => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($status), esc_attr(selected($post_status_selected, $status, false)), esc_html($label));
                }; ?>
            </select>
        </td>
    </tr>
    <!-- Add new button settings  -->
    <tr class="setting">
        <th><?= __('Add new button', 'front-editor') ?></th>
        <td>
            <select name="settings[fe_add_new_button]" id="fe_add_new_button">
                <?php
                $options = [
                    'display' => __('Display', 'front-editor'),
                    'always_display' => __('Always display', 'front-editor'),
                    'disable' => __('Disable', 'front-editor')
                ];

                $options_selected = isset($form_settings['fe_add_new_button']) ? $form_settings['fe_add_new_button'] : 'display';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', $option, selected($options_selected, $option, false), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('It will show add new button after post creation or on editing', 'front-editor') ?></p>
        </td>
    </tr>
    <tr class="setting">
        <th><?php esc_html_e('Add new button text', 'front-editor'); ?></th>
        <td>
            <input type="text" name="settings[add_new_button_text]" placeholder="<?= __('Add New', 'front-editor'); ?>" value="<?php echo isset($form_settings['add_new_button_text']) ? esc_attr($form_settings['add_new_button_text']) : __('Add New', 'front-editor'); ?>">
        </td>
    </tr>
    <!-- End Add new button settings  -->
    <!-- Post redirection settings  -->
    <tr class="setting">
        <th><?= __('Redirect To', 'front-editor') ?></th>
        <td>
            <select name="settings[post_redirect_to]" id="post_redirect_to">
                <?php
                $options = [
                    'disable' => __('No Redirect', 'front-editor'),
                    'post' => __('Currently Edited Post', 'front-editor'),
                    'url' => __('To a custom URL', 'front-editor')
                ];

                $options_selected = isset($form_settings['post_redirect_to']) ? $form_settings['post_redirect_to'] : 'disable';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('After successfully submit, where the page will redirect to', 'front-editor') ?></p>
        </td>
    </tr>

    <tr class="setting hidden_element" id="post_redirect_to_link">
        <th><?= __('Custom URL', 'front-editor') ?></th>
        <td>
            <input type="text" name="settings[post_redirect_to_link]" value="<?php echo esc_attr($form_settings['post_redirect_to_link']??''); ?>">
        </td>
    </tr>
    <!-- Post redirection settings  -->

    <!-- Save to draft button settings -->
    <tr class="setting">
        <th><?= __('Draft button', 'front-editor') ?></th>
        <td>
            <select name="settings[save_draft]" id="save_draft">
                <?php
                $options = [
                    'display' => __('Display', 'front-editor'),
                    'disable' => __('Disable', 'front-editor')
                ];

                $options_selected = isset($form_settings['save_draft']) ? $form_settings['save_draft'] : 'display';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('Show button save to draft', 'front-editor') ?></p>
        </td>
    </tr>
    <tr class="setting">
        <th><?php esc_html_e('Draft button text', 'front-editor'); ?></th>
        <td>
            <input type="text" name="settings[save_draft_button_text]" placeholder="<?= __('Save Draft', 'front-editor'); ?>" value="<?php echo isset($form_settings['save_draft_button_text']) ? esc_attr($form_settings['save_draft_button_text']) : ''; ?>">
        </td>
    </tr>
    <!-- End Save to draft button settings -->
    <!-- Preview button settings -->
    <tr class="setting">
        <?php $button_name = 'preview_button'?>
        <th><?= __('Preview button', 'front-editor') ?></th>
        <td>
            <select name="settings[<?= $button_name ?>]" id="<?= 'fus_'.$button_name?>">
                <?php
                $options = [
                    'display' => __('Display', 'front-editor'),
                    'disable' => __('Disable', 'front-editor')
                ];

                $options_selected = isset($form_settings[$button_name]) ? $form_settings[$button_name] : 'display';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <p class="description"><?= __('Show button preview after post published.', 'front-editor') ?></p>
        </td>
    </tr>
    <tr class="setting">
        <th><?php esc_html_e('Preview button text', 'front-editor'); ?></th>
        <td>
            <input type="text" name="settings[<?= $button_name ?>_text]" placeholder="<?= __('Preview', 'front-editor'); ?>" value="<?php echo isset($form_settings[$button_name.'_text']) ? esc_attr($form_settings[$button_name.'_text']) : ''; ?>">
        </td>
    </tr>
    <!-- End Preview button settings -->

    <tr class="setting">
        <th><?php esc_html_e('Comment Status', 'wp-user-frontend'); ?></th>
        <td>
            <select name="settings[comment_status]">
                <option value="open" <?php selected($comment_status, 'open'); ?>><?php esc_html_e('Open', 'front-editor'); ?></option>
                <option value="closed" <?php selected($comment_status, 'closed'); ?>><?php esc_html_e('Closed', 'front-editor'); ?></option>
            </select>
        </td>
    </tr>
    <tr class="setting-submit-text">
        <th><?php esc_html_e('Submit Post Button text', 'wp-user-frontend'); ?></th>
        <td>
            <input type="text" name="settings[submit_text]" value="<?php echo esc_attr($submit_text); ?>">
        </td>
    </tr>
    <tr class="setting-post_added_message">
        <th><?php esc_html_e('Post Added Message', 'front-editor'); ?></th>
        <td>
            <textarea rows="3" cols="40" name="settings[post_added_message]"><?php echo esc_textarea($post_added_message); ?></textarea>
        </td>
    </tr>
</table>