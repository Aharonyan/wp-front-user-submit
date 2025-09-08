<?php

use BFE\LoginRegisterShortcodes;

/**
 * Register Form Template (register-form.php)
 * Enhanced with AJAX support - for webpack build
 */

// Variables passed from the shortcode handler:
// $fus_ajax_enabled - boolean indicating if AJAX is enabled
// $fus_form_id - unique form ID
// $fus_redirect - redirect URL after registration

$form_classes = ['fus-form'];
if (!empty($fus_ajax_enabled)) {
    $form_classes[] = 'fus-ajax-form';
}

$options = get_option('bfe_general_settings_login_register_group_options');
if (isset($options['code_editor_css'])) {
    echo '<style id="code-editor-css">' . $options['code_editor_css'] . '</style>';
}

// Get design CSS class
$design_class = LoginRegisterShortcodes::get_design_css_class($fus_form_design ?? null);
?>
<div class="<?php echo $design_class; ?>">
    <div class="fus-register-form-wrap">
        <!-- Message container for AJAX responses -->
        <div class="fus-message" style="display: none;"></div>

        <!-- Display server-side messages (for fallback mode) -->
        <?php if ($error = LoginRegisterShortcodes::get_fus_error($fus_form_id)): ?>
            <div class="fus-message error">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success = LoginRegisterShortcodes::get_fus_success($fus_form_id)): ?>
            <div class="fus-message success">
                <p><?php echo $success; ?></p>
            </div>
        <?php endif; ?>

        <form class="<?php echo implode(' ', $form_classes); ?>" method="post">
            <div class="fus-form-row">
                <div class="fus-form-group fus-half">
                    <label for="first_name_<?php echo $fus_form_id; ?>">
                        <?php _e('First Name', 'front-editor'); ?>
                    </label>
                    <input
                        type="text"
                        name="first_name"
                        id="first_name_<?php echo $fus_form_id; ?>"
                        class="fus-form-control"
                        autocomplete="given-name" />
                </div>

                <div class="fus-form-group fus-half">
                    <label for="last_name_<?php echo $fus_form_id; ?>">
                        <?php _e('Last Name', 'front-editor'); ?>
                    </label>
                    <input
                        type="text"
                        name="last_name"
                        id="last_name_<?php echo $fus_form_id; ?>"
                        class="fus-form-control"
                        autocomplete="family-name" />
                </div>
            </div>

            <div class="fus-form-group">
                <label for="fus_username_<?php echo $fus_form_id; ?>">
                    <?php _e('Username', 'front-editor'); ?> <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="fus_username"
                    id="fus_username_<?php echo $fus_form_id; ?>"
                    class="fus-form-control"
                    required
                    autocomplete="username" />
            </div>

            <div class="fus-form-group">
                <label for="fus_email_<?php echo $fus_form_id; ?>">
                    <?php _e('Email Address', 'front-editor'); ?> <span class="required">*</span>
                </label>
                <input
                    type="email"
                    name="fus_email"
                    id="fus_email_<?php echo $fus_form_id; ?>"
                    class="fus-form-control"
                    required
                    autocomplete="email" />
            </div>

            <div class="fus-form-group">
                <label for="website_<?php echo $fus_form_id; ?>">
                    <?php _e('Website', 'front-editor'); ?>
                </label>
                <input
                    type="url"
                    name="website"
                    id="website_<?php echo $fus_form_id; ?>"
                    class="fus-form-control"
                    placeholder="https://"
                    autocomplete="url" />
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="fus_action" value="register" />
            <input type="hidden" name="fus_form" value="<?php echo $fus_form_id; ?>" />

            <?php if (!empty($fus_redirect)): ?>
                <input type="hidden" name="redirect" value="<?php echo esc_url($fus_redirect); ?>" />
            <?php endif; ?>

            <?php if (empty($fus_ajax_enabled)): ?>
                <!-- Traditional nonce for fallback -->
                <?php wp_nonce_field('fus_register_nonce', 'fus_register_nonce'); ?>
            <?php endif; ?>

            <div class="fus-form-group">
                <p class="fus-password-note">
                    <?php _e('A password will be sent to your email address.', 'front-editor'); ?>
                </p>
            </div>

            <div class="fus-form-group">
                <input
                    type="submit"
                    class="fus-submit-btn"
                    value="<?php _e('Register', 'front-editor'); ?>" />
            </div>
        </form>
    </div>
</div>