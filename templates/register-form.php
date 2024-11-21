<?php
$settings = get_option('bfe_general_settings_login_register_group_options');

$name_fields = isset($settings['registration_first_last_name']) ? $settings['registration_first_last_name'] : false;

$error = self::get_fus_error($fus_form_count);
?>
<form action='' method='post' class='fus_form fus_form_register'>

    <?php if ($error)
        echo "<p class='error'>{$error}</p>";
    $success = self::get_fus_success($fus_form_count);
    if ($success)
        echo "<p class='success'>{$success}</p>";
    ?>


    <?php
    if (!empty($name_fields)) :
        foreach ($name_fields as $name => $field_options) :
            if (!empty($field_options['checked'])) :
                $required = isset($field_options['required']) ? 'required' : '';
    ?>
                <p class="name_field <?= esc_html($name) ?>">
                    <label for="<?= esc_html($name) ?>"><?= esc_html($field_options['label']) ?> <span style="color:red;"><?= !empty($required) ? '*' : '' ?></span></label>
                <div>
                    <input type="text" id="<?= esc_html($name) ?>" placeholder="<?= esc_html($field_options['placeholder']) ?>" name="<?= esc_html($name) ?>" <?= $required ?> />
                </div>
                </p>
    <?php endif;
        endforeach;
    endif;
    ?>

    <?php
    $website_field = isset($settings['registration_website_name']) ? $settings['registration_website_name'] : false;

    if (!empty($website_field)) :
        $name = 'website';
        if (!empty($website_field['checked'])) :
            $required = isset($website_field['required']) ? 'required' : '';
    ?>
            <p class="name_field <?= esc_html($name) ?>">
                <label for="<?= esc_html($name) ?>"><?= esc_html($website_field['label']) ?> <span style="color:red;"><?= !empty($required) ? '*' : '' ?></span></label>
            <div>
                <input type="url" id="<?= esc_html($name) ?>" placeholder="<?= esc_html($website_field['placeholder']) ?>" name="<?= esc_html($name) ?>" <?= $required ?> />
            </div>
            </p>
    <?php endif;
    endif;
    ?>

    <?php
    $username = isset($settings['registration_username']) ? $settings['registration_username'] : false;
    $name = 'fus_username';
    ?>

    <p class="name_field <?= esc_html($name) ?>">
        <label for="<?= esc_html($name) ?>">
            <?= isset($username['label']) ? esc_html($username['label']) : 'Username' ?> <span style="color:red;">*</span>
        </label>
    <div>
        <input type="text" id="<?= esc_html($name) ?>" placeholder="<?= isset($username['placeholder']) ? esc_html($username['placeholder']) : 'Username' ?>" name="<?= esc_html($name) ?>" required />
    </div>
    </p>

    <?php
    $email = isset($settings['registration_email']) ? $settings['registration_email'] : false;
    $name = 'fus_email';
    $label = 'Email';
    if($email && isset($email['label'])){
        $label = esc_html($email['label']);
    }
    $placeholder = 'Email';
    if($placeholder && isset($email['placeholder'])){
        $label = esc_html($email['placeholder']);
    }
    ?>

    <p class="name_field <?= esc_html($name) ?>">
        <label for="<?= esc_html($name) ?>">
            <?= $label ?> <span style="color:red;">*</span>
        </label>
    <div>
        <input type="email" id="<?= esc_html($name) ?>" placeholder="<?= $placeholder ?>" name="<?= esc_html($name) ?>" required />
    </div>
    </p>

    <?php
    // where to redirect on success
    $redirect = isset($settings['registration_redirect']) ? $settings['registration_redirect'] : false;
    if (isset($redirect['link']) && !empty($redirect['link']))
        printf('<input type="hidden" name="redirect" value="%s">', $redirect['link']);
    ?>

    <input type="hidden" name="fus_action" value="register">
    <input type="hidden" name="fus_form" value="<?= $fus_form_count ?>">

    <?php wp_nonce_field('fus_register_nonce', 'fus_register_nonce'); ?>

    <button type='submit'><?= isset($settings['registration_button_name']) ? $settings['registration_button_name'] : __('Register', 'front-editor') ?></button>
</form>