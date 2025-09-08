<?php

use BFE\LoginRegisterShortcodes;

/**
 * Login Form Template (login-form.php)
 * Enhanced with AJAX support - for webpack build
 */

// Variables passed from the shortcode handler:
// $fus_ajax_enabled - boolean indicating if AJAX is enabled
// $fus_form_id - unique form ID
// $fus_redirect - redirect URL after login

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
    <div class="fus-login-form-wrap">
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
            <div class="fus-form-group">
                <label for="fus_username_<?php echo $fus_form_id; ?>">
                    <?php _e('Username or Email', 'front-editor'); ?>
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
                <label for="fus_password_<?php echo $fus_form_id; ?>">
                    <?php _e('Password', 'front-editor'); ?>
                </label>
                <input
                    type="password"
                    name="fus_password"
                    id="fus_password_<?php echo $fus_form_id; ?>"
                    class="fus-form-control"
                    required
                    autocomplete="current-password" />
            </div>

            <div class="fus-form-group fus-checkbox-group">
                <label>
                    <input
                        type="checkbox"
                        name="rememberme"
                        value="1" />
                    <?php _e('Remember Me', 'front-editor'); ?>
                </label>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="fus_action" value="login" />
            <input type="hidden" name="fus_form" value="<?php echo $fus_form_id; ?>" />

            <?php if (!empty($fus_redirect)): ?>
                <input type="hidden" name="redirect" value="<?php echo esc_url($fus_redirect); ?>" />
            <?php endif; ?>

            <?php if (empty($fus_ajax_enabled)): ?>
                <!-- Traditional nonce for fallback -->
                <?php wp_nonce_field('fus_register_nonce', 'fus_register_nonce'); ?>
            <?php endif; ?>

            <div class="fus-form-group">
                <input
                    type="submit"
                    class="fus-submit-btn"
                    value="<?php _e('Log In', 'front-editor'); ?>" />
            </div>
        </form>
    </div>
</div>