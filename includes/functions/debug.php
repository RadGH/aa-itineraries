<?php

// Version 1.6

// Get user IP from cloudflare or remote_addr
if ( ! function_exists('aa_get_ip_address') ) {
	function aa_get_ip_address() {
		$visitor_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
		$visitor_ip = explode(', ', $visitor_ip); // sometimes stored as two ips: "1.2.3.4, 5.6.7.8"
		return $visitor_ip[0];
	}
}

// List specific developers by IP address
if ( ! function_exists('aa_get_developer_ip_addresses') ) {
	function aa_get_developer_ip_addresses() {
		return array(
			'47.224.137.47',  // Radley home pc
			'192.241.231.69', // Radley vpn
		);
	}
}

// Return true if the user is a developer based on ip address
if ( ! function_exists('aa_is_developer') ) {
	function aa_is_developer() {
		// Allow disabling developer mode with the URL by adding ?nodev
		if ( isset($_GET['nodev']) ) return false;
		
		// Check if user IP address in the list of developer IPs
		$user_ip = aa_get_ip_address();
		$developer_ips = aa_get_developer_ip_addresses();
		
		return in_array( $user_ip, $developer_ips );
	}
}

// https://alpinehikerdev.wpengine.com/?amideveloper
if ( ! function_exists('aa_developer_check') ) {
	function aa_developer_check() {
		$html = '';
		
		// do not use pre_dump below, this should work for non-developers too
		if ( aa_is_developer() ) {
			$html .= '<p>✔ You are a developer</p>';
		}else{
			$html .= '<p>❌ You are NOT a developer</p>';
		}
		
		$html .= '<p>Your IP address is: ' . esc_html( aa_get_ip_address() ) . '</p>';
		
		wp_die($html, 'Developer Status');
		
		exit;
	}
	if ( isset($_GET['amideveloper']) ) add_action( 'init', 'aa_developer_check' );
}

/**
 * Get a backtrace as an array.
 * Each item contains file, line, function, args, and data.
 * The "data" property is an array of all other args included in the backtrace, however objects are replaced with their class name.
 */
if ( ! function_exists( 'aa_get_backtrace' ) ) {
	function aa_get_backtrace( $limit = 0 ) {
		
		$formatted_backtrace = array();
		
		// note: it is important we do not include DEBUG_BACKTRACE_PROVIDE_OBJECT or else this can cause infinite loops.
		$backtraces = debug_backtrace( 0, $limit );
		
		// Discard the calling of this function
		array_shift( $backtraces );
		
		$is_args_empty = function( $args ) {
			if ( count($args) < 1 ) return true;
			if ( count($args) > 2 ) return false;
			while ( is_array($args) ) $args = reset($args);
			return empty($args);
		};
		
		foreach( $backtraces as $i => $bt ) {
			$file = $bt['file'] ?? null;
			$line = $bt['line'] ?? null;
			$function = $bt['function'] ?? null;
			$args = $bt['args'] ?? null;
			$class = $bt['class'] ?? null;
			$type = $bt['type'] ?? null;
			
			// All other args stored in here
			// To prevent an issue with infinite loops, objects store their class name instead of the object itself
			$data = array();
			
			foreach( $bt as $b => $t ) {
				if ( ! in_array( $b, array( 'file', 'line', 'function', 'args', 'class', 'type' ) ) ) {
					$data[$b] = $t;
				}
			}
			
			// Format the function call for display
			$fn = '';
			
			if ( $class ) {
				$fn .= $class . $type . $function;
			}else{
				$fn .= $function;
			}
			
			$fn .= '(';
			
			// Show args, unless only one arg that is empty
			if ( ! call_user_func( $is_args_empty, $args ) ) {
				$fn .= ' ';
				foreach( $args as $a ) {
					$fn .= esc_html(json_encode($a)) . ', ';
				}
				$fn = substr($fn, 0, -2 );
				$fn .= ' ';
			}
			
			$fn .= ')';
			
			$item = array(
				'function_formatted' => $fn,
				'file' => $file,
				'line' => $line,
				'function' => $function,
				'args' => $args,
				'class' => $class,
				'type' => $type,
				'data' => $data,
			);
			
			$formatted_backtrace[] = $item;
		}
		
		return $formatted_backtrace;
	}
}

