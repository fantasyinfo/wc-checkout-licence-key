<?php
/**
 * Plugin Name: WooCommerce Checkout Call API
 * Description: Sends WooCommerce checkout details to an external API.
 * Version: 1.0
 * Author: Gaurav Sharma
 * Auther URI: https://www.freelancer.com/u/fantasyinfo
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if WooCommerce is active
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // Hook into WooCommerce checkout update order meta
    add_action('woocommerce_checkout_update_order_meta', 'send_checkout_details_to_api');

    function send_checkout_details_to_api($order_id) {
  $order = wc_get_order($order_id);

        // Get the first product ID and name from the order
        $items = $order->get_items();
        foreach ( $items as $item ) {
            $product_id = $item->get_product_id();
            $product_name = $item->get_name();
            break; // only need the first product
        }

      if($product_id ){
              // Collect user information
        $user_id = $order->get_user_id();
        $user_info = get_userdata($user_id);
        $buyer_username = $user_info->user_login;

        // Fetch the user's password
        $buyer_password = get_user_meta($user_id, '_user_password', true); // Assume user password is stored as user meta
        if (empty($buyer_password)) {
            $buyer_password = wp_generate_password(); // Generate a new password if not found
            update_user_meta($user_id, '_user_password', $buyer_password); // Save the generated password
        }

        $data = array(
            'type' => 'INSERT_BUYER_INFO_ON_CHECKOUT',
          	'order_id' => $order_id,
            'product_id' => $product_id,
            'product_name' => $product_name,
            'buyer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'buyer_email' => $order->get_billing_email(),
            'buyer_username' => $buyer_username,
            'buyer_password' => $buyer_password,
        );

        $api_url = 'https://masplusmas.com/api/api.php';

        $response = wp_remote_post($api_url, array(
            'method'    => 'POST',
            'body'      => json_encode($data),
            'headers'   => array(
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            error_log('Error sending order data to API: ' . $response->get_error_message());
        } else {
            error_log('Order data sent successfully to API.');
        }
      }
  
    }
} else {
    // Admin notice if WooCommerce is not active
    add_action( 'admin_notices', 'woocommerce_inactive_notice' );

    function woocommerce_inactive_notice() {
        ?>
        <div class="error">
            <p><?php _e( 'WooCommerce API Integration requires WooCommerce to be installed and active.', 'woocommerce-api-integration' ); ?></p>
        </div>
        <?php
    }
}
