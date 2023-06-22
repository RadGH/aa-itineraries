<?php

/**
 * Create a new alert which is displayed in the backend
 * Wrapper of aa_add_alert from the aa-alerts plugin.
 * If plugin is not loaded the alert will not be created and this returns false.
 *
 * @param string $type     error, warning, info, success
 * @param string $title
 * @param string $message
 * @param array $data
 *
 * @return int|false
 */
function ah_add_alert( $type, $title, $message = '', $data = array() ) {
	if ( ! function_exists('aa_add_alert') ) return false;
	
	return aa_add_alert( $type, $title, $message, $data );
}

/**
 * Adjust a date such as "+30 days" with standard php date formatting.
 *
 * @param string $offset           "+30 days" or "-1 month"
 * @param string $format           "Y-m-d" or "timestamp"
 * @param null|int $current_time   timestamp, defaults to current time in server timezone
 *
 * @return string|int
 */
function ah_adjust_date( $offset, $format, $current_time = null ) {
	if ( $current_time === null ) $current_time = current_time( 'timestamp' );
	
	$ts = strtotime( $offset, $current_time );
	
	if ( $format === 'timestamp' )
		return $ts;
	else
		return date( $format, $ts );
}

/**
 * Displays a relative date (eg: 3 days ago) in HTML. Hover to view the actual date.
 *
 * @param string      $date     Formatted date string, must be compatible with strtotime()
 * @param string|null $date_2   Optional. Another date string used as the end of the date range
 * @param string      $format   The date format to use when hovering over the relative date
 *
 * @return string|false
 */
function ah_get_relative_date_html( $date, $date_2 = null, $format = 'F j, Y g:i a' ) {
	// From $date
	$ts = strtotime((string) $date);
	if ( ! $ts ) return false;
	
	// To $date_2
	if ( $date_2 === null ) {
		$ts_2 = current_time('timestamp');
	}else{
		$ts_2 = strtotime( (string) $date_2 );
	}
	
	$date_formatted = date( $format, $ts );
	$date_relative = human_time_diff( $ts, $ts_2 ) . ' ago';
	
	return sprintf(
		'<abbr title="%s" class="ah-relative-date ah-tooltip">%s</abbr>',
		esc_attr($date_formatted),
		esc_html($date_relative)
	);
}

/**
 * Format a number for use as currency: 0.35 -> $0.35
 * Optionally remove zero cents: 5.00 -> $5
 *
 * @param $amount
 * @param $remove_zeroes
 *
 * @return array|string|string[]
 */
function ah_format_price( $amount, $remove_zeroes = true ) {
	$is_negative = $amount < 0;
	$amount = number_format(  abs( $amount ), 2 );
	$amount = '$' . $amount;
	if ( $remove_zeroes ) $amount = str_replace( '.00', '', $amount );
	if ( $is_negative ) $amount = '-' . $amount;
	return $amount;
}

/**
 * Get the user ID of the author of a post
 *
 * @param $post_id
 *
 * @return int
 */
function ah_get_author_user_id( $post_id ) {
	return get_post_field( 'post_author', $post_id );
}

/**
 * Get the value from a WP_User object based on the user's ID
 *
 * @param $user_id
 * @param $field
 * @param $default
 *
 * @return int|mixed|null
 */
function ah_get_user_field( $user_id, $field, $default = null ) {
	$cache_key = 'ah-user-' . $user_id;
	
	$user = wp_cache_get( $cache_key );
	
	if ( !$user ) {
		$user = get_user_by( 'id', $user_id );
		wp_cache_set( $cache_key, $user );
	}
	
	return ( $user instanceof WP_User ) ? $user->get( $field ) : $default;
}

/**
 * Get the user's full name by combining their first and last name.
 *
 * @param $user_id
 *
 * @return string
 */
function ah_get_user_full_name( $user_id ) {
	$first_name = get_user_meta( $user_id, 'first_name', true );
	$last_name = get_user_meta( $user_id, 'last_name', true );
	
	if ( ! $first_name ) $first_name = ah_get_user_field( $user_id, 'first_name');
	if ( ! $last_name ) $last_name = ah_get_user_field( $user_id, 'last_name');
	
	$full_name = trim( $first_name . ' ' . $last_name );
	if ( ! $full_name ) $full_name = 'User #'. $user_id;
	
	return $full_name;
}

