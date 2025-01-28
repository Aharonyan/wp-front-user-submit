<?php

/**
 * Woocommerce integration.
 *
 * @package BFE;
 */
namespace BFE;

defined( 'ABSPATH' ) || exit;
/**
 * Class Woocommerce integration - registers custom gutenberg block.
 */
class WooIntegration {
    private static $form_settings = [];

    /**
     * Init logic.
     */
    public static function init() {
    }

    /**
     * Sets the entire form settings array.
     *
     * @param array $settings The new array of form settings to assign.
     */
    public static function set_form_settings( array $settings ) {
        self::$form_settings = $settings;
    }

    /**
     * Retrieves the form settings.
     *
     * This static method checks if the form settings are already set.
     * If not, it fetches the settings using the `Form::get_form_settings()` method
     * and assigns them to the static property `form_settings` using `set_form_settings()`.
     * 
     * @param int $form_id The ID of the form.
     * @return array The current form settings.
     */
    public static function get_form_settings( $form_id ) {
        if ( empty( self::$form_settings ) ) {
            $all_form_settings = Form::get_form_settings( $form_id );
            $form_settings = ( isset( $all_form_settings['form_settings'] ) ? $all_form_settings['form_settings'] : [] );
            self::set_form_settings( $form_settings );
        }
        return self::$form_settings;
    }

    /**
     * Adds a specified product to the WooCommerce cart with custom data.
     *
     * @param int $post_id The ID of the post associated with the product.
     * @param int $form_id The ID of the form providing settings for the product.
     * @return void Returns a JSON response indicating success or failure.
     */
    public static function add_product_to_cart( $post_id, $form_id, $post_data ) {
        // Check if WooCommerce is active
        if ( !class_exists( 'WooCommerce' ) ) {
            return;
        }
        $is_draft_button_clicked = false;
        if ( isset( $post_data['save_to_draft'] ) && $post_data['save_to_draft'] === "true" ) {
            $is_draft_button_clicked = true;
        }
        if ( $is_draft_button_clicked ) {
            return;
        }
        $payment_enabled = false;
        if ( isset( $post_data['payment_enabled'] ) ) {
            $payment_enabled = $post_data['payment_enabled'];
        }
        if ( !$payment_enabled ) {
            return;
        }
        if ( $post_data['is_post_already_payed'] ) {
            return;
        }
        $user_id = get_current_user_id();
        $product_id = self::get_product_id( $post_id, $form_id );
        $order_data = [
            'payment_product_id' => $product_id,
            'fus_post_id'        => $post_id,
            'fus_form_id'        => intval( $form_id ),
        ];
        // Initialize WooCommerce session if not already set
        if ( WC()->session === null ) {
            WC()->session = new \WC_Session_Handler();
            WC()->session->init();
        }
        //If the customer is not initialized, create one (handles anonymous users)
        if ( WC()->customer === null ) {
            WC()->customer = new \WC_Customer();
        }
        // If the cart is not initialized, load it
        if ( WC()->cart === null ) {
            WC()->cart = new \WC_Cart();
        }
        if ( is_null( WC()->customer ) ) {
            WC()->customer = new \WC_Customer($user_id, true);
        }
        // Remove the product from the cart if it exists
        if ( WC()->cart && !WC()->cart->is_empty() ) {
            WC()->cart->empty_cart();
        }
        $direct_checkout = self::direct_checkout( $post_id, $form_id );
        $no_direct_checkout_msg = self::no_direct_checkout_msg( $post_id, $form_id );
        $redirect_to = false;
        if ( $direct_checkout ) {
            $redirect_to = esc_url( wc_get_checkout_url() );
        }
        $product_added = \WC()->cart->add_to_cart(
            $product_id,
            1,
            0,
            [],
            $order_data
        );
        if ( !$product_added ) {
            wp_send_json_error( [
                'message' => __( 'Failed to add product to cart', 'front-editor' ),
            ], 500 );
        }
        update_post_meta( $post_id, 'add_to_cart_custom_data', $order_data );
        wp_send_json_success( [
            'url'          => get_the_permalink( $post_id ),
            'post_id'      => $post_id,
            'message'      => ( $direct_checkout || $redirect_to ? '->' : $no_direct_checkout_msg ),
            'message_type' => 'warning',
            'redirect_to'  => $redirect_to,
        ] );
    }

