// Initialized in global.js
window.AH_Admin = new (function() {

	let o = this;

	o.init = function() {

		let is_post_edit_screen = (AH_API.get_setting( 'screen', 'base' ) === 'post');
		let post_type = AH_API.get_setting( 'screen', 'post_type' );

		// Allow clicking our custom admin notices to silently dismiss with ajax, rather than reloading the page
		o.admin_notices();

		if ( is_post_edit_screen && post_type === 'ah_invoice' ) {
			// When editing an invoice, disable the post title. It is generated when the invoice is saved.
			o.disable_post_title();
		}

	};

	/**
	 * Allow clicking our custom admin notices to silently dismiss with ajax, rather than reloading the page.
	 */
	o.admin_notices = function() {
		jQuery(document.body).on('click', '.ah-admin-notice-dismiss', function(e) {
			let $dismiss = jQuery(this);
			let $notice = $dismiss.closest('.ah-admin-notice');
			let url = $dismiss.attr('href').replace('ah-ajax=0', 'ah-ajax=1');

			let on_complete = function() {
				$notice.animate({
					opacity: 0
				}, {
					duration: 300,
					complete: function() {
						$notice.remove();
					}
				});
			};

			AH_API.ajax( url, {}, on_complete, on_complete );

			return false;
		});
	};

	/**
	 * Make post title readonly, assuming it will be generated when the post gets saved
	 */
	o.disable_post_title = function() {
		ah_log( 'AH: Disabling post title' );

		jQuery('#title').attr('readonly', true).css('opacity', 0.5);

		let title_value = jQuery('h1.wp-heading-inline').text();
		title_value = title_value.replace( 'Add New', 'New' );

		if ( jQuery('#title').val() === '' && title_value ) jQuery('#title').val( title_value );
	};

})();