/**
 * Apply merge tags to a string, replacing keys [first_name] with values "Radley". Merge tag keys should include brackets.
 *
 * @param string $string
 * @param array $merge_tags
 *
 * @return string
 */
function ah_apply_merge_tags( $string, $merge_tags ) {
	return str_ireplace( array_keys($merge_tags), array_values($merge_tags), $string );
}

/**
 * Stream a file to the user's browser allowing them to download a file directly.
 *
 * @param $attachment_id
 *
 * @return void
 */
function ah_stream_file_to_browser( $attachment_id ) {
	$path = get_attached_file( $attachment_id );
	$mime = mime_content_type( $path );
	$filename = pathinfo( $path, PATHINFO_BASENAME );
	$filesize = filesize( $path );
	
	ob_clean();
	header( "Content-type: " . $mime, true, 200 );
	header( "Content-Transfer-Encoding: Binary" );
	header( "Content-disposition: attachment;filename=" . esc_attr($filename) );
	header( "Content-length: " . $filesize );
	header( "Pragma: no-cache" );
	header( "Expires: 0" );
	header( "Cache-Control: no-store, no-cache, must-revalidate" );
	header( "Cache-Control: post-check=0, pre-check=0", false );
	echo file_get_contents( $path );
	exit();
}

/**
 * Splits an HTML string approximately in half at a <p> tag. Returns an array in two parts [0] and [1].
 *
 * @param string $html
 * @param int $min_chars
 *
 * @return array|string
 */
function ah_split_html_string( $html, $min_chars = 1000 ) {
	if ( mb_strlen($html) < $min_chars ) return $html;
	if ( !str_contains($html, '</p>') ) return $html;
	
	// Split the string into an array of <p> tags (the </p> gets removed at the end which we add back later)
	$split = preg_split('/<\/p>\s*/', $html);
	$length = mb_strlen($html);
	$half_length = ceil($length / 2);
	
	$count_length = 0;
	$count_index = 0;
	
	// Clean up and count characters in each item
	foreach( $split as $k => &$s ) {
		$s = trim($s);
		
		if ( empty($s) ) {
			unset($split[$k]);
		}else{
			$s = $s . '</p>';
		}
		
		// Keep counting until we have at least half characters needed. We split it at that index
		if ( $count_length < $half_length ) {
			$count_length += mb_strlen($s);
			$count_index += 1;
		}
	}
	
	// Join the HTML parts back together at the half-way point.
	$html = array(
		0 => implode( "\n", array_slice( $split, 0, $count_index ) ),
		1 => implode( "\n", array_slice( $split, $count_index, null ) ),
	);
	
	return $html;
}

/**
 * Get the URL to a CSS or JS file by url, with version
 *
 * @param $filename
 *
 * @return string
 */
function ah_get_asset_url( $filename ) {
	$v = filemtime( AH_PATH . '/assets/' . $filename );
	return AH_URL . '/assets/' . $filename . '?v=' . $v;
}

/**
 * Displays an <img> tag at full resolution, scaled to fit the given width and height
 *
 * @param $image_id
 * @param $max_w
 * @param $max_h
 *
 * @return void
 */
function ah_display_image( $image_id, $max_w = 1055, $max_h = 815 ) {
	
	$img = wp_get_attachment_image_src( $image_id, 'full' );
	
	$img_src = $img[0];
	list( $img_w, $img_h ) = ah_fit_image_size( $img[1], $img[2], $max_w, $max_h );
	
	$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	if ( !$alt ) $alt = get_the_title($image_id);
	
	echo sprintf(
		'<img src="%s" class="ah-image" width="%d" height="%d" alt="%s" loading="lazy">',
		esc_attr($img_src),
		intval($img_w),
		intval($img_h),
		esc_attr($alt)
	);
}

/**
 * Gets a width and height that will fix within the maximum width/height parameters.
 *
 * @param int $original_w
 * @param int $original_h
 * @param int $max_w
 * @param int $max_h
 * @param bool $as_float    Default (false) returns rounded integers. If true, returns with decimals, as a float.
 *
 * @return false|int[]|float[]
 */
