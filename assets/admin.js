// Initialized in global.js
window.AH_Admin = new (function() {

	let o = this;

	o.init = function() {

		let is_post_edit_screen = (AH_API.get_setting( 'screen', 'base' ) === 'post');
		let post_type = AH_API.get_setting( 'screen', 'post_type' );

		// Allow clicking our custom admin notices to silently dismiss with ajax, rather than reloading the page
		o.admin_notices();

		// Remove empty ACF labels
		o.hide_empty_acf_labels();

		if ( is_post_edit_screen && post_type === 'ah_invoice' ) {
			// When editing an invoice, disable the post title. It is generated when the invoice is saved.
			o.disable_post_title();
		}

		// Delete ah_notice from url
		let new_url = window.location.href;
		if ( new_url.indexOf('ah_notice') !== false ) {
			new_url = o.remove_query_param_from_url( new_url, 'ah_notice' );
			new_url = o.remove_query_param_from_url( new_url, 'ah_notice_count' );
			history.replaceState(null, null, new_url);
		}

	};

	/**
	 * Allow clicking our custom admin notices to silently dismiss with ajax, rather than reloading the page.
	 */
	o.admin_notices = function() {
		jQuery(document.body).on('click', '.ah-admin-notice-dismiss', function(e) {
			let $dismiss = jQuery(this);
			let $notice = $dismiss.closest('.ah-admin-notice');

			if ( $notice.hasClass('ah-auto-dismiss') ) {
				$notice.animate({
					opacity: 0,
				}, {
					duration: 300,
					complete: function() {
						$notice.remove();
					}
				});
				return false;
			}

			let url = $dismiss.attr('href').replace('ah-ajax=0', 'ah-ajax=1');

			$notice.addClass('ah-dismissing');

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
	 * If an ACF label is empty it still has a space in the HTML element. This hides those ACF labels.
	 */
	o.hide_empty_acf_labels = function() {
		jQuery( '.acf-label' ).each(function() {
			let $label = jQuery(this);
			let text = $label.text();

			if ( text.trim() === "" ) {
				$label
					.css('display', 'none')
					.addClass('aa-hidden-empty-acf-label');
			}
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

	o.remove_query_param_from_url = function(url, parameter) {
		var urlParts = url.split('?');

		if (urlParts.length >= 2) {
			// Get first part, and remove from array
			var urlBase = urlParts.shift();

			// Join it back up
			var queryString = urlParts.join('?');

			var prefix = encodeURIComponent(parameter) + '=';
			var parts = queryString.split(/[&;]/g);

			// Reverse iteration as may be destructive
			for (var i = parts.length; i-- > 0; ) {
				// Idiom for string.startsWith
				if (parts[i].lastIndexOf(prefix, 0) !== -1) {
					parts.splice(i, 1);
				}
			}

			url = urlBase + '?' + parts.join('&');
		}

		return url;
	}

})();