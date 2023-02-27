// Initialized within
window.AH_Global = new (function() {

	let o = this;

	// Initialize modules after jquery is loaded
	o.init = function() {

		ah_log( 'AH: Initializing AH_Global, and other modules:' );

		o.initialize_module( 'AH_API' );
		o.initialize_module( 'AH_Admin' );
		o.initialize_module( 'AH_Public' );

	};

	// Initializes an object
	o.initialize_module = function( module_name ) {
		if ( typeof window[ module_name ] === 'undefined' ) return;

		ah_log( 'AH: Initializing module ' + module_name, window[ module_name ] );

		window[ module_name ].init();
	};

	// Initialize modules after jquery is loaded
	jQuery(document).ready(o.init);

})();