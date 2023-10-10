// Initialized in global.js
window.AH_Public = new (function() {

	let o = this;

	let $body = null;
	let $account_nav = null;
	let $itinerary_menu = null;

	o.init = function() {

		$body = jQuery('body');
		$account_nav = jQuery('.account-sidebar');
		$itinerary_menu = jQuery('#menu-itinerary');

		// Everywhere
		o.setup_notices();

		o.setup_scroll_up_down();

		// Account pages
		if ( $account_nav.length > 0 ) {
			o.setup_scroll_account_nav();

			o.setup_account_menu_scrolling();
		}

		// Itinerary page
		if ( $itinerary_menu.length > 0 ) {

			o.setup_itinerary_menu_scroll_indicators( $itinerary_menu );

		}

	};

	o.setup_notices = function() {

		$body.on('click', '.ah-theme-dismiss', function() {
			jQuery(this).closest('.ah-theme-notice').remove();
		});

	}

	o.setup_scroll_up_down = function() {
		$body.addClass('scrolled-down');

		let scroll_y = 0;
		let last_scroll_y = -1;
		let last_direction = '';

		let on_scroll = function() {
			scroll_y = window.scrollY;

			if ( scroll_y === last_scroll_y ) {
				// Did not scroll
				return;
			}else if ( scroll_y < last_scroll_y && last_direction !== 'up' ) {
				// Scrolled up
				last_direction = 'up';
				$body
					.addClass('scrolled-up')
					.removeClass('scrolled-down');
			}else if ( scroll_y > last_scroll_y && last_direction !== 'down' ) {
				// Scrolled down
				last_direction = 'down';
				$body
					.removeClass('scrolled-up')
					.addClass('scrolled-down');
			}

			last_scroll_y = scroll_y;

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
		jQuery(document.body).on('click', '.ah-account-menu-nav a, .menu-dots a', function() {

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

	o.setup_itinerary_menu_scroll_indicators = function( $itinerary_menu ) {

		// Store each menu list item, link, dot, section, and y-pos of the section:
		// li, a, dot_a, dot_li, section, yPos
		const menu_items = [];

		// Manages the current menu item / dot item
		let activeMenuItem = null;
		let activeMenuDotItem = null;

		// Get a menu item based on type
		const get_menu_item = function( element, type ) {
			if ( type === 'li' ) {
				return menu_items.find(item => item.li === element);
			} else if ( type === 'a' ) {
				return menu_items.find(item => item.a === element);
			}else if ( type === 'dot_a' ) {
				return menu_items.find(item => item.dot_a === element);
			}else if ( type === 'dot_li' ) {
				return menu_items.find(item => item.dot_li === element);
			}else if ( type === 'section' ) {
				return menu_items.find(item => item.section === element);
			}else{
				return null;
			}
		};

		// Create a separate list used for dots that correspond to each menu item from the "links" variable
		const setup_menu_elements = function() {

			// Get all menu items as links
			const menuItems = $itinerary_menu[0].querySelectorAll(':scope > li.menu-item > a');

			// Create a new unordered list for the dot menu
			const dotList = document.createElement('ul');
			dotList.classList.add('menu-dots');

			// For each menu item...
			menuItems.forEach((menuLink) => {

				// 1. Get the section that the menu item links to
				const selector = menuLink.getAttribute('href');
				let section = null;

				if ( selector && selector.substring(0, 1) === '#' ) {
					section = document.querySelector(selector);
				}else{
					console.log('No selector found for menu item', menuLink);
					return;
				}

				// 2. Create a new list item for the dot list
				const dotItem = document.createElement('li');
				dotItem.classList.add('menu-dot');

				// Create a new anchor element
				const dotLink = document.createElement('a');
				dotLink.href = menuLink.getAttribute('href'); // Set the href to match the original menu item

				// Create a span inside the anchor
				const dotSpan = document.createElement('span');
				dotSpan.textContent = menuLink.textContent; // Set the text to match the original menu item

				// Append the dot element
				dotLink.appendChild(dotSpan);
				dotItem.appendChild(dotLink);
				dotList.appendChild(dotItem);

				// 3. Add the menu item elements to an array
				menu_items.push({
					a: menuLink,                // <a>
					li: menuLink.parentElement, // <li>
					dot_a: dotLink,             // <a>
					dot_li: dotItem,            // <li>
					section: section,           // <div>
					yPos: 0,		            // position of the section
				});

			});

			// Append the new list to the body
			document.body.appendChild(dotList);

			// Indicate the dot nav is added to the body
			document.body.classList.add('menu-dots-added');

		};

		// Calculate the position of each section at page load, and if the page resizes
		const calculate_menu_positions = function() {
			// get the y-position of each section
			menu_items.forEach((menu_item) => {
				const section = menu_item.section;
				menu_item.yPos = section.offsetTop;
			});

			update_scroll_position();
		};

		// Change the active menu item to the section in the middle of the viewport
		const update_scroll_position = function() {

			const scroll_y = window.scrollY;
			const scroll_h = window.innerHeight;
			const center_y = scroll_y + (scroll_h / 2);

			// Locate the section whose Y-position is closest to the middle of the viewport, but not past the middle
			let closest_menu_item = null;
			let closest_menu_item_distance = null;
			let distance = null;

			// Find the section in the middle of the viewport
			menu_items.forEach((menu_item) => {
				// If the section is below the middle of the viewport, ignore it, we are not there yet
				if ( menu_item.yPos > center_y ) return;

				distance = Math.abs( menu_item.yPos - center_y );

				if ( closest_menu_item_distance === null || distance < closest_menu_item_distance ) {
					closest_menu_item = menu_item;
					closest_menu_item_distance = distance;
				}
			});

			// Ignore if current active menu item is the same
			if ( closest_menu_item === activeMenuItem ) return;

			// Deactivate the previous menu item and dot
			if ( activeMenuItem !== null ) {
				toggle_menu_item( activeMenuItem, false );
				activeMenuItem = null;
			}

			// Activate the new menu item
			if ( closest_menu_item !== null ) {
				toggle_menu_item( closest_menu_item, true );
				activeMenuItem = closest_menu_item;
			}

		};

		const toggle_menu_item = function( menu_item, active ) {
			// Toggle the class "scroll-active"
			menu_item.li.classList.toggle('scroll-target', active);
			menu_item.dot_li.classList.toggle('scroll-target', active);
			menu_item.section.classList.toggle('scroll-target', active);
		};

		// Set up the list of each menu item, dot item, and section element
		setup_menu_elements();

		// Calculate the y-position of each element
		calculate_menu_positions();
		window.addEventListener('resize', calculate_menu_positions); // and when window resizes
		//setInterval(calculate_menu_positions, 15000); // and every 15 seconds

		// When scrolling, activate the corresponding section
		window.addEventListener('scroll', update_scroll_position);

		// Add a class to the menu
		$itinerary_menu.addClass('scroll-tracking-menu');

	}

	/*
	o.OLD_setup_itinerary_menu_scroll_indicators = function( $itinerary_menu ) {

		$itinerary_menu.addClass('scroll-tracking-menu');

		let activeMenuItem = null;
		let activeMenuDotItem = null;
		const observerEntries = new Map();
		const links = $itinerary_menu[0].querySelectorAll(':scope > li.menu-item > a');
		const menuItemDots = new Map();

		// Create a separate list used for dots that correspond to each menu item from the "links" variable
		const create_linked_menu_dots = function( menuItems ) {

			// Create a new unordered list
			const dotList = document.createElement('ul');

			dotList.classList.add('menu-dots');

			// For each menu item...
			menuItems.forEach((menuItem) => {
				// Create a new list item for the new list
				const dotItem = document.createElement('li');
				dotItem.classList.add('menu-dot');

				// Create a new anchor element
				const dotLink = document.createElement('a');
				dotLink.href = menuItem.getAttribute('href'); // Set the href to match the original menu item

				// Create a span inside the anchor
				const dotSpan = document.createElement('span');
				dotSpan.textContent = menuItem.textContent; // Set the text to match the original menu item

				// Append the new span to the new anchor
				dotLink.appendChild(dotSpan);

				// Append the new anchor to the new list item
				dotItem.appendChild(dotLink);

				// Append the new list item to the new list
				dotList.appendChild(dotItem);

				// Associate the original menu item with the new list item
				menuItemDots.set(menuItem, dotItem);
			});

			// Append the new list to the body
			document.body.appendChild(dotList);

			// Indicate the dot nav is added to the body
			document.body.classList.add('menu-dots-added');

		};

		// Searches for the closest menu item to the middle of the screen
		const get_closest_observer_entry = function() {
			// Get viewport height and midpoint
			const viewportHeight = window.innerHeight;
			const viewportMidpoint = viewportHeight / 2;

			// Filter observerEntries where the center line of the viewport falls within the element
			// Returns the first entry in the array is the closest to the viewport's center
			return Array.from(observerEntries.values()).find(entry => {
				const bounds = entry.target.getBoundingClientRect();
				return bounds.top <= viewportMidpoint && bounds.bottom >= viewportMidpoint;
			});
		};

		// Observer is triggered when a target element enters the viewport
		const observer = new IntersectionObserver((entries) => {

			// Add the observed items to the observerEntries map
			entries.forEach(entry => {
				observerEntries.set(entry.target, entry);
			});

			// Find the closest item from the observed elements
			const active_entry = get_closest_observer_entry();
			const id = active_entry ? active_entry.target.getAttribute('id') : false;
			const menuItem = id ? document.querySelector(`#menu-itinerary a[href="#${id}"]`) : false;
			if ( ! menuItem ) return;

			// Deactivate the previous menu item and dot
			if ( activeMenuItem !== null ) {
				activeMenuItem.parentElement.classList.remove('scroll-target');
				activeMenuItem = null;
			}

			if ( activeMenuDotItem !== null ) {
				activeMenuDotItem.classList.remove('scroll-target');
				activeMenuDotItem = null;
			}

			activeMenuItem = menuItem;
			activeMenuDotItem = menuItemDots.get(menuItem);

			// Activate the menu item
			activeMenuItem.parentElement.classList.add('scroll-target');
			activeMenuDotItem.classList.add('scroll-target');
		}, {
			rootMargin: '-60px 0px', // negative margin = highlight the menu after this far in the viewport
			threshold: [0, 0.1, 0.5] // 0 = trigger when any amount is visible, 1 = trigger when 100% in viewport
		});

		// Create separate list of menu dots, used as dot navigation on mobile
		create_linked_menu_dots( links );

		// Add all the sections you want to observe
		links.forEach((el) => {
			const selector = el.getAttribute('href');
			if ( !selector || selector.substring(0, 1) !== '#' ) return;

			const target = document.querySelector(selector);
			if (target) observer.observe(target);
		});

	}
	*/

})();