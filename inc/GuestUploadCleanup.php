<?php

/**
 * Deletes files guests uploaded through a form but never used in a submitted post
 *
 * @package BFE;
 */

namespace BFE;

defined('ABSPATH') || exit;

class GuestUploadCleanup
{
    /**
     * Meta key with the upload time of a guest file no submitted post uses yet.
     * Files uploaded before this existed have no such meta and are never deleted.
     */
    const UNCLAIMED_META = '_fus_unclaimed_upload';

    const CRON_HOOK = 'bfe_cleanup_guest_uploads';

    /**
     * Unclaimed files older than this are deleted (filter bfe_guest_upload_cleanup_age, 0 = keep all).
     * Longer than the guest cookie lives: after that the guest cannot use the file any more.
     */
    const MAX_AGE = 2 * DAY_IN_SECONDS;

    /**
     * The filter cannot go below the guest cookie lifetime: the guest may still be filling in the form
     */
    const MIN_AGE = DAY_IN_SECONDS;

    /**
     * Files checked per run; a full batch schedules the next run a few minutes later
     */
    const BATCH_SIZE = 100;

    const NEXT_BATCH_DELAY = 5 * MINUTE_IN_SECONDS;

    public static function init()
    {
        add_action(self::CRON_HOOK, [__CLASS__, 'delete_unclaimed']);
        add_action('bfe_ajax_after_front_editor_post_update_or_creation', [__CLASS__, 'claim_guest_uploads']);
        add_action('BFE_deactivate', [__CLASS__, 'unschedule']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Remember when a guest uploaded the file
     *
     * @param int $attachment_id
     * @return void
     */
    public static function mark_unclaimed($attachment_id)
    {
        update_post_meta((int) $attachment_id, self::UNCLAIMED_META, time());
    }

    /**
     * The guest submitted a post: keep every file this guest browser uploaded,
     * whichever field of the post uses it
     *
     * @return void
     */
    public static function claim_guest_uploads()
    {
        if (is_user_logged_in()) {
            return;
        }

        $attachment_ids = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => SavePost::GUEST_UPLOAD_META, 'value' => UploadSecurity::guest_upload_marker()],
                ['key' => self::UNCLAIMED_META, 'compare' => 'EXISTS'],
            ],
        ]);

        foreach ($attachment_ids as $attachment_id) {
            delete_post_meta($attachment_id, self::UNCLAIMED_META);
        }
    }

    /**
     * Cron: delete unattached guest files no post was submitted with
     *
     * @return int number of deleted files
     */
    public static function delete_unclaimed()
    {
        $max_age = (int) apply_filters('bfe_guest_upload_cleanup_age', self::MAX_AGE);
        if ($max_age <= 0) {
            return 0;
        }
        $max_age = max($max_age, self::MIN_AGE);

        $attachment_ids = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_parent'    => 0,
            'fields'         => 'ids',
            'posts_per_page' => self::BATCH_SIZE,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'no_found_rows'  => true,
            'meta_query'     => [
                ['key' => self::UNCLAIMED_META, 'value' => time() - $max_age, 'compare' => '<', 'type' => 'NUMERIC'],
            ],
        ]);

        $deleted = 0;
        foreach ($attachment_ids as $attachment_id) {
            if (self::is_in_use($attachment_id)) {
                delete_post_meta($attachment_id, self::UNCLAIMED_META);
                continue;
            }

            if (wp_delete_attachment($attachment_id, true)) {
                $deleted++;
                continue;
            }

            error_log(sprintf('Front User Submit: could not delete unused guest upload %d', $attachment_id));
            // not checked again, so a full batch of such files cannot stall the cleanup
            delete_post_meta($attachment_id, self::UNCLAIMED_META);
        }

        if (count($attachment_ids) === self::BATCH_SIZE) {
            wp_schedule_single_event(time() + self::NEXT_BATCH_DELAY, self::CRON_HOOK);
        }

        return $deleted;
    }

    /**
     * Someone took the file over: an author, a post shows it as featured image, or a post links it
     * in its content (Markdown, TinyMCE, EditorJS file blocks keep only the URL)
     *
     * @param int $attachment_id
     * @return bool
     */
    public static function is_in_use($attachment_id)
    {
        if ((int) get_post_field('post_author', $attachment_id) !== 0) {
            return true;
        }

        $in_use = self::is_featured_image($attachment_id) || self::is_in_post_content($attachment_id);

        return (bool) apply_filters('bfe_guest_upload_in_use', $in_use, $attachment_id);
    }

    private static function is_featured_image($attachment_id)
    {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %s LIMIT 1",
            (string) $attachment_id
        ));
    }

    /**
     * Content links the file or one of its image sizes ("2026/11/photo" matches photo-300x200.jpg too)
     */
    private static function is_in_post_content($attachment_id)
    {
        global $wpdb;

        $file = (string) get_post_meta($attachment_id, '_wp_attached_file', true);
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $path = $extension !== '' ? substr($file, 0, -strlen($extension) - 1) : $file;
        if ($path === '') {
            // no file path to look for: keep the file
            return true;
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'revision') AND post_content LIKE %s LIMIT 1",
            '%' . $wpdb->esc_like($path) . '%'
        ));
    }

    public static function unschedule()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }
}

GuestUploadCleanup::init();
