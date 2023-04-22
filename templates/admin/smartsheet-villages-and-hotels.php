<?php
$sheet_id = AH_Smartsheet_Hotels()->get_sheet_id();
$column_ids = AH_Smartsheet_Hotels()->get_column_ids();
$columns = AH_Smartsheet_Hotels()->columns;
$sheet_url = AH_Smartsheet_Hotels()->get_smartsheet_permalink();

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_hotels' => 1));

// -- HOTELS --

// Get hotels from a prior Smartsheet sync
$hotel_list = AH_Smartsheet_Hotels()->get_stored_hotel_list();

// Get all hotel post IDs
$all_hotel_ids = get_posts(array('post_type' => AH_Hotel()->get_post_type(), 'nopaging' => true, 'fields' => 'ids'));

// Calculate lists of hotels to display:
// 1. $valid_hotels = Rows with a valid post ID
// 2. $deleted_hotels = Rows that have a post ID which does not exist on the website
// 3. $new_hotels = Rows with no post ID specified
// 4. $unassigned_post_ids = Hotels on the website that do not have a corresponding row on the spreadsheet
$valid_hotels = array();
$deleted_hotels = array();
$new_hotels = array();
$unassigned_post_ids = $all_hotel_ids; // starts with all post ids, ids get removed if they are found in a row

// Loop through each hotel to add it to one of the above groups
if ( $hotel_list ) foreach( $hotel_list as $h ) {
	$post_id = $h['wordpress_id'];
	
	if ( $post_id ) {
		// Post ID exists, check if it is valid
		$post_exists = in_array( $post_id, $all_hotel_ids );
		$post_valid = $post_exists && AH_Hotel()->is_valid($post_id);
		
		if ( $post_exists && $post_valid ) {
			$valid_hotels[] = $h;
		}else{
			$deleted_hotels[] = $h;
		}
		
		// Remove the post ID from the list, since this is not a new hotel to be added
		$key = array_search( $post_id, $unassigned_post_ids );
		if ( $key !== false ) unset($unassigned_post_ids[$key]);
		
	}else{
		// Post ID does not exist for this one
		$new_hotels[] = $h;
	}
	
}

// -- VILLAGES --
$village_list = AH_Smartsheet_Hotels()->get_stored_village_list();

?>