function ah_fit_image_size( $original_w, $original_h, $max_w = 0, $max_h = 0, $as_float = false ) {
	
	$w = (int) $original_w;
	$h = (int) $original_h;
	
	if ( $w <= 0 || $h <= 0 ) return false;

	$ratio_h = $w / $h;
	$ratio_w = $h / $w;
	
	// [based on ratio_w]
	// for a 300x150 image (landscape orientation):
	// h / w = r [ratio]
	// 150 / 300 = 0.5
	// if max_w = 200:
	//   new_w = 200
	//   new_h = 200 * r = 100
	
	// Fit width
	if ( $max_w > 0 && $w > $max_w ) {
		$w = $max_w;
		$h = $w * $ratio_w;
	}
	
	// Fit height
	if ( $max_h > 0 && $h > $max_h ) {
		$h = $max_h;
		$w = $h * $ratio_h;
	}
	
	$w = round($w);
	$h = round($h);
	
	return array( $w, $h );
}

/**
 * Displays a <bookmark> element which is used by mPDF to generate a table of contents in the itinerary PDF.
 * @see https://mpdf.github.io/reference/html-control-tags/bookmark.html
 *
 * @param $title
 * @param $level
 *
 * @return void
 */
function ah_display_bookmark( $title, $level = 0 ) {
	$title = str_replace( array('–', '—'), '-', $title ); // convert ndash and mdash to hyphen
	$title = preg_replace('/[^a-zA-Z0-9 -._]/', '', $title ); // only alphanumeric and basic symbols
	
	?>
	<bookmark content="<?php echo esc_attr($title); ?>" level="<?php echo $level; ?>"></bookmark>
	<?php
}

/**
 * Removes some unsupported html from image tags like srcset and sizes.
 *
 * @param $img_tag
 *
 * @return array|string|string[]|null
 */
function ah_sanitize_mpdf_img( $img_tag ) {
	// Remove some attributes
	$removal = array(
		'/ loading="lazy"/',
		'/ decoding="async"/',
		'/ sizes="(.*?)"/',
		'/ srcset="(.*?)"/',
	);
	
	$img_tag = preg_replace( $removal, '', $img_tag );
	
	return $img_tag;
}

/**
 * Get the attachment ID of the preview image to use for the document.
 * If no preview image specified, uses the attached file to generate a preview.
 * Returns false if no preview image is available.
 *
 * @param $post_id
 *
 * @return int|false
 */
function ah_get_document_preview_image( $post_id ) {
	// use preview image first
	$attachment_id = (int) get_field( 'preview_image', $post_id, false );
	$img = wp_get_attachment_image( $attachment_id );
	
	if ( !$img ) {
		// get image directly from the file
		$attachment_id = (int) get_field( 'file', $post_id, false );
		$img = wp_get_attachment_image( $attachment_id );
		
		if ( !$img ) {
			$attachment_id = false;
		}
	}
	
	return $attachment_id ?: false;
}

/**
 * Get the target of a document link.
 * For files, this is a direct link to the attachment.
 * For custom URLs, this links to that custom url.
 *
 * @param $post_id
 *
 * @return false|mixed|string
 */
function ah_get_document_link( $post_id ) {
	$type = get_field( 'type', $post_id );
	
	$url = false;
	
	if ( $type == 'url' ) {
		$url = get_field( 'url', $post_id );
	}else if ( $type == 'file' ) {
		$attachment_id = (int) get_field( 'file', $post_id, false );
		$url = wp_get_attachment_url( $attachment_id );
	}
	
	return $url ?: false;
}

/**
 * Displays a document preview which links to the full size document on the website
 *
 * @param $post_id
 *
 * @return void
 */
function ah_display_document_embed( $post_id ) {
	$url = ah_get_document_link( $post_id );
	
	$attachment_id = ah_get_document_preview_image( $post_id );
	
	// Display image tag
	if ( $attachment_id ) {
		$content = wp_get_attachment_image( $attachment_id, 'document-embed', false );
	}else{
		$content = $url;
	}
	
	// Remove unsupported attributes from image tag(s)
	$content = ah_sanitize_mpdf_img( $content );
	
	// Display the document
	if ( $url ) echo '<a href="', esc_attr($url), '">';
	
	echo $content;
	
	if ( $url ) echo '</a>';
}

