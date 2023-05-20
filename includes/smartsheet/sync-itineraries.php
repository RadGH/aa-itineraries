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
	public $column_hike_list;
	public $column_outdoor_active;
	
	// Stores rows that contain data
	public $rows;
	public $rows_hotels;
	public $rows_restaurants;
	public $rows_hikes;
	public $row_arrival;   // single row
	public $row_departure; // single row
	
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
		$this->columns = $columns;
		
		// $columns[0] = !!! (Checkboxes)
		
		// Task column contains identifiers
		// Hotel1 ... Hotel9
		// Restaurant1 ... Restaurant3
		// Hike1 ... Hike10
		// Travel Details, Arrival:, Departure:
		$this->column_task = $columns[1];
		
		// $columns[2] = Task Detail/Comments
		
		// Room and Meal columns contain a code for each hotel
		// Codes are converted, refer to sync-rooms-and-meals.php
		// task | room | meal
		// Hotel1 | 1 Db | HB
		// Hotel2 | 1 Db-for-Sg | BD/B&B
		$this->column_room = $columns[3];
		$this->column_meal = $columns[4];
		
		// $columns[5] = Em (Checkboxes)
		// $columns[6] = Done (Checkboxes)
		
		// Arrival dates are used for hotels and restaurants (mm/dd/yy format)
		// Arrival and departure dates are stored where Task column is: Travel Details -> "Arrival:" and "Departure:"
		$this->column_arrival = $columns[7];
		
		// $columns[8] = Nts (numbers)
		
		// Location column stores the village/country for each hotel and restaurant (WP Village ID)
		// Schwarzwaldalp - CH
		// Grindelwald - CH
		$this->column_location = $columns[9];
		
		// Hotel column stores the hotel code (WP Hotel ID)
		$this->column_hotel = $columns[10];
		
		// Departure column is similar to Arrival column. Not used for restaurants.
		$this->column_departure = $columns[11];
		
		// $columns[12] = Luggage (checkboxes)
		// $columns[13] = Assigned To
		
		// Hike list is the name of a hike
		$this->column_hike_list = $columns[14];
		
		// $columns[15] = Due date
		// $columns[16] = Em to Client
		// $columns[17] = Due Date Override
		
		// Outdoor active links for each hike (only one link?)
		$this->column_outdoor_active = $columns[18];
		
		// Columns 19+ are removed from the result in $this->rows in $this->load_rows
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
			$row['cells'] = array_slice( $row['cells'], 0, 19 );
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
		
		// Get arrival date and departure date rows
		$this->row_arrival = $this->find_row_by_column( $task_column_id, 'Arrival:' );
		$this->row_departure = $this->find_row_by_column( $task_column_id, 'Departure:' );
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
		foreach( $cells as $cell ) {
			if ( $cell['columnId'] == $column_id ) return $cell['value'];
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
	public function sync_itinerary_with_sheet( $post_id, $sheet_id ) {
		
		// API request to get columns, stored in $this->column_NAME
		$this->load_columns( $sheet_id );
		
		// API request to get all rows, stored in $this->rows
		$this->load_rows( $sheet_id );
		
		// Retrieve the arrival date
		$arrival_date = $this->get_arrival_date();
		
		// @todo
		
		echo '<h2>TODO: Sync itinerary with smartsheet</h2>';
		echo '<p>Sheet ID: ' . $sheet_id . '</p>';
		
		echo '<p>Column B (Task):</p>';
		pre_dump($this->column_task);
		
		echo '<p>Row 2 (Hotel1):</p>';
		$r = $this->rows[1];
		unset($r['cells']);
		pre_dump($r);
		
		echo '<p>Cell B2 (Task -> Hotel1):</p>';
		pre_dump($this->rows[1]['cells'][1]);
		
		echo '<p>Arrival date:</p>';
		pre_dump($arrival_date);
		
		echo '<p>Rows found:</p>';
		$row_counts = array(
			'hotels' => count( $this->rows_hotels ),
			'restaurants' => count( $this->rows_restaurants ),
			'hikes' => count( $this->rows_hikes ),
			'arrival' => empty( $this->row_arrival ) ? 0 : 1, // single row
			'departure' => empty( $this->row_departure ) ? 0 : 1, // single row
		);
		pre_dump($row_counts);
		
		echo '<p>All columns:</p>';
		pre_dump($this->columns);
		
		echo '<p>All rows:</p>';
		pre_dump($this->rows);
		exit;
	}
	
	public function get_arrival_date() {
		// Date is stored in the column titled "Arrive"
		$column_id = $this->column_arrival['id'];
		return $this->get_cell_value( $this->row_arrival['cells'], $column_id );
	}
	
}