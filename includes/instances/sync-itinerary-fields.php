<?php

/** used by @see Class_AH_Smartsheet_Sync_Itineraries */

class Class_Sync_Itinerary_Fields {
	
	// List of all of the fields that can be changed during a smartsheet sync
	// + user assignment is not implemented
	// + documents are not implemented
	
	private array $fields = array(
		/*
		array(
			'title' => 'Title',
			'meta_key' => 'title',
			'format_callback' => 'get_formatted_title',
			'type' => 'text',
			'value' => NULL,
		),
		*/
		array(
			'title' => 'Subtitle',
			'meta_key' => 'subtitle',
			'format_callback' => 'get_formatted_subtitle',
			'type' => 'text',
			'value' => NULL,
		),
		array(
			'title' => 'Date Range',
			'meta_key' => 'date_range',
			'format_callback' => 'get_formatted_date_range',
			'type' => 'text',
			'value' => NULL,
		),
		/*
		// array(
		// 	'title' => 'Introduction Message',
		// 	'meta_key' => 'introduction_message',
		// 	'format_callback' => '',
		// 	'type' => 'editor', // wysiwyg / tinymce
		// 	'value' => NULL,
		// ),
		// array(
		// 	'title' => 'Contact Information',
		// 	'meta_key' => 'contact_information',
		// 	'format_callback' => '',
		// 	'type' => 'editor', // wysiwyg / tinymce
		// 	'value' => NULL,
		// ),
		*/
		array(
			'title' => 'Schedule',
			'meta_key' => 'schedule',
			'format_callback' => 'get_formatted_schedule',
			'type' => 'repeater',
			'repeater_row_template' => array(
				'column_1' => '',
				'column_2' => '',
				'column_3' => '',
			),
			'value' => NULL,
		),
		/*
		array(
			'title' => 'Departure Information',
			'meta_key' => 'departure_information',
			'format_callback' => '',
			'type' => 'textarea',
			'value' => NULL,
		),
		array(
			'title' => 'Phone Numbers',
			'meta_key' => 'phone_numbers',
			'format_callback' => '',
			'type' => 'repeater',
			'value' => NULL,
		),
		array(
			'title' => 'Country Codes',
			'meta_key' => 'country_codes',
			'format_callback' => '',
			'type' => 'text',
			'value' => NULL,
		),
		array(
			'title' => 'Tour Overview',
			'meta_key' => 'tour_overview',
			'format_callback' => '',
			'type' => 'editor',
			'value' => NULL,
		),
		*/
		array(
			'title' => 'Villages',
			'meta_key' => 'villages',
			'format_callback' => 'get_formatted_villages',
			'type' => 'repeater',
			'repeater_row_template' => array(
				'village' => false,
				'hotel' => false,
				'add_text' => false,
				'content' => '',
			),
			'value' => NULL,
		),
		array(
			'title' => 'Hikes',
			'meta_key' => 'hikes',
			'format_callback' => 'get_formatted_hikes',
			'type' => 'repeater',
			'repeater_row_template' => array(
				'hike' => false,
				'add_text' => false,
				'content' => '',
			),
			'value' => NULL,
		),
	);
	
	private $new_values = null; // Values from new data
	
	private $old_values = null; // Values from the post
	
	/** used by @see Class_AH_Smartsheet_Sync_Itineraries */
	public function __construct( $post_id, $client = null, $hotels = null, $dates = null, $hikes = null ) {
		
		// Get values of the existing itinerary post
		if ( $post_id !== null ) {
			$this->old_values = $this->load_from_post( $post_id );
		}
		
		// Get values from the raw smartsheet data
		/** used by @see Class_AH_Smartsheet_Sync_Itineraries::sync_itinerary_with_sheet() */
		if ( $client !== null ) {
			$this->new_values = $this->load_from_data( $client, $hotels, $dates, $hikes );
		}
		
	}
	
	/**
	 * Returns an array of values for each field
	 *
	 * @return array
	 */
	public function get_old_values() {
		return $this->old_values;
	}
	
	public function get_new_values() {
		return $this->new_values;
	}
	
	public function get_fields() {
		return $this->fields;
	}
	
	public function get_field( $meta_key ) {
		return ah_find_in_array( $this->fields, 'meta_key', $meta_key );
	}
	
