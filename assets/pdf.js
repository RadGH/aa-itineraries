/** @var jQuery */
jQuery(function() {

	let after_page_load = function() {
		let $auto_page_breaks = jQuery( '.section-content' );
		console.log( 'Page breaks:', $auto_page_breaks );

		let split_recursive = function( $page, $element, max_height, depth ) {
			let element_top, element_height, $new_page, $next_page, $move_elements, $next_element;

			// If element is taller than a whole page, leave it alone.
			element_top = $element.position().top;
			element_height = $element.height();
			if ( element_height > max_height ) return;

			console.log( 'Evaluating page ', {
				'0_page': $page[0],
				'0_page_hieght': max_height,
				'1_element': $element[0],
				'2_top': element_top,
				'3_height': element_height,
				'4_too_large': (element_top + element_height > max_height),
			});

			// Move the element to a new page if it goes off the page
			if ( element_top + element_height > max_height ) {
				$move_elements = $element.add($element.nextAll());

				$new_page = jQuery('<div>', {class: 'pdf-page'});

				$new_page.append( $move_elements );

				$page.after( $new_page );

				$next_page = $new_page;
				console.log( 'Moved to new page ', {
					'0_new_page': $new_page[0],
					'1_elements': $move_elements,
				});
			}else{
				$next_page = $page;
			}

			// Check the next element
			$next_element = $element.next();

			if ( $next_element.length > 0 ) {
				split_recursive( $next_page, $next_element, max_height, depth + 1 );
			}
		}

		let page_split = function( $element ) {
			let $page = $element.closest( '.pdf-page' );
			if ( $page.length < 1 ) return;

			let page_height = $page.height();

			split_recursive( $page, $element, page_height, 0 );
		};

		$auto_page_breaks.each(function() {
			page_split( jQuery(this) );
		});

	};

	$(window).on( 'load', after_page_load );
});