<?php

namespace BFE;

wp_enqueue_script('useradmin');

$options = get_option('bfe_general_user_admin_settings_group_options');
if (isset($options['code_editor_css'])) {
    echo '<style id="code-editor-css">' . $options['code_editor_css'] . '</style>';
}

$tabs = [
    'publish' => isset($options['publish_btn']) ? $options['publish_btn'] : __('Publish', 'front-editor'),
    'pending'  => isset($options['pending_btn']) ? $options['pending_btn'] : __('Pending', 'front-editor'),
    'draft' => isset($options['draft_btn']) ? $options['draft_btn'] : __('Draft', 'front-editor'),
];
$logout = isset($options['logout_btn']['label']) ? $options['logout_btn']['label'] : __('Logout', 'front-editor');
$all_user_post_label = isset($options['show_all_user_post_link']['label']) ? $options['show_all_user_post_link']['label'] : __('Show all users posts', 'front-editor');

?>
<div class="fe_fs_user_admin_wrap">
    <div class="fe_fs_header_buttons">
        <ul class="fe_fs_tabs">
            <?php
            foreach ($tabs as $slug => $name) :
            ?>
                <?php if (!isset($name['checked'])): ?>
                    <li class="<?php echo $post_status === $slug ? 'active-tab' : '' ?>">
                        <a href="<?php printf('?post_status=%s', $slug) ?>">
                            <?php $label = isset($name['label']) ? $name['label'] : $name; ?>
                            <button><?php esc_html_e($label) ?></button>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php if (!isset($options['logout_btn']['checked'])): ?>
            <div class="fus-logout">
                <a href="<?php echo wp_logout_url(get_permalink()); ?>"><button><?php esc_html_e($logout) ?></button></a>
            </div>
        <?php endif; ?>
    </div>

    <div class="fe_fs_post_list">
        <div class="fe_fs_title">
            <strong><?php echo strtoupper(__($post_status, 'front-editor')) ?></strong>
            <?php if ($user_can && !isset($options['show_all_user_post_link']['checked'])) : ?>
                <a href="<?php echo esc_url($current_url) ?>"><?php esc_html_e($all_user_post_label) ?></a>
            <?php endif; ?>
        </div>
        <?php
        if ($post_lists->have_posts()) :
        ?>
            <table>

                <?php
                while ($post_lists->have_posts()) :
                    $post_lists->the_post();
                    $post_url = get_the_permalink();
                    $post_id = get_the_ID();
                ?>

                    <tr>
                        <td class="fe_fs_img">
                            <a href="<?= $post_url ?>">
                                <div class="img__box"><?= wp_get_attachment_image(get_post_thumbnail_id($post_id), 'medium') ?></div>
                            </a>
                        </td>
                        <td>
                            <a href="<?= $post_url ?>">
                                <?= wp_trim_words(get_the_title(), 6) ?>
                            </a>
                        </td>
                        <?php $edit_link = Editor::get_post_edit_link($post_id); ?>
                        <?php if ($edit_link): ?>
                            <td class="fe_fs_icon_container">
                                <span class="fe_fs_edit__btn">
                                    <a href="<?= Editor::get_post_edit_link($post_id) ?>">
                                        <img class="fe_fs_icon" src="<?= FE_PLUGIN_URL . '/assets/img/edit.png' ?>" />
                                    </a>
                                </span>
                            </td>
                        <?php endif; ?>
                        <?php if (!isset($options['remove_post_icon']['checked'])) { ?>
                            <td class="fe_fs_icon_container">
                                <span class="fe_fs_delete__btn">
                                    <a href="<?php printf('?delete_post=%s', $post_id); ?>">
                                        <img class="fe_fs_icon" src="<?= FE_PLUGIN_URL . '/assets/img/delete.png' ?>" />
                                </span>
                            </td>
                        <?php } ?>
                    </tr>

                <?php endwhile; ?>
            </table>
            <?php
            // Pagination
            if (!isset($options['pagination']['checked'])) {
                $big = 999999999; // Need an unlikely integer for base
                echo paginate_links(array(
                    'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format'    => '?paged=%#%',
                    'current'   => max(1, get_query_var('paged')),
                    'total'     => $post_lists->max_num_pages,
                    'prev_text' => isset($options['pagination']['previous']) ? $options['pagination']['previous'] : __('« Previous', 'front-editor'),
                    'next_text' => isset($options['pagination']['next']) ? $options['pagination']['next'] : __('Next »', 'front-editor'),
                ));
            }
            wp_reset_postdata();

            ?>
        <?php else : ?>
            <?php $no_post_found = isset($options['no_post_found_text']['no_post_found']) ? $options['no_post_found_text']['no_post_found'] :  __('0 posts found', 'front-editor') ?>
            <?php if ($no_post_found) { ?>
                <p><?php esc_html_e($no_post_found); ?></p>
            <?php } ?>
        <?php endif; ?>
    </div>

</div>