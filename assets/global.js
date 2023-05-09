// Initialized within
window.AH_Global = new (function() {

	let o = this;

	// Initialize modules after jquery is loaded
	o.init = function() {

		ah_log( 'AH: Initializing AH_Global, and other modules:' );

		o.initialize_module( 'AH_API' );
		o.initialize_module( 'AH_Admin' );
		o.initialize_module( 'AH_Public' );

		o.register_ah_toggle();

		o.register_ah_accordion();

	};

	// Initializes an object
	o.initialize_module = function( module_name ) {
		if ( typeof window[ module_name ] === 'undefined' ) return;

		ah_log( 'AH: Initializing module ' + module_name, window[ module_name ] );

		window[ module_name ].init();
	};

	// Clicking on a toggle item will show or hide the target element
	o.register_ah_toggle = function() {
		jQuery(document.body).on('click', '.ah-toggle', function(e) {
			let $button = jQuery(this);
			let target_selector = $button.attr('data-target');
			let $target = jQuery( target_selector );

			if ( $target.length < 1 ) {
				ah_log('Could not toggle element, element not found.', 'Selector: ' + target_selector, this);
			}else{
				$target.css( 'display', $target.css('display') === 'block' ? 'none' : 'block' );
			}

			return false;
		});
	};

	// Clicking an accordion handle will show or hide the content
	// See shortcode: [ah_accordion]
	o.register_ah_accordion = function() {

		// Set up all accordion items
		jQuery('.ah-accordion').each(function() {
			new o.AH_Accordion_Item( jQuery(this) );
		});

	};

	// Accordion item object to be created for each <div class="ah-accordion">
	o.AH_Accordion_Item = function( $accordion ) {
		let $handle, $content, $link;
		
		// Handle and content are required and must be direct children of the accordion
		$handle = $accordion.find('> .ah-handle').first();
		$content = $accordion.find('> .ah-content').first();

		// If the handle is a link or button, wrap it in a div instead
		if ( $handle.is('a, button') ) {
			$link = $handle;
			$handle = jQuery('<div>').addClass('ah-handle');
			$link.after( $handle );
			$handle.append( $link );
			$link.removeAttr('ah-handle');
		}else{
			$link = $handle.find( 'a, button' ).first();
		}
		
		// Required elements
		if ( $accordion.length !== 1 ) return console.log('Accordion item is invalid', $accordion);
		if ( $handle.length !== 1 ) return console.log('Accordion has no handle element: ', $accordion);
		if ( $content.length !== 1 ) return console.log('Accordion has no content element: ', $accordion);
		if ( $link.length !== 1 ) return console.log('Accordion has no link element: ', $accordion);

		let a = this;

		// First parameter should be a jQuery element of <div class="ah-accordion">
		a.initialize_item = function() {
			if ( $accordion.hasClass('ah-initialized') ) return;

			// Collapse by default unless ah-expanded is present
			if ( ! $accordion.hasClass('ah-expanded') ) {
				$accordion.addClass('ah-collapsed');
			}

			// Add an arrow element to indicate if the item is expanded or collapsed
			$link.prepend(
				jQuery('<span class="ah-arrow" aria-hidden="true"></span>')
			);

			// Update aria attributes for the first time
			a.update_aria_atts();

			// Click the link to toggle the class
			$link.on('click', function(e) {
				a.on_click_toggle();
				return false;
			});

			// Remember this accordion item is already set up
			$accordion.addClass('ah-initialized');

			ah_log('AH: Accordion item initialized:', $accordion);
		};

		// Check if an accordion is expanded
		a.is_expanded = function() {
			return $accordion.hasClass('ah-expanded');
		};

		// Get the ID of the element. ID will be generated if it is blank.
		a.get_or_add_element_id = function( $element, name ) {
			// Get existing ID
			let id = $element.attr('id');
			if ( id ) return id;

			// Create a new ID based on the provided name
			id = (typeof name === 'undefined' || !name) ? 'accordion-element' : name;

			// Convert to slug (Hello World! -> hello-world)
			// 1. Lower case
			// 2. Combine spaces, hyphens, and underscores into a single hyphen.
			// 3. Remove all characters except a-z, 0-9, and hyphens.
			id = id.toLowerCase().replace(/[\-_\s]+/, '-').replace(/[^a-z0-9\-]+/g, '');

			// Check if ID exists. If so, add a random string to the end
			if ( jQuery('#' + id).length > 0 ) {
				id += '-' + (Math.random() + 1).toString(36).substring(5); // https://stackoverflow.com/a/8084248/470480
			}

			// Update the ID of the element
			$element.attr('id', id);

			return id;
		};

		a.on_click_toggle = function() {
			// If expanded, make it collapsed
			let make_expanded = ! a.is_expanded();

			// Toggle the classes
			$accordion
				.toggleClass('ah-expanded', make_expanded)
				.toggleClass('ah-collapsed', !make_expanded);

			// Update aria attributes
			a.update_aria_atts();
		};

		a.update_aria_atts = function() {
			// Get ID of the content element (auto-generated if blank)
			let name = $link.text();
			let link_id = a.get_or_add_element_id( $link, name + '-link' );
			let content_id = a.get_or_add_element_id( $content, name + '-content' );

			let is_expanded = a.is_expanded();

			// Update the link attributes
			$link
				.attr( 'aria-expanded', is_expanded ? 'true' : 'false' )
				.attr( 'aria-controls', content_id );

			// Update the content attributes
			$content
				.attr( 'role', 'region' )
				.attr( 'aria-labelledby', link_id );
		};

		a.initialize_item();

	}; // end of: o.AH_Accordion_Item()

	// Initialize modules after jquery is loaded
	jQuery(document).ready(o.init);

})();