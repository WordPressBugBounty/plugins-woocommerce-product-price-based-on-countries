/* global wc_price_based_country_ajax_geo_params, woocommerce_price_slider_params, accounting */
;( function() {

	// wc_price_based_country_ajax_geo_params is required to continue, ensure the object exists.
	if ( typeof wc_price_based_country_ajax_geo_params === 'undefined' ) {
		return;
	}

	const get_jquery = function() {
		return typeof window.jQuery === 'function' ? window.jQuery : null;
	};

	const trigger_event = function( event_name, args ) {
		document.dispatchEvent(
			new CustomEvent(event_name, {
				detail: args
			})
		);
	
		// Backward compatibility.
		trigger_jquery_event( document.body, event_name.replace( ':', '_' ), [ args ] );
	};

	const trigger_jquery_event = function( element, event_name, args, handler_only ) {
		const jquery = get_jquery();

		if ( jquery ) {
			jquery( element )[ handler_only ? 'triggerHandler' : 'trigger' ]( event_name, args );
		}
	};

	const on_jquery_event = function( element, event_name, callback ) {
		const jquery = get_jquery();

		if ( jquery ) {
			jquery( element ).on( event_name, callback );
		}
	};

	const parse_data_value = function( value ) {
		if ( value === undefined ) {
			return undefined;
		}
		if ( value === 'true' ) {
			return true;
		}
		if ( value === 'false' ) {
			return false;
		}
		if ( value === 'null' ) {
			return null;
		}
		if ( value !== '' && ! isNaN( value ) && String( Number( value ) ) === value ) {
			return Number( value );
		}
		if ( /^[\[{]/.test( value ) ) {
			try {
				return JSON.parse( value );
			} catch ( error ) {
				return value;
			}
		}

		return value;
	};

	const get_data = function( element, key ) {
		return parse_data_value( element.dataset[ key ] );
	};

	const set_data = function( element, key, value ) {

		const jquery = get_jquery();

		if ( jquery ) {
			jquery( element ).data( key, value );
		}
		try{
			element.dataset[ key ] = typeof value === 'string' ? value : JSON.stringify( value );
		} catch ( error ) {
			return null;
		}
	};

	const each = function( collection, callback ) {
		Object.keys( collection || {} ).forEach( function( key ) {
			callback( key, collection[ key ] );
		} );
	};

	const html_container = function( html ) {
		const container = document.createElement( 'div' );
		container.innerHTML = html || '';
		return container;
	};

	const has_add_to_cart_with_options_selector = function() {
		return document.getElementById('wc-price-based-country-ajax-geo-add-to-cart-with-options-js-module') !== null
			&& document.querySelector('[data-block-name="woocommerce/add-to-cart-with-options-variation-selector"]') !== null
			&& 'undefined' === typeof document.body.dataset['wc_price_based_country:add_to_cart_with_options_ready'];
	};

	const set_prices_visible = function() {
		document.querySelectorAll( '.wcpbc-price' ).forEach( function( price ) {
			price.style.visibility = '';
			price.style.display = '';
			price.classList.remove( 'loading' );
		} );
	};

	const append_request_data = function( params, key, value ) {
		if ( Array.isArray( value ) ) {
			value.forEach( function( item ) {
				append_request_data( params, key + '[]', item );
			} );
		} else if ( value !== null && typeof value === 'object' ) {
			each( value, function( child_key, child_value ) {
				append_request_data( params, key + '[' + child_key + ']', child_value );
			} );
		} else {
			params.append( key, value === null ? '' : value );
		}
	};

	const geolocation = {

		controller: false,

		get_product_ids: function() {
			let ids = [];

			document.querySelectorAll( 'span.wcpbc-price.loading' ).forEach( function( price ) {
				var product_id = get_data( price, 'productId' );

				if ( typeof product_id === 'undefined' ) {
					// Get product_id from class because the data attr may have been removed.
					var product_id_class = price.className.match( /wcpbc-price-\d+/ );
					if ( product_id_class !== null && product_id_class.length > 0 ) {
						product_id = parseInt( product_id_class[ 0 ].replace( 'wcpbc-price-', '' ), 10 );
					}
				}
				if ( product_id ) {
					ids.push( product_id );
				}
			} );

			document.querySelectorAll( '[data-product_variations]' ).forEach( function( variation_form ) {
				let product_variations = get_data( variation_form, 'product_variations' );

				if ( product_variations !== null && typeof product_variations !== 'undefined' ) {
					each( product_variations, function( index, variation ) {
						if ( typeof variation.variation_id !== 'undefined' ) {
							ids.push( variation.variation_id );
						}
					} );
				}
			} );

			trigger_event( 'wc_price_based_country:get_product_ids', ids );
			ids.sort(); // Sort before return.
			return ids;
		},

		get_areas: function() {
			const areas = {};

			document.querySelectorAll( '.wc-price-based-country-refresh-area:not(.refreshed)' ).forEach( function( element ) {
				const area = get_data( element, 'area' );
				const id = get_data( element, 'id' );
				const options = get_data( element, 'options' );

				if ( typeof area !== 'undefined' && typeof id !== 'undefined' && typeof options !== 'undefined' ) {
					if ( typeof areas[ area ] === 'undefined' ) {
						areas[ area ] = {};
					}
					areas[ area ][ id ] = options;
				}
			} );

			return areas;
		},

		refresh_product_price: function( products ) {
			each( products, function( index, product ) {
				var price_html = html_container( product.price_html ).querySelector( '.wcpbc-price.wcpbc-price-' + product.id );

				if ( price_html ) {
					document.querySelectorAll( '.wcpbc-price.wcpbc-price-' + product.id ).forEach( function( price ) {
						price.innerHTML = price_html.innerHTML;
						price.classList.remove( 'loading' );
					} );
				}
			} );

			document.querySelectorAll( '[data-product_variations]' ).forEach( function( variation_form ) {
				var product_variations = get_data( variation_form, 'product_variations' );

				if ( product_variations === null || typeof product_variations === 'undefined' ) {
					return;
				}

				each( product_variations, function( index, variation ) {
					var product = products[ variation.variation_id ];
					var price_html;

					if ( typeof product === 'undefined' ) {
						return;
					}

					price_html = html_container( variation.price_html );
					product_variations[ index ].display_price = product.display_price;
					product_variations[ index ].display_regular_price = product.display_regular_price;

					if ( price_html.children.length > 0 ) {
						// Replace the price.
						if ( price_html.children.length === 1 && price_html.firstElementChild.classList.contains( 'price' ) ) {
							var price_wrap = html_container( price_html.innerHTML );
							var price_element = price_wrap.querySelector( '.price' );
							if ( price_element ) {
								price_element.innerHTML = product.price_html;
							}
							price_html = price_wrap;
						} else {
							price_html = html_container( product.price_html );
						}
					} else if ( ! document.body.classList.contains( 'single-product' ) ) {
						price_html = geolocation.compatibility.variation_price_html( price_html, variation_form, product.price_html );
					}

					if ( price_html.children.length > 0 ) {
						price_html.querySelectorAll( '.wcpbc-price' ).forEach( function( price ) {
							price.style.visibility = '';
							price.classList.remove( 'loading' );
						} );
						product_variations[ index ].price_html = price_html.innerHTML;
					}
				} );

				set_data( variation_form, 'product_variations', product_variations );
				trigger_jquery_event( variation_form, 'reload_product_variations' );
			} );

			trigger_event( 'wc_price_based_country:set_product_price', products );
			set_prices_visible();
		},

		refresh_areas: function( areas ) {
			each( areas, function( index, data ) {
				if ( ! data || typeof data.id === 'undefined' || typeof data.area === 'undefined' ) {
					return;
				}

				const content = html_container( data.content );
				const wrapper = content.querySelector( '.wc-price-based-country-refresh-area');
				const areaHTML = wrapper ? wrapper.innerHTML : content.innerHTML;
			
				document.querySelectorAll( `.wc-price-based-country-refresh-area[data-area="${data.area}"][data-id="${data.id}"]` ).forEach( function( area ) {
					area.innerHTML = areaHTML;
					area.classList.add( 'refreshed' );
				} );
			} );

			trigger_event( 'wc_price_based_country:refresh_areas', areas );
		},

		refresh_currency_settings: function( currency_params ) {
			if ( typeof woocommerce_price_slider_params !== 'undefined' && typeof accounting !== 'undefined' ) {
				var min_price = document.querySelector( '.price_slider_amount #min_price' );
				var max_price = document.querySelector( '.price_slider_amount #max_price' );
				var from = document.querySelector( '.price_slider_amount span.from' );
				var to = document.querySelector( '.price_slider_amount span.to' );
				var format = {
					symbol: currency_params.symbol,
					decimal: currency_params.decimal_sep,
					thousand: currency_params.thousand_sep,
					precision: woocommerce_price_slider_params.currency_format_num_decimals,
					format: currency_params.format
				};

				if ( min_price && from ) {
					from.innerHTML = accounting.formatMoney( min_price.value, format );
				}
				if ( max_price && to ) {
					to.innerHTML = accounting.formatMoney( max_price.value, format );
				}

				woocommerce_price_slider_params.currency_format_symbol = currency_params.symbol;
				woocommerce_price_slider_params.currency_format_decimal_sep = currency_params.decimal_sep;
				woocommerce_price_slider_params.currency_format_thousand_sep = currency_params.thousand_sep;
				woocommerce_price_slider_params.currency_format = currency_params.format;
			}
			trigger_event( 'wc_price_based_country:set_currency_params', currency_params );
		},

		compatibility: {
			variation_price_html: function( price_html, variation_form, variation_price_html ) {
				// Variation Swatches for WooCommerce - Pro by Emran Ahmed compatibility.
				if ( price_html.children.length === 0 && variation_form.classList.contains( 'wvs-archive-variation-wrapper' ) ) {
					price_html = html_container( '<span></span>' );
					price_html.firstElementChild.innerHTML = variation_price_html;
				}

				return price_html;
			},

			bind: function() {
				// All Products for WooCommerce Subscriptions.
				if ( document.querySelectorAll( '.wc-price-based-country-refresh-area .wcsatt-options-wrapper' ).length > 0 ) {
					document.querySelectorAll( '.wc-price-based-country-refresh-area .wcsatt-options-wrapper' ).forEach( function( wrapper ) {
						var area = wrapper.closest( '.wc-price-based-country-refresh-area' );
						wrapper.classList.add( 'wcpbc-refresh-flag', 'wcpbc-refresh-flag-' + get_data( area, 'id' ) );
					} );

					document.body.addEventListener( 'wc_price_based_country:refresh_areas', function() {
						if ( document.querySelectorAll( '.wcsatt-options-wrapper.wcpbc-refresh-flag' ).length ) {
							document.querySelectorAll( '.wc-price-based-country-refresh-area .wcsatt-options-wrapper' ).forEach( function( wrapper ) {
								var area = wrapper.closest( '.wc-price-based-country-refresh-area' );
								var flag = 'wcpbc-refresh-flag-' + get_data( area, 'id' );

								document.querySelectorAll( '.wcsatt-options-wrapper.' + flag ).forEach( function( flagged_wrapper ) {
									flagged_wrapper.outerHTML = area.innerHTML;
								} );
								wrapper.remove();
							} );
						}

						document.querySelectorAll( '.product form.cart' ).forEach( function( form ) {
							set_data( form, 'satt_script', null );
						} );
						trigger_jquery_event( document.body, 'wcsatt-initialize', [], true );
					} );
				}

				if ( document.querySelectorAll( '.variations_form.cart' ).length > 0 && document.querySelectorAll( '[class^="wcsatt"]' ).length > 0 ) {
					document.body.addEventListener( 'wc_price_based_country:set_product_price', function() {
						if ( document.querySelectorAll( '.variations_form.cart .wcsatt-options-wrapper' ).length > 0 ) {
							var jquery = get_jquery();
							document.querySelectorAll( '.variations_form.cart .variations select' ).forEach( function( select ) {
								if ( jquery ) {
									jquery( select ).trigger( 'change.wc-variation-form' );
								} else {
									select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
								}
							} );
						}
					} );
				}

				if ( document.querySelectorAll( '.skeleton-loading' ).length ) {
					document.querySelectorAll( '.skeleton-loading' ).forEach( function( skeleton ) {
						on_jquery_event( skeleton, 'skeleton-loaded', geolocation.geolocate_customer );
					} );
				}

				// Variation Swatches for WooCommerce Pro Emran Ahmed.
				if ( document.querySelectorAll( '.wvs-archive-variations-wrapper' ).length ) {
					document.querySelectorAll( '.wvs-archive-variations-wrapper' ).forEach( function( wrapper ) {
						wrapper.classList.replace( 'wvs-archive-variations-wrapper', 'wcpbc-wvs-archive-variations-wrapper' );
					} );

					document.body.addEventListener( 'wc_price_based_country:after_ajax_geolocation', function() {
						document.querySelectorAll( '.wcpbc-wvs-archive-variations-wrapper' ).forEach( function( wrapper ) {
							wrapper.classList.replace( 'wcpbc-wvs-archive-variations-wrapper', 'wvs-archive-variations-wrapper' );
						} );
						trigger_jquery_event(document, 'woo_variation_swatches_pro_init');
					} );
				}
			}
		},

		geolocate_customer: function() {
			if ( geolocation.controller ) {
				geolocation.controller.abort();
			}

			var xhr_data = {
				ids: geolocation.get_product_ids(),
				areas: geolocation.get_areas(),
				is_single: document.body.classList.contains( 'single' ) ? '1' : '0'
			};

			if ( xhr_data.ids.length === 0 && Object.keys( xhr_data.areas ).length === 0 && document.querySelectorAll( '.wcpbc-content:not(.refreshed)' ).length === 0 ) {
				return;
			}

			const controller = new AbortController();
			const params = new URLSearchParams();
			append_request_data( params, 'ids', xhr_data.ids );
			append_request_data( params, 'areas', xhr_data.areas );
			append_request_data( params, 'is_single', xhr_data.is_single );
			geolocation.controller = controller;

			fetch( wc_price_based_country_ajax_geo_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'wcpbc_get_location' ), {
				method: 'POST',
				body: params.toString(),
				credentials: 'same-origin',
				headers: {
					'Cache-Control': 'no-cache, max-age=0',
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					'Accept': 'application/json'
				},
				signal: controller.signal
			} )
				.then( function( response ) {
					if ( ! response.ok ) {
						throw new Error( 'Geolocation request failed with status ' + response.status );
					}
					return response.json();
				} )
				.then( function( response ) {
					geolocation.refresh_product_price( response.products );
					geolocation.refresh_areas( response.areas );
					geolocation.refresh_currency_settings( response.currency_params );
					trigger_event( 'wc_price_based_country:after_ajax_geolocation', response.zone_id );
					geolocation.observe_infinite_scroll();
				} )
				.catch( function( error ) {
					if ( error.name !== 'AbortError' ) {
						set_prices_visible();
					}
				} )
				.finally( function() {
					if ( geolocation.controller === controller ) {
						geolocation.controller = false;
					}
				} );
		},

		observe_infinite_scroll: function() {
			if ( document.body.classList.contains( 'single' ) || ! geolocation.observer ) {
				return;
			}

			geolocation.observer.observe( document.body, { childList: true, subtree: true } );
		},

		infinite_scroll: function() {
			if ( document.body.classList.contains( 'single' ) ) {
				return;
			}

			geolocation.observer = new MutationObserver( function() {
				if ( document.querySelectorAll( '.wcpbc-price.loading' ).length ) {
					geolocation.observer.disconnect();
					geolocation.geolocate_customer();
				}
			} );
		},

		init: function() {
			this.compatibility.bind();
			this.infinite_scroll();

			document.body.addEventListener( 'wc_price_based_country:ajax_geolocation', geolocation.geolocate_customer );
			// Backward compatibility
			on_jquery_event( document.body, 'wc_price_based_country_ajax_geolocation', geolocation.geolocate_customer );

			if ( has_add_to_cart_with_options_selector() ) {
				document.addEventListener(
					'wc_price_based_country:add_to_cart_with_options_ready',
					geolocation.geolocate_customer,
					{ once: true }
				);
			} else {
				this.geolocate_customer();
			}
		}
	};

	geolocation.init();
} )();