/**
 * Return an item from the array which matches the given key/value.
 *
 * If you have an array of customers each with a "first_name" property, this can locate the item whose "first_name" = "radley".
 *
 * @param array $array
 * @param mixed $key
 * @param mixed $value
 *
 * @return mixed|null
 */
function ah_find_in_array( $array, $key, $value ) {
	if ( is_array($array) ) foreach( $array as $a ) {
		if ( isset($a[$key]) && $a[$key] == $value ) {
				return $a;
		}
	}
	
	return null;
}

/**
 * Sorts a list of associative arrays by a key within each array.
 *
 * @param array[] $list
 * @param string $key
 * @param bool $asc
 *
 * @return array
 */
function ah_sort_by_key( $list, $key, $asc = true ) {
	
	// Sort the list using a custom function, passing $key and $asc as additional arguments
	usort($list, function( $a, $b ) use ( $key, $asc ) {
		
		// Check if A before B and returns: 1, 0, or -1
		$order = strcmp( strtolower($a[$key]), strtolower($b[$key]) );
		
		// If descending order, flip the positive/negative value
		if ( ! $asc ) $order *= -1;
		
		return $order;
	});
	
	return $list;
}

/**
 * Check if an array is empty. If it contains arrays, checks that those arrays are empty. Everything must be empty!
 *
 * @param array $array
 *
 * @return bool
 */
function ah_is_array_recursively_empty( $array ) {
	if ( empty($array) ) return true; // 0, false, array()
	if ( ! is_array($array) ) return false; // non-empty value
	
	foreach( $array as $item ) {
		if ( ! ah_is_array_recursively_empty( $item ) ) return false; // check each item, abort if any contain a value
	}
	
	return true; // array is empty
}

/**
 * Search an array of items for a string for a search term.
 * Only searches 1 level deep (arrays and objects are ignored).
 * Only searches in keys with a strings or number as the value.
 * Does not sort by relevance.
 *
 * Returns an array of items that contain the search string in any of the keys that were searched.
 *
 * @param array[]       $item_list      Array of items to search
 * @param string        $search_term    String to search for
 * @param null|array    $keys_to_search (optional) Which keys to search in. Can be a single key or array of keys.
 *
 * @return array[]
 */
function ah_search_array_items( $item_list, $search_term, $keys_to_search = null ) {
	if ( $keys_to_search !== null ) $keys_to_search = (array) $keys_to_search;
	
	$search_term = strtolower($search_term);
	
	$results = array();
	
	// Search through every item
	foreach( $item_list as $item ) {
		// Search through each key
		foreach( $item as $key => $value ) {
			// Should search this key?
			if ( $keys_to_search !== null && ! in_array( $key, (array) $keys_to_search, true ) ) {
				continue;
			}
			
			// Only search numbers and string
			if ( ! is_string($value) && ! is_numeric($value) ) {
				continue;
			}
			
			// Check if search term is in this key
			if ( str_contains( strtolower($value), $search_term ) ) {
				$results[] = $item;
				
				// Stop searching through this item's keys
				break;
			}
		}
	}
	
	return $results;
}

/**
 * Test if a string appears to be a phone number by checking if it contains 8 to 18 numeric digits.
 * If the string contains 50% non-numbers it fails the test.
 * This is a very rough test.
 *
 * @param string $phone
 *
 * @return bool
 */
function ah_is_phone_number( $phone ) {
	$number = preg_replace('/[^0-9]/', '', $phone);
	
	$length = strlen($number);
	if ( $length < 8 ) return false;
	if ( $length > 18 ) return false;
	
	// fail if 50% non numbers
	$max_length = strlen($phone);
	$number_ratio = $length / $max_length;
	if ( $number_ratio <= 0.5 ) return false;
	
	return true;
}

/**
 * Create an HTML link using a phone number.
 * Supports international numbers by preserving common symbols.
 * Supports an optional extension displayed after the link.
 *
 * @param string $phone
 *
 * @return string
 */
