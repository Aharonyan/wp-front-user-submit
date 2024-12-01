<?php
$prefix = sprintf('post_%s', $post_notification_type);
$settings = [];
if (isset($form_settings['notifications'][$prefix])) {
    $settings = $form_settings['notifications'][$prefix];
}
$enabled = !empty($settings['enabled']) ? $settings['enabled'] : 'false';
$subject = isset($settings['subject']) ? $settings['subject'] : sprintf('Post %sed- [post_title]', $post_notification_type);
$from_name = isset($settings['from_name']) ? $settings['from_name'] : __('No Reply', 'front-editor');
$from_email = (isset($settings['from_email']) && !empty($settings['from_email'])) ? $settings['from_email'] : get_option( 'admin_email' );
$message = isset($settings['message']) ? $settings['message'] : 'Hi [author_name],' . PHP_EOL . PHP_EOL . 'Your post has been '.$post_notification_type.'ed [sitename] ([siteurl]).' . PHP_EOL . 'Here is the details: ' . PHP_EOL . 'Post Title: [post_title] ' . PHP_EOL . 'Author: [author_name] ' . PHP_EOL . 'Post URL: [post_link] ' . PHP_EOL . 'Edit URL: [post_link] ' . PHP_EOL;
?>

<h3><?= sprintf('Post %s notification', $post_notification_type); ?></h3>
<table class="form-table">
    <tr class="setting">
        <th><?php esc_html_e('Activate Notifications', 'front-editor'); ?></th>
        <td>
            <input type="checkbox" name="settings[notifications][<?= $prefix ?>][enabled]" value="true" <?php checked($enabled, 'true'); ?> />
        </td>
    </tr>
    <tr class="send_<?= $prefix ?>_notification_subject">
        <th><?php esc_html_e('Subject', 'front-editor'); ?></th>
        <td>
            <input type="text" class="big_width_input" name="settings[notifications][<?= $prefix ?>][subject]" value="<?php echo esc_attr($subject); ?>">
            <p class="description">Subject line for email alerts. You may include any of the following variables:
                <code>[sitename]</code>,<code>[siteurl]</code>,<code>[post_title]</code>,<code>[author_name]</code>,<code>[post_link]</code>,<code>[post_admin_link]</code>,<code>[post_content]</code>,<code>[post_status]</code>
            </p>
        </td>
    </tr>
    <tr class="send_<?= $prefix ?>_notification_from_name">
        <th><?php esc_html_e('From Name', 'front-editor'); ?></th>
        <td>
            <input type="text"  class="email_input" name="settings[notifications][<?= $prefix ?>][from_name]" value="<?php echo esc_attr($from_name); ?>">
        </td>
    </tr>
    <tr class="send_<?= $prefix ?>_notification_from_email">
        <th><?php esc_html_e('From Email', 'front-editor'); ?></th>
        <td>
            <input type="text"  class="email_input" name="settings[notifications][<?= $prefix ?>][from_email]" value="<?php echo esc_attr($from_email); ?>">
        </td>
    </tr>
    <tr class="send_<?= $prefix ?>_notification_text">
        <th><?php esc_html_e('Message', 'front-editor'); ?> </th>
        <td>
            <textarea rows="10" cols="80" name="settings[notifications][<?= $prefix ?>][message]"><?php echo esc_textarea($message); ?></textarea>
            <p class="description">
                Message for email alerts. Leave blank to use default message. You may include any of the following variables:
                <code>[sitename]</code>,<code>[siteurl]</code>,<code>[post_title]</code>,<code>[author_name]</code>,<code>[post_link]</code>,<code>[post_admin_link]</code>,<code>[post_content]</code>,<code>[post_status]</code>
            </p>
        </td>
    </tr>
</table>