if ( ! function_exists( 'aa_die' ) ) {
	function aa_die( $message, $data = array(), $title = 'Error' ) {
		$backtraces = aa_get_backtrace( 10 );
		
		// Discard the calling of this function
		array_shift( $backtraces );
		
		// Get the function that called this one
		$caller = array_shift( $backtraces );
		
		// Get the path, relative to wp root
		$function = $caller['function_formatted'];
		$file = str_replace( ABSPATH, '/', $caller['file']);
		$line = $caller['line'];
		
		$html = wpautop($message);
		
		// Show extended information to developers
		if ( aa_is_developer() ) {
			
			$html .= '<hr>';
			$html .= '<pre class="pre-die-debug" style="font-size: 12px;">';
			$html .= "\n\n<strong>Caller:</strong>\n";
			$html .= $function . "\n" . $file . ':' . $line;
			
			if ( !empty($data) ) {
				$html .= "\n\n<strong>Data:</strong>\n";
				$html .= esc_html(print_r( $data, true ));
			}
			
			// query monitor shows a backtrace too
			if ( ! is_plugin_active( 'query-monitor/query-monitor.php' ) ) {
				if ( !empty($backtraces) ) {
					$html .= "\n\n<strong>Backtrace:</strong>\n";
					$html .= esc_html(print_r( $backtraces, true ));
				}
			}
			
			$html .= '</pre>';
			
		}
		
		wp_die( $html, $title );
		exit;
	}
}

// Non-developers register blank versions of our debugging functions.
if ( ! aa_is_developer() ) {
	
	if ( ! function_exists( 'pre_dump' ) ) {
		function pre_dump( ...$args ) {}
	}
	
	if ( ! function_exists( 'pre_dump_get' ) ) {
		function pre_dump_get( ...$args ) {}
	}
	
	if ( ! function_exists( 'pre_dump_table' ) ) {
		function pre_dump_table( $array ) {}
	}
	
	return;
	
}

/**
 * For debugging
 *
 * @param ...$args
 *
 * @return void
 */
if ( ! function_exists( 'pre_dump' ) ) {
	function pre_dump( ...$args ) {
		$margin = is_admin() ? 'margin: 0 0 0 180px;' : 'margin: 0;';
		echo '<div style="font-family: \'Segoe UI\', Roboto, \'Helvetica Neue\', sans-serif;">';
		echo '<pre style="overflow: auto; font-family: \'Courier New\', monospace; background: #fff; color: #000; font-size: 14px; line-height: 1.35; position: relative; z-index: 10; clear: both; width: fit-content; '. $margin .' padding: 15px; max-width: 65vw;">';
		
		// Args are always an array. If its one item, we don't need the array.
		if ( isset($args[0]) && is_array($args[0]) && count($args) === 1 ) {
			$args = reset($args);
		}
		
		// If using compact() you need it twice
		if ( isset($args[0]) && is_array($args[0]) && count($args) === 1 ) {
			$args = reset($args);
		}
		
		ob_start(); var_dump( $args );
		echo esc_html( ob_get_clean() );
		
		echo '</pre>';
		echo '</div>';
	}
}

if ( ! function_exists( 'pre_dump_get' ) ) {
	function pre_dump_get( $array ) {
		ob_start();
		echo "\n";
		pre_dump( $array );
		echo "\n";
		return ob_get_clean();
	}
}

if ( ! function_exists( 'pre_dump_table' ) ) {
	function pre_dump_table( $array ) {
		$margin = is_admin() ? 'margin: 0 0 0 180px;' : 'margin: 0;';
		echo '<div style="font-family: \'Segoe UI\', Roboto, \'Helvetica Neue\', sans-serif;">';
		echo '<div style="overflow: auto; font-family: \'Courier New\', monospace; background: #fff; color: #000; font-size: 14px; line-height: 1.35; position: relative; z-index: 10; clear: both; width: fit-content; '. $margin .' padding: 15px; max-width: 65vw;">';
		
		echo '<table><tbody>';
		
		// header row
		foreach( $array as $item ) {
			echo '<tr>';
			foreach( $item as $k => $v ) {
				echo '<th>', esc_html($k), '</th>';
			}
			echo '</tr>';
			break;
		}
		
		// rows
		foreach( $array as $item ) {
			echo '<tr>';
			
			foreach( $item as $k => $v ) {
				echo '<td>', esc_html($v), '</td>';
			}
			
			echo '</tr>';
		}
		
		echo '</tbody></table>';
		
		echo '</div>';
		echo '</div>';
	}
}



