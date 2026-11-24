import { store } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/products';

const productsStoreLock = 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';
const addToCartWithOptions = document.querySelector( '[data-block-name="woocommerce/add-to-cart-with-options-variation-selector"]' );

if ( addToCartWithOptions ) {
	const { state } = store( 'woocommerce/products', {}, { lock: productsStoreLock } );

	document.addEventListener( 'wc_price_based_country:set_product_price', function( event ) {
		const products = event.detail;

		if ( ! products || ! state.productVariations ) {
			return;
		}

		Object.keys( products ).forEach( function( productId ) {
			const product = products[ productId ];
			const variation = state.productVariations[ product.id ];

			if ( variation ) {
				variation.price_html = product.price_html;
			}
		} );
	} );

	document.addEventListener( 'wc_price_based_country:get_product_ids', function( event ) {
		if ( Array.isArray( event.detail ) ) {
			event.detail.push( ...Object.keys( state.productVariations || {} ) );
		}
	} );
}

document.body.dataset['wc_price_based_country:add_to_cart_with_options_ready']=1;
document.dispatchEvent(
	new CustomEvent( 'wc_price_based_country:add_to_cart_with_options_ready' )
);
