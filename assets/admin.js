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

		// When editing an invoice, disable the post title. It is generated when the invoice is saved.
		if ( is_post_edit_screen && post_type === 'ah_invoice' ) {
			o.setup_invoice_post_titles();
		}

		// When clicking to create or update a (village, hotel, itinerary), change the button appearance
		o.create_and_update_buttons();

		// Enable select2 with ajax results when using the "Search Sheets" dropdown, see sheet-select.php
		o.setup_sheet_search_fields();

		// Editing an itinerary...
		if ( is_post_edit_screen && post_type === 'ah_itinerary' ) {

			// The hotel dropdown used in the Villages repeater should only show hotels assigned to the selected village
			o.link_hotel_and_village_dropdowns();

			// Allow inviting a user by email address to create an account for this itinerary
			o.setup_invite_user_to_itinerary();

		}

		// Create a spreadsheet/column lookup tool for every instance of ".ah-spreadsheet-finder"
		// Used on master sheet settings pages, to streamline looking up the sheet and column IDs
		jQuery('.ah-spreadsheet-finder').each(function() {
			o.setup_spreadsheet_finder( jQuery(this) );
		});

	};

	/**
	 * Allow clicking our custom admin notices to silently dismiss with ajax, rather than reloading the page.
	 */
	o.admin_notices = function() {

		// Remove existing ah_notice and ah_notice_data arguments from url
		let new_url = window.location.href;
		if ( new_url.indexOf('ah_notice') !== false ) {
			new_url = o.remove_query_param_from_url( new_url, 'ah_notice' );
			new_url = o.remove_query_param_from_url( new_url, 'ah_notice_data' );
			history.replaceState(null, null, new_url);
		}

		// Clicking the dismiss notice button closes the notice, and uses ajax to clear it from the database
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
	/*
	o.disable_post_title = function() {
		ah_log( 'AH: Disabling post title' );

		jQuery('#title').attr('readonly', true).css('opacity', 0.5);

		let title_value = jQuery('h1.wp-heading-inline').text();
		title_value = title_value.replace( 'Add New', 'New' );

		if ( jQuery('#title').val() === '' && title_value ) jQuery('#title').val( title_value );
	};
	*/

	/**
	 * Make invoices generate a post title automatically based on the invoice number
	 */
	o.setup_invoice_post_titles = function() {
		let $invoice_number = jQuery('#acf-field_6498ac9d1e899');
		let $post_title = jQuery('#title');
		let $title_label = jQuery('#title-prompt-text');

		let update_title_placeholder = function() {
			let invoice_number = $invoice_number.val();

			if ( invoice_number ) {
				$title_label.text( 'Invoice #' + invoice_number );
			}else{
				$title_label.text( 'Invoice' );
			}
		};

		$invoice_number.on('change keyup', function() {
			update_title_placeholder();
		});

		update_title_placeholder();
	};

	/**
	 * Clicking "Create [item]" buttons makes the button turn gray
	 */
	o.create_and_update_buttons = function() {
		// Create button
		jQuery(document.body).on( 'click', '.ah-create-item-button', function() {
			jQuery(this).removeClass('button-primary');
			jQuery(this).addClass('button-secondary');
			jQuery(this).html( jQuery(this).html().replace(/Create (Village|Hotel)/, '$1 Created') );
		});

		// Update button just changes appearance
		jQuery(document.body).on( 'click', '.ah-update-item-button', function() {
			jQuery(this).removeClass('button-primary');
			jQuery(this).addClass('button-secondary');
		});
	};

	/**
	 * Enable select2 with ajax results when using the "Search Sheets" dropdown, see sheet-select.php
	 */
	o.setup_sheet_search_fields = function() {
		let $select_elements = jQuery('.ah-sheet-select');
		if ( $select_elements.length < 1 ) return;

		let select2_args = {
			ajax: {
				url: AH_API.get_setting('admin', 'ajaxurl'),
				dataType: 'json',
				method: 'POST',

				// Add ajax action, search term, page number
				// ?search=[term]&page=[page]&action=ah_search_sheets
				data: function (params) {
					return {
						search: params.term || '',
						page: params.page || 1,
						action: 'ah_search_sheets',
					};
				},

				// Rate limit to prevent spamming the server
				delay: 250,
			}
		};

		// Enable each select2
		$select_elements.each(function() {
			let $select = jQuery(this);

			// Add a placeholder: placeholder="Choose an option"
			select2_args.placeholder = $select.attr('placeholder');

			// Allow clearing the selection (default = true)
			// Disabled with: data-allow-clear="0"
			select2_args.allowClear = ($select.attr('data-allow-clear') !== '0');

			$select.select2( select2_args );
		});
	};

	/**
	 * Remove a URL arg from a given url
	 *
	 * @param url
	 * @param parameter
	 * @returns {string}
	 */
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

	/*
	The hotel dropdown used in the Villages repeater should only show hotels assigned to the selected village
	 */
	o.link_hotel_and_village_dropdowns = function() {
		if ( typeof acf === 'undefined' ) return; // acf not running on this page

		let village_field_key = 'field_641a98e77d31a';
		let hotel_field_key = 'field_6438875876dfa';

		// Make the Hotel dropdown filter by the Village dropdown when editing an itinerary, in the Villages field group.
		acf.add_filter('select2_ajax_data', function( data, args, $hotel_select, field, instance ) {
			let field_key = data.field_key || false;

			// Must be a hotel dropdown
			if ( field_key !== hotel_field_key ) {
				return data;
			}

			// Find the corresponding Village dropdown, or <select>
			// The hotel and village have the same ID, except for their field_name which is at the end.
			let village_id = $hotel_select[0].getAttribute('id').replace( hotel_field_key, village_field_key );
			let $village_select = document.querySelector( '#' + village_id );
			if ( ! $village_select ) {
				return data;
			}

			// Add the village ID
			data['village_id'] = $village_select.value;

			return data;
		});
	};

	/**
	 * Allow inviting a user by email address to create an account for this itinerary
	 */
	o.setup_invite_user_to_itinerary = function() {

		// Get the post ID being edited
		const itinerary_id = document.querySelector('#post_ID').value;
		if ( ! itinerary_id ) {
			alert('Unable to locate the ID of this itinerary. Cannot invite users.');
			return;
		}

		// Get the form and list elements, which come from PHP through acf message fields.
		const invite_form = document.querySelector('#ah_invite_form');
		const invite_list = document.querySelector('#ah_invite_list');

		// New fields that will be added when the form is set up
		const email_input = document.createElement('input');
		const invite_button = document.createElement('input');

		// Prepare the form to Send an invitation to an email address
		const setup_form = function() {

			// Remove the loading field from the form
			invite_form.innerHTML = '';

			// Add email field
			email_input.setAttribute('type', 'email');
			email_input.setAttribute('placeholder', 'Email address');
			// email_input.setAttribute('required', true);
			email_input.setAttribute('id', 'ah_invite_email');
			invite_form.appendChild(email_input);

			// Add submit button (invite button)
			invite_button.setAttribute('type', 'button');
			invite_button.setAttribute('class', 'button button-secondary');
			invite_button.value = 'Invite';
			invite_form.appendChild(invite_button);
		};

		// Prepare the list of invites
		const setup_list = function() {

			refresh_list();

		};

		// Refresh the list of invites
		const refresh_list = function() {

			// Get the list from users.php using ajax
			AH_API.ajax(

				AH_API.get_setting('admin', 'ajaxurl'),

				{
					method: 'POST',
					data: {
						action: 'ah_refresh_invitation_list',
						itinerary_id: itinerary_id
					}
				},

				function( response, textStatus, jqXHR ) {

					html = response.data.html;
					if ( html ) invite_list.innerHTML = html;

				},

				function( response_text, textStatus, jqXHR ) {
					// Error occurred
					ah_log( 'Error refreshing: ', {response_text:response_text, textStatus:textStatus, jqXHR:jqXHR} );
					alert( 'Error refreshing: ' + response_text );
				}
			);

		};

		// Send an invitation to the user who is entered in the text area, adding them to the list of invites
		const add_invitation_for_email = function() {

			const email = email_input.value;
			if ( ! email ) return;

			// Validate email
			if ( ! email.match( /^[^@]+@[^@]+\.[^@]+$/ ) ) {
				alert( 'Please enter a valid email address.' );
				return;
			}

			// Disable the email field and button
			invite_form.classList.add('processing');
			email_input.setAttribute('disabled', true);
			invite_button.setAttribute('disabled', true);

			// Re-enable the email field and button after ajax call (success or fail)
			const after_ajax = function() {
				invite_form.classList.remove('processing');
				email_input.removeAttribute('disabled');
				invite_button.removeAttribute('disabled');
			};

			// Send the invite to users.php
			AH_API.ajax(

				AH_API.get_setting('admin', 'ajaxurl'),

				{
					method: 'POST',
					data: {
						action: 'ah_add_invitation',
						itinerary_id: itinerary_id,
						email: email
					}
				},

				function( response, textStatus, jqXHR ) {
					if ( ! response || ! response.data ) {
						alert('Ajax response failed when adding user to itinerary. See console for details.');
						ah_log( 'Ajax response failed when adding user to itinerary.', {response:response, textStatus:textStatus, jqXHR:jqXHR} );
						after_ajax();
						return;
					}

					// Update the field if a new list was returned
					if ( response.data.html ) {
						invite_list.innerHTML = response.data.html;
					}

					// Show a message if one was returned
					// This might warn that the user already existed, for example
					if ( response.data.message ) {
						alert( response.data.message );
					}

					// Clear the email field
					email_input.value = '';

					// Re-enable the email field and button
					after_ajax();
				},

				function( response_text, textStatus, jqXHR ) {
					// Error occurred
					ah_log( 'Error inviting user: ', {response_text:response_text, textStatus:textStatus, jqXHR:jqXHR} );
					alert( 'Error inviting user: ' + response_text );

					// Re-enable the email field and button
					after_ajax();
				}
			);

		};

		// Prepare the form to invite an email address
		setup_form();

		// Prepare the list
		setup_list();

		// When the invite button is clicked, invite the user
		invite_button.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			add_invitation_for_email();
		});

		// When enter is pressed while focusing the email input, invite the user
		invite_form.addEventListener('keyup', function(e) {
			if ( e.keyCode === 13 ) {
				e.preventDefault();
				e.stopPropagation();
				add_invitation_for_email();
			}
		});

		// Resend an invitation to an email who has already been invited
		const resend_invite = function( $row ) {
			let email = $row.attr('data-email');
			if ( ! email ) return;

			// Ask to confirm
			if ( ! confirm( 'Are you sure you want to resend the invitation to ' + email + '?' ) ) {
				return;
			}

			$row.addClass('processing');

			// Re-enable the email field and button after ajax call (success or fail)
			const after_ajax = function() {
				$row.removeClass('processing');
			};

			// Send the invite to users.php
			AH_API.ajax(

				AH_API.get_setting('admin', 'ajaxurl'),

				{
					method: 'POST',
					data: {
						action: 'ah_send_invitation',
						itinerary_id: itinerary_id,
						email: email
					}
				},

				function( response, textStatus, jqXHR ) {
					if ( ! response || ! response.data ) {
						alert('Ajax response failed when re-sending an invitation. See console for details.');
						ah_log( 'Ajax response failed when re-sending an invitation.', {response:response, textStatus:textStatus, jqXHR:jqXHR} );
						after_ajax();
						return;
					}

					// Update the list if a new list was returned
					if ( response.data.html ) {
						invite_list.innerHTML = response.data.html;
					}

					after_ajax();
				},

				function( response_text, textStatus, jqXHR ) {
					// Error occurred
					ah_log( 'Error re-sending invitation: ', {response_text:response_text, textStatus:textStatus, jqXHR:jqXHR} );
					alert( 'Error re-sending invitation: ' + response_text );

					// Re-enable the email field and button
					after_ajax();
				}
			);
		};

		// Remove an invitation from the list
		const remove_invitation = function( $row ) {
			let email = $row.attr('data-email');
			if ( ! email ) return;

			$row.addClass('processing');

			// Re-enable the email field and button after ajax call (success or fail)
			const after_ajax = function() {
				$row.removeClass('processing');
			};

			// Send the invite to users.php
			AH_API.ajax(

				AH_API.get_setting('admin', 'ajaxurl'),

				{
					method: 'POST',
					data: {
						action: 'ah_remove_invitation',
						itinerary_id: itinerary_id,
						email: email
					}
				},

				function( response, textStatus, jqXHR ) {
					if ( ! response || ! response.data ) {
						alert('Ajax response failed when adding user to itinerary. See console for details.');
						ah_log( 'Ajax response failed when adding user to itinerary.', {response:response, textStatus:textStatus, jqXHR:jqXHR} );
						after_ajax();
						return;
					}

					// Update the list if a new list was returned
					if ( response.data.html ) {
						invite_list.innerHTML = response.data.html;
					}

					// Don't need to run after ajax because the list has been updated.
					// after_ajax();
				},

				function( response_text, textStatus, jqXHR ) {
					// Error occurred
					ah_log( 'Error removing invite: ', {response_text:response_text, textStatus:textStatus, jqXHR:jqXHR} );
					alert( 'Error removing invite: ' + response_text );

					// Re-enable the email field and button
					after_ajax();
				}
			);
		};

		// When clicking "Send Invite", re-send the invite email
		// When clicking on a "Remove" button in the invite list, remove that invitation
		invite_list.addEventListener('click', function(e) {
			let $button = jQuery(e.target);
			let $row = $button.closest('.user-invite');

			if ( $button.hasClass('send-invite') ) {
				resend_invite( $row );
			}

			if ( $button.hasClass('remove-invite') ) {
				remove_invitation( $row );
			}

		});

	};

	/**
	 * Create a spreadsheet/column lookup tool for every instance of ".ah-spreadsheet-finder"
	 */
	o.setup_spreadsheet_finder = function( $container ) {

		/*
		// Create a search box and results container to identify the spreadsheet.
		let $search = jQuery('<input type="text" placeholder="Search for a spreadsheet">');
		let $results = jQuery('<div class="ah-spreadsheet-finder-results" style="display: none;"></div>');
		let $errors = jQuery('<div class="ah-spreadsheet-finder-errors" style="display: none;"></div>');
		let $reset_search = jQuery('<a href="#" class="button button-secondary reset-results">Reset Search</a>');

		let is_searching = false;
		let queue_next_search = false;
		let search_stopped = false;
		let prev_search_term = '';

		const show_error = function( message ) {
			if ( message ) {
				$errors.html( '<p><strong>Error:</strong> ' + message + '</p>' ).css('display', '');
			}else{
				$errors.html('').css('display', 'none');
			}
		};

		const clear_error = function() {
			show_error('');
		};

		const clear_results = function() {
			$results.html('').css('display', 'none');
		};

		const search_spreadsheets = function() {
			let search_term = $search.val().trim();
			if ( ! search_term ) return;

			if ( search_term === prev_search_term ) {
				return;
			}else{
				prev_search_term = search_term;
			}

			search_stopped = false;
			is_searching = true;
			queue_next_search = false;

			jQuery.ajax({
				url: AH_API.get_setting('admin', 'ajaxurl'),
				method: 'POST',
				data: {
					action: 'ah_search_spreadsheets',
					search: search_term
				},
				success: function( response, textStatus, jqXHR ) {
					if ( search_stopped ) {
						return; // ignore the results if the search was stopped early
					}

					if ( ! response || response === "0" || typeof response !== 'string' ) {
						// show_error('Ajax response failed when searching for spreadsheets. See console for details.');
						ah_log( 'Ajax response had no results when searching spreadsheets.', {response:response, textStatus:textStatus, jqXHR:jqXHR} );
						return;
					}


					clear_error();

					if ( response ) {
						$results.html( response ).css('display', '');

						// Scroll to the top of the results container
						$results[0].scrollTop = 0;
					}else{
						$results.html( '<p>No results found</p>' ).css('display', '');
					}
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					show_error( 'Error searching for spreadsheets: ' + errorThrown );
					ah_log( 'Error searching for spreadsheets: ', {jqXHR:jqXHR, textStatus:textStatus, errorThrown:errorThrown} );
				},
				complete: function() {
					is_searching = false;

					if ( queue_next_search ) {
						queue_next_search = false;
						ah_log( 'Spreadsheet search completed. Searching again because queue_next_search is true.' );
						search_spreadsheets();
					}else{
						ah_log( 'Spreadsheet search completed' );
					}
				}
			});
		};

		// When the search box is changed, search for spreadsheets using ajax
		$search.on('keyup', function() {
			if ( is_searching ) {
				queue_next_search = true;
			}else{
				search_spreadsheets();
			}
		});

		// When the reset button is clicked, clear the search box and results
		$reset_search.on('click', function(e) {
			queue_next_search = false;
			search_stopped = true;
			e.preventDefault();
			e.stopPropagation();
			$search.val('');
			clear_results();
			clear_error();
		});

		// Append elements to container
		$container.append( $errors );
		$container.append( $search ).append(' ');
		$container.append( $reset_search );
		$container.append( $results );

		*/

	};

})();