	// Warning system to show when a village/hike/etc from smartsheet is not present on the website, or other issues
	private $warnings = array();
	
	private function add_warning( $message, $data = null ) {
		$this->warnings[] = array(
			'message' => $message,
			'data' => $data
		);
	}
	
	public function get_warnings() {
		return $this->warnings;
	}
	// End warning system
	
	/**
	 * Get each meta key from $this->fields for the provided post (itinerary id)
	 * 
	 * @param $post_id
	 *
	 * @return array
	 */
	private function load_from_post( $post_id ) {
		$old_values = array();
		
		foreach( $this->fields as &$field ) {
			// $field: title, meta_key, type
			$old_values[ $field['meta_key'] ] = get_field( $field['meta_key'], $post_id );
		}
		
		return $old_values;
	}
	
	/**
	 * Get each meta key from $this->fields from the provided smartsheet data
	 * 
	 * @param array $client
	 * @param array $hotels
	 * @param array $dates
	 * @param array $hikes
	 *
	 * @return array
	 */
	private function load_from_data( $client, $hotels, $dates, $hikes ) {
		
		$values = array();
		
		// Prepare client
		// This makes sure each property exists, and NULL if it was not already defined
		$client = ah_prepare_atts( array(
			'name' => null,         // string(22) "Rich & Rachel Buchanan"
			'email' => null,        // string(0) "rich@example.org"
			'mobile' => null,       // string(0) "5551231234"
		), $client );
		
		// Prepare hotels
		// * There are multiple hotels
		$hotels = ah_prepare_atts(array(
			'room' => null,           // string(4) "1 Db"
			'meal' => null,           // string(2) "HB"
			'village_name' => null,   // string(19) "Schwarzwaldalp - CH"
			'village_id' => null,     // int(6334)
			'hotel_name' => null,     // string(31) "Schwarzwaldalp - Schwarzwaldalp"
			'hotel_id' => null,       // int(6336)
			'arrival_date' => null,   // string(10) "2023-08-17"
			'departure_date' => null, // string(10) "2023-08-18"
			'arrival_ts' => null,     // int(1692230400)
			'departure_ts' => null,   // int(1692316800)
			'duration' => null,       // int(1)
		), $hotels, true );
		
		// Prepare the dates
		$dates = ah_prepare_atts(array(
			'arrival_date' => null,   // string(10) "2023-08-17"
			'departure_date' => null, // string(10) "2023-08-23"
			'duration' => null,       // int(6)
		), $dates );
		
		// Prepare hikes
		// * There are multiple hikes
		$hikes = ah_prepare_atts(array(
			'hike_name' => null,      // string(4) "1 Db"
			'url' => null,           // string(2) "HB"
		), $hikes, true );
		
		// Get the value of each field using its callback
		foreach( $this->fields as $field ) {
			$cb = array( $this, $field['format_callback'] );
			$values[ $field['meta_key'] ] = call_user_func($cb, $client, $hotels, $dates, $hikes, $field );
		}
		
		return $values;
	}
	
	/*
	private function get_formatted_title( $client, $hotels, $dates, $hikes, $field ) {
		return false;
	}
	*/
	
	/**
	 * Create a subtitle based on the start and end hotel such as "Hotel A to Hotel B"
	 *
	 * @param $client
	 * @param $hotels
	 * @param $dates
	 * @param $hikes
	 * @param $field
	 *
	 * @return false|string
	 */
	private function get_formatted_subtitle( $client, $hotels, $dates, $hikes, $field ) {
		$start_hotel = reset($hotels);
		$end_hotel = end($hotels);
		if ( ! $start_hotel || ! $end_hotel ) {
			$this->add_warning( '[Subtitle] Start or end hotel is empty' );
			return false;
		}
		
		$start_village = AH_Village()->get_village_name( $start_hotel['village_id'] );
		if ( ! $start_village ) {
			if ( $start_village['hotel_name'] ) {
				$this->add_warning( '[Subtitle] Start hotel does not exist on the site: "'. $end_hotel['hotel_name'] . '". Try <a href="admin.php?page=ah-smartsheet-villages-and-hotels">syncing the hotel</a> first.', $end_hotel['village_id'] );
			}else{
				$this->add_warning( '[Subtitle] Start hotel name is invalid', $end_hotel['village_id'] );
			}
			$this->add_warning( '[Subtitle] Start hotel not found', $start_hotel['village_id'] );
			$start_village = $start_hotel['village_name'];
		}
		
		$end_village = AH_Village()->get_village_name( $end_hotel['village_id'] );
		if ( ! $end_village ) {
			if ( $end_hotel['hotel_name'] ) {
				$this->add_warning( '[Subtitle] End hotel does not exist on the site: "'. $end_hotel['hotel_name'] . '". Try <a href="admin.php?page=ah-smartsheet-villages-and-hotels">syncing the hotel</a> first.', $end_hotel['village_id'] );
				$end_village = $end_hotel['village_name'];
			}else{
				$this->add_warning( '[Subtitle] End hotel name is invalid', $end_hotel['village_id'] );
			}
		}
		
		return $start_village . ' to ' . $end_village;
	}
	
