// Initialized within
window.AH_Global = new (function() {

	let o = this;

	// Initialize modules after jquery is loaded
	o.init = function() {

		ah_log( 'AH: Initializing AH_Global, and other modules:' );

		o.initialize_module( 'AH_API' );
		o.initialize_module( 'AH_Admin' );
		o.initialize_module( 'AH_Public' );

		o.add_toggles();

	};

	// Initializes an object
	o.initialize_module = function( module_name ) {
		if ( typeof window[ module_name ] === 'undefined' ) return;

		ah_log( 'AH: Initializing module ' + module_name, window[ module_name ] );

		window[ module_name ].init();
	};

	o.add_toggles = function() {
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

	// Initialize modules after jquery is loaded
	jQuery(document).ready(o.init);

})();