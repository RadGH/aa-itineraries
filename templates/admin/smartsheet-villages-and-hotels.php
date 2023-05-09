<?php
// Base URL for this page
$base_url = add_query_arg(array('page' => $_GET['page']), admin_url('admin.php'));

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_hotels_and_villages' => 1), $base_url);

// Get the last sync time
$sync_time = get_option( 'ah_hotels_and_villages_last_sync', false );

if ( $sync_time ) {
	$sync_time = date('F j, Y g:i a', strtotime($sync_time));
}else{
	$sync_time = '<em>(never)</em>';
}

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

// Identify which hotels are assigned to a post
list( $assigned_hotels, $unassigned_hotels ) = AH_Smartsheet_Sync_Hotels_And_Villages()->group_by_smartsheet_assignment( $hotel_list, 'hotel' );
list( $assigned_villages, $unassigned_villages ) = AH_Smartsheet_Sync_Hotels_And_Villages()->group_by_smartsheet_assignment( $village_list, 'village' );

if ( ! function_exists('ah_list_village_and_hotel_items') ) {
	/**
	 * Displays a list of village or hotel items with a column of actions.
	 *
	 * @param array $list   array of items including "name" and "post_id"
	 * @param string $type  either "hotel" or "village"
	 *
	 * @return void
	 */
	function ah_list_village_and_hotel_items( $list, $type ) {
		if ( empty($list) ) return;
		?>
		<table class="ah-admin-table ah-admin-table-fixed">
			
			<thead>
			<tr>
				<!-- <th class="col-smartsheet_name"><?php echo $type == 'village' ? 'Village Name' : 'Hotel Name'; ?></th>-->
				<th class="col-smartsheet_id"><?php echo $type == 'village' ? 'Village ID' : 'Hotel ID'; ?></th>
				<th class="col-actions">Actions</th>
			</tr>
			</thead>
			
			<tbody>
			<?php
			foreach( $list as $item ) {
				$post_id = $item['post_id'];
				$smartsheet_name = $item['smartsheet_name'];
				$smartsheet_id = $item['smartsheet_id'];
				?>
				<tr>
					<!-- <td class="col-smartsheet_name"><span class="cell"><?php echo esc_html($smartsheet_name ?: '&ndash;'); ?></span></td>-->
					<td class="col-smartsheet_id"><span class="cell"><?php echo esc_html($smartsheet_id ?: '&ndash;'); ?></span></td>
					
					<td class="col-actions">
						<?php
						if ( $post_id ) {
							// Button to edit the existing post
							$edit_url = get_edit_post_link($post_id);
							echo sprintf(
								'<a href="%s" class="button button-small button-link" target="_blank">Edit %s #%d</a>',
								esc_attr( $edit_url ),
								esc_html($smartsheet_name),
								esc_html($post_id)
							);
						}else{
							// Button to create a new village/hotel
							if ( $type == 'village' ) {
								$create_text = 'Create Village';
							}else{
								$create_text = 'Create Hotel';
							}
							
							$create_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_edit_village_or_hotel_link( $type, $smartsheet_name, $smartsheet_id );
							
							echo sprintf(
								'<a href="%s" class="button button-primary button-small ah-insert-button" target="_blank">%s</a>',
								esc_attr( $create_url ),
								esc_html( $create_text )
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
		<?php
	}
}

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
						
						<div class="postbox">
							<div class="postbox-header">
								<h2>Navigation</h2>
							</div>
							
							<div class="inside">
								<ul class="ul-disc">
									<li><a href="#instructions">Instructions</a></li>
									<li><a href="#villages">Villages (<?php echo count($village_list); ?>)</a></li>
									<li><a href="#hotels">Hotels (<?php echo count($hotel_list); ?>)</a></li>
									<li><a href="#advanced-settings">Advanced Settings</a></li>
								</ul>
							</div>
						</div>
					
						<!--
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
						-->
						
						<div class="postbox">
							<div class="postbox-header">
								<h2>Sync with Smartsheet</h2>
							</div>
							
							<div class="inside">
								<p>Updates the hotel list with current information from the Hotel master spreadsheet.</p>
								<p>* Does NOT automatically create, update, or delete hotels on this website.</p>
								<p>
									<a href="<?php echo esc_attr($sync_url); ?>" class="button button-secondary">Run Sync</a>
								</p>
								<p style="opacity:0.5;">Last sync: <?php echo $sync_time; ?></p>
							</div>
						</div>
					
					</div>
					
				</div>
				
				<div id="postbox-container-2" class="ah-postbox-main postbox-container">
					
					
					<!-- Instructions -->
					<div class="instructions-postbox postbox">
						<div class="postbox-header">
							<h2 id="instructions">Instructions</h2>
						</div>
						
						<div class="inside">
							
							<p>This tool helps you maintain the list of villages and hotels to keep data in sync across WordPress and Smartsheet.</p>
							
							<p><strong>How to use this tool:</strong></p>
							
							<ol>
								<li>Use the "Run Sync" button to the right to update the list of Villages and Hotels from Smartsheet.</li>
								<li>Find any unassigned villages/hotels in the list below and use the "Create" button to add the item and connect it to Smartsheet.</li>
								<li>Use the list of assigned villages/hotels to easily locate the item in WordPress and start editing.</li>
							</ol>
							
							<?php // @todo: list Hotels/Villages that exist in WordPress but NOT in smartsheet? ?>
							<p><em>Please note that items in WordPress that are not in the spreadsheet will <strong>NOT</strong> be listed below. This feature may be implemented later.</em></p>
							
						</div>
					</div>
					<!-- End: Instructions -->
					
					
					<!-- Villages -->
					<div class="village-postbox postbox">
						<div class="postbox-header">
							<h2 id="villages">Villages (<?php echo count($village_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $village_list ) { ?>
								
								<p class="description"><em>No villages found.</em></p>
							
							<?php }else{ ?>
								
								<div class="ah-accordion ah-collapsed" id="unassigned-villages">
									<div class="ah-handle">
										<a href="#unassigned-villages">Unassigned Villages (<?php echo count($unassigned_villages); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $unassigned_villages ) {
											echo '<p class="description"><strong>Action Required:</strong> These villages exist in Smartsheet but have not been created in WordPress. Use the buttons below to automatically create and begin editing each village.</p>';
											ah_list_village_and_hotel_items( $unassigned_villages, 'village' );
										}else{
											echo '<p class="description">No action needed – All villages have been assigned.</p>';
										}
										?>
										
									</div>
								</div>
								
								<div class="ah-accordion ah-collapsed" id="assigned-villages">
									<div class="ah-handle">
										<a href="#assigned-villages">Assigned Villages (<?php echo count($assigned_villages); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $assigned_villages ) {
											echo '<p class="description">No action needed – These villages exist in both Smartsheet and WordPress.</p>';
											ah_list_village_and_hotel_items( $assigned_villages, 'village' );
										}else{
											echo '<p class="description">No villages have been assigned yet.</p>';
										}
										?>
										
									</div>
								</div>
								
							<?php } ?>
						
						</div>
					</div>
					<!-- End: Villages -->
					
					
					<!-- Hotels -->
					<div class="hotel-postbox postbox">
						<div class="postbox-header">
							<h2 id="hotels">Hotels (<?php echo count($hotel_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $hotel_list ) { ?>
								
								<p class="description"><em>No hotels found.</em></p>
							
							<?php }else{ ?>
								
								<div class="ah-accordion ah-collapsed" id="unassigned-hotels">
									<div class="ah-handle">
										<a href="#unassigned-hotels">Unassigned hotels (<?php echo count($unassigned_hotels); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $unassigned_hotels ) {
											echo '<p class="description"><strong>Action required:</strong> These hotels exist in Smartsheet but have not been created in WordPress. Use the buttons below to automatically create and begin editing each hotel.</p>';
											ah_list_village_and_hotel_items( $unassigned_hotels, 'hotel' );
										}else{
											echo '<p class="description">No action needed – All hotels have been assigned.</p>';
										}
										?>
										
									</div>
								</div>
								
								<div class="ah-accordion ah-collapsed" id="assigned-hotels">
									<div class="ah-handle">
										<a href="#assigned-hotels">Assigned hotels (<?php echo count($assigned_hotels); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $assigned_hotels ) {
											echo '<p class="description">No action needed – These hotels exist in both Smartsheet and WordPress.</p>';
											ah_list_village_and_hotel_items( $assigned_hotels, 'hotel' );
										}else{
											echo '<p class="description">No hotels have been assigned yet.</p>';
										}
										?>
										
									</div>
								</div>
								
							<?php } ?>
						
						</div>
					</div>
					<!-- End: Hotels -->
					
					
					
					
					
					<div class="postbox">
						<div class="postbox-header">
							<h2 id="advanced-settings">Advanced Settings</h2>
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
							
							<div class="ah-admin-field ah-submit">
								<input type="submit" name="publish" value="Save Changes" class="button button-secondary button-large" id="publish">
							</div>
						
						</div>
					</div>
					
					
					
					
					
				</div>
				
			</div>
			
			<div class="clear"></div>
		</div>
	
	</form>
	
</div>