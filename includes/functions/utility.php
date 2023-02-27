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