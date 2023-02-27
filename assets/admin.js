// Initialized in global.js
window.AH_Admin = new (function() {

	let o = this;

	o.init = function() {

		let is_post_edit_screen = (AH_API.get_setting( 'screen', 'base' ) === 'post');
		let post_type = AH_API.get_setting( 'screen', 'post_type' );

		if ( is_post_edit_screen && post_type === 'ah_invoice' ) {
			// When editing an invoice, disable the post title. It is generated when the invoice is saved.
			o.disable_post_title();
		}

	};

	/**
	 * Make post title readonly, assuming it will be generated when the post gets saved
	 */
	o.disable_post_title = function() {
		ah_log( 'AH: Disabling post title' );

		jQuery('#title').attr('readonly', true).css('opacity', 0.5);

		if ( jQuery('#title').val() === '' ) jQuery('#title').val( 'New Invoice' );
	};

})();