<div class="wrap">
	
	<h1>Hotel Info</h1>
	
	<?php
	AH_Admin()->display_notices();
	?>
	
	<form id="post" method="post" name="post">
		
		<div id="poststuff" class="poststuff">
			<div id="post-body" class="ah-metabox-holder metabox-holder columns-2">
				
				<div id="postbox-container-1" class="ah-postbox-sidebar postbox-container">
					
					<div class="ah-postbox-sticky">
					
						<div id="submitdiv" class="postbox">
							<div class="postbox-header">
								<h2>Actions</h2>
							</div>
							
							<div class="inside">
								<div id="major-publishing-actions">
									<div id="publishing-action">
										<span class="spinner"></span>
										<input type="submit" name="publish" value="Update" class="button button-primary button-large" id="publish">
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
						
						<div class="postbox">
							<div class="postbox-header">
								<h2>Sync with Smartsheet</h2>
							</div>
							
							<div class="inside">
								<p>Updates the hotel list with current information from the Hotel master spreadsheet.</p>
								<p>* Does NOT automatically create, update, or delete hotels on this website.</p>
								<p><a href="<?php echo esc_attr($sync_url); ?>" class="button button-secondary">Run Sync</a></p>
							</div>
						</div>
						
						<div class="postbox">
							<div class="postbox-header">
								<h2>Navigation</h2>
							</div>
							
							<div class="inside">
								<ul class="ul-disc">
									<li><a href="#smartsheet-settings">Smartsheet Settings</a></li>
									<li><a href="#hotels">Hotels</a>
										<ul class="ul-disc">
											<?php
											if ( $unassigned_post_ids ) {
												echo '<li><a href="#unassigned_post_ids">Unassigned Hotels ('. count($unassigned_post_ids) .')</a></li>';
											}
											if ( $deleted_hotels ) {
												echo '<li><a href="#deleted_hotels">Deleted Hotels ('. count($deleted_hotels) .')</a></li>';
											}
											if ( $new_hotels ) {
												echo '<li><a href="#new_hotels">New Hotel Rows ('. count($new_hotels) .')</a></li>';
											}
											if ( $valid_hotels ) {
												echo '<li><a href="#valid_hotels">Valid Hotels ('. count($valid_hotels) .')</a></li>';
											}
											?>
										</ul>
									</li>
									<li><a href="#villages">Villages</a>
										<ul class="ul-disc">
											<?php
											if ( $unassigned_post_ids ) {
												echo '<li><a href="#unassigned_post_ids">Todo Village Nav ('. count($unassigned_post_ids) .')</a></li>';
											}
											?>
										</ul>
									</li>
								</ul>
							</div>
						</div>
					
					</div>
					
				</div>
				
				<div id="postbox-container-2" class="ah-postbox-main postbox-container">
					<div class="postbox">
						<div class="postbox-header">
							<h2 id="smartsheet-settings">Smartsheet Settings</h2>
						</div>
						<div class="inside">
							
							<input type="hidden" name="ah-action" value="<?php echo wp_create_nonce('save-hotel-info'); ?>">
							
							<p>For instructions on how to get the Sheet and Column IDs, refer to the <a href="admin.php?page=acf-ah-smartsheet">Smartsheet Settings</a> page.</p>
							
							<!-- Sheet ID -->
							<div class="ah-admin-field">
								<div class="ah-label">
									<label for="ah-sheet-id">Sheet ID:</label>
								</div>
								<div class="ah-field">
									<input type="text" name="ah[sheet_id]" id="ah-sheet-id" value="<?php echo esc_attr($sheet_id); ?>">
									<?php if ( $sheet_url ) { ?>
									<a href="<?php echo $sheet_url; ?>" target="_blank" class="button button-secondary">View Spreadsheet <span class="dashicons dashicons-external ah-dashicon-inline"></span></a>
									<?php } ?>
								</div>
							</div>
							
							<div class="ah-admin-field">
								<div class="ah-label">
									<label for="ah-sheet-id">Column IDs:</label>
								</div>
								
								<div class="ah-field-list">
									<?php
									/* Each column name must be defined in smartsheet-sync-hotels.php, $this->columns */
									foreach( $columns as $key => $title ) {
										$value = $column_ids[ $key ] ?? '';
										?>
										<label for="ah-hotel-col-<?php echo $key; ?>"><?php echo $title; ?>:</label>
										<div class="ah-field">
											<input type="text" name="ah[column_ids][<?php echo $key; ?>]" id="ah-hotel-col-<?php echo $key; ?>" value="<?php echo esc_attr($value); ?>">
										</div>
										<?php
									}
									?>
								</div>
								
							</div>
							
						</div>
					</div>
					
					<div class="hotel-postbox postbox">
						<div class="postbox-header">
							<h2 id="hotels">Hotels</h2>
						</div>
						
						<div class="inside">
							
							<ul class="hotel-nav ul-disc">
								<?php
								if ( $unassigned_post_ids ) {
									echo '<li><a href="#unassigned_post_ids">Unassigned Hotels ('. count($unassigned_post_ids) .')</a> <p class="description">These hotels do not have a matching row in the spreadsheet.</p></li>';
								}
								if ( $deleted_hotels ) {
									echo '<li><a href="#deleted_hotels">Deleted Hotels ('. count($deleted_hotels) .')</a> <p class="description">These rows have an invalid WordPress ID. Perhaps they were deleted?</p></li>';
								}
								if ( $new_hotels ) {
									echo '<li><a href="#new_hotels">New Hotel Rows ('. count($new_hotels) .')</a> <p class="description">These rows are not assigned to a hotel yet.</p></li>';
								}
								if ( $valid_hotels ) {
									echo '<li><a href="#valid_hotels">Valid Hotels ('. count($valid_hotels) .')</a> <p class="description">These hotels have a matching row in the spreadsheet (no action needed).</p></li>';
								}
								?>
							</ul>
							
							<!-- Unassigned Hotels (based on post_id) -->
							<?php if ( $unassigned_post_ids ) { ?>
								<hr>
								
								<h3 id="unassigned_post_ids">Unassigned Hotels (<?php echo count($unassigned_post_ids); ?>)</h3>
								
								<p class="description">These hotels do not have a matching row in the spreadsheet.</p>
								<p class="description"><strong>Suggested Action:</strong> Create a new row for the hotel using the WordPress ID that is displayed below. After editing the spreadsheet, run the sync again.</p>
								
								<table class="ah-hotel-table">
									
									<thead>
									<tr>
										<th class="col-hotel_name">Hotel Name</th>
										<th class="col-village_name">Village Name</th>
										<th class="col-wordpress_id">WordPress ID</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $unassigned_post_ids as $post_id ) {
										$hotel_name = get_field( 'hotel_name', $post_id ) ?: get_the_title($post_id);
										$village_id = get_field( 'village', $post_id, false );
										$village_name = $village_id ? get_the_title($village_id) : false;
										$wordpress_id = $post_id;
										?>
										<tr>
											<td class="col-hotel_name"><span class="cell"><?php echo esc_html($hotel_name ?: '&ndash;'); ?></span></td>
											<td class="col-village_name"><span class="cell"><?php echo esc_html($village_name ?: '&ndash;'); ?></span></td>
											
											<td class="col-wordpress_id">
												<span class="cell"><code class="code"><?php echo $wordpress_id; ?></code></span>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							<?php } ?>
							
							
							<!-- Deleted Hotels (based on spreadsheet) -->
							<?php if ( $deleted_hotels ) { ?>
								<hr>
								
								<h3 id="deleted_hotels">Deleted Hotels (<?php echo count($deleted_hotels); ?>)</h3>
								
								<p class="description">These rows have an invalid WordPress ID. Perhaps they were deleted?</p>
								<p class="description"><strong>Suggested Action:</strong> Delete the row from the spreadsheet, or change the WordPress ID, or restore the hotel from trash.</p>
								
								<table class="ah-hotel-table">
									<thead>
									<tr>
										<th class="col-hotel_name">Hotel Name</th>
										<th class="col-village_name">Village Name</th>
										<th class="col-wordpress_id">WordPress ID</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach( $deleted_hotels as $h ) {
										$hotel_name = $h['hotel_name'];
										$village_name = $h['village_name'];
										
										$wordpress_id = $h['wordpress_id'];
										$smartsheet_row_id = $h['smartsheet_row_id'];
										?>
										<tr data-smartsheet-row-id="<?php echo esc_attr($smartsheet_row_id); ?>">
											<td class="col-hotel_name"><span class="cell"><?php echo esc_html($hotel_name ?: '&ndash;'); ?></span></td>
											<td class="col-village_name"><span class="cell"><?php echo esc_html($village_name ?: '&ndash;'); ?></span></td>
											<td class="col-wordpress_id">
												<span class="cell"><code class="code"><?php echo $wordpress_id; ?></code></span>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							<?php } ?>
							
							<!-- New Hotels (based on spreadsheet) -->
							<?php if ( $new_hotels ) { ?>
								<hr>
								
								<h3 id="new_hotels">New Hotels (<?php echo count($new_hotels); ?>)</h3>
								
								<p class="description">These rows are not assigned to a hotel yet.</p>
								<p class="description"><strong>Suggested Action:</strong> Use the button below to automatically create the hotel. The spreadsheet will automatically update with the new WordPress ID.</p>
								
								<table class="ah-hotel-table">
									<thead>
									<tr>
										<th class="col-hotel_name">Hotel Name</th>
										<th class="col-village_name">Village Name</th>
										<th class="col-wordpress_id">WordPress ID</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach( $new_hotels as $h ) {
										$hotel_name = $h['hotel_name'];
										$village_name = $h['village_name'];
										
										$wordpress_id = $h['wordpress_id'];
										$smartsheet_row_id = $h['smartsheet_row_id'];
										
										$create_url = add_query_arg(array('ah_insert_smartsheet_hotel' => $smartsheet_row_id));
										?>
										<tr data-smartsheet-row-id="<?php echo esc_attr($smartsheet_row_id); ?>">
											<td class="col-hotel_name"><span class="cell"><?php echo esc_html($hotel_name ?: '&ndash;'); ?></span></td>
											<td class="col-village_name"><span class="cell"><?php echo esc_html($village_name ?: '&ndash;'); ?></span></td>
											
											<td class="col-wordpress_id">
												<span class="cell"><?php
												echo sprintf(
													'<a href="%s" class="button button-primary button-small add-hotel-button" target="_blank">Add New Hotel</a>',
													esc_attr($create_url)
												);
												?></span>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							<?php } ?>
							
							<!-- Valid Hotels (based on spreadsheet) -->
							<?php if ( $valid_hotels ) { ?>
								<hr>
							
								<h3 id="valid_hotels">Valid Hotels (<?php echo count($valid_hotels); ?>)</h3>
								
								<p class="description">These hotels have a matching row in the spreadsheet (no action needed).</p>
								
								<table class="ah-hotel-table">
									<thead>
									<tr>
										<th class="col-hotel_name">Hotel Name</th>
										<th class="col-village_name">Village Name</th>
										<th class="col-wordpress_id">WordPress ID</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach( $valid_hotels as $h ) {
										$hotel_name = $h['hotel_name'];
										$village_name = $h['village_name'];
										
										$wordpress_id = $h['wordpress_id'];
										$smartsheet_row_id = $h['smartsheet_row_id'];
										
										$edit_url = get_edit_post_link($wordpress_id);
										?>
										<tr data-smartsheet-row-id="<?php echo esc_attr($smartsheet_row_id); ?>">
											<td class="col-hotel_name"><span class="cell"><?php echo esc_html($hotel_name ?: '&ndash;'); ?></span></td>
											<td class="col-village_name"><span class="cell"><?php echo esc_html($village_name ?: '&ndash;'); ?></span></td>
											
											<td class="col-wordpress_id">
												<span class="cell"><?php
												// Edit hotel
												echo sprintf(
													'<a href="%s" class="button button-secondary">%s</a>',
													esc_attr($edit_url),
													esc_attr($wordpress_id)
												);
												?></span>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							<?php } ?>
							
							
							
						</div>
					</div>
					
					
					<div class="village-postbox postbox">
						<div class="postbox-header">
							<h2 id="villages">Villages</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $village_list ) { ?>
								
								<p><em>No villages found</em></p>
								
							<?php }else{ ?>
								
								<h3 id="village_list">Villages (<?php echo count($village_list); ?>)</h3>
								
								<p class="description">The villages listed below are assigned to one or more hotels in the spreadsheet.</p>
								
								<table class="ah-village-table">
									
									<thead>
									<tr>
										<th class="col-village_name">Village Name</th>
										<th class="col-wordpress_id">WordPress ID</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									sort($village_list);
									
									foreach( $village_list as $village_name ) {
										$wordpress_id = null;
										?>
										<tr>
											<td class="col-village_name"><span class="cell"><?php echo esc_html($village_name ?: '&ndash;'); ?></span></td>
											
											<td class="col-wordpress_id">
												<span class="cell"><code class="code"><?php echo $wordpress_id; ?></code></span>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
								
							<?php } ?>
						
						</div>
					</div>
					
					
				</div>
				
			</div>
			
			<div class="clear"></div>
		</div>
	
	</form>
	
</div>

<script type="text/javascript">
// Clicking "Add hotel" buttons makes the button turn gray
document.addEventListener('click', function(e) {
	if ( ! e.target.classList.contains('add-hotel-button') ) return;
	
	e.target.classList.remove('button-primary');
	e.target.classList.add('button-secondary');
	e.target.innerHTML = 'Hotel Added';
});
</script>