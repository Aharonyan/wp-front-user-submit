<?php

function fe_admin_role()
{
    return apply_filters('fe_admin_role', 'manage_options');
}

/**
 * Format the post status for user dashboard
 *
 * @param string $status
 *
 * @since version 0.1
 *
 */
function fe_admin_post_status($status)
{
    if ('publish' === $status) {
        $title     = __('Published', 'wp-user-frontend');
        $fontcolor = '#009200';
    } elseif ('draft' === $status || 'private' === $status) {
        $title     = __('Draft', 'wp-user-frontend');
        $fontcolor = '#bbbbbb';
    } elseif ('pending' === $status) {
        $title     = __('Pending', 'wp-user-frontend');
        $fontcolor = '#C00202';
    } elseif ('future' === $status) {
        $title     = __('Scheduled', 'wp-user-frontend');
        $fontcolor = '#bbbbbb';
    }

    echo wp_kses_post('<span style="color:' . $fontcolor . ';">' . $title . '</span>');
}

/**
 * WP admin bar settings disable or not
 */
add_filter('show_admin_bar', 'fe_fs_disable_wp_admin_bar');

function fe_fs_disable_wp_admin_bar($show_admin_bar)
{
    $option = get_option('bfe_front_editor_wp_admin_menu');

    if (empty($option)) {
        return $show_admin_bar;
    }

    if($option == 'default') {
        return $show_admin_bar;
    }

    if(is_user_logged_in() && $option == 'display_logged_in'){
        return $show_admin_bar;
    }

    if ($option === 'display') {
        return true;
    }

    if ($option === 'disable') {
        return false;
    }

    if ($option === 'disable_but_admin') {
        $user = wp_get_current_user();
        if (current_user_can('administrator')) {
            return true;
        }

        return false;
    }

    return true;
}

/**
 * Users can see only oun attachments
 */
add_action('pre_get_posts', 'fe_fs_users_own_attachments');

function fe_fs_users_own_attachments($wp_query_obj)
{

    global $current_user, $pagenow;

    $is_attachment_request = ($wp_query_obj->get('post_type') == 'attachment');

    if (!$is_attachment_request)
        return;

    if (!is_a($current_user, 'WP_User'))
        return;

    if (!in_array($pagenow, array('upload.php', 'admin-ajax.php')))
        return;

    if (!current_user_can('edit_others_posts'))
        $wp_query_obj->set('author', $current_user->ID);

    return;
}

/**
 * Store errors to Sentry
 *
 * @return void
 */
function fe_fs_add_sentry_error($message, $function_name, $extra = [])
{
    $timestamp = time();
    $body = [
        "culprit" => $function_name,
        "timestamp" => $timestamp,
        "message" => $message,
        "environment" => 'prod',
        "extra" => [
            'wp_info' => [
                'wp_url' => get_bloginfo('url'),
                'admin_email' => get_bloginfo('admin_email'),
                'wp_version' => get_bloginfo('version'),
                'php_version' => phpversion(),
            ],
            'args' => $extra,
        ]
    ];

    // https://c56819c9e54046148a01ce8b1e5e7267@o1073647.ingest.sentry.io/api/6073383/store/
    $request = wp_remote_post('https://c56819c9e54046148a01ce8b1e5e7267@o1073647.ingest.sentry.io/api/6073383/store/', [
        'timeout'     => 45,
        'redirection' => 10,
        'headers' => [
            "Content-type" => "application/json",
            "X-Sentry-Auth" => "Sentry sentry_version=7,sentry_key=c56819c9e54046148a01ce8b1e5e7267,sentry_timestamp=" . $timestamp,
        ],
        'body' => json_encode($body),
        'sslverify' => false,
    ]);

    return $request;
}

/**
 * Search for template overrides
 * 
 * @return string
 */
function fe_template_path($template)
{
    if (!empty($path = locate_template('/front-user-submit/' . $template))) {
        return $path;
    } else {
        return FE_Template_PATH . $template;
    }
}

/**
 * Get the base URL of the current admin page, with query params.
 *
 * @return string
 */
function fus_get_current_admin_url()
{
    if (!is_admin()) {
        return false;
    }

    if (!isset($_SERVER['REQUEST_URI'])) {
        return false;
    }

    return admin_url(basename($_SERVER['REQUEST_URI']));
}
/**
 * Sort terms hierarchically
 *
 * @param Array $cats
 * @param Array $into
 * @param integer $parentId
 * @return void
 */
function sort_terms_hierarchically(array &$cats, array &$into, $parentId = 0)
{
    foreach ($cats as $i => $cat) {
        if ($cat->parent == $parentId) {
            $into[$cat->term_id] = $cat;
            unset($cats[$i]);
        }
    }

    foreach ($into as $topCat) {
        $topCat->children = array();
        sort_terms_hierarchically($cats, $topCat->children, $topCat->term_id);
    }
}

/**
 * Update attachment id
 *
 * @param int $att_id
 * @param string $filename
 * @return void
 */
function fus_rename_attachment($att_id, $new_filename)
{
    $file = get_attached_file($att_id);
    if (file_exists($file)) {
        $path = pathinfo($file);
        $new_file = $path['dirname'] . "/" . $new_filename . "." . $path['extension'];
        rename($file, $new_file);
        update_attached_file($att_id, $new_file);
        wp_update_post([
            'ID'         => $att_id,
            'post_title' => $new_filename
        ], true);
    }
}