// https://alpinehikerdev.wpengine.com/?rad_20234821_34819
function rad_20234821_34819() {
	// settings
	$api_key = 'fqqgSHk6vetds8djU915DIa5aRlHzHrmoAu31';
	$auth_header = 'Bearer ' . $api_key;
	
	// create api instance
	$api = new RS_API();
	
	// enable debug mode
	$api->set_debug_mode( true );
	
	// apply settings
	$api->set_authorization_header( 'Bearer ' . $api_key );
	
	// perform a request to get all sheets
	$url = 'https://api.smartsheet.com/2.0/sheets';
	$result = $api->request( $url );
	
	// get the result
	$body = $api->get_response_body();
	
	// display results
	?>
<table>
	<tbody>
	<tr>
		<th>Page Number (pageNumber)</th>
		<td><?php echo $body['pageNumber'] ?? ''; ?></td>
	</tr>
	<tr>
		<th>Page Size (pageSize)</th>
		<td><?php echo $body['pageSize'] ?? ''; ?></td>
	</tr>
	<tr>
		<th>Total Pages (totalPages)</th>
		<td><?php echo $body['totalPages'] ?? ''; ?></td>
	</tr>
	<tr>
		<th>Total Sheets (totalCount)</th>
		<td><?php echo $body['totalCount'] ?? ''; ?></td>
	</tr>
	</tbody>
</table>

<p><strong>Results (data):</strong></p>
<table>
	<thead>
	<tr>
		<th>id</th>
		<th>name</th>
		<th>accessLevel</th>
		<th>permalink</th>
		<th>createdAt</th>
		<th>modifiedAt</th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach( $body['data'] as $row ) {
		?>
		<tr>
			<td><?php echo $row['id']; ?></td>
			<td><?php echo $row['name']; ?></td>
			<td><?php echo $row['accessLevel']; ?></td>
			<td><?php echo $row['permalink']; ?></td>
			<td><?php echo $row['createdAt']; ?></td>
			<td><?php echo $row['modifiedAt']; ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
<?php
	
	pre_dump(compact('api_key', 'auth_header', 'url', 'result', 'body', 'api'));
	
	exit;
}
if ( isset($_GET['rad_20234821_34819']) ) add_action( 'init', 'rad_20234821_34819' );


// https://alpinehikerdev.wpengine.com/?rad_2023222_226
function rad_2023222_226() {
	$invoice_id = 6072;
	
	$status = AH_Plugin()->Invoice->get_invoice_status( $invoice_id );
	$processing_start_date = get_post_meta( $invoice_id, 'processing_start_date', true );
	
	echo '<pre>';
	var_dump(compact( 'invoice_id', 'status', 'processing_start_date' ));
	echo '</pre>';
	
	pre_dump( get_post_meta( $invoice_id ) );
	
	exit;
}
if ( isset($_GET['rad_2023222_226']) ) add_action( 'init', 'rad_2023222_226' );

// https://alpinehikerdev.wpengine.com/?rad_2023124_12142
function rad_2023124_12142() {
	$entry_id = 4312;
	$form_id = 9;
	
	$entry = GFAPI::get_entry( $entry_id );
	
	$post_id = AH_Plugin()->Invoice->get_invoice_id_from_entry_id( $entry );
	$entry_2 = AH_Plugin()->Invoice->get_entry_id_from_invoice_id( $post_id );
	
	$ah_payment_status = gform_get_meta( $entry_id, 'ah_payment_status' );
	
	echo '<pre>';
	var_dump(compact( 'entry', 'post_id', 'entry_2', 'ah_payment_status' ));
	echo '</pre>';
	
	exit;
}
if ( isset($_GET['rad_2023124_12142']) ) add_action( 'init', 'rad_2023124_12142' );

// https://alpinehikerdev.wpengine.com/?rad_20235927_11593
function rad_20235927_11593() {
	$invoice_id = 6118;
	$post_id = $invoice_id;
	
	$brandi_user_id = 21; // brandi@brandibernoskie.com
	$alchemy_user_id = 2; // alchemyandaim
	
	AH_Plugin()->Invoice->set_owner( $invoice_id, $alchemy_user_id );
	
	$new_owner = AH_Plugin()->Invoice->get_owner_user_id( $invoice_id );
	
	/*
	delete_post_meta( $invoice_id, 'reminder_1_enabled' );
	delete_post_meta( $invoice_id, 'reminder_1_sent' );
	delete_post_meta( $invoice_id, 'reminder_1_date' );
	delete_post_meta( $invoice_id, 'reminder_1_notes' );
	delete_post_meta( $invoice_id, 'reminder_2_enabled' );
	delete_post_meta( $invoice_id, 'reminder_2_sent' );
	delete_post_meta( $invoice_id, 'reminder_2_date' );
	delete_post_meta( $invoice_id, 'reminder_2_notes' );
	delete_post_meta( $invoice_id, 'reminder_3_enabled' );
	delete_post_meta( $invoice_id, 'reminder_3_sent' );
	delete_post_meta( $invoice_id, 'reminder_3_date' );
	delete_post_meta( $invoice_id, 'reminder_3_notes' );
	delete_post_meta( $invoice_id, 'reminder_4_enabled' );
	delete_post_meta( $invoice_id, 'reminder_4_sent' );
	delete_post_meta( $invoice_id, 'reminder_4_date' );
	delete_post_meta( $invoice_id, 'reminder_4_notes' );
	
	AH_Plugin()->Invoice->setup_reminder_notifications( $invoice_id );
	*/
	
	AH_Plugin()->Reminders->send_daily_reminders();
	
	echo '<pre>';
	var_dump(compact( 'new_owner' ));
	echo '<br><br><br>';
	var_dump(compact( 'invoice_id', 'brandi_user_id', 'alchemy_user_id' ));
	echo '</pre>';
	
	exit;
}
if ( isset($_GET['rad_20235927_11593']) ) add_action( 'init', 'rad_20235927_11593' );