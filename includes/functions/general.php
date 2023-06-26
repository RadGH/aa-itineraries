<?php

/**
 * Get generic merge tags that can be used for things like filling in email templates with tags
 *
 * @return string[]
 */
function ah_get_general_merge_tags() {
	$site_url = site_url('/');
	$account_url = site_url('/account/');
	
	return array(
		'[site_url]' => $site_url,
		'[account_url]' => $account_url,
	);
}

/**
 * Get the URL that a document links to
 *
 * @param $post_id
 *
 * @return string
 */
function ah_get_document_redirect_url( $post_id ) {
	return untrailingslashit( get_permalink( $post_id )) . '/download/';
}

/**
 * Creates an admin notice that is displayed to any user on the admin dashboard
 *
 * @param $type
 * @param $message
 * @param $data
 * @param $unique_key
 * @param $auto_dismiss
 *
 * @return void
 */
function ah_add_admin_notice( $type, $message, $data = array(), $unique_key = null, $auto_dismiss = false ) {
	AH_Admin()->add_notice( $type, $message, $data, $unique_key, $auto_dismiss );
}

/**
 * Adds a notice to be displayed on the front-end to the current user.
 *
 * @param $type
 * @param $message
 * @param $data
 * @param $unique_key
 * @param $class
 *
 * @return void
 */
function ah_add_theme_notice( $type, $message, $data = array(), $unique_key = null, $class = '' ) {
	AH_Theme()->add_notice( $type, $message, $data, $unique_key, $class );
}

/**
 * Immediately display a single notice on the front-end.
 *
 * @param $type
 * @param $message
 * @param $data
 * @param $class
 *
 * @return void
 */
function ah_display_theme_notice( $type, $message, $data = array(), $class = '' ) {
	AH_Theme()->add_notice( $type, $message, $data, null, $class, true );
}

/**
 * Return TRUE if viewing a PDF in preview mode. Configured in theme.php and stored in pdf.php
 *
 * @return bool|mixed
 */
function ah_is_pdf() {
	return AH_PDF()->use_pdf;
}

/**
 * Return TRUE if viewing a PDF in preview mode. Configured in theme.php and stored in pdf.php
 *
 * @return bool|mixed
 */
function ah_is_pdf_preview() {
	return AH_PDF()->use_preview;
}

/**
 * Gets the hike summary for an itinerary
 *
 * @param $itinerary_id
 *
 * @return string|false
 */
function ah_get_hike_summary( $itinerary_id ) {
	$hikes = get_field( 'hikes', get_the_ID() );
	if ( ah_is_array_recursively_empty($hikes) ) return false;
	
	ob_start();
	
	foreach( $hikes as $i => $s ) {
		$hike_id = (int) $s['hike'];
		$title = get_field( 'hike_name', $hike_id ) ?: get_the_title( $hike_id );
		$links = get_field( 'link_links', $hike_id );
		$slug = get_post_field( 'post_name', $hike_id );
		
		?>
		<h3><a href="#hike-<?php echo esc_attr($slug); ?>"><?php echo $title; ?></a></h3>
		
		<?php
		if ( ! ah_is_array_recursively_empty( $links ) ) {
			foreach( $links as $l ) {
				$label = $l['label'];
				$url = $l['url'];
				?>
				<ul class="hike-list">
					<li><?php echo esc_html($label); ?>:<br><a href="<?php echo esc_attr($url); ?>"><?php echo esc_html($url); ?></a></li>
				</ul>
				<?php
			}
		}
		?>
		
		<?php
	}
	
	return ob_get_clean();
}

/**
 * Get the number of days between two dates.
 * If the dates are the same, returns 0.
 * If either date is invalid, returns false.
 *
 * @param string $date_1
 * @param string $date_2
 *
 * @return int|false
 */
function ah_get_duration_in_days( $date_1, $date_2 ) {
	$t1 = $date_1 ? strtotime($date_1) : false;
	$t2 = $date_2 ? strtotime($date_2) : false;
	
	if ( $t1 && $t2 ) {
		return (int) ceil(abs($t2 - $t1) / DAY_IN_SECONDS);
	}
	
	return false;
}


/**
 * Gets a date range formatted for display
 * 2023-08-17 -> 2023-08-23 = August 17-23, 2023
 *
 * @param $date_1
 * @param $date_2
 * @param $year_optional
 *
 * @return false|string
 */
