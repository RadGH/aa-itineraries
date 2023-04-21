<?php
$sheet_id = AH_Smartsheet_Hotels()->get_sheet_id();
$column_ids = AH_Smartsheet_Hotels()->get_column_ids();
$registered_hotels = AH_Smartsheet_Hotels()->get_stored_hotel_list();
$columns = AH_Smartsheet_Hotels()->columns;

$sheet_url = AH_Smartsheet_Hotels()->get_smartsheet_permalink();

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_hotels' => 1));
?>

<div class="wrap">
	
	<h1>Hotel Info</h1>
	
	<?php
	AH_Admin()->display_notices();
	?>
	
	<form id="post" method="post" name="post">
		
		<div id="poststuff" class="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				
				<div id="postbox-container-1" class="postbox-container">
					
					<div id="submitdiv" class="postbox">
						<div class="postbox-header">
							<h2>Actions</h2>
						</div>
						
						<div class="inside">
							<div class="misc-pub-section">
								<p><a href="edit.php?post_type=ah_hotel" class="button button-secondary" target="_blank">View Hotels</a></p>
								<p><a href="<?php echo $sheet_url; ?>" class="button button-secondary" target="_blank">Open in Smartsheet</a></p>
							</div>
							
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
							<p>Updates the hotel list with any new information from the master Hotel master spreadsheet.</p>
							<p>Rows that have been deleted will <strong>not</strong> be removed from the website.</p>
							<p><a href="<?php echo esc_attr($sync_url); ?>" class="button button-secondary">Run Sync</a></p>
						</div>
					</div>
					
				</div>
				
				<div id="postbox-container-2" class="postbox-container">
					<div class="postbox">
						<div class="postbox-header">
							<h2>Hotel Information</h2>
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
					
					<div class="postbox">
						<div class="postbox-header">
							<h2>Hotel Details</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( empty($registered_hotels) ) { ?>
								
								<p>Hotel data is not available.</p>
								
							<?php }else{ ?>
							
							<table class="ah-hotel-table">
								<thead>
									<tr>
										<?php
										foreach( $columns as $key => $title ) {
											echo '<th class="col-', esc_attr($key), '">';
											echo esc_html($title);
											echo '</th>';
										}
										?>
									</tr>
								</thead>
								
								<tbody>
								<?php
								if ( $registered_hotels ) foreach( $registered_hotels as $h ) {
									/* @see Class_AH_Smartsheet_Hotels::$columns */
									$hotel_name = $h['hotel_name'];
									$proprietor_name = $h['proprietor_name'];
									$location = $h['location'];
									$email = $h['email'];
									$phone = $h['phone'];
									
									$wordpress_id = $h['wordpress_id'];
									$smartsheet_row_id = $h['smartsheet_row_id'];
									
									$create_url = add_query_arg(array('ah_insert_smartsheet_hotel' => $smartsheet_row_id));
									?>
									<tr>
										<?php
										foreach( $columns as $key => $title ) {
											if ( $key == 'wordpress_id' ) continue;
											
											$value = $h[$key] ?? false;
											echo '<td class="col-', esc_attr($key), '">';
											echo '<span class="cell">', esc_html($value), '</span>';
											echo '</td>';
										}
										?>
										
										<td class="col-wordpress_id">
											<?php
											if ( $wordpress_id ) {
												if ( AH_Hotel()->is_valid($wordpress_id) ) {
													// Edit hotel
													echo sprintf(
														'<a href="%s" class="button button-secondary">%s</a>',
														esc_attr(get_edit_post_link($wordpress_id)),
														esc_attr($wordpress_id)
													);
												}else{
													// Hotel ID specified, but not found
													echo '<strong>#'. $wordpress_id .' Not found</strong>';
												}
											}else{
												// No hotel ID yet, offer to create it
												echo sprintf(
													'<a href="%s" class="button button-primary button-small">Create</a>',
													esc_attr($create_url)
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