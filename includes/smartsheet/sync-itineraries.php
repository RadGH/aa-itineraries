<?php

class Class_AH_Smartsheet_Sync_Itineraries {
	
	public function __construct() {
		
		// To sync, use the sync button when editing an itinerary
		/** @see Class_Itinerary_Post_Type::sync_itinerary_with_sheet() */
		
	}
	
	// Stores column settings from the sheet
	/** @see self::load_columns() */
	public $columns;
	public $column_task;
	public $column_room;
	public $column_meal;
	public $column_arrival;
	public $column_location;
	public $column_hotel;
	public $column_departure;
	public $column_luggage;
	public $column_hike;
	public $column_outdooractive;
	public $column_region;
	
	// Stores rows that contain data
	public $rows;
	public $rows_hotels;
	public $rows_restaurants;
	public $rows_hikes;
	// public $row_arrival;   // single row
	// public $row_departure; // single row
	
	/**
	 * Load and store column data which is used during the sync
	 *
	 * @param int $sheet_id
	 *
	 * @return void
	 */
	public function load_columns( $sheet_id ) {
		$column_info = AH_Smartsheet_API()->get_sheet_columns( $sheet_id, true );
		
		$columns = $column_info['data'];
		
		foreach( $this->columns as $i => &$c ) {
			$c['index'] = $i;
		}
		
		$this->columns = $columns;
		
		// $columns[0] = !!! (Checkboxes)
		
		// Task column contains identifiers
		// Hotel1 ... Hotel9
		// Restaurant1 ... Restaurant3
		// Hike1 ... Hike10
		// Travel Details, Arrival:, Departure:
		$this->column_task = $this->locate_column( $columns, 'Task', 1 );
		
		// $columns[2] = Task Detail/Comments
		
		// Room and Meal columns contain a code for each hotel
		// Codes are converted, refer to sync-rooms-and-meals.php
		// task | room | meal
		// Hotel1 | 1 Db | HB
		// Hotel2 | 1 Db-for-Sg | BD/B&B
		$this->column_room = $this->locate_column( $columns, 'Rooms', 3 );
		
		$this->column_meal = $this->locate_column( $columns, 'Meals', 4 );
		
		// $columns[5] = Em (Checkboxes)
		// $columns[6] = Done (Checkboxes)
		
		// Arrival dates are used for hotels and restaurants (mm/dd/yy format)
		// Arrival and departure dates are stored where Task column is: Travel Details -> "Arrival:" and "Departure:"
		$this->column_arrival = $this->locate_column( $columns, 'Arrive', 7 );
		
		// $columns[8] = Nts (numbers)
		
		// Location column stores the village/country for each hotel and restaurant (WP Village ID)
		// Schwarzwaldalp - CH
		// Grindelwald - CH
		$this->column_location = $this->locate_column( $columns, 'Location', 9 );
		
		// Hotel column stores the hotel code (WP Hotel ID)
		$this->column_hotel = $this->locate_column( $columns, 'Hotel/Vendor', 10 );
		
		// Departure column is similar to Arrival column. Not used for restaurants.
		$this->column_departure = $this->locate_column( $columns, 'Depart', 11 );
		
		// Luggage checkboxes
		$this->column_luggage = $this->locate_column( $columns, 'luggage', 12 );
		
		// $columns[13] = Assigned To
		
		// Hike list is the name of a hike
		//$this->column_hike = $columns[14];
		$this->column_hike = $this->locate_column( $columns, 'Hike list', 14 );
		
		// $columns[15] = Due date
		// $columns[16] = Em to Client
		// $columns[17] = Due Date Override
		
		// Outdoor active links for each hike (only one link?)
		$this->column_outdooractive = $this->locate_column( $columns, 'Outdooractive', 20 ); // or 18 previously
		
		// $columns[19] = Signup Date
		// $columns[20] = Tags
		// $columns[21] = Print Pages Lookup

		$this->column_region = $this->locate_column( $columns, 'Region', 22 );
		
		// Columns 23+ are removed from the result in $this->rows in $this->load_rows
	}
	
