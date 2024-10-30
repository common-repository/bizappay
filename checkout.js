const bizappay_data = window.wc.wcSettings.getSetting( 'bizappay_data', {} );
const bizappay_label = window.wp.htmlEntities.decodeEntities( bizappay_data.title )
	|| window.wp.i18n.__( 'Bizappay Checkout', 'bpay' );
const bizappay_content = () => {
	return window.wp.htmlEntities.decodeEntities( bizappay_data.description || '' );
};
const Bizappay = {
	name: 'bizappay',
	label: window.wp.element.createElement(() =>
      window.wp.element.createElement(
        "span",
        null,
        window.wp.element.createElement("img", {
          src: bizappay_data.icon,
          alt: bizappay_label,
          class: 'bpay_logo'
        }),
        // "  " + bizappay_label
      )
    ),

	content: Object( window.wp.element.createElement )( bizappay_content, null ),
	edit: Object( window.wp.element.createElement )( bizappay_content, null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: window.wp.i18n.__( 'Pay with Bizappay', 'bpay' ),
	ariaLabel: bizappay_label,
	supports: {
		features: bizappay_data.supports,
	},
};

window.wc.wcBlocksRegistry.registerPaymentMethod( Bizappay );