	/**
	 * Create a date range based on the arrival and departure dates formatted like "August 17-23, 2023"
	 *
	 * @param $client
	 * @param $hotels
	 * @param $dates
	 * @param $hikes
	 * @param $field
	 *
	 * @return false|string
	 */
	private function get_formatted_date_range( $client, $hotels, $dates, $hikes, $field ) {
		$date_range = ah_get_date_range( $dates['arrival_date'], $dates['departure_date'] );
		if ( ! $date_range ) {
			$this->add_warning( '[Date Range] Invalid date range between arrival and departure', array( 'arrival_date' => $dates['arrival_date'], 'departure_date' => $dates['departure_date'] ) );
			return false;
		}
		
		return $date_range;
	}
	
	/**
	 * Create the schedule based on all of the hotels, matching the ACF repeater structure
	 *
	 * @param $client
	 * @param $hotels
	 * @param $dates
	 * @param $hikes
	 * @param $field
	 *
	 * @return array|false
	 */
	private function get_formatted_schedule( $client, $hotels, $dates, $hikes, $field ) {
		if ( ! $hotels ) {
			$this->add_warning( '[Schedule] No hotels found' );
			return false;
		}
		
		$schedule = array();
		
		foreach( $hotels as $i => $hotel ) {
			$column_1 = $this->get_formatted_schedule__column_1( $hotel, $i, $client, $hotels, $dates, $hikes, $field );
			$column_2 = $this->get_formatted_schedule__column_2( $hotel, $i, $client, $hotels, $dates, $hikes, $field );
			$column_3 = $this->get_formatted_schedule__column_3( $hotel, $i, $client, $hotels, $dates, $hikes, $field );
			
			$schedule[] = array(
				'column_1' => $column_1,
				'column_2' => $column_2,
				'column_3' => $column_3,
			);
		}
		
		return $schedule ?: false;
	}
	
	// Schedule Column 1: Date range + Number of nights
	private function get_formatted_schedule__column_1( $hotel, $i, $client, $hotels, $dates, $hikes, $field ) {
		// [Schedule - Column 1]
		// September 6-7
		// 2 nights
		
		$output = array();
		
		$start_date = $hotel['arrival_date'];
		$end_date = $hotel['departure_date'];
		
		if ( $start_date && $end_date ) {
			// September 6-7
			$end_date = date('Y-m-d', strtotime( '-1 day', strtotime($end_date) ) );
			$date_range = ah_get_date_range( $start_date, $end_date, true );
			$output[] = $date_range;
			
			// 2 nights
			$days = $hotel['duration'];
			$output[] = sprintf( _n( '%d night', '%d nights', $days), $days );
		}else{
			$this->add_warning( '[Schedule]['. $i .'][Column 1] Start or End date invalid for hotel', $hotel );
		}
		
		return implode( "\n", $output );
	}
	