    /**
     * Sets the post status to 'draft' if payment is required.
     *
     * @param array $post_data The post data array to be saved.
     * @param array $post The raw $_POST data submitted.
     * @param array $files The raw $_FILES data associated with the post.
     * @param int|string $post_id The ID of the post or 'new' if it’s a new post.
     * @param int $form_id The ID of the form providing settings.
     * @return array Modified post data with potentially updated post status.
     */
    public static function change_post_data(
        $post_data,
        $post,
        $files,
        $post_id,
        $form_id
    ) {
        $form_settings = self::get_form_settings( $form_id );
        $is_draft_clicked = $post['save_to_draft'] === "true";
        $payment_enabled = false;
        if ( $is_draft_clicked ) {
            return $post_data;
        }
        if ( isset( $form_settings['enable_payments'] ) && isset( $form_settings['select_product'] ) ) {
            if ( !empty( $form_settings['select_product'] ) ) {
                $product = wc_get_product( $form_settings['select_product'] );
                if ( !empty( $product ) ) {
                    $payment_enabled = true;
                } else {
                    wp_send_json_error( [
                        'message' => __( "It looks like you're trying to pay for this post, but the product does not exist. Please contact support for assistance.", 'front-editor' ),
                    ] );
                }
            }
        }
        if ( !$payment_enabled ) {
            return $post_data;
        }
        $is_post_already_payed = self::is_post_already_payed( $post_id, $form_id );
        if ( !$is_draft_clicked && !$is_post_already_payed ) {
            $post_data['post_status'] = 'draft';
            $post_data['payment_enabled'] = $payment_enabled;
            $post_data['is_post_already_payed'] = $is_post_already_payed;
        }
        return $post_data;
    }

