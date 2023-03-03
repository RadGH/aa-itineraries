// Initialized in global.js
window.AH_API = new (function() {

	let o = this;

	o.init = function() {
		//
	};

	/**
	 * Get a setting from JS variables provided by enqueue.php
	 *
	 * Examples from /wp-admin/post-new.php?post_type=ah_invoice
	 *
	 * is_admin:    bool     true
	 * debug_mode:  bool     true
	 *
	 * admin:
	 *   ajaxurl:   string   "/wp-admin/admin-ajax.php"
	 *   adminpage: string   "post-new-php"
	 *   pagenow:   string   "ah_invoice"
	 *   typenow:   string   "ah_invoice"
	 *
	 * screen
	 *   action:    string   "add"
	 *   base:      string   "post"
	 *   id:        string   "ah_invoice"
	 *   post_type: string   "ah_invoice"
	 *   taxonomy:  string   ""
	 *
	 * @param category
	 * @param name
	 * @returns {null|*}
	 */
	o.get_setting = function( category, name ) {
		if ( typeof window.ah_js_settings[category] === 'undefined' ) return null;

		if ( typeof name === 'undefined' ) {
			return window.ah_js_settings[category];
		}else{
			if ( typeof window.ah_js_settings[category][name] === 'undefined' ) return null;
			return window.ah_js_settings[category][name];
		}
	};

	/**
	 * Return true if on the WordPress dashboard
	 * @returns {*|null}
	 */
	o.is_admin = function() {
		return AH_API.get_setting( 'admin', 'is_admin' );
	};

	/**
	 * Alias of console.log that only works if debug mode is enabled (for developers)
	 */
	o.log = function() {
		if ( typeof console !== 'object' || typeof console.log !== 'function' ) return;

		if ( AH_API.get_setting( 'debug_mode' ) ) {

			let a = arguments;

			switch( a.length ) {
				case 1: console.log( a[0] ); break;
				case 2: console.log( a[0], a[1] ); break;
				case 3: console.log( a[0], a[1], a[2] ); break;
				case 4: console.log( a[0], a[1], a[2], a[3] ); break;
				case 5: console.log( a[0], a[1], a[2], a[3], a[4] ); break;
				case 6: console.log( a[0], a[1], a[2], a[3], a[4], a[5] ); break;
				default: console.log( a ); break;
			}
		}
	};

	/**
	 * Perform an ajax request. The response must be JSON in order to be successful.
	 *
	 * @param url               string
	 * @param args              object    optional
	 * @param success_callback  function  optional
	 * @param error_callback    function  optional
	 */
	o.ajax = function( url, args, success_callback, error_callback ) {
		if ( typeof args !== 'object' ) args = {};
		if ( typeof success_callback !== 'function' ) success_callback = function(){};
		if ( typeof error_callback !== 'function' ) error_callback = function(){};

		args.url = url;

		jQuery
			.ajax(args)
			.always(function( response_text, textStatus, jqXHR ) {
				let response;

				if ( typeof response_text === 'string' ) {
					response = jQuery.parseJSON( response_text );
				}else if ( typeof response_text === 'object' ) {
					response = response_text;
					response_text = jQuery.stringify( response );
				}else{
					response = false;
				}

				// @todo: For some reason these don't get called? wwwhhyyy
				if ( typeof response === 'object' ) {
					console.log( 'Ajax successful', url, response );
					success_callback( response, textStatus, jqXHR );
				}else{
					console.log( 'Ajax FAILED', url, response_text );
					error_callback( response_text, textStatus, jqXHR );
				}
			});
	}

	/**
	 * Aliases for other functions
	 *
	 * @type {function}
	 */
	window.ah_log = o.log;

})();