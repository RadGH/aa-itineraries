<?php
// Base URL for this page
$base_url = add_query_arg(array('page' => $_GET['page']), admin_url('admin.php'));

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_hikes' => 1), $base_url);

// Get the last sync time
$sync_date = get_option( 'ah_hike_last_sync', false );

// Get sheet settings
$sheet_id = AH_Smartsheet_Sync_Hikes()->get_sheet_id();
$column_ids = AH_Smartsheet_Sync_Hikes()->get_column_ids();
$sheet_url = AH_Smartsheet_Sync_Hikes()->get_smartsheet_permalink();
$columns = AH_Smartsheet_Sync_Hikes()->columns;

// Preload the list of hike post IDs along with their smartsheet id
$hike_posts = AH_Smartsheet_Sync_Hikes()->preload_hike_post_list();

// Get hikes from a prior Smartsheet sync
$hike_list = AH_Smartsheet_Sync_Hikes()->get_stored_hike_list();

// Identify which hikes are assigned to a post
//   assigned: Exists in smartsheet and corresponds with wordpress
//   unassigned: Only exists in smartsheet
//   missing: Only exists in wordpress
list( $assigned_hikes, $unassigned_hikes, $missing_hikes ) = AH_Smartsheet_Sync_Hikes()->group_by_smartsheet_assignment( $hike_list );

$hike_count = count($assigned_hikes) + count($unassigned_hikes) + count($missing_hikes);

if ( ! function_exists('ah_list_hike_items') ) {
	/**
	 * Displays a list of hike items with a column of actions.
	 *
	 * @param array $list   array of items, each including: "post_id", "smartsheet_name", and "smartsheet_id"
	 * @param string $mode  one of: "assigned" or "unassigned" or "missing"
	 *
	 * @return void
	 */
	function ah_list_hike_items( $list, $mode ) {
		if ( empty($list) ) return;
		?>
		<table class="ah-admin-table ah-admin-table-fixed">
			
			<thead>
			<tr>
				<?php if ( $mode == 'missing' ) { ?>
					<th class="col-wordpress_title"><?php echo 'Post Title'; ?></th>
					<th class="col-smartsheet_id">Smartsheet ID</th>
				<?php }else{ ?>
					<th class="col-smartsheet_id">Hike Name</th>
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
						<td class="col-smartsheet_id"><span class="cell"><?php echo esc_html($smartsheet_id ?: '<em>(empty)</em>'); ?></span></td>
					<?php }else{ ?>
						<td class="col-smartsheet_id"><span class="cell"><?php echo esc_html($smartsheet_id ?: '<em>(empty)</em>'); ?></span></td>
					<?php } ?>
					
					<td class="col-actions">
						<?php
						
						// Button to sync the item
						if ( $post_id && $smartsheet_id ) {
							$sync_url = AH_Smartsheet_Sync_Hikes()->get_sync_hike_link(  $post_id, $smartsheet_id );
							
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
							$create_text = 'Create Hike';
							
							$create_url = AH_Smartsheet_Sync_Hikes()->get_edit_hike_link( $smartsheet_id );
							
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
	
	<h1>Smartsheet - Sync Hikes</h1>
	
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
									<li><a href="#hikes">Hikes (<?php echo $hike_count; ?>)</a></li>
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
								<p>Updates the hike list with current information from the Hike master spreadsheet.</p>
								<p>* Does NOT automatically create, update, or delete hikes on this website.</p>
								<p>
									<a href="<?php echo esc_attr($sync_url); ?>" class="button button-secondary">Run Sync</a>
								</p>
								<p style="opacity:0.5;">Last sync: <?php echo ah_get_relative_date_html( $sync_date ) ?: '(never)'; ?></p>
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
							
							<p>This tool helps you maintain the list of hikes to keep data in sync across WordPress and Smartsheet.</p>
							
							<p><strong>How to use this tool:</strong></p>
							
							<ol>
								<li>Use the "Run Sync" button to the right to update the list of Hikes from Smartsheet.</li>
								<li>Find any unassigned hikes in the list below and use the "Create" button to add the item and connect it to Smartsheet.</li>
								<li>Use the list of assigned hikes to easily locate the item in WordPress and start editing.</li>
							</ol>
							
							<?php // @todo: list hikes that exist in WordPress but NOT in smartsheet? ?>
							<p><em>Please note that items in WordPress that are not in the spreadsheet will <strong>NOT</strong> be listed below. This feature may be implemented later.</em></p>
							
						</div>
					</div>
					<!-- End: Instructions -->
					
					<!-- Hikes -->
					<div class="hike-postbox postbox">
						<div class="postbox-header">
							<h2 id="hikes">Hikes (<?php echo $hike_count; ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $hike_list ) { ?>
								
								<p class="description"><em>No hikes found.</em></p>
							
							<?php }else{ ?>
								
								<?php if ( $missing_hikes ) { ?>
									<div class="ah-accordion ah-collapsed" id="missing-hikes">
										<div class="ah-handle">
											<a href="#missing-hikes">Missing hikes (<?php echo count($missing_hikes); ?>)</a>
										</div>
										<div class="ah-content">
											<?php
											echo '<p class="description">These hikes only exist in WordPress and are not present in Smartsheet. Verify that the Smartsheet ID is correct for these items.</p>';
											ah_list_hike_items( $missing_hikes, 'hike', 'missing' );
											?>
										</div>
									</div>
								<?php } ?>
								
								<?php if ( $unassigned_hikes ) { ?>
								<div class="ah-accordion ah-collapsed" id="unassigned-hikes">
									<div class="ah-handle">
										<a href="#unassigned-hikes">Unassigned hikes (<?php echo count($unassigned_hikes); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										echo '<p class="description"><strong>Action required:</strong> These hikes exist in Smartsheet but have not been created in WordPress. Use the buttons below to automatically create and begin editing each hike.</p>';
										ah_list_hike_items( $unassigned_hikes, 'hike', 'unassigned' );
										?>
										
									</div>
								</div>
								<?php } ?>
								
								<div class="ah-accordion ah-collapsed" id="assigned-hikes">
									<div class="ah-handle">
										<a href="#assigned-hikes">Assigned hikes (<?php echo count($assigned_hikes); ?>)</a>
									</div>
									<div class="ah-content">
										
										<?php
										if ( $assigned_hikes ) {
											echo '<p class="description">No action needed – These hikes exist in both Smartsheet and WordPress.</p>';
											ah_list_hike_items( $assigned_hikes, 'hike', 'assigned' );
										}else{
											echo '<p class="description">No hikes have been assigned yet.</p>';
										}
										?>
										
									</div>
								</div>
								
							<?php } ?>
						
						</div>
					</div>
					<!-- End: Hikes -->
					
					
					
					
					
					<div class="postbox">
						<div class="postbox-header">
							<h2 id="advanced-settings">Advanced Settings</h2>
						</div>
						
						<div class="inside">
							
							<input type="hidden" name="ah-action" value="<?php echo wp_create_nonce('save-hike-info'); ?>">
							
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
									/* Each column name must be defined in smartsheet-sync-hikes.php, $this->columns */
									foreach( $columns as $key => $title ) {
										$value = $column_ids[ $key ] ?? '';
										?>
										<label for="ah-hike-col-<?php echo $key; ?>"><?php echo $title; ?>:</label>
										<div class="ah-field">
											<input type="text" name="ah[column_ids][<?php echo $key; ?>]" id="ah-hike-col-<?php echo $key; ?>" value="<?php echo esc_attr($value); ?>">
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