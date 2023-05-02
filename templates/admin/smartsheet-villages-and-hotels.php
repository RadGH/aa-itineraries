<?php
// Base URL for this page
$base_url = admin_url('admin.php?page=ah-smartsheet-hotels-and-villages');

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_hotels_and_villages' => 1), $base_url);

// Get sheet settings
$sheet_id = AH_Smartsheet_Sync_Hotels_And_Villages()->get_sheet_id();
$column_ids = AH_Smartsheet_Sync_Hotels_And_Villages()->get_column_ids();
$sheet_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_smartsheet_permalink();
$columns = AH_Smartsheet_Sync_Hotels_And_Villages()->columns;

// Preload the list of hotel and village post IDs along with their smartsheet name
$hotel_posts = AH_Smartsheet_Sync_Hotels_And_Villages()->preload_hotel_post_list();
$village_posts = AH_Smartsheet_Sync_Hotels_And_Villages()->preload_village_post_list();

// Get hotels from a prior Smartsheet sync
$hotel_list = AH_Smartsheet_Sync_Hotels_And_Villages()->get_stored_hotel_list();
$village_list = AH_Smartsheet_Sync_Hotels_And_Villages()->get_stored_village_list();

// Find which hotels and villages do not match a row in the spreadsheet
$unassigned_hotels = AH_Smartsheet_Sync()->get_unassigned_post_list( $hotel_posts, $hotel_list );
$unassigned_villages = AH_Smartsheet_Sync()->get_unassigned_post_list( $village_posts, $village_list );
?>

<div class="wrap">
	
	<h1>Smartsheet - Sync Villages and Hotels</h1>
	
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
									<li><a href="#villages">Villages (<?php echo count($village_list); ?>)</a></li>
									<li><a href="#hotels">Hotels (<?php echo count($hotel_list); ?>)</a></li>
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
					
					
					<div class="village-postbox postbox">
						<div class="postbox-header">
							<h2 id="villages">Villages (<?php echo count($village_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( $unassigned_villages ) { ?>
								<p class="description">The villages below were not found in the spreadsheet but exist on the website. You may need to enter the smartsheet name manually by editing the village.</p>
							
								<table class="ah-admin-table">
									
									<thead>
									<tr>
										<th class="col-village_name">Village Title</th>
										<th class="col-smartsheet_name">Smartsheet Name</th>
										<th class="col-actions">Actions</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $unassigned_villages as $post_id ) {
										$title = get_the_title( $post_id );
										$name = get_field( 'smartsheet_name', $post_id );
										$edit_url = get_edit_post_link( $post_id );
										?>
										<tr>
											<td class="col-village_name"><span class="cell"><?php echo esc_html( $title ); ?></span></td>
											<td class="col-smartsheet_name"><span class="cell"><?php echo esc_html( $name ) ?: '<span style="opacity: 0.5;">(none)</span>'; ?></span></td>
											
											<td class="col-actions">
												<?php
												// Edit the post
												echo sprintf(
													'<a href="%s" class="button button-small button-link" target="_blank">Edit %s #%d</a>',
													esc_attr( $edit_url ),
													esc_html( $name ),
													esc_html( $post_id )
												);
												?>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
								
							<?php } ?>
							
							<?php if ( ! $village_list ) { ?>
								
								<p class="description"><em>No villages found.</em></p>
							
							<?php }else{ ?>
								
								<p class="description">The villages listed below are assigned to one or more hotel in the spreadsheet.</p>
								
								<table class="ah-admin-table">
									
									<thead>
									<tr>
										<th class="col-village_name">Village Name</th>
										<th class="col-actions">Actions</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $village_list as $village_name ) {
										$village_id = AH_Smartsheet_Sync_Hotels_And_Villages()->get_village_id_by_name( $village_name );
										?>
										<tr>
											<td class="col-village_name"><span class="cell"><?php echo esc_html($village_name ?: '&ndash;'); ?></span></td>
											
											<td class="col-actions">
												<?php
												if ( $village_id ) {
													// Edit the post
													$edit_url = get_edit_post_link($village_id);
													echo sprintf(
														'<a href="%s" class="button button-small button-link" target="_blank">Edit %s #%d</a>',
														esc_attr( $edit_url ),
														esc_html($village_name),
														esc_html($village_id)
													);
												}else{
													// Create a new post
													$create_url = add_query_arg(array('ah_create_village' => $village_name), $base_url);
													echo sprintf(
														'<a href="%s" class="button button-primary button-small ah-insert-button" target="_blank">Create Village</a>',
														esc_attr( $create_url )
													);
												}
												?>
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
					
					
					<div class="hotel-postbox postbox">
						<div class="postbox-header">
							<h2 id="hotels">Hotels (<?php echo count($hotel_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( $unassigned_hotels ) { ?>
								<p class="description">The hotels below were not found in the spreadsheet but exist on the website. You may need to enter the smartsheet name manually by editing the hotel.</p>
								
								<table class="ah-admin-table">
									
									<thead>
									<tr>
										<th class="col-hotel_name">Hotel Title</th>
										<th class="col-smartsheet_name">Smartsheet Name</th>
										<th class="col-actions">Actions</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $unassigned_hotels as $post_id ) {
										$title = get_the_title( $post_id );
										$name = get_field( 'smartsheet_name', $post_id );
										$edit_url = get_edit_post_link( $post_id );
										?>
										<tr>
											<td class="col-hotel_name"><span class="cell"><?php echo esc_html( $title ); ?></span></td>
											<td class="col-smartsheet_name"><span class="cell"><?php echo esc_html( $name ) ?: '<span style="opacity: 0.5;">(none)</span>'; ?></span></td>
											
											<td class="col-actions">
												<?php
												// Edit the post
												echo sprintf(
													'<a href="%s" class="button button-small button-link" target="_blank">Edit %s #%d</a>',
													esc_attr( $edit_url ),
													esc_html( $name ),
													esc_html( $post_id )
												);
												?>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							
							<?php } ?>
							
							<?php if ( ! $hotel_list ) { ?>
								
								<p><em>No hotels found.</em></p>
							
							<?php }else{ ?>
								
								<p class="description">Each hotel corresponds to a row from the spreadsheet.</p>
								
								<table class="ah-admin-table">
									
									<thead>
									<tr>
										<th class="col-hotel_name">Hotel Name</th>
										<th class="col-actions">Actions</th>
									</tr>
									</thead>
									
									<tbody>
									<?php
									foreach( $hotel_list as $hotel_name ) {
										$hotel_id = AH_Smartsheet_Sync_Hotels_And_Villages()->get_hotel_id_by_name( $hotel_name );
										?>
										<tr>
											<td class="col-hotel_name"><span class="cell"><?php echo esc_html($hotel_name ?: '&ndash;'); ?></span></td>
											
											<td class="col-actions">
												<?php
												if ( $hotel_id ) {
													// Edit the post
													$edit_url = get_edit_post_link($hotel_id);
													echo sprintf(
														'<a href="%s" class="button button-small button-link" target="_blank">Edit %s #%d</a>',
														esc_attr( $edit_url ),
														esc_html($hotel_name),
														esc_html($hotel_id)
													);
												}else{
													// Create a new post
													$create_url = add_query_arg(array('ah_create_hotel' => $hotel_name), $base_url);
													echo sprintf(
														'<a href="%s" class="button button-primary button-small ah-insert-button" target="_blank">Create Hotel</a>',
														esc_attr( $create_url )
													);
												}
												?>
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