    /***
     * Is post already payed
     */
    public static function is_post_already_payed( $post_id, $form_id ) {
        $meta_key = 'fus_post_id';
        $meta_value = $post_id;
        $orders = wc_get_orders( [
            'status'     => ['wc-processing', 'wc-completed'],
            'limit'      => 1,
            'meta_key'   => $meta_key,
            'meta_value' => $meta_value,
        ] );
        if ( !empty( $orders ) ) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve product ID based on form settings.
     *
     * @return int|false Returns
     */
    public static function get_product_id( $post_id, $form_id ) {
        $form_settings = self::get_form_settings( $form_id );
        $enable_payments = ( isset( $form_settings['enable_payments'] ) ? $form_settings['enable_payments'] : false );
        $select_product = ( isset( $form_settings['select_product'] ) ? $form_settings['select_product'] : '' );
        if ( $enable_payments && $select_product ) {
            return intval( $select_product );
        }
        return false;
    }

    /**
     * Retrieve post status based on form settings.
     *
     * @return string Returns
     */
    public static function get_post_status( $post_id, $form_id ) {
        $form_settings = self::get_form_settings( $form_id );
        $fe_post_status = ( isset( $form_settings['fe_post_status'] ) ? $form_settings['fe_post_status'] : '' );
        return $fe_post_status;
    }

    /**
     * Retrieve direct checkout based on form settings.
     *
     * @return string Returns
     */
    public static function direct_checkout( $post_id, $form_id ) {
        $form_settings = self::get_form_settings( $form_id );
        $direct_checkout = ( isset( $form_settings['direct_checkout'] ) ? $form_settings['direct_checkout'] : false );
        return $direct_checkout;
    }

    /**
     * Retrieve no direct checkout message based on form settings.
     *
     * @return string Returns
     */
    public static function no_direct_checkout_msg( $post_id, $form_id ) {
        $form_settings = self::get_form_settings( $form_id );
        $cart_url = wc_get_cart_url();
        $cart_link = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $cart_url ), __( 'cart', 'front-editor' ) );
        $default_message = sprintf( __( 'Product added to the cart. Check the %s.', 'front-editor' ), $cart_link );
        $message = ( !empty( $form_settings['no_direct_checkout_msg'] ) ? $form_settings['no_direct_checkout_msg'] : $default_message );
        return $message;
    }

    /**
     * Retrieve no direct checkout message based on form settings.
     *
     * @return string Returns
     */
    public static function order_already_exist_msg( $post_id, $form_id ) {
        $form_settings = self::get_form_settings( $form_id );
        $default_message = __( 'Please complete payment to ensure the publication of your post.', 'front-editor' );
        $message = ( !empty( $form_settings['order_already_exist_msg'] ) ? $form_settings['order_already_exist_msg'] : $default_message );
        return $message;
    }

    /**
     * Retrieve redirect to based on form settings.
     *
     * @return string Returns
     */
    public static function redirect_to( $post_id, $form_id ) {
        $form_settings = self::get_form_settings( $form_id );
        $redirect_to = ( isset( $form_settings['payment_redirect_to'] ) ? $form_settings['payment_redirect_to'] : '' );
        $custom_url = ( !empty( $form_settings['payment_redirect_to_link'] ) ? esc_url( $form_settings['payment_redirect_to_link'] ) : '' );
        $payment_redirect = '';
        if ( $redirect_to == 'post' ) {
            $payment_redirect = Editor::get_post_edit_link( $post_id );
        } elseif ( $redirect_to == 'wc_user_admin' ) {
            $payment_redirect = ( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : '' );
        } elseif ( $redirect_to == 'url' ) {
            $payment_redirect = $custom_url;
        }
        return $payment_redirect;
    }

    /**
     * Listing products in select
     * 
     * @return array an array of product list
     */
    public static function listing_products_in_select() {
        $product_list = [];
        $product_list[''] = __( 'Select Product', 'front-editor' );
        if ( !class_exists( 'WooCommerce' ) ) {
            return $product_list;
        }
        $args = [
            'limit'  => -1,
            'status' => 'publish',
        ];
        $products = wc_get_products( $args );
        if ( !empty( $products ) ) {
            foreach ( $products as $product ) {
                $product_list[$product->get_id()] = $product->get_title();
            }
        }
        return $product_list;
    }

    public static function display_custom_data_in_cart( $item_data, $cart_item ) {
        if ( isset( $cart_item['fus_post_id'] ) ) {
            $item_data[] = [
                'name'  => __( 'Post Title', 'front-editor' ),
                'value' => get_the_title( $cart_item['fus_post_id'] ),
            ];
        }
        return $item_data;
    }

    public static function add_custom_data_to_order_items(
        $item,
        $cart_item_key,
        $values,
        $order
    ) {
        if ( isset( $values['fus_post_id'] ) ) {
            $item->add_meta_data( __( 'Post Title', 'front-editor' ), get_the_title( $values['fus_post_id'] ) );
            $item->add_meta_data( __( 'Post Id', 'front-editor' ), $values['fus_post_id'] );
            $order->update_meta_data( 'fus_post_id', $values['fus_post_id'] );
        }
        if ( isset( $values['fus_form_id'] ) ) {
            $item->add_meta_data( __( 'Form Id', 'front-editor' ), $values['fus_form_id'] );
            $order->update_meta_data( 'fus_form_id', $values['fus_form_id'] );
        }
        $order->save();
    }

    public static function hide_custom_data_on_thankyou( $formatted_meta, $item ) {
        if ( is_wc_endpoint_url( 'order-received' ) ) {
            foreach ( $formatted_meta as $meta_id => $meta ) {
                if ( $meta->key === 'Post Id' || $meta->key === 'Form Id' || $meta->key === 'User Id' ) {
                    unset($formatted_meta[$meta_id]);
                }
            }
        }
        return $formatted_meta;
    }

    public static function redirect_after_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( empty( $order ) ) {
            return;
        }
        $post_id = $order->get_meta( 'fus_post_id' );
        $form_id = $order->get_meta( 'fus_form_id' );
        if ( empty( $form_id ) ) {
            return;
        }
        if ( $post_id && $form_id && !is_admin() ) {
            $redirect_to = self::redirect_to( $post_id, $form_id );
            if ( filter_var( $redirect_to, FILTER_VALIDATE_URL ) !== false ) {
                wp_redirect( $redirect_to );
                exit;
            }
        }
    }

    public static function update_post_status_after_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( empty( $order ) ) {
            return;
        }
        $post_id = $order->get_meta( 'fus_post_id' );
        $form_id = $order->get_meta( 'fus_form_id' );
        $post_status = self::get_post_status( $post_id, $form_id );
        if ( empty( $form_id ) ) {
            return;
        }
        if ( empty( $post_status ) ) {
            return;
        }
        $post_data = [
            'ID'          => $post_id,
            'post_status' => $post_status,
        ];
        wp_update_post( $post_data );
    }

}

WooIntegration::init();