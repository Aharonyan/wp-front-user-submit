<?php

$restricted_message = ( !empty( $form_settings['login']['restricted_message'] ) ? $form_settings['login']['restricted_message'] : 'This page is restricted. Please Login to view this page.' );
$login_title = ( !empty( $form_settings['login']['login_title'] ) ? $form_settings['login']['login_title'] : '' );
$show_login = ( !empty( $form_settings['login']['show_login'] ) ? $form_settings['login']['show_login'] : 'false' );
$show_register = ( !empty( $form_settings['login']['show_register'] ) ? $form_settings['login']['show_register'] : 'false' );
?>

<h3><?php 
echo __( 'Login / Register Settings', 'front-editor' );
?></h3>
<table class="form-table">
    <tr class="setting">
        <th><?php 
esc_html_e( 'Form Restriction Message', 'front-editor' );
?></th>
        <td>
            <input type="text" name="settings[login][restricted_message]" value="<?php 
echo esc_attr( $restricted_message );
?>" style="width:350px">
            <p class="description"><?php 
echo __( 'Will be shown to the user if he is not registered and guest posting is not active', 'front-editor' );
?></p>
        </td>
    </tr>
    <tr class="setting">
        <th><?php 
esc_html_e( 'Show login', 'front-editor' );
?></th>
        <td>
            <?php 
?>
                <input type="checkbox" name="demo_show_login" value="true" <?php 
checked( $show_login, 'true' );
?> />
            <?php 
?>
            <p class="description"><?php 
esc_html_e( 'Show login instead of restricted message (Pro)', 'front-editor' );
?>.</p>
        </td>
    </tr>
</table>