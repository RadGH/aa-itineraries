// Initialized in global.js
window.AH_Public = new (function() {

	let o = this;

	o.init = function() {

		o.setup_notices();

	};

	o.setup_notices = function() {

		jQuery(document.body).on('click', '.ah-theme-dismiss', function() {
			jQuery(this).closest('.ah-theme-notice').remove();
		});

	}

})();