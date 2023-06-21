<?php
// Base URL for this page
$base_url = admin_url('admin.php?page=ah-smartsheet-sheets');

// Visiting this URL loads data from smartsheet
$sync_url = add_query_arg(array('ah_sync_sheets' => 1), $base_url);

// Get the last sync time
$sync_date = get_option( 'ah_sheets_last_sync', false );

// Get sheets from a prior Smartsheet sync
$sheet_list = AH_Smartsheet_Sync_Sheets()->get_stored_sheet_list();
?>

<div class="wrap">
	
	<h1>Smartsheet - Sync Sheets</h1>
	
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
								<h2>Sync with Smartsheet</h2>
							</div>
							
							<div class="inside">
								<p>Updates the list of all spreadsheets from the Smartsheet account.</p>
								<p><a href="<?php echo esc_attr($sync_url); ?>" class="button button-secondary">Run Sync</a></p>
								<p class="ah-last-sync">Last sync: <?php echo ah_get_relative_date_html( $sync_date ) ?: '(never)'; ?></p>
							</div>
						</div>
						
						<div class="postbox">
							<div class="postbox-header">
								<h2>Navigation</h2>
							</div>
							
							<div class="inside">
								<ul class="ul-disc">
									<li><a href="#sheets">Sheets (<?php echo count($sheet_list); ?>)</a></li>
								</ul>
							</div>
						</div>
					
					</div>
					
				</div>
				
				<div id="postbox-container-2" class="ah-postbox-main postbox-container">
					
					<div class="sheet-postbox postbox">
						<div class="postbox-header">
							<h2 id="sheets">Sheets (<?php echo count($sheet_list); ?>)</h2>
						</div>
						
						<div class="inside">
							
							<?php if ( ! $sheet_list ) { ?>
								
								<p><em>No sheets found.</em></p>
							
							<?php }else{ ?>
							
								<div class="ah-accordion ah-collapsed" id="sheet-list">
									<div class="ah-handle">
										<a href="#sheet-list">Toggle sheet list</a>
									</div>
									
									<div class="ah-content">
										<table class="ah-admin-table">
											
											<?php
											$cols = array(
												'id' => 'ID',
												'name' => 'Sheet Name',
												// 'accessLevel' => 'Access Level',
												'permalink' => 'URL',
												'createdAt' => 'Created',
												'modifiedAt' => 'Modified',
											);
											?>
											
											<thead>
											<tr>
												<?php
												foreach( $cols as $id => $label ) {
													echo sprintf(
														'<th class="col-%s">%s</th>',
														esc_attr($id),
														esc_html($label)
													);
												}
												?>
											</tr>
											</thead>
											
											<tbody>
											<?php
											foreach( $sheet_list as $sheet ) {
												?>
												<tr>
													<?php
													foreach( $cols as $id => $label ) {
														$value = $sheet[$id] ?? '(false)';
														
														if ( $id == 'permalink' ) {
															$value = sprintf(
																'<a href="%s" target="_blank">%s</a>',
																esc_attr($value),
																esc_html($value)
															);
														}else if ( $id == 'createdAt' || $id == 'modifiedAt' ) {
															$value = ah_get_relative_date_html($value);
														}else{
															$value = esc_html($value);
														}
														
														echo sprintf(
															'<td class="col-%s">%s</td>',
															esc_attr($id),
															$value
														);
													}
													?>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</div>
							
							<?php } ?>
						
						</div>
					</div>
					
				</div>
				
			</div>
			
			<div class="clear"></div>
		</div>
	
	</form>
	
</div>