function ah_get_date_range( $date_1, $date_2, $year_optional = false ) {
	if ( ! $date_1 ) {
		return false;
	}
	
	$start_ts = strtotime($date_1);
	$end_ts = strtotime($date_2);
	
	$start_y = date('Y', $start_ts);
	$start_m = date('n', $start_ts); // no leading zero
	$start_d = date('j', $start_ts); // no leading zero
	$start_m_name = date('F', $start_ts); // "June", full month name
	
	if ( ! $end_ts ) {
		// Just one day for some reason
		// December 20, 2023
		return $start_m_name . ' ' . $start_d . ', ' . $start_y;
	}
	
	$end_y = date('Y', $end_ts);
	$end_m = date('n', $end_ts); // no leading zero
	$end_d = date('j', $end_ts); // no leading zero
	$end_m_name = date('F', $end_ts); // "June", full month name
	
	// If year and month match, should the day be combined into one?
	$same_day_range = ($start_d != $end_d ? $start_d . '-' . $end_d : $start_d);
	
	if ( $start_y !== $end_y || $start_m !== $end_m ) {
		
		// If year or month is different, show the entire date range
		// December 20 - January 3, 2023
		return $start_m_name . ' ' . $start_d . ' - ' . $end_m_name . ' ' . $end_d . ', ' . $end_y;
		
	}else if ( $year_optional ) {
		
		// Show only relevant date range, without the year
		// December 20-24
		return $start_m_name . ' ' . $same_day_range;
		
	}else{
		
		// Show only relevant date range
		// December 20-24, 2023
		return $start_m_name . ' ' . $same_day_range . ', ' . $end_y;
		
	}
}

/**
 * Creates a "View Spreadsheet" button to use on the admin interface
 *
 * @param string $url
 * @param string $text
 * @param bool $is_button  default: true
 *
 * @return string|false
 */
function ah_create_html_link( $url, $text = 'View Spreadsheet', $is_button = true ) {
	if ( ! $url ) return false;
	
	$is_external = ah_is_link_external( $url );
	
	$classes = '';
	$target_attr = '';
	
	if ( $is_button ) {
		$classes = 'ah-link button button-secondary';
	}else{
		$classes = 'ah-link';
	}
	
	if ( $is_external ) {
		$text .= ' <span class="dashicons dashicons-external ah-dashicon-inline"></span>';
		$target_attr .= ' target="_blank" ';
	}
	
	return '<a href="'. esc_attr($url)  .'" '. $target_attr .' class="'. $classes .'">'. $text .'</a>';
}

/**
 * Formats the "Smartsheet Actions" field that is used in the ACF field group for Hikes, Hotels, and Villages
 *
 * @param int $post_id
 * @param array $field
 * @param string $sheet_url
 * @param string $sync_page_url
 * @param string $sync_item_url
 *
 * @return array
 */
function ah_prepare_smartsheet_actions_field( $post_id, $field, $sheet_url, $sync_page_url, $sync_item_url ) {
	if ( acf_is_screen('acf-field-group') ) return $field; // never modify the field group edit screen
	
	$post_type = get_post_type($post_id);
	$post_type_name = acf_get_post_type_label($post_type);
	$sync_item_text = 'Sync this ' . $post_type_name;
	
	$last_sync = get_post_meta( $post_id, 'smartsheet_last_sync', true );
	
	if ( $sheet_url ) {
		if ( $field['message'] ) $field['message'] .= "\n\n";
		
		$button = ah_create_html_link( $sheet_url, 'View Spreadsheet', false );
		$field['message'] .= $button;
	}
	
	if ( $sync_page_url ) {
		if ( $field['message'] ) $field['message'] .= "\n\n";
		
		$button = ah_create_html_link( $sync_page_url, 'View Sync Page', false );
		$field['message'] .= $button;
	}
	
	if ( $sync_item_url ) {
		if ( $field['message'] ) $field['message'] .= "\n\n";
		
		$button = ah_create_html_link( $sync_item_url, $sync_item_text, false );
		$field['message'] .= $button;
	}
	
	if ( $field['message'] ) $field['message'] .= "\n\n";
	$field['message'] .= '<span class="ah-last-sync">Last sync: ' . (ah_get_relative_date_html( $last_sync ) ?: '(never)') . '</span>';
	
	$field['label'] = false;
	
	return $field;
}