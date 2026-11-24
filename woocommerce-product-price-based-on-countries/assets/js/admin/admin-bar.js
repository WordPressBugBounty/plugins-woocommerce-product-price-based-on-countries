/* global jQuery */
;( function( $ ) {
	'use strict';

	$( function() {
		const $countrySelector = $( '#wcpbc-admin-bar-country' );

		if ( ! $countrySelector.length || ! $.fn.selectWoo ) {
			return;
		}
	
		const selectionTemplate = function() {
			$('#select2-wcpbc-admin-bar-country-container').wrapInner('<span></span>');
		}

		$countrySelector.selectWoo( {
			minimumResultsForSearch: 8,
			width: 'auto',
			dropdownCssClass: 'wcpbc-admin-bar-country-dropdown',
		} ).on( 'change', function() {
			selectionTemplate();
			this.form.submit();
		} ).on('select2:close', function() {
			const container = document.getElementById('select2-wcpbc-admin-bar-country-container').closest('.select2.select2-container');
			if ( container ) {
				setTimeout(function() {
					container.classList.remove('select2-container--focus');
				},100)
			}
		});

		selectionTemplate();

	} );
} )( jQuery );
