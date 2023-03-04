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
	$amount = '$' . number_format(  (float) $amount, 2 );
	if ( $remove_zeroes ) $amount = str_replace( '.00', '', $amount );
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
		$user = get_user_by( 'id', 'user_id' );
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
	return trim( ah_get_user_field( $user_id, 'first_name' ) . ' ' . ah_get_user_field( $user_id, 'last_name' ) );
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