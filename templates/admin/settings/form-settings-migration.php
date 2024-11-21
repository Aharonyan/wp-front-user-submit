<table class="form-table">
    <tr class="update-message">
        <td>
            <textarea id="fe-migrate-settings" name="fe_migrate_setting"></textarea>
            <div class="fe-copy-migrate-msg"><?php _e('JSON copied!', 'front-editor') ?></div>
            <div class="fe-migration-btns">
                <button class="button fe-copy-migrate-setting"><?php _e('Copy', 'front-editor') ?></button>
                <button class="button button-primary fe-migrate-setting" data-migrate="import"><?php _e('Migrate', 'front-editor') ?></button>
            </div>
            <?php printf(
                '<div><b>%s</b> %s</div>', 
                __('Note:', 'front-editor'), 
                __('To transfer the form settings, simply copy the text from this textarea, paste it into the textarea of the other form, and click the "Migrate" button.', 'front-editor') 
                ) ?>
        </td>
    </tr>
</table>