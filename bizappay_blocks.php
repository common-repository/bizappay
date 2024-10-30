<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Bizappay_Blocks extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'bizappay';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_bizappay_settings', [] );
		$this->gateway = new Bizappay_Gateway();
	}

	public function is_active() {
		return $this->get_setting( 'enabled' ) === 'yes';
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'bizappay-blocks-integration',
			plugins_url( 'checkout.js', __FILE__ ),
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			'1.0.3',
			true
		);

		if( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'bizappay-blocks-integration');
		}

		return [ 'bizappay-blocks-integration' ];
	}

	public function get_payment_method_data() {
		return [
			'title' => $this->gateway->title,
			'description' => $this->gateway->description,
			'icon' => $this->gateway->icon
		];
	}

}