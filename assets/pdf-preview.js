jQuery(function() {

	let after_page_load = function() {

		let $pagebreaks = jQuery('pagebreak');

		// Loop through elements in a page.
		// If the height is exceeded, move all remaining elements to a new page
		let recurse_pages = function( $pagebreak, height, depth ) {
			depth += 1;

			if ( depth > 10 ) console.log('Too much recursion!');

			$pagebreak.css('position', 'relative' );

			$pagebreak.children().each(function() {
				let $child = jQuery(this);
				let child_height = $child.height();
				if ( child_height > height ) return;

				let top = $child.position().top + child_height;

				console.log( top, height, this );

				// Move this and remaining children to a new page
				if ( top > height ) {
					let $page_2 = $pagebreak.clone();

					let $children = $child.add( $child.nextAll() );

					$page_2
						.html('')
						.append( $children );

					$pagebreak
						.after( $page_2 );

					requestAnimationFrame(function() {
						console.log( 'Recursion', depth );
						recurse_pages( $page_2, height, depth );
					});

					return false;
				}
			});

			$pagebreak.css('position', '' );
		};

		$pagebreaks.each(function() {
			let $pagebreak = jQuery(this);
			let height = $pagebreak.height();

			recurse_pages( $pagebreak, height, 0 );
		});

	};

	$(window).on( 'load', after_page_load );
});