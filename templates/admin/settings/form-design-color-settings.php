<?php
$settings = [];
if (isset($form_settings['design'])) {
    $settings = $form_settings['design'];
}

$buttons = [];

if (isset($settings['buttons'])) {
    $buttons = $settings['buttons'];
}

$button_background = isset($buttons['background']) ? $buttons['background'] : '#6161ff';
$button_color = isset($buttons['color']) ? $buttons['color'] : '#ffffff';
$button_border = isset($buttons['border']) ? $buttons['border'] : '#6161ff';
$button_radius = isset($buttons['border-radius']) ? $buttons['border-radius'] : 5;
?>
<tbody id="show-design-colors">
    <th><?= __('Color Settings', 'front-editor') ?></th>
    <tr class="setting">
        <th><?= __('Buttons background color', 'front-editor') ?></th>
        <td>
            <input type="color" name="settings[design][buttons][background]" value="<?= $button_background ?>" />
        </td>
    </tr>
    <tr class="setting">
        <th><?= __('Buttons text color', 'front-editor') ?></th>
        <td>
            <input type="color" name="settings[design][buttons][color]" value="<?= $button_color ?>" />
        </td>
    </tr>
    <tr class="setting">
        <th><?= __('Buttons border color', 'front-editor') ?></th>
        <td>
            <input type="color" name="settings[design][buttons][border]" value="<?= $button_border ?>" />
        </td>
    </tr>
    <tr class="setting">
        <th><?= __('Buttons border radius', 'front-editor') ?></th>
        <td>
            <input type="number" name="settings[design][buttons][border-radius]" value="<?= $button_radius ?>" />
        </td>
    </tr>
</tbody>