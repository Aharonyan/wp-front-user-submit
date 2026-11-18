<?php

/**
 * "Preview on my form" page
 *
 * Variables: $form_html, $form_id, $preview_design, $has_pro
 */

defined('ABSPATH') || exit;

$edit_url = admin_url(sprintf('admin.php?page=fe-post-forms&action=edit&id=%d', $form_id));
$is_locked = $preview_design['pro'] && !$has_pro;
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <?php wp_head(); ?>
    <style>
        html {
            margin-top: 0 !important;
        }

        .fus-design-preview-bar {
            position: sticky;
            top: 0;
            z-index: 9999;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: #111827;
            color: #f9fafb;
            font: 14px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .fus-design-preview-bar strong {
            font-weight: 600;
        }

        .fus-design-preview-bar .fus-pro {
            padding: 2px 8px;
            border-radius: 999px;
            background: #6161ff;
            font-size: 11px;
            font-weight: 700;
        }

        .fus-design-preview-bar .fus-actions {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .fus-design-preview-bar a {
            padding: 7px 14px;
            border-radius: 6px;
            background: #374151;
            color: #fff;
            text-decoration: none;
        }

        .fus-design-preview-bar a.fus-primary {
            background: #6161ff;
        }

        .fus-design-preview-body {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 16px;
        }
    </style>
</head>

<body <?php body_class('fus-design-preview'); ?>>
    <div class="fus-design-preview-bar">
        <span>
            <?php esc_html_e('Design preview:', 'front-editor'); ?>
            <strong><?php echo esc_html($preview_design['name']); ?></strong>
        </span>
        <?php if ($preview_design['pro']) : ?>
            <span class="fus-pro">PRO</span>
        <?php endif; ?>
        <span class="fus-actions">
            <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Back to form', 'front-editor'); ?></a>
            <?php if ($is_locked) : ?>
                <a class="fus-primary" href="https://wpfronteditor.com/pricing/" target="_blank"><?php esc_html_e('Upgrade to Pro', 'front-editor'); ?></a>
            <?php endif; ?>
        </span>
    </div>
    <main class="fus-design-preview-body">
        <?php echo $form_html; ?>
    </main>
    <?php wp_footer(); ?>
</body>

</html>
