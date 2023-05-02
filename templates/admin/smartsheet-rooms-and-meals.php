<?php
// Base URL for this page
$base_url = admin_url('admin.php?page=ah-smartsheet-rooms-and-meals');

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_rooms_and_meals' => 1), $base_url);

// Get sheet settings
$sheet_id = AH_Smartsheet_Sync_Rooms_And_Meals()->get_sheet_id();
$column_ids = AH_Smartsheet_Sync_Rooms_And_Meals()->get_column_ids();
$sheet_url = AH_Smartsheet_Sync_Rooms_And_Meals()->get_smartsheet_permalink();
$columns = AH_Smartsheet_Sync_Rooms_And_Meals()->columns;

// Get rooms from a prior Smartsheet sync
$room_list = AH_Smartsheet_Sync_Rooms_And_Meals()->get_stored_room_list();
$meal_list = AH_Smartsheet_Sync_Rooms_And_Meals()->get_stored_meal_list();
?>

<div class="wrap">
	
	<h1>Smartsheet - Sync Rooms and Meals</h1>
	
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
								<p>Updates the room list with current information from the Rooms and Meals master spreadsheet.</p>
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
									<li><a href="#rooms">Rooms (<?php echo count($room_list); ?>)</a></li>
									<li><a href="#meals">Meals (<?php echo count($meal_list); ?>)</a></li>
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
							
							<input type="hidden" name="ah-action" value="<?php echo wp_create_nonce('save-room-info'); ?>">
							
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
									/* Each column name must be defined in smartsheet-sync-rooms.php, $this->columns */
									foreach( $columns as $key => $title ) {
										$value = $column_ids[ $key ] ?? '';
										?>
										<label for="ah-room-col-<?php echo $key; ?>"><?php echo $title; ?>:</label>
										<div class="ah-field">
											<input type="text" name="ah[column_ids][<?php echo $key; ?>]" id="ah-room-col-<?php echo $key; ?>" value="<?php echo esc_attr($value); ?>">
										</div>
										<?php
									}
									?>
								</div>
								
							</div>
							
						</div>
					</div>
					
					
					<div class="room-postbox postbox">
						<div class="postbox-header">
							<h2 id="rooms">Rooms (<?php echo count($room_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $room_list ) { ?>
								
								<p><em>No rooms found.</em></p>
							
							<?php }else{ ?>
								
								<table class="ah-room-table">
									
									<thead>
									<tr>
										<th class="col-room_code">Room Code</th>
										<th class="col-room_name">Room Name</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $room_list as $r ) {
										$room_code = $r['room_code'];
										$room_name = $r['room_name'];
										?>
										<tr>
											<td class="col-room_code"><span class="cell"><?php echo esc_html($room_code ?: '&ndash;'); ?></span></td>
											<td class="col-room_name"><span class="cell"><?php echo esc_html($room_name ?: '&ndash;'); ?></span></td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							
							<?php } ?>
						
						</div>
					</div>
					
					
					<div class="meal-postbox postbox">
						<div class="postbox-header">
							<h2 id="meals">Meals (<?php echo count($meal_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $meal_list ) { ?>
								
								<p class="description"><em>No meals found.</em></p>
							
							<?php }else{ ?>
								
								<table class="ah-room-table">
									
									<thead>
									<tr>
										<th class="col-meal_code">Meal Code</th>
										<th class="col-meal_name_short">Meal Name (Short)</th>
										<th class="col-meal_name_full">Meal Name (Full)</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $meal_list as $m ) {
										$meal_code = $m['meal_code'];
										$meal_name_short = $m['meal_name_short'];
										$meal_name_full = $m['meal_name_full'];
										?>
										<tr>
											<td class="col-meal_code"><span class="cell"><?php echo esc_html($meal_code ?: '&ndash;'); ?></span></td>
											<td class="col-meal_name_short"><span class="cell"><?php echo esc_html($meal_name_short ?: '&ndash;'); ?></span></td>
											<td class="col-meal_name_full"><span class="cell"><?php echo esc_html($meal_name_full ?: '&ndash;'); ?></span></td>
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