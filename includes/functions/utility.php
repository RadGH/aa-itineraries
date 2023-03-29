<?php

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
 * Display an HTML string with 2 columns.
 *
 * @param $html
 *
 * @return void
 */
function ah_display_content_columns( $html ) {
	echo wpautop($html);
}


function ah_display_image( $image_id, $max_w = 1055, $max_h = 815 ) {
	
	$img = wp_get_attachment_image_src( $image_id, 'full' );
	
	$img_src = $img[0];
	list( $img_w, $img_h ) = ah_fit_image_size( $img[1], $img[2], $max_w, $max_h );
	
	$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	if ( !$alt ) $alt = get_the_title($image_id);
	
	echo sprintf(
		'<img src="%s" class="ah-image" width="%d" height="%d" alt="%s">',
		esc_attr($img_src),
		intval($img_w),
		intval($img_h),
		esc_attr($alt)
	);
}

/**
 * Gets a width and height that will fix within the maximum width/height parameters.
 *
 * @param $original_w
 * @param $original_h
 * @param $max_w
 * @param $max_h
 *
 * @return array|float[]|int[]
 */
function ah_fit_image_size( $original_w, $original_h, $max_w = 0, $max_h = 0 ) {
	
	$w = $original_w;
	$h = $original_h;
	
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
	
	return array( $w, $h );
}