	/**
	 * Look for a column with the given title and return its index if it matches.
	 *
	 * @param array[] $columns
	 * @param string $title             Regex pattern such as: '/^.*' . preg_quote("Task") . '.*$/i'
	 * @param int|null $default_index   Default: null. If no match is found, use this column index.
	 *
	 * @return array|false
	 */
	public function locate_column( $columns, $title, $default_index = null ) {
		foreach( $columns as $i => $c ) {
			if ( isset($c['title']) && $c['title'] == $title ) {
				return $c;
			}
		}
		
		if ( $default_index !== null && isset($columns[$default_index]) ) {
			$this->add_warning( '[Spreadsheet] Column not found with title "' . esc_html($title) . '". Using fallback column index "' . $default_index .'" with title "'. esc_html($columns[$default_index]['title'] ?? '(no title)') .'"' );
			return $columns[$default_index];
		}
		
		return false;
	}
	
	/**
	 * Load and store all rows from a sheet
	 *
	 * @param $sheet_id
	 *
	 * @return void
	 */
	public function load_rows( $sheet_id ) {
		$this->rows = AH_Smartsheet_API()->get_rows_from_sheet( $sheet_id );
		
		// Only keep columns 0 through 18
		foreach( $this->rows as &$row ) {
			$row['cells'] = array_slice( $row['cells'], 0, 23 );
		}
		
		// Rows are identified by the Task column
		$task_column_id = $this->column_task['id'];
		
		// Get hotel rows
		$start_row = $this->find_row_by_column( $task_column_id, 'Hotel1' );
		$parent_id = $start_row['parentId'] ?? false;
		$this->rows_hotels = $this->find_rows_by_property( $parent_id, 'parentId' );
		
		// Get restaurant rows
		$start_row = $this->find_row_by_column( $task_column_id, 'Restaurant1' );
		$parent_id = $start_row['parentId'] ?? false;
		$this->rows_restaurants = $this->find_rows_by_property( $parent_id, 'parentId' );
		
		// Get hike rows
		$start_row = $this->find_row_by_column( $task_column_id, 'Hike1' );
		$parent_id = $start_row['parentId'] ?? false;
		$this->rows_hikes = $this->find_rows_by_property( $parent_id, 'parentId' );
		
		// UNUSED
		// Get arrival date and departure date rows
		// $this->row_arrival = $this->find_row_by_column( $task_column_id, 'Arrival:' );
		// $this->row_departure = $this->find_row_by_column( $task_column_id, 'Departure:' );
	}
	
	/**
	 * Searches for all rows assigned the given value or other $property.
	 * If no matches found, returns an empty array.
	 *
	 * @param string $value     Value within the row
	 * @param string $key       Key within a cell where the $value is stored. Defaults to the cell "value"
	 * @param bool $_multiple   Default: false. If true, searches for all rows that match.
	 *
	 * @return array|false
	 */
	public function find_row_by_property( $value, $key = 'value', $_multiple = false ) {
		
		// If searching for multiple rows
		$found_rows = array();
		
		// Search all rows
		foreach( $this->rows as $row ) {
			// Check that the value (or other property) matches
			$row_value = $row[$key] ?? '';
			
			if ( $row_value == $value ) {
				// Row has correct value
				if ( $_multiple ) {
					$found_rows[] = $row;
				}else{
					return $row; // Return a single row
				}
			}
		}
		
		if ( $_multiple ) return $found_rows;
		
		return false;
		
	}
	
	/**
	 * Searches for all rows assigned the given value or other $property.
	 *
	 * @param string $value
	 * @param string $key
	 *
	 * @return array
	 */
	public function find_rows_by_property( $value, $key = 'value' ) {
		if ( ! $value ) return array();
		
		return $this->find_row_by_property( $value, $key, true );
	}
	
	/**
	 * Searches for a row with a specific value (or other $property) in a specific column
	 *
	 * @param int $column_id
	 * @param string $value     Value with a cell of a row
	 * @param string $key       The key within a cell to search for $value. Defaults to "value"
	 * @param bool $_multiple   Default: false. If true, searches for all rows that match.
	 *
	 * @return array|false
	 */
	public function find_row_by_column( $column_id, $value, $key = 'value', $_multiple = false ) {
		
		// If searching for multiple rows
		$found_rows = array();
		
		// Search all rows
		foreach( $this->rows as $row ) {
			// Search columns within the row
			foreach( $row['cells'] as $cell ) {
				
				// Only search the specified column
				if ( $cell['columnId'] != $column_id ) continue;
				
				// Check that the value (or other property) matches
				$cell_value = $cell[$key] ?? '';
				
				if ( $cell_value == $value ) {
					// Row has correct value
					if ( $_multiple ) {
						$found_rows[] = $row;
						continue 2; // Skip to next row
					}else{
						return $row; // Return a single row
					}
				}
				
			}
		}
		
		if ( $_multiple ) return $found_rows;
		
		return false;
		
	}
	
