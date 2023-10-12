<?php
// Base URL for this page
$base_url = add_query_arg(array('page' => $_GET['page']), admin_url('admin.php'));

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_hotels_and_villages' => 1), $base_url);

// Get the last sync time
$sync_date = get_option( 'ah_hotels_and_villages_last_sync', false );

// Get sheet settings
$sheet_id = AH_Smartsheet_Sync_Hotels_And_Villages()->get_sheet_id();
$column_ids = AH_Smartsheet_Sync_Hotels_And_Villages()->get_column_ids();
$sheet_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_smartsheet_permalink();
$columns = AH_Smartsheet_Sync_Hotels_And_Villages()->columns;

// Preload the list of hotel and village post IDs along with their smartsheet id
$hotel_posts = AH_Smartsheet_Sync_Hotels_And_Villages()->preload_hotel_post_list();
$village_posts = AH_Smartsheet_Sync_Hotels_And_Villages()->preload_village_post_list();

// Get hotels from a prior Smartsheet sync
$hotel_list = AH_Smartsheet_Sync_Hotels_And_Villages()->get_stored_hotel_list();
$village_list = AH_Smartsheet_Sync_Hotels_And_Villages()->get_stored_village_list();

// Identify which hotels are assigned to a post
//   assigned: Exists in smartsheet and corresponds with wordpress
//   unassigned: Only exists in smartsheet
//   missing: Only exists in wordpress
list( $assigned_hotels, $unassigned_hotels, $missing_hotels ) = AH_Smartsheet_Sync_Hotels_And_Villages()->group_by_smartsheet_assignment( $hotel_list, 'hotel' );
list( $assigned_villages, $unassigned_villages, $missing_villages ) = AH_Smartsheet_Sync_Hotels_And_Villages()->group_by_smartsheet_assignment( $village_list, 'village' );

$hotel_count = count($assigned_hotels) + count($unassigned_hotels) + count($missing_hotels);
$village_count = count($assigned_villages) + count($unassigned_villages) + count($missing_villages);

