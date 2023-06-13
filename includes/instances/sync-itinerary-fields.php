<?php

/** used by @see Class_AH_Smartsheet_Sync_Itineraries() */

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
			'value' => NULL,
		),
		/*
		array(
			'title' => 'Departure Information',
			'meta_key' => 'departure_information',
			'format_callback' => 'get_formatted_departure_information',
			'type' => 'textarea',
			'value' => NULL,
		),
		array(
			'title' => 'Phone Numbers',
			'meta_key' => 'phone_numbers',
			'format_callback' => 'get_formatted_phone_numbers',
			'type' => 'repeater',
			'value' => NULL,
		),
		array(
			'title' => 'Country Codes',
			'meta_key' => 'country_codes',
			'format_callback' => 'get_formatted_country_codes',
			'type' => 'text',
			'value' => NULL,
		),
		array(
			'title' => 'Tour Overview',
			'meta_key' => 'tour_overview',
			'format_callback' => 'get_formatted_tour_overview',
			'type' => 'editor',
			'value' => NULL,
		),
		*/
		array(
			'title' => 'Villages',
			'meta_key' => 'villages',
			'format_callback' => 'get_formatted_villages',
			'type' => 'repeater',
			'value' => NULL,
		),
		array(
			'title' => 'Hikes',
			'meta_key' => 'hikes',
			'format_callback' => 'get_formatted_hikes',
			'type' => 'repeater',
			'value' => NULL,
		),
	);
	
	/*
	private string $village_field_name = 'villages';
	private array $village_field_settings = array(
		array(
			'title' => 'Village',
			'meta_key' => 'village',
			'type' => 'post_id',
			'post_type' => 'ah_village',
		),
		array(
			'title' => 'Hotel',
			'meta_key' => 'hotel',
			'type' => 'post_id',
			'post_type' => 'ah_hotel',
		),
		array(
			'title' => 'Add Text',
			'meta_key' => 'add_text',
			'type' => 'true_false',
		),
	);
	
	private string $hike_field_name = 'hikes';
	private array $hike_field_settings = array(
		array(
			'title' => 'Hike',
			'meta_key' => 'hike',
			'type' => 'post_id',
			'post_type' => 'ah_village',
		),
		array(
			'title' => 'Add Text',
			'meta_key' => 'add_text',
			'type' => 'true_false',
		),
	);
	*/
	
	/** used by @see Class_AH_Smartsheet_Sync_Itineraries() */
	public function __construct( $id_or_client, $hotels = null, $dates = null, $hikes = null ) {
		
		// Prepare values from a post ID
		if ( is_int( $id_or_client ) ) {
			$post_id = $id_or_client;
			$this->load_from_post( $post_id );
		}
		
		// Prepare values from values prepared by from smartsheet
		/** used by @see Class_AH_Smartsheet_Sync_Itineraries::sync_itinerary_with_sheet() */
		else if ( is_array( $id_or_client ) ) {
			$client = $id_or_client;
			$this->load_from_args( $client, $hotels, $dates, $hikes );
		}
		
	}
	
	/**
	 * Returns an array of values for each field
	 *
	 * @return array
	 */
	public function get_values() {
		$all_values = array();
		
		foreach( $this->fields as $field ) {
			$all_values[ $field['meta_key'] ] = $field['value'];
		}
		
		return $all_values;
	}
	
	private function load_from_post( $post_id ) {
		foreach( $this->fields as &$field ) {
			// $field: title, meta_key, type
			$field['value'] = get_field( $field['meta_key'], $post_id );
		}
	}
	
	private function prepare_atts( $template, $args, $is_array = null ) {
		if ( $is_array ) {
			// For arrays, prepare each item the same way
			$value = array();
			
			if ( $args && is_array($args) ) foreach( $args as $k => $v ) {
				$value[$k] = $this->prepare_atts( $template, $v );
			}
			
			return $value;
		}else{
			// Single values
			$value = shortcode_atts( $template, $args );
			return $value;
		}
	}
	
	private function load_from_args( $client, $hotels, $dates, $hikes ) {
		
		// Prepare client
		$client = $this->prepare_atts( array(
			'name' => null,         // string(22) "Rich & Rachel Buchanan"
			'email' => null,        // string(0) "rich@example.org"
			'mobile' => null,       // string(0) "5551231234"
		), $client );
		
		// Prepare hotels
		// * There are multiple hotels, identified by ARRAY_A below
		$hotels = $this->prepare_atts(array(
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
		$dates = $this->prepare_atts(array(
			'arrival_date' => null,   // string(10) "2023-08-17"
			'departure_date' => null, // string(10) "2023-08-23"
			'duration' => null,       // int(6)
		), $dates );
		
		// Get the value of each field from its callback
		foreach( $this->fields as &$field ) {
			$cb = array( $this, $field['format_callback'] );
			$field['value'] = call_user_func($cb, $client, $hotels, $dates, $hikes, $field );
		}
	}
	
	private function get_formatted_title( $client, $hotels, $dates, $hikes, $field ) {
		return false;
	}
	
	private function get_formatted_subtitle( $client, $hotels, $dates, $hikes, $field ) {
		$start_hotel = reset($hotels);
		$end_hotel = end($hotels);
		if ( ! $start_hotel || ! $end_hotel ) return false;
		
		$start_village = AH_Village()->get_village_name( $start_hotel['village_id'] );
		if ( ! $start_village ) $start_village = $start_hotel['village_name'];
		
		$end_village = AH_Village()->get_village_name( $end_hotel['village_id'] );
		if ( ! $end_village ) $end_village = $end_hotel['village_name'];
		
		return $start_village . ' to ' . $end_village;
	}
	
	private function get_formatted_date_range( $client, $hotels, $dates, $hikes, $field ) {
		$date_range = ah_get_date_range( $dates['arrival_date'], $dates['departure_date'] );
		if ( ! $date_range ) return false;
		
		return $date_range;
	}
	
	private function get_formatted_schedule( $client, $hotels, $dates, $hikes, $field ) {
		if ( ! $hotels ) return false;
		
		$schedule = array();
		
		foreach( $hotels as $hotel ) {
			$column_1 = $this->get_formatted_schedule__column_1( $hotel, $client, $hotels, $dates, $hikes, $field );
			$column_2 = $this->get_formatted_schedule__column_2( $hotel, $client, $hotels, $dates, $hikes, $field );
			$column_3 = $this->get_formatted_schedule__column_3( $hotel, $client, $hotels, $dates, $hikes, $field );
			
			$schedule[] = array(
				'column_1' => $column_1,
				'column_2' => $column_2,
				'column_3' => $column_3,
			);
		}
		
		return $schedule ? $schedule : false;
	}
	
	private function get_formatted_schedule__column_1( $hotel, $client, $hotels, $dates, $hikes, $field ) {
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
		}
		
		return implode( "\n", $output );
	}
	
	private function get_formatted_schedule__column_2( $hotel, $client, $hotels, $dates, $hikes, $field ) {
		// [Schedule - Column 2]
		// Chalet Schwarzwaldalp
		// breakfast & dinner included
		// 1 double room
		// luggage: yes
		
		$output = array();
		
		// Chalet Schwarzwaldalp
		if ( $hotel['hotel_id'] ) {
			$hotel_name = AH_Hotel()->get_hotel_name($hotel['hotel_id'] );
			$output[] = $hotel_name;
		}else if ( $hotel['hotel_name'] ) {
			$output[] = '[Missing Hotel: "' . $hotel['hotel_name'] . '"]';
		}
		
		// breakfast & dinner included
		if ( $hotel['meal'] ) {
			$meal_code = $hotel['meal']; // BD/B&B -> breakfast included
			$meal_name = AH_Smartsheet_Sync_Rooms_And_Meals()->get_meal( $meal_code, 'meal_name_short' );
			if ( $meal_name ) $output[] = $meal_name;
		}
		
		// 1 double room
		if ( $hotel['room'] ) {
			$room_code = $hotel['room']; // 1 Db -> 1 double room
			$room_name = AH_Smartsheet_Sync_Rooms_And_Meals()->get_room( $room_code, 'room_name' );
			if ( $room_name ) $output[] = $room_name;
		}
		
		// luggage: yes
		$output[] = 'luggage: ' . ($hotel['luggage'] ? 'yes' : 'no');
		
		return implode( "\n", $output );
	}
	
	private function get_formatted_schedule__column_3( $hotel, $client, $hotels, $dates, $hikes, $field ) {
		// [Schedule - Column 3]
		// 41 33 971 3515
		
		$output = array();
		
		if ( $hotel['hotel_id'] ) {
			$output[] = get_field( 'phone', $hotel['hotel_id'] );
		}
		
		return implode( "\n", $output );
	}
	
	/*
	private function get_formatted_departure_information( $client, $hotels, $dates, $hikes, $field ) {
		return false;
	}
	
	private function get_formatted_phone_numbers( $client, $hotels, $dates, $hikes, $field ) {
		return false;
	}
	
	private function get_formatted_country_codes( $client, $hotels, $dates, $hikes, $field ) {
		return false;
	}
	
	private function get_formatted_tour_overview( $client, $hotels, $dates, $hikes, $field ) {
		return false;
	}
	*/
	
	private function get_formatted_villages( $client, $hotels, $dates, $hikes, $field ) {
		if ( ! $hotels ) return false;
		
		$item = array(
			'village',
			'hotel',
			'add_text',
			'content',
		);
		
		// @todo list village ids
		
		return false;
	}
	
	private function get_formatted_hikes( $client, $hotels, $dates, $hikes, $field ) {
		if ( ! $hikes ) return false;
		
		$item = array(
			'hike',
			'add_text',
			'content',
		);
		
		$list = array();
		
		if ( $hikes ) foreach( $hikes as $hike ) {
			
			// @todo add hike post ID
			
			$row = array(
				'hike' => $hike['hike_name'],
				'add_text' => null,
				'content' => null,
			);
			
			$list[] = $row;
		}
		
		return $list;
	}
	
}