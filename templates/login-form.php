<?php
global $wp;
$redirect = $redirect ?? home_url($wp->request);

$settings = get_option('bfe_general_settings_login_register_group_options');

$username_placeholder = !empty($settings['login_username']['placeholder']) ? $settings['login_username']['placeholder'] : __('Username or Email', 'front-editor');
$username_label = !empty($settings['login_username']['label']) ? $settings['login_username']['label'] : __('Username or Email', 'front-editor');

$login_pw_placeholder = !empty($settings['login_pw']['placeholder']) ? $settings['login_pw']['placeholder'] : __('Password', 'front-editor');
$login_pw_label = !empty($settings['login_pw']['label']) ? $settings['login_pw']['label'] : __('Password', 'front-editor');

$login_redirect = !empty($settings['login_redirect']['link']) ? $settings['login_redirect']['link'] : $redirect;
$login_button_name = !empty($settings['login_button_name']) ? $settings['login_button_name'] : __('Login', 'front-editor');

?>
<form action method="post" class="fus_form fus_form_login">
    <?php
    $error = self::get_fus_error($fus_form_count);
    if ($error)
        printf('<p class="fus-info error">%s</p>', $error);

    $success = self::get_fus_success($fus_form_count);
    if ($success)
        printf('<p class="fus-info success">%s</p>', $success);
    ?>
    <p class="fus-input-wrap login-wrap">
        <label for="fus_username"><?php esc_html_e($username_label) ?></label>
        <input type="text" id="fus_username" name="fus_username" placeholder="<?php esc_attr_e($username_placeholder) ?>" />
    </p>

    <p class="fus-input-wrap password-wrap">
        <label for="fus_password"><?php esc_html_e($login_pw_label) ?></label>
        <input type="password" id="fus_password" name="fus_password" placeholder="<?php esc_attr_e($login_pw_placeholder) ?>"/>
    </p>
    <input type="hidden" name="redirect" value="<?= $login_redirect ?>">
    <input type="hidden" name="fus_action" value="login">
    <input type="hidden" name="fus_form" value="<?= $fus_form_count ?>">

    <?php
    $remember_field = isset($settings['login_remember_me']) ? $settings['login_remember_me'] : false;

    if (!empty($remember_field) && !empty($remember_field['checked'])) :
        $name = 'Remember Me';
        $label = 'Remember Me';
        if(isset($remember_field['label'])){
            $label = $remember_field['label'];
        }
    ?>
        <p class="forgetmenot">
            <input name="rememberme" type="checkbox" id="fus-rememberme" value="forever">
            <label for="fus-rememberme"><?= esc_html($label) ?></label>
        </p>
    <?php
    endif;
    ?>

    <button type="submit"><?php esc_html_e($login_button_name) ?></button>

</form>