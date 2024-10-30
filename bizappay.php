<?php

/**
 * Plugin Name: Bizappay for WooCommerce
 * Plugin URI: https://laratechsystems.my/bizappay-woocommerce
 * Description: Bizappay for WooCommerce. Online payments made simple
 * Version: 1.0.5
 * Requires at least: 6.2
 * Requires PHP: 7.3
 * Author: Laratech Systems
 * Author URI: https://laratechsystems.my
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: bizappay
 */

if( ! defined( 'ABSPATH' ) ) { exit; }

// Declare Support For HPOS
add_action( 'before_woocommerce_init', function() {
	if( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables', __FILE__, true
		);
	}
} );

// Declare Support for Checkout Blocks
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

// Link To Settings @ plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'bizappay_actionlinks' );

function bizappay_actionlinks( $links ) {
  $plugin_links = array(
    '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bizappay' ) . '">' . __( 'Settings', 'bizappay' ) . '</a>',
  );

  # Merge our new link with the default ones
  return array_merge( $plugin_links, $links );
}

// Require Gateway PHP Class
require_once( 'bizappay_gateway.php' );

// Add Gateway To WooCommerce
add_filter( 'woocommerce_payment_gateways', function( $methods ) {

	$methods[] = 'Bizappay_Gateway'; 
	return $methods;

} );

// Blocks Support
add_action( 'woocommerce_blocks_loaded', function() {

	if( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once( 'bizappay_blocks.php' );
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new Bizappay_Blocks );
		} );
	}

} );

// callback from Bizappay
add_action('woocommerce_api_callback', function() {
	$callback = new Bizappay_Gateway();
	$callback->bizappay_callback();
});

// load css
add_action('wp_enqueue_scripts', 'bizappay_loadstyles');
function bizappay_loadstyles() {
    wp_register_style( 'namespace', plugins_url("assets/css/laratech.css", __FILE__), array(), '1.0.3' );
    wp_enqueue_style( 'namespace' );
}