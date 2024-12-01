<?php
$admin_notifications = !empty($form_settings['admin_notifications']) ? $form_settings['admin_notifications'] : 'false';
$send_admin_notification_to = (isset($form_settings['send_admin_notification_to']) && !empty($form_settings['send_admin_notification_to'])) ? $form_settings['send_admin_notification_to'] : get_option( 'admin_email' );
$send_admin_notification_subject = isset($form_settings['send_admin_notification_subject']) ? $form_settings['send_admin_notification_subject'] : __('New post created - [post_title]', 'front-editor');
$send_admin_notification_text = isset($form_settings['send_admin_notification_text']) ? $form_settings['send_admin_notification_text'] : 'Hi Admin,' . PHP_EOL . PHP_EOL . 'A new post has been created in your site [sitename] ([siteurl]).' . PHP_EOL . 'Here is the details: ' . PHP_EOL . 'Post Title: [post_title] ' . PHP_EOL . 'Author: [author_name] ' . PHP_EOL . 'Post URL: [post_link] ' . PHP_EOL . 'Edit URL: [post_link] ' . PHP_EOL;
?>

<h3><?= __('Admin Notification', 'front-editor') ?></h3>
<table class="form-table">
    <tr class="setting">
        <th><?php esc_html_e('Activate Notifications', 'front-editor'); ?></th>
        <td>
            <input type="checkbox" class="email_content" name="settings[admin_notifications]" value="true" <?php checked($admin_notifications, 'true'); ?> />
        </td>
    </tr>
    <tr class="send_admin_notification_to">
        <th><?php esc_html_e('To', 'front-editor'); ?></th>
        <td>
            <input type="text" class="email_input" name="settings[send_admin_notification_to]" value="<?php echo esc_attr($send_admin_notification_to); ?>">
        </td>
    </tr>
    <tr class="send_admin_notification_subject">
        <th><?php esc_html_e('Subject', 'front-editor'); ?></th>
        <td>
            <input type="text" class="email_input" name="settings[send_admin_notification_subject]" value="<?php echo esc_attr($send_admin_notification_subject); ?>">
            <p class="description">Subject line for email alerts. You may include any of the following variables:
                <code>[sitename]</code>,<code>[siteurl]</code>,<code>[post_title]</code>,<code>[author_name]</code>,<code>[post_link]</code>,<code>[post_admin_link]</code>,<code>[post_content]</code>,<code>[post_status]</code>
            </p>
        </td>
    </tr>
    <tr class="send_admin_notification_text">
        <th><?php esc_html_e('Post Update Message', 'front-editor'); ?> </th>
        <td>
            <textarea class="email_content" rows="10" cols="80" name="settings[send_admin_notification_text]"><?php echo esc_textarea($send_admin_notification_text); ?></textarea>
            <p class="description">
                Message for email alerts. Leave blank to use default message. You may include any of the following variables:
                <code>[sitename]</code>,<code>[siteurl]</code>,<code>[post_title]</code>,<code>[author_name]</code>,<code>[post_link]</code>,<code>[post_admin_link]</code>,<code>[post_content]</code>,<code>[post_status]</code>
            </p>
        </td>
    </tr>
</table>