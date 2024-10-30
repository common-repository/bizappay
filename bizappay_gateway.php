<?php

if( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'woocommerce_init', function() {

	// Bail If Preexists
	if( class_exists( 'Bizappay_Gateway' ) ) {
		return;
	}

	// Payment Gateway Class
	class Bizappay_Gateway extends WC_Payment_Gateway {

		// Constructor
		public function __construct() {
			$this->has_fields = false;
			$this->id = 'bizappay';
     		$this->method_title = __('Bizappay', 'bizappay');
     		$this->method_description = __('Accept payments via Bizappay', 'bizappay');
     		$this->order_button_text = __('Pay with Bizappay', 'bizappay');

			$this->init_form_fields();
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );

			$this->icon = plugins_url("assets/img/logo.png", __FILE__);

			add_action(
				'woocommerce_update_options_payment_gateways_' . $this->id,
				[ $this, 'process_admin_options' ]
			);
		}

		// Settings Fields
		public function init_form_fields() {

			$this->form_fields = [

		          'enabled'        => array(
		              'title'   => __( 'Enable / Disable', 'bizappay' ),
		              'label'   => __( 'Activate this payment gateway', 'bizappay' ),
		              'type'    => 'checkbox',
		              'default' => 'no',
		          ),
		          'title'          => array(
		            'title'    => __( 'Title', 'bizappay' ),
		            'type'     => 'text',
		            'desc_tip' => __( 'Payment title the customer will see during the checkout process.', 'bizappay' ),
		            'default'  => __( 'Bizappay', 'bizappay' ),
		          ),
		          'description'    => array(
		            'title'    => __( 'Description', 'bizappay' ),
		            'type'     => 'textarea',
		            'desc_tip' => __( 'Payment description the customer will see during the checkout process.', 'bizappay' ),
		            'default'  => __( 'Secure payment with Bizappay.', 'bizappay' ),
		            'css'      => 'max-width:350px;'
		          ),
		          'category_code' => array(
		            'title'    => __( 'Category Code', 'bizappay' ),
		            'type'     => 'text',
		            'desc_tip' => __( 'Obtain the category code from your Bizappay category page', 'bizappay' ),
		          ),
		          'merchant_email' => array(
		            'title'    => __( 'Email / Username', 'bizappay' ),
		            'type'     => 'text',
		            'desc_tip' => __( 'Provide the username or email associated with your Bizappay account', 'bizappay' ),
		          ),
		          'merchant_key'      => array(
		            'title'    => __( 'Merchant API Key', 'bizappay' ),
		            'type'     => 'text',
		            'desc_tip' => __( 'Obtain this API key from your Bizappay settings page', 'bizappay' ),
		          ),
		          'enabled_sandbox' => array(
		            'title'   => __( 'Enable Sandbox Mode', 'bizappay' ),
		            'label'   => __( 'You need to register a sandbox account at <a href="https://stg.bizappay.my/merchant">BIZAPPAY SANDBOX</a>. Make sure Category Code, Email / Username and Merchant API Key (above fields) follows your Bizappay sandbox account. Remember! all sandbox transactions will not get paid from Bizappay.<br><br>Use this bank for testing<br><b>SBI BANK A</b><br>Username: 1234 Password: 1234', 'bizappay' ),
		            'type'    => 'checkbox',
		            'default' => 'no',
		          ),
				  'additional' => array(
					'title'	=> __('Additional settings', 'bizappay'),
					'type'	=> 'title',
					'description'	=> ''
				  ),
				  'bill_expiry'	=> array(
					'title'		=> 'Bill Expiry',
					'type'		=> 'select',
					'label'		=> __('Enable bill expiry', 'bizappay'),
					'default'	=> '',
					'options'	=> array(
						''	=>	__('Not set', 'bizappay'),
						'5'	=>	__('after 5 minutes', 'bizappay'),
						'10'	=>	__('after 10 minutes', 'bizappay'),
						'15'	=>	__('after 15 minutes', 'bizappay'),
						'30'	=>	__('after 30 minutes', 'bizappay'),
						'60'	=>	__('after 1 hour', 'bizappay'),
						'120'	=>	__('after 2 hours', 'bizappay'),
						'180'	=>	__('after 3 hours', 'bizappay'),
						'240'	=>	__('after 4 hours', 'bizappay'),
						'1440'	=>	__('after 1 day', 'bizappay')						
					),
					'description'	=>	__('The generated bill will automatically expire according to the selected option if no payment is made.', 'bizappay')
				  )

		    ];
		}

		// submit order to bizappay
		public function process_payment( $order_id ) {

			$url = '';
		    $order = wc_get_order($order_id);

		    $order_id = $order->get_id();
		    $amount   = $order->get_total();
		    $name     = $order->get_billing_first_name().' '.$order->get_billing_last_name();
		    $email    = $order->get_billing_email();
		    $phone    = $order->get_billing_phone();

		    $order_name = "Order_".$order_id;

		    $hash_value = md5( $this->settings['merchant_key'] . $order_name . $amount . $order_id );

		    // prepare callback url
		    $cb_url = parse_url(wc_get_checkout_url());

		    // data to be posted to Bizappay API
		    $post_args = array(
		        'detail'   => $order_name,
		        'amount'   => $amount,
		        'order_id' => $order_id,
		        'hash'     => $hash_value,
		        'name'     => $name,
		        'email'    => $email,
		        'phone'    => $phone,
		        'merchant'    => $this->settings['merchant_email'],
		        'category'    => $this->settings['category_code'],
				'bill_expiry' => $this->settings['bill_expiry'],
		        'checkout_page' => $order->get_checkout_order_received_url(),
		        'callback_url' => $cb_url['scheme'].'://'.$cb_url['host'].'?vid=5781', // plugin vid

		    );

		    if($this->settings['enabled_sandbox'] == "yes") {
		        $url = 'https://stg.bizappay.my/api/wordpress?';
		    } else {
		        $url = 'https://bizappay.my/api/wordpress?';
		    }

		    # Format it properly using get
		    $bizappay_args = '';
		    foreach ( $post_args as $key => $value ) {
		       	if ( $bizappay_args != '' ) {
		          $bizappay_args .= '&';
		        }
		        $bizappay_args .= $key . "=" . $value;
		      }

		    return array(
		        'result'   => 'success',
		        'redirect' => $url . $bizappay_args
		    );

		} // End Process Payment Function


		// receives callback from bizappay
		public function bizappay_callback()
		{
			// sanitize all $_GET params from bizappay
			$msg = filter_input(INPUT_GET, "msg", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$order_id = filter_input(INPUT_GET, "order_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$status_id =  filter_input(INPUT_GET, "status_id", FILTER_SANITIZE_NUMBER_INT);
			$transaction_id = filter_input(INPUT_GET, "transaction_id", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$returned_hash = filter_input(INPUT_GET, "hash", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

			if ( isset($status_id) && isset($order_id) && $msg==true && isset($transaction_id) && isset($returned_hash) ) {

				$order = wc_get_order( $order_id );

				if($order && $order->get_id() != 0) {

					// build hash from returned value from Bizappay
					$hash = md5( $this->settings['merchant_key'] . $status_id . $order_id . $transaction_id . $msg );

					if($hash == $returned_hash) { // if both hash is same

						// update order status if payment success and order is pending
						if ( $status_id == 1 && strtolower( $order->get_status() ) == 'pending' ) { 

							$order->payment_complete($transaction_id);
							$order->add_order_note( 'Payment successfully made via Bizappay. Reference # ' . $transaction_id );

							WC()->cart->empty_cart(); // empty the cart

							echo 'OK';
							exit();
						}
					}

				}
			}
		} // end Bizappay callback

	} // End Payment Gateway Class

} ); 