	/**
	 * Find multiple rows with a value in a cell.
	 *
	 * @param $column_id
	 * @param $value
	 * @param $key
	 *
	 * @return array[]
	 */
	public function find_rows_by_column( $column_id, $value, $key = 'value' ) {
		return $this->find_row_by_column( $column_id, $value, $key, true );
	}
	
	/**
	 * Get the value from a list of cells by locating the column ID
	 *
	 * @param $cells
	 * @param $column_id
	 *
	 * @return false|mixed
	 */
	public function get_cell_value( $cells, $column_id ) {
		// Allow using a row directly
		if ( isset($cells['cells']) ) $cells = $cells['cells'];
		
		if ( $cells ) {
			foreach( $cells as $cell ) {
				if ( $cell['columnId'] == $column_id ) return $cell['value'];
			}
		}
		
		return false;
	}
	
	/**
	 * Get the timestamp for a date string. Returns false if the date format is invalid, or if the year is 1970 or earlier.
	 *
	 * @param string $date
	 *
	 * @return false|int
	 */
	public function get_timestamp( $date ) {
		if ( $date ) {
			$ts = strtotime($date);
			if ( date('Y', $ts) > 1970 ) return $ts;
		}
		
		return false;
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
	public function get_duration_in_days( $date_1, $date_2 ) {
		$t1 = $date_1 ? strtotime($date_1) : false;
		$t2 = $date_2 ? strtotime($date_2) : false;
		
		if ( $t1 && $t2 ) {
			return (int) ceil(abs($t2 - $t1) / DAY_IN_SECONDS);
		}
		
		return false;
	}
	
	/**
	 * Sync an itinerary with the given sheet ID
	 *
	 * @param int $post_id
	 * @param int $sheet_id
	 *
	 * @return void
	 */
	public function display_sync_results_page( $post_id, $sheet_id ) {
		
		// API request to get columns, stored in $this->column_NAME
		$this->load_columns( $sheet_id );
		
		// API request to get all rows, stored in $this->rows
		$this->load_rows( $sheet_id );
		
		// Get structured data from retrieved rows
		$client = $this->get_client();
		
		$hotels = $this->get_hotels();
		
		$dates = $this->get_dates( $hotels );
		
		$hikes = $this->get_hikes();
		
		$fields = new Class_Sync_Itinerary_Fields( $post_id, $client, $hotels, $dates, $hikes );
		
		$compare = new Class_Compare_Field_Values( $post_id, $fields );
		
		$debug_info = compact( 'client', 'hotels', 'dates', 'hikes' );
		
		$compare->display_form($debug_info );
		
	}
	
	/**
	 * Save data from a completed sync form for this itinerary
	 *
	 * @param $post_id
	 * @param $values
	 * @param $fields_to_sync
	 *
	 * @return void
	 */
	public function save_sync_item_data( $post_id, $values, $fields_to_sync ) {
		$f = new Class_Sync_Itinerary_Fields( null );
		
		// Loop through each field that might be updated
		foreach( $values as $meta_key => $value ) {
			// Check if the field was checked to be updated
			if ( ! isset($fields_to_sync[$meta_key]) ) continue;
			
			$field = $f->get_field( $meta_key );
			
			$new_value = null;
			
			switch( $field['type'] ) {
				
				// Save plain text fields directly
				case 'text':
				case 'textarea':
				case 'editor':
					$new_value = $value;
					break;
					
				// Save repeaters, preserving any rows that were not checked to be updated
				case 'repeater':
					$repeater_template = $field['repeater_row_template'] ?? false;
					$sync_fields = $fields_to_sync[$meta_key]; // array( 'column_1' => '1', ... )
					$old_value = get_field( $meta_key, $post_id );
					$new_value = $this->build_repeater_value( $value, $old_value, $sync_fields, $repeater_template );
					break;
			}
			
			// Update the field (even if blank - since the user checked the update checkbox)
			update_field( $meta_key, $new_value, $post_id );
			
		}
		
		// Update the last sync date
		update_post_meta( $post_id, 'smartsheet_last_sync', current_time('Y-m-d H:i:s') );
		
	}
	
	/**
	 * Creates an array when saving a repeater, containing items that were selected during the sync
	 *
	 * @param array $new_value
	 * @param array $old_value
	 * @param array $sync_fields
	 * @param array $repeater_template
	 *
	 * @return array
	 */
	public function build_repeater_value( $new_value, $old_value, $sync_fields, $repeater_template ) {
		$final_items = array();
		
		// Note that $new_value may have NULL values for fields that do not sync
		// If a field is not present in $sync_fields, keep the $old_value for that field
		
		if ( ! is_array($new_value) ) $new_value = array();
		if ( ! is_array($old_value) ) $old_value = array();
		
		$rows = max( count($new_value), count($old_value) );
		
		for ( $i = 0; $i < $rows; $i++ ) {
			$row = array();
			$sync_row_fields = $sync_fields[$i] ?? false;
			
			foreach( $repeater_template as $col_key => $default_value ) {
				if ( isset($sync_row_fields[$col_key]) ) {
					// Sync this field - use the new value (even if blank)
					$v = $new_value[$i][$col_key] ?? false;
				}else{
					// Keep the old value
					$v = $old_value[$i][$col_key] ?? $default_value;
				}
			
				$row[$col_key] = $v;
			}
			
			// Skip completely blank rows
			if ( ! ah_is_array_recursively_empty($row) ) {
				$final_items[] = $row;
			}
		}
		
		return $final_items;
	}
	
	/**
	 * Return information about the client from the first row in the Task column.
	 *
	 * @return array {
	 *     @type string $name     Name: (value)
	 *     @type string $email    Email: (value)
	 *     @type string $mobile   Mobile: (value) [or] Phone: (value)
	 * }
	 */
	public function get_client() {
		$row = reset( $this->rows );
		
		$data = $this->get_cell_value( $row, $this->column_task['id'] );
		
		// Name: Rich & Rachel Buchanan
		// Email:
		// Mobile:
		// Dietary: none
		// Medical: none
		// Travelers: 2
		// Room: 1 Db
		// Notes:
		
		$client = array(
			'name' => '',
			'email' => '',
			'mobile' => '',
		);
		
		if ( preg_match( '/^Name: (.+)$/m', $data, $matches ) ) {
			$client['name'] = $matches[1];
		}
		
		if ( preg_match( '/^Email: (.+)$/m', $data, $matches ) ) {
			$client['email'] = $matches[1];
		}
		
		if ( preg_match( '/^(Mobile|Phone): (.+)$/m', $data, $matches ) ) {
			$client['mobile'] = $matches[2];
		}
		
		return $client;
	}
	
	/**
	 * Return an array of hotels for this itinerary
	 *
	 * @return array[] {
	 *     ["room"]=> "1 Db"
	 *     ["meal"]=> "HB"
	 *     ["location"]=> "Wengen - CH"
	 *     ["hotel"]=> "Schonegg - Wengen"
	 *     ["arrival"]=> "2023-08-19"
	 *     ["departure"]=> "2023-08-20"
	 *     ["arrival_ts"]=> int(1692403200)
	 *     ["departure_ts"]=> int(1692489600)
	 *     ["duration"]=> float(1)
	 * }
	 */
	public function get_hotels() {
		$hotels = array();
		
		// Loop through hotel rows to get their information:
		// rooms: 1Db
		// meals: HB
		// arrival: 08/17/2023
		// departure: 08/23/2023
		
		// And calculate the following:
		// days: (departure - arrival)
		
		foreach( $this->rows_hotels as $i => $row ) {
			$hotel = array(
				'hotel_name'     => $this->get_cell_value( $row, $this->column_hotel['id'] ),
				'hotel_id'       => null,
				
				'village_name'   => $this->get_cell_value( $row, $this->column_location['id'] ),
				'village_id'     => null,
				
				'room'           => $this->get_cell_value( $row, $this->column_room['id'] ),
				'meal'           => $this->get_cell_value( $row, $this->column_meal['id'] ),
				'luggage'        => $this->get_cell_value( $row, $this->column_luggage['id'] ),
				'region'         => $this->get_cell_value( $row, $this->column_region['id'] ),
				
				'arrival_date'   => $this->get_cell_value( $row, $this->column_arrival['id'] ),
				'departure_date' => $this->get_cell_value( $row, $this->column_departure['id'] ),
				'arrival_ts'     => null,
				'departure_ts'   => null,
				
				'duration'       => null,
			);
			
			// Hotel and location are required or else the row will be skipped
			if ( empty($hotel['hotel_name']) ) continue;
			if ( empty($hotel['village_name']) ) continue;
			
			// Look up the village and hotel
			// ['hotel_name'] = Schwarzwaldalp - Schwarzwaldalp
			// ['village_name'] = Schwarzwaldalp - CH
			// Note that the location is the Village Smartsheet ID, but the hotel is just the name.
			$hotel['village_id'] = AH_Smartsheet_Sync_Hotels_And_Villages()->get_village_by_smartsheet_id( $hotel['village_name'] );
			$hotel['hotel_id'] = AH_Smartsheet_Sync_Hotels_And_Villages()->get_hotel_by_smartsheet_name_and_village( $hotel['hotel_name'], $hotel['village_id'] );
			
			// Calculate the dates
			$hotel['arrival_ts'] = $hotel['arrival_date'] ? strtotime($hotel['arrival_date']) : false;
			$hotel['departure_ts'] = $hotel['departure_date'] ? strtotime($hotel['departure_date']) : false;
			$hotel['duration'] = ah_get_duration_in_days( $hotel['arrival_date'], $hotel['departure_date'] );
			
			$hotels[] = $hotel;
		}
		
		return $hotels;
	}
	
	/**
	 * Get the start and end dates as well as the duration in days, from the provided hotels.
	 *
	 * @param $hotels
	 *
	 * @return array {
	 *     @type string|false $arrival_date
	 *     @type string|false $departure_date
	 *     @type int|false    $duration
	 * }
	 */
	public function get_dates( $hotels ) {
		$start = $this->get_hotel_date( $hotels, true );
		$end = $this->get_hotel_date( $hotels, false );
		$duration = ah_get_duration_in_days( $start, $end );
		
		return array(
			'arrival_date' => $start,
			'departure_date' => $end,
			'duration' => $duration,
		);
	}
	
	/**
	 * Get the arrival date such as "2023-08-17"
	 *
	 * @param array  $hotels
	 * @param bool   $arrival  True for arrival date (default). False for departure date.
	 *
	 * @return string|false    YYYY-MM-DD formatted date, or false if not available
	 */
	public function get_hotel_date( $hotels, $arrival ) {
		if ( $arrival ) {
			$hotel = reset($hotels); // first item
		}else{
			$hotel = end($hotels); // last item
		}
		
		$ts = $hotel[ $arrival ? 'arrival_ts' : 'departure_ts' ] ?? false;
		
		return $ts ? date( 'Y-m-d', $ts ) : false;
	}
	
	/**
	 * Returns a list of hike IDs and URLs from the provided rows.
	 *
	 * @return array {
	 *     @type string $hike_name  Schwarzwaldalp to Grindelwald
	 *     @type string $url        https://www.outdooractive.com/en/r/64809261
	 * }
	 */
	public function get_hikes() {
		$hikes = array();
		
		foreach( $this->rows_hikes as $i => $row ) {
			// $hike_name = "Schwarzwaldalp to Grindelwald"
			$hike_name = $this->get_cell_value( $row, $this->column_hike['id'] );
			if ( ! $hike_name ) continue;
			
			// $url = "https://www.outdooractive.com/en/r/64809261"
			$url = $this->get_cell_value( $row, $this->column_outdooractive['id'] );
			if ( ! $url ) continue;
			
			// $region = "BO"
			$region = $this->get_cell_value( $row, $this->column_region['id'] );
			
			// Look up the hike post from WordPress that matches the hike name and region
			// $hike_id = AH_Hike()->get_hike_by_name_and_region( $hike_name, $region );
			$hike_id = AH_Smartsheet_Sync_Hikes()->get_hike_by_smartsheet_id( $hike_name );
			
			$hikes[] = array(
				'hike_name' => $hike_name,
				'hike_id' => $hike_id,
				'url' => $url,
				'region' => $region,
			);
		}
		
		return $hikes;
	}
	
	/** Add a sync warning */
	public function add_warning( $message, $data = null ) {
		AH_Smartsheet_Warnings()->add_warning( $message, $data );
	}
	
}