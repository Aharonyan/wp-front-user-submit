<?php

namespace BFE;

class Notifications
{
    public static function init()
    {
        add_action('bfe_ajax_after_front_editor_post_inserted', [__CLASS__, 'send_admin_notification'], 10, 2);

        add_action('bfe_ajax_after_front_editor_post_inserted', [__CLASS__, 'send_submit_notification'], 10, 2);

        add_action('post_updated', [__CLASS__, 'init_post_notification'], 10, 3);
    }

    public static function init_post_notification($post_id, $post_after, $post_before)
    {
        $form_id = get_post_meta($post_id, 'BFE_the_post_edited_by', true);
        if (empty($form_id)) {
            return;
        }
        $form_settings = get_post_meta($form_id, 'fe_form_settings', true);
        $type = false;

        if ($post_before->post_status !== 'publish' && $post_after->post_status === 'publish') {
            $type = 'post_publish';
        } elseif ($post_before->post_status !== 'trash' && $post_after->post_status === 'trash') {
            $type = 'post_trash';
        }

        if (!$type) {
            return;
        }

        self::send_post_notification($post_id, $type, $form_settings);
    }

    public static function send_submit_notification($post_id, $form_id)
    {
        $form_settings = get_post_meta($form_id, 'fe_form_settings', true);
        $type = 'post_submit';

        self::send_post_notification($post_id, $type, $form_settings);
    }

    public static function send_post_notification($post_id, $type, $form_settings)
    {
        if (!isset($form_settings['notifications'])) {
            return;
        }

        if (!isset($form_settings['notifications'][$type])) {
            return;
        }

        if(!isset($form_settings['notifications'][$type]['enabled'])){
            return;
        }

        if (!$form_settings['notifications'][$type]['enabled']) {
            return;
        }

        $args = $form_settings['notifications'][$type];

        $post_array = get_post($post_id, 'ARRAY_A');

        $user_info = get_userdata($post_array['post_author']);

        if (empty($post_array)) {
            return;
        }

        $args['send_to'] = $user_info->user_email;

        self::send_email($post_id, $args);
    }

    /**
     * Send notification when post created
     *
     * @param [type] $post_id
     * @param [type] $form_id
     * @return void
     */
    public static function send_admin_notification($post_id, $form_id)
    {
        $form_settings = get_post_meta($form_id, 'fe_form_settings', true);
        $notification_text = $form_settings['send_admin_notification_text'];
        $subject = $form_settings['send_admin_notification_subject'];
        $send_to = $form_settings['send_admin_notification_to'];

        // if is not enabled notifications
        if (empty($form_settings['admin_notifications'])) {
            return;
        }

        if (empty($notification_text)) {
            return;
        }

        $settings = [
            'message' => $notification_text,
            'subject' => $subject,
            'send_to' => $send_to,
            'from_email' => get_bloginfo('admin_email') ?? '',
            'from_name' => 'Front User Submit'
        ];

        self::send_email($post_id, $settings);
    }

    public static function send_email($post_id, $settings)
    {
        $args = [
            'message' => $settings['message'] ?? '',
            'subject' => $settings['subject'] ?? '',
            'send_to' => $settings['send_to'] ?? '',
            'from_email' => $settings['from_email'] ?? '',
            'from_name' => $settings['from_name'] ?? 'admin'
        ];

        $post_array = get_post($post_id, 'ARRAY_A');

        if (empty($post_array)) {
            return;
        }

        $args = [
            'sitename' => get_bloginfo('name'),
            'siteurl' => home_url(),
            'post_title' => $post_array['post_title'],
            'post_content' => apply_filters('the_content', $post_array['post_content']),
            'post_status' => $post_array['post_status'],
            'post_admin_link' => get_edit_post_link($post_id),
            'post_link' => get_permalink($post_id),
            'author_name' => get_the_author_meta('user_nicename', $post_array['post_author'])
        ];

        $message = $settings['message'];
        // Replace variables in text
        foreach ($args as $key => $value) {
            $message = preg_replace(sprintf('/\[%s]/', $key), $value, $message);
        }

        $subject = $settings['subject'];
        // the same for subject
        foreach ($args as $key => $value) {
            $subject = preg_replace(sprintf('/\[%s]/', $key), $value, $subject);
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        if (!empty($settings['from_email'])) {
            $headers[] = sprintf('From: %s <%s>', $settings['from_name'], $settings['from_email']);
        }

        wp_mail($settings['send_to'], $subject, nl2br($message), $headers);
    }
}

Notifications::init();
