<?php

/**
 * Provides the ability to list all smartsheets and display them in a dropdown, allowing you to
 * search for and choose a sheet. This is used to connect Itineraries to their spreadsheet.
 */
class Class_AH_Smartsheet_Sheet_Select {
	
	public function __construct() {
		
		// Get sheets by search queries
		// AJAX [1/2]: Use select2 dropdown
		add_action( 'wp_ajax_ah_search_sheets', array( $this, 'ajax_search_sheets' ) );
		
		// AJAX [2/2]: Seems to be faster to use a hook at init instead. We really want speed for this.
		if ( isset($_POST['action']) && $_POST['action'] == 'ah_search_sheets' ) {
			add_action( 'init', array( $this, 'ajax_search_sheets' ) );
		}
		
		// Test URL: https://alpinehikerdev.wpengine.com/?ah_test_sheet_select_ajax&search=test
		if ( isset($_GET['ah_test_sheet_select_ajax']) ) {
			add_action( 'init', array( $this, 'ajax_search_sheets' ) );
		}
		
	}
	
	/**
	 * Handles ajax request for Select2 to retrieve a list of sheets.
	 *
	 * This returns a stored copy of sheets from Smartsheet - it does not perform an API request.
	 * If sheets are missing you probably need to run a sync from: Alpine Hikers Settings -> Sync Sheets
	 *
	 * Test URL:
	 * https://alpinehikerdev.wpengine.com/?ah_test_sheet_select_ajax&search=test
	 *
	 * JSON body structured as:
	 * array {
	 *     @type array[] $results     {
	 *         @type int    $id             5187134723254148
	 *         @type string $name           "*2023 (Forrest) Best TMB-0615"
	 *
	 *         [NOT INCLUDED, but can be added below if needed]
	 *         @type string $accessLevel    "ADMIN"
	 *         @type string $permalink      "https://app.smartsheet.com/sheets/9Jhp3828pWqfC5mfrfPFhJhrpJRJjj78x2xc3pr1"
	 *         @type string $createdAt      "2022-08-15T14:24:42Z"
	 *         @type string $modifiedAt     "2023-05-02T20:17:45Z"
	 *     }
	 *
	 *     @type array  $pagination  {
	 *         @type bool $more             true
	 *     }
	 * }
	 */
	public function ajax_search_sheets() {
		
		// Configurable
		$results_per_page = 25;
		
		// Data from select2
		$search_term = isset($_REQUEST['search']) ? stripslashes($_REQUEST['search']) : '';
		$page = isset($_REQUEST['page']) ? stripslashes($_REQUEST['page']) : '';
		
		// Get all sheets
		$sheets = AH_Smartsheet_Sync_Sheets()->get_stored_sheet_list();
		
		// Apply search filter
		$sheets = ah_search_array_items( $sheets, $search_term, 'name' );
		
		// Format results for select2
		$output = array(
			'results' => array(),
			'pagination' => array(
				// Check if there are more pages to show when you scroll to the end of the results
				'more' => count($sheets) >= $results_per_page,
			),
		);
		
		// Add each sheet that matched, limited to $results_per_page
		if ( $sheets ) foreach( $sheets as $sheet ) {
			$output['results'][] = array(
				'id' => $sheet['id'],
				'text' => $sheet['name'],
			);
			
			if ( count( $output['results'] ) >= $results_per_page ) {
				break;
			}
		}
		
		// Output as json
		echo json_encode( $output );
		exit;
	}
	
	public function get_select_html( $args ) {
		// Include select2
		// These assets are registered in enqueue.php but are not enqueued automatically.
		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'select2' );
		
		// Format args
		$args = shortcode_atts(array(
			// HTML attributes
			'id' => '',
			'class' => '',
			'name' => '',
			'placeholder' => '&ndash; Select Sheet &ndash;',
			
			// Selected value
			'value' => '',
			'label' => null, // text for current value (defaults to value if not provided)
			
			// Select2 options
			'allow_clear' => true, // Add button to clear the result ("Allow null" in ACF)
		), $args);
		
		// Apply defaults
		if ( $args['label'] === null ) $args['label'] = $args['value'];
		
		// Always add the class "ah-sheet-select", used in js
		$args['class'] .= ' ah-sheet-select';
		
		// Format html attributes
		$select_atts = '';
		$select_atts .= 'id="'.           esc_attr($args['id'])           .'" ';
		$select_atts .= 'class="'.        esc_attr($args['class'])        .'" ';
		$select_atts .= 'name="'.         esc_attr($args['name'])         .'" ';
		$select_atts .= 'placeholder="'.  esc_attr($args['placeholder'])  .'" ';
		
		// Select2 options as data attributes
		$select_atts .= 'data-allow-clear="'.  ($args['allow_clear'] ? 1 : 0)  .'" ';
		
		// Add element
		$html = '<select '. $select_atts .'>';
		
		// Add default option
		$html .= '<option value="'. esc_attr($args['value']) .'">'. esc_html($args['label']) .'</option>';
		
		$html .= '</select>';
		
		return $html;
	}
	
	// https://alpinehikerdev.wpengine.com/?ah_test_sheet_select
	public function test_sheet_select() {
		?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width">
	<title>Document</title>
	
	<script
		src="https://code.jquery.com/jquery-3.7.0.min.js"
		integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g="
		crossorigin="anonymous"></script>
	
	<link rel="stylesheet" href="<?php echo AH_URL; ?>/assets/select2/css/select2.min.css">
	<script src="<?php echo AH_URL; ?>/assets/select2/js/select2.js"></script>
	
	<script src="<?php echo AH_URL; ?>/assets/api.js"></script>
	<link rel="stylesheet" href="<?php echo AH_URL; ?>/assets/global.css">
	<script src="<?php echo AH_URL; ?>/assets/global.js"></script>
</head>
<body>

<?php
echo $this->get_select_html(array(
	'id' => 'ah-sheet-select-2',
));
?>

<select id="ah-sheet-select">
	<option value="">&ndash; Select Spreadsheet &ndash;</option>
</select>

<script>
	/*
let $select = jQuery('.ah-sheet-select');

let select2_args = {
	ajax: {
		url: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
		dataType: 'json',
		
		// Use POST, and add ajax action
		// ?search=[term]&page=[page]&action=ah_search_sheets
		method: 'POST',
		data: function (params) {
			return {
				search: params.term || '',
				page: params.page || 1,
				action: 'ah_search_sheets',
			};
		},
		
		// Rate limit to prevent spamming the server
		delay: 250,
	}
};

$select.select2( select2_args );
*/
</script>

<pre><?php
	//var_dump(compact( 'sheets' ));
?></pre>


</body>
</html>
		<?php
		exit;
	}
	
}