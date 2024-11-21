<?php
use BFE\WooIntegration;
$enable_payments = isset($form_settings['enable_payments']) ? 'true' : 'false';
$direct_checkout = isset($form_settings['direct_checkout']) ? 'true' : 'false';

$product_titles = WooIntegration::listing_products_in_select();
$disabled = !fe_fs()->can_use_premium_code__premium_only() ? ' disabled' : '';
?>
<table class="form-table">
<p class="description"><?php  esc_html_e('These settings are applicable only when the WooCommerce plugin is installed.','front-editor'); ?></p>
    <tr>
        <th><?php esc_html_e('Payment Options', 'front-editor'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="settings[enable_payments]" <?php checked($enable_payments, 'true'); ?><?php esc_attr_e($disabled) ?>/>
                <?php if ($disabled) : ?>
                    <?php  esc_html_e('Available in Pro version','front-editor'); ?>
                <?php else: ?>
                    <?php  esc_html_e('Enable Payments','front-editor'); ?>
                <?php endif; ?>
            </label>
            <p class="description"><?php esc_html_e('Check to enable Payments for this form', 'front-editor'); ?>.</p>
        </td>
    </tr>
    <tr class="setting">
        <th><?= __('Select The Product', 'front-editor') ?></th>
        <td>
            <select name="settings[select_product]" id="fe_settings_select_product"<?php esc_attr_e($disabled) ?>>
                <?php
                $selected_product    = isset($form_settings['select_product']) ? $form_settings['select_product'] : '';
                if (is_array($product_titles)) {
                    foreach ($product_titles as $product_id => $title) {
                        printf('<option value="%s"%s>%s</option>', esc_attr($product_id), esc_attr(selected($selected_product, $product_id, false)), esc_html($title));
                    }
                } ?>
            </select>
            <?php if ($disabled) : ?>
                <p class="description"><?php  esc_html_e('Available in Pro version','front-editor'); ?></p>
            <?php endif; ?>
            <?php if (!class_exists('WooCommerce')) : ?>
                <p class="description"><?php _e('WooCommerce is not activated. Please install and activate WooCommerce.', 'front-editor') ?>.</p>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('The user will be redirect to this product when submit the form', 'front-editor'); ?>.</p>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e('Direct Checkout', 'front-editor'); ?></th>
        <td>
            <label>
                <input type="checkbox" name="settings[direct_checkout]" <?php checked($direct_checkout, 'true'); ?><?php esc_attr_e($disabled) ?>/>
                <?php if ($disabled) : ?>
                    <?php  esc_html_e('Available in Pro version','front-editor'); ?>
                <?php else: ?>
                    <?php  esc_html_e('Go direct to checkout','front-editor'); ?>
                <?php endif; ?>
            </label>
            <p class="description"><?php esc_html_e('Send your user direct to checkout not to the cart', 'front-editor'); ?>.</p>
        </td>
    </tr>
    <tr class="setting">
        <th><?= __('Product Added', 'front-editor') ?></th>
        <?php $default_checkout_msg = __('Product added to the cart. Check the cart.', 'front-editor') ?>
        <td>
            <input style="width:300px" type="text" name="settings[no_direct_checkout_msg]" placeholder="<?= $default_checkout_msg ?>" value="<?php echo esc_attr($form_settings['no_direct_checkout_msg']?? $default_checkout_msg); ?>"<?php esc_attr_e($disabled) ?>>
            <?php if ($disabled) : ?>
                <p class="description"><?php  esc_html_e('Available in Pro version','front-editor'); ?></p>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('This message user will see when product will be added to the cart.', 'front-editor'); ?>.</p>
        </td>
    </tr>
    <!-- Post redirection settings  -->
    <tr class="setting">
        <th><?= __('Redirect To', 'front-editor') ?></th>
        <td>
            <select name="settings[payment_redirect_to]" id="payment_redirect_to"<?php esc_attr_e($disabled) ?>>
                <?php
                $options = [
                    'disable' => __('No Redirect', 'front-editor'),
                    'post' => __('Same Post', 'front-editor'),
                    'wc_user_admin' => __('WooCommerce user admin', 'front-editor'),
                    //'url' => __('To a custom URL', 'front-editor'),
                ];

                $options_selected = isset($form_settings['payment_redirect_to']) ? $form_settings['payment_redirect_to'] : 'disable';

                foreach ($options as $option => $label) {
                    printf('<option value="%s"%s>%s</option>', esc_attr($option), esc_attr(selected($options_selected, $option, false)), esc_html($label));
                }; ?>
            </select>
            <?php if ($disabled) : ?>
                <p class="description"><?php  esc_html_e('Available in Pro version','front-editor'); ?></p>
            <?php endif; ?>
            <p class="description"><?= __('After successfully submit, where the page will redirect to', 'front-editor') ?></p>
        </td>
    </tr>

    <tr class="setting hidden_element" id="payment_redirect_to_link"<?php esc_attr_e($disabled) ?>>
        <th><?= __('Custom URL', 'front-editor') ?></th>
        <td>
            <input type="text" name="settings[payment_redirect_to_link]" value="<?php echo esc_attr($form_settings['payment_redirect_to_link']??''); ?>">
            <?php if ($disabled) : ?>
                <p class="description"><?php  esc_html_e('Available in Pro version','front-editor'); ?></p>
            <?php endif; ?>
        </td>
    </tr>
</table>