if ( ! function_exists('ah_list_village_and_hotel_items') ) {
	/**
	 * Displays a list of village or hotel items with a column of actions.
	 *
	 * @param array $list   array of items, each including: "post_id", "smartsheet_name", and "smartsheet_id"
	 * @param string $type  one of: "hotel" or "village"
	 * @param string $mode  one of: "assigned" or "unassigned" or "missing"
	 *
	 * @return void
	 */
	function ah_list_village_and_hotel_items( $list, $type, $mode ) {
		if ( empty($list) ) return;
		?>
		<table class="ah-admin-table ah-admin-table-fixed">
			
			<thead>
			<tr>
				<?php if ( $mode == 'missing' ) { ?>
					<th class="col-wordpress_title"><?php echo 'Post Title'; ?></th>
					<th class="col-smartsheet_id">Smartsheet ID</th>
				<?php }else{ ?>
					<th class="col-smartsheet_id"><?php echo ($type == 'village' ? 'Village ID' : 'Hotel ID'); ?></th>
				<?php } ?>
				
				<th class="col-actions">Actions</th>
			</tr>
			</thead>
			
			<tbody>
			<?php
			foreach( $list as $item ) {
				$post_id = $item['post_id'];
				$smartsheet_name = $item['smartsheet_name'];
				$smartsheet_id = $item['smartsheet_id'];
				
				if ( $mode == 'missing' ) {
					$smartsheet_id = get_field( 'smartsheet_id', $post_id );
				}
				
				?>
				<tr>
					<?php if ( $mode == 'missing' ) { ?>
						<td class="col-wordpress_title"><span class="cell"><?php echo esc_html(get_the_title($post_id) ?: '&ndash;'); ?></span></td>
						<td class="col-smartsheet_id"><span class="cell"><?php echo esc_html($smartsheet_id) ?: '<em>(empty)</em>'; ?></span></td>
					<?php }else{ ?>
						<td class="col-smartsheet_id"><span class="cell"><?php echo esc_html($smartsheet_id) ?: '<em>(empty)</em>'; ?></span></td>
					<?php } ?>
					
					<td class="col-actions">
						<?php
						
						// Button to sync the item
						if ( $post_id && $smartsheet_id ) {
							$sync_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_sync_village_or_hotel_link( $type, $post_id, $smartsheet_id );
							
							echo sprintf(
								'<a href="%s" class="button button-primary button-small ah-update-item-button" target="_blank">Sync</a> ',
								esc_attr( $sync_url )
							);
						}
						
						// Button to edit the existing item
						if ( $post_id ) {
							$edit_url = get_edit_post_link($post_id);
							
							echo sprintf(
								'<a href="%s" class="button button-small button-link" target="_blank">Edit %s #%d</a> ',
								esc_attr( $edit_url ),
								esc_html($smartsheet_name),
								esc_html($post_id)
							);
						}
						
						// Button to create a new item
						if ( ! $post_id ) {
							if ( $type == 'village' ) {
								$create_text = 'Create Village';
							}else{
								$create_text = 'Create Hotel';
							}
							
							$create_url = AH_Smartsheet_Sync_Hotels_And_Villages()->get_edit_village_or_hotel_link( $type, $smartsheet_name, $smartsheet_id );
							
							echo sprintf(
								'<a href="%s" class="button button-primary button-small ah-create-item-button" target="_blank">%s</a>',
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
									<li><a href="#villages">Villages (<?php echo $village_count; ?>)</a></li>
									<li><a href="#hotels">Hotels (<?php echo $hotel_count; ?>)</a></li>
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
								<p class="ah-last-sync">Last sync: <?php echo ah_get_relative_date_html( $sync_date ) ?: '(never)'; ?></p>
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
							<h2 id="villages">Villages (<?php echo $village_count; ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $village_list ) { ?>
								
								<p class="description"><em>No villages found.</em></p>
							
							<?php }else{ ?>
								
								<?php if ( $missing_villages ) { ?>
									<div class="ah-accordion ah-collapsed" id="missing-villages">
										<div class="ah-handle">
											<a href="#missing-villages">Missing villages (<?php echo count($missing_villages); ?>)</a>
										</div>
										<div class="ah-content">
											<?php
											echo '<p class="description">These villages only exist in WordPress and are not present in Smartsheet. Verify that the Smartsheet ID is correct for these items.</p>';
											ah_list_village_and_hotel_items( $missing_villages, 'village', 'missing' );
											?>
										</div>
									</div>
								<?php } ?>
								
								<?php if ( $unassigned_villages ) { ?>
								<div class="ah-accordion ah-collapsed" id="unassigned-villages">
									<div class="ah-handle">
										<a href="#unassigned-villages">Unassigned Villages (<?php echo count($unassigned_villages); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										echo '<p class="description"><strong>Action Required:</strong> These villages exist in Smartsheet but have not been created in WordPress. Use the buttons below to automatically create and begin editing each village.</p>';
										ah_list_village_and_hotel_items( $unassigned_villages, 'village', 'unassigned' );
										?>
										
									</div>
								</div>
								<?php } ?>
								
								<div class="ah-accordion ah-collapsed" id="assigned-villages">
									<div class="ah-handle">
										<a href="#assigned-villages">Assigned Villages (<?php echo count($assigned_villages); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $assigned_villages ) {
											echo '<p class="description">No action needed – These villages exist in both Smartsheet and WordPress.</p>';
											ah_list_village_and_hotel_items( $assigned_villages, 'village', 'assigned' );
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
							<h2 id="hotels">Hotels (<?php echo $hotel_count; ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $hotel_list ) { ?>
								
								<p class="description"><em>No hotels found.</em></p>
							
							<?php }else{ ?>
								
								<?php if ( $missing_hotels ) { ?>
									<div class="ah-accordion ah-collapsed" id="missing-hotels">
										<div class="ah-handle">
											<a href="#missing-hotels">Missing hotels (<?php echo count($missing_hotels); ?>)</a>
										</div>
										<div class="ah-content">
											<?php
											echo '<p class="description">These hotels only exist in WordPress and are not present in Smartsheet. Verify that the Smartsheet ID is correct for these items.</p>';
											ah_list_village_and_hotel_items( $missing_hotels, 'hotel', 'missing' );
											?>
										</div>
									</div>
								<?php } ?>
								
								<?php if ( $unassigned_hotels ) { ?>
								<div class="ah-accordion ah-collapsed" id="unassigned-hotels">
									<div class="ah-handle">
										<a href="#unassigned-hotels">Unassigned hotels (<?php echo count($unassigned_hotels); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										echo '<p class="description"><strong>Action required:</strong> These hotels exist in Smartsheet but have not been created in WordPress. Use the buttons below to automatically create and begin editing each hotel.</p>';
										ah_list_village_and_hotel_items( $unassigned_hotels, 'hotel', 'unassigned' );
										?>
										
									</div>
								</div>
								<?php } ?>
								
								<div class="ah-accordion ah-collapsed" id="assigned-hotels">
									<div class="ah-handle">
										<a href="#assigned-hotels">Assigned hotels (<?php echo count($assigned_hotels); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $assigned_hotels ) {
											echo '<p class="description">No action needed – These hotels exist in both Smartsheet and WordPress.</p>';
											ah_list_village_and_hotel_items( $assigned_hotels, 'hotel', 'assigned' );
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
							
							<div class="ah-spreadsheet-finder"></div>
							
							<!-- Sheet ID -->
							<div class="ah-admin-field">
								<div class="ah-label">
									<label for="ah-sheet-id">Sheet ID:</label>
								</div>
								<div class="ah-field">
									<input type="text" name="ah[sheet_id]" id="ah-sheet-id" value="<?php echo esc_attr($sheet_id); ?>">
									<?php if ( $sheet_url ) echo ah_create_html_link( $sheet_url, 'View Spreadsheet' ); ?>
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
							
							<!-- Submit button -->
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