	// Schedule Column 2: Hotel, meal, room, luggage
	private function get_formatted_schedule__column_2( $hotel, $i, $client, $hotels, $dates, $hikes, $field ) {
		// [Schedule - Column 2]
		// Chalet Schwarzwaldalp
		// breakfast & dinner included
		// 1 double room
		// luggage: yes
		
		$output = array();
		
		// Chalet Schwarzwaldalp
		$hotel_name = AH_Hotel()->get_hotel_name($hotel['hotel_id'] );
		if ( $hotel['hotel_id'] && $hotel_name ) {
			$output[] = $hotel_name;
		}else{
			$output[] = '[Missing Hotel: "' . $hotel['hotel_name'] . '"]';
			$this->add_warning( '[Schedule]['. $i .'][Column 2] The hotel provided in Smartsheet does not yet exist on this website: "'. $hotel['hotel_name'] .'". Try <a href="admin.php?page=ah-smartsheet-villages-and-hotels">syncing the hotel</a> first.' );
		}
		
		// breakfast & dinner included
		$meal_code = $hotel['meal']; // BD/B&B -> breakfast included
		$meal_name = AH_Smartsheet_Sync_Rooms_And_Meals()->get_meal( $meal_code, 'meal_name_short' );
		if ( $meal_name ) {
			$output[] = $meal_name;
		}else{
			$this->add_warning( '[Schedule]['. $i .'][Column 2] No meals for hotel "'. $hotel['hotel_name'] .'"' );
		}
		
		// 1 double room
		$room_code = $hotel['room']; // 1 Db -> 1 double room
		$room_name = AH_Smartsheet_Sync_Rooms_And_Meals()->get_room( $room_code, 'room_name' );
		if ( $room_name ) {
			$output[] = $room_name;
		}else if ( $room_code ) {
			$this->add_warning( '[Schedule]['. $i .'][Column 2] Room code "'. $room_code .'" not found for hotel "'. $hotel['hotel_name'] .'". Try <a href="admin.php?page=ah-smartsheet-rooms-and-meals">syncing the room codes</a> first.' );
		}else{
			$this->add_warning( '[Schedule]['. $i .'][Column 2] No rooms entered for hotel "'. $hotel['hotel_name'] .'"' );
		}
		
		// luggage: yes
		$output[] = 'luggage: ' . ($hotel['luggage'] ? 'yes' : 'no');
		
		return implode( "\n", $output );
	}
	
	private function get_formatted_schedule__column_3( $hotel, $i, $client, $hotels, $dates, $hikes, $field ) {
		// [Schedule - Column 3]
		// 41 33 971 3515
		
		$output = array();
		
		$phone = get_field( 'phone', $hotel['hotel_id'] );
		if ( $phone ) {
			$output[] = $phone;
		}else{
			$this->add_warning( '[Schedule]['. $i .'][Column 3] No phone number for hotel "'. $hotel['hotel_name'] .'"' );
		}
		
		return implode( "\n", $output );
	}
	
	private function get_formatted_villages( $client, $hotels, $dates, $hikes, $field ) {
		if ( ! $hotels ) {
			$this->add_warning( '[Villages] No hotels found (which where villages would be listed)' );
			return false;
		}
		
		$villages = array();
		
		foreach( $hotels as $i => $hotel ) {
			// each row should contain: village, hotel, add_text, content
			$village_id = $hotel['village_id'] ?: false;
			$hotel_id = $hotel['hotel_id'] ?: false;
			
			if ( ! $village_id ) {
				$this->add_warning( '[Villages]['. $i .'] Village not found: "'. esc_html($hotel['village_name']) .'". Try <a href="admin.php?page=ah-smartsheet-villages-and-hotels">syncing the village</a> first.' );
			}
			
			if ( ! $hotel_id ) {
				$this->add_warning( '[Villages]['. $i .'] Hotel not found: "'. esc_html($hotel['hotel_name']) .'". Try <a href="admin.php?page=ah-smartsheet-villages-and-hotels">syncing the hotel</a> first.' );
			}
			
			$item = array(
				'village' => $village_id,
				'hotel' => $hotel_id,
				'add_text' => null,
				'content' => null,
			);
			
			$villages[] = $item;
		}
		
		return $villages ?: false;
	}
	
	private function get_formatted_hikes( $client, $hotels, $dates, $hikes, $field ) {
		$list = array();
		
		if ( $hikes ) foreach( $hikes as $i => $hike ) {
			$hike_name = $hike['hike_name'] ?? false;
			$hike_id = AH_Smartsheet_Sync_Hikes()->get_hike_by_smartsheet_id( $hike_name );
			
			if ( $hike_name && ! $hike_id ) {
				$this->add_warning( '[Hikes]['. $i .'] Hike not found: "'. esc_html($hike_name) .'". Try <a href="admin.php?page=ah-smartsheet-hikes">syncing the hike</a> first.' );
			}
			
			$list[] = array(
				'hike' => $hike_id,
				'add_text' => null,
				'content' => null,
			);
		}
		
		return $list;
	}
	
}