function ah_get_phone_number_link( $phone ) {
	// Check for an extension, split it from the phone number
	if ( preg_match('/([0-9])[^0-9]*(extension|ext|x).*?([0-9\-|\/]+)\b/i', $phone, $matches) ) {
		// Get extension to put after the link
		$extension = ' ext. ' . $matches[3];
		
		// Remove the extension from the phone number
		$pos = strpos( $phone, $matches[0] );
		$phone = substr( $phone, 0, $pos + 1 );
	}else{
		// No extension
		$extension = '';
	}
	
	// Get the digits
	$number = strtoupper(preg_replace('/[^0-9]/i', '', $phone)); // "(123) 456-7890 ext. 123" => "1234567890"
	
	// Special formatting for USA
	if ( preg_match('/^(\+1|1)?([2-9]\d{2})([\d]{3})([\d]{4})$/i', $number, $matches) ) {
		//   555.123.1234 ->
		// (555) 456-7890 ->
		// = 1 (555) GET-LOST
		$link = sprintf('tel:+1%d%d%d', $matches[2], $matches[3], $matches[4]);
		$display = sprintf('1 (%d) %d-%d', $matches[2], $matches[3], $matches[4]);
	} else {
		// Other countries:
		// Only allow numbers and these symbols
		$link = 'tel:+' . $number;
		$display = preg_replace('/[^0-9\(\)\-\. ]/', '', $phone );
	}
	
	// Do not use links in PDF
	if ( ah_is_pdf() ) {
		return $display . $extension;
	}
	
	// Get an HTML link
	return sprintf(
		'<a href="%s" itemprop="telephone">%s</a>%s',
		esc_attr($link),
		esc_html($display),
		esc_html($extension)
	);
}

/**
 * Check if a URL is external (takes you to a different website)
 *
 * @param string $url
 *
 * @return bool
 */
function ah_is_link_external($url) {
	// Parse the given URL
	$parsed_url = wp_parse_url($url);
	
	// Check if the URL is relative, these are considered internal
	if ( empty($parsed_url['host']) ) {
		return false; // internal (relative)
	}
	
	// Get the current website domain
	$home_url = wp_parse_url(home_url());
	
	// Check if the URL domain matches the current website domain
	if ( $parsed_url['host'] === $home_url['host'] ) {
		return false; // internal
	}
	
	return true; // external
}

/**
 * Format an array of attributes based on the provided $template.
 * If $is_array is true, assumes the $values are an array of args that each need to be prepared.
 *
 * Could also have been called ah_structure_atts() or ah_format_atts()
 *
 * @param array $template
 * @param array|array[] $values
 * @param bool $has_sub_items
 *
 * @return array
 */
function ah_prepare_atts( $template, $values, $has_sub_items = false ) {
	if ( $has_sub_items ) {
		// For arrays, prepare each item the same way
		$value = array();
		
		if ( $values && is_array($values) ) foreach( $values as $k => $v ) {
			$value[$k] = ah_prepare_atts( $template, $v );
		}
		
		return $value;
	}else{
		// Apply the array template to single values
		$result = array();
		
		foreach( $template as $key => $default_value ) {
			$result[ $key ] = $values[ $key ] ?? $default_value;
		}
		
		return $result;
	}
}

/**
 * Takes an array of column and data and returns an array formatted to match the columns using values from data
 *
 * @param array $columns
 * @param array $data
 *
 * @return array
 */
function ah_prepare_columns( $columns, $data ) {
	$template = array_fill_keys( array_keys($columns), null );
	return ah_prepare_atts( $template, $data );
}

/**
 * All post types added by this plugin
 */
function ah_get_custom_post_types() {
	return array(
		'ah_document',
		'ah_account_page',
		'ah_hike',
		'ah_hotel',
		'ah_invoice',
		'ah_itinerary',
		'ah_itinerary_tpl',
		'ah_village',
	);
}

/**
 * Post types used by the itinerary system
 */
function ah_get_itinerary_post_types() {
	return array(
		'ah_hike',
		'ah_hotel',
		'ah_itinerary',
		'ah_itinerary_tpl',
		'ah_village',
	);
}