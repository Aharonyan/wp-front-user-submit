<?php

/**
 * Design Selector Template
 * Reusable template for form design selection
 * 
 * Variables passed to template:
 * $designs - array of design options
 * $current_design - currently selected design
 * $has_pro - boolean if user has pro license
 * $field_name - field name for form
 * $field_args - field arguments
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="fus-design-selector">

    <div class="design-options-grid">
        <?php foreach ($designs as $design_key => $design): ?>
            <?php
            $is_disabled = $design['pro'] && !$has_pro;
            $is_selected = $current_design === $design_key;
            ?>

            <div class="design-option <?php echo $is_selected ? 'selected' : ''; ?> <?php echo $is_disabled ? 'disabled' : ''; ?>"
                data-design="<?php echo esc_attr($design_key); ?>">

                <label>
                    <input type="radio"
                        name="<?php echo esc_attr($field_args['name']); ?>[<?php echo esc_attr($field_name); ?>]"
                        value="<?php echo esc_attr($design_key); ?>"
                        <?php checked($is_selected); ?>
                        <?php disabled($is_disabled); ?> />

                    <div class="design-preview">
                        <img src="<?php echo esc_url($design['preview']); ?>"
                            alt="<?php echo esc_attr($design['name']); ?>"
                            loading="lazy" />

                        <?php if ($is_disabled): ?>
                            <div class="overlay-disabled">
                                <span class="lock-icon">🔒</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="design-info">
                        <h4 class="design-name">
                            <?php echo esc_html($design['name']); ?>
                            <?php if ($design['pro']): ?>
                                <span class="pro-badge">PRO</span>
                            <?php endif; ?>
                        </h4>

                        <?php if ($is_disabled): ?>
                            <p class="pro-notice">
                                <?php _e('Available in Pro Version', 'front-editor'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$has_pro): ?>
        <div class="pro-upgrade-notice">
            <h4><?php _e('🎨 Unlock All Form Designs', 'front-editor'); ?></h4>
            <p><?php _e('Get access to 7 additional professional form designs with the Pro version. Perfect for matching your brand and increasing conversions.', 'front-editor'); ?></p>
            <a href="https://wpfronteditor.com/pricing/" target="_blank" class="button button-primary">
                <?php _e('Upgrade to Pro', 'front-editor'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>