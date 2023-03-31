// Initialized in global.js
window.AH_Public = new (function() {

	let o = this;

	let $body = null;
	let $account_nav = null;

	o.init = function() {

		$body = jQuery('body');

		$account_nav = jQuery('.account-sidebar');

		// Everywhere
		o.setup_notices();

		o.setup_scroll_up_down();

		// Account pages
		if ( $account_nav.length > 0 ) {
			o.setup_scroll_account_nav();

			o.setup_account_menu_scrolling();
		}

	};

	o.setup_notices = function() {

		$body.on('click', '.ah-theme-dismiss', function() {
			jQuery(this).closest('.ah-theme-notice').remove();
		});

	}

	o.setup_scroll_up_down = function() {
		let scroll_y = -1;
		let last_scroll_y = 0;
		let last_scrolled_up = false;

		$body.addClass('scrolled-down');

		let on_scroll = function() {

			last_scroll_y = scroll_y;
			scroll_y = Math.round( jQuery(this).scrollTop() );

			if ( last_scroll_y > scroll_y && ! last_scrolled_up ) {
				last_scrolled_up = true;

				$body
					.addClass('scrolled-up')
					.removeClass('scrolled-down');
			}else if ( last_scroll_y < scroll_y && last_scrolled_up ) {
				// scrolling down
				last_scrolled_up = false;

				$body
					.removeClass('scrolled-up')
					.addClass('scrolled-down');
			}

		};

		jQuery(window).on('scroll', on_scroll);

		on_scroll();
	}

	o.setup_scroll_account_nav = function() {

		$account_nav.css('position', 'static');

		let account_top = $account_nav.offset().top;
		let account_height = $account_nav.height();
		let account_bottom = account_top + account_height;

		$account_nav.css('position', '');

		let y = 0;
		let last_y = 0;

		let scroll_past_account = false;

		let on_scroll = function() {

			last_y = y;
			y = jQuery(window).scrollTop();

			if ( y > account_bottom && ! scroll_past_account ) {
				scroll_past_account = true;
				$body.addClass('scrolled-past-account-sidebar');
			}else if ( y < account_bottom && scroll_past_account ) {
				scroll_past_account = false;
				$body.removeClass('scrolled-past-account-sidebar');
			}

		};

		jQuery(window).on('scroll', on_scroll);

		on_scroll();

	};

	o.setup_account_menu_scrolling = function() {

		// When clicking an internal link in the account navigation:
		// 1. Close the navigation menu (mobile)
		// 2. Scroll to the element
		jQuery('.ah-account-menu-nav a').on('click', function() {

			let $a = jQuery(this);
			let href = $a.attr('href');

			// Internal links scroll to their destination and close the mobile menu
			if ( href.substring(0, 1) === '#' ) {

				let $target = jQuery( href );
				if ( $target.length < 1 ) return;

				let target_y = $target.offset().top;
				let scroll_y = jQuery('html').scrollTop();

				// Offset so the title doesn't touch the browser edge
				target_y -= 20;
				if ( $body.hasClass('admin-bar') ) target_y -= 32;

				let distance_px = Math.abs(scroll_y - target_y);
				let scroll_ms_per_px = 0.025;
				let scroll_time_base = 350;
				let scroll_duration = Math.min( (distance_px * scroll_ms_per_px) + scroll_time_base, 1500 );

				jQuery('#ah-mobile-nav-toggle').prop('checked', false);

				jQuery('html,body').delay(150).animate({
					scrollTop: target_y
				}, {
					duration: scroll_duration,
					complete: function() {
					}
				});

				return false;

			}

		});

	}

})();