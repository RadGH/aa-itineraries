<?php

// create a class that can compare the value fields in a table, with a checkbox to accept the change
class Class_Compare_Field_Values {
	
	private int $post_id;
	
	private Class_Sync_Itinerary_Fields $fields;
	
	private array $new_values;
	
	private array $old_values;
	
	public function __construct( int $post_id, Class_Sync_Itinerary_Fields $fields ) {
		if ( ! $post_id || ! $fields ) {
			wp_die('Invalid post_id or $fields in ' . __CLASS__ . ' at ' . __FILE__ . ':' . __LINE__);
			exit;
		}
		
		$this->post_id = $post_id;
		$this->fields = $fields;
		
		$this->new_values = $fields->get_new_values();
		$this->old_values = $fields->get_old_values();
	}
	
	public function display_form() {
		$field_list = $this->fields->get_fields();
		?>
		<style>
			table.compare-fields {
				max-width: 1200px;
				table-layout: fixed;
			}
			
			table.compare-fields .field-title { width: 150px; }
			table.compare-fields .field-value { width: 40%; }
			table.compare-fields .field-actions { width: 150px; }
			
			table.compare-fields tr:nth-child(even) { background-color: rgba(0,0,0,0.015); }
			
			table.compare-fields th,
			table.compare-fields td {
				vertical-align: top;
				text-align: left;
				padding: 6px 5px;
			}

			table.compare-fields th.field-header {
				font-weight: 500;
				font-size: 16px;
			}

			table.compare-fields .type-repeater .content {
				display: grid;
				grid-template-columns: auto 1fr;
				gap: 10px;
			}

			table.compare-fields .type-repeater .repeater-col-name {
				min-width: 50px;
				font-weight: 300;
				font-size: 12px;
			}

			table.compare-fields .repeater-col-value {
				white-space: pre;
			}
			
			table.compare-fields .repeater-col-value > :first-child {
				margin-top: 0;
			}
			table.compare-fields .repeater-col-value > :last-child {
				margin-bottom: 0;
			}

			.compare-fields tr.has-new-value.values-changed .field-value.value-new {
				background-color: #f3f1ea;
			}

			.compare-fields tr.selected.has-new-value.values-changed .field-value.value-new {
				background-color: #eaf3ea;
			}
			
			.compare-fields tr.selected.has-new-value .field-value.value-new {
				color: #109e10;
			}
			
			.compare-fields tr.selected.no-new-value.values-changed .field-value.value-old {
				color: #9e1010;
				background-color: #f3eaea;
			}
			
			.compare-fields .field-value a {
				color: inherit;
			}
		</style>
		
		<form action="" method="POST">
			
			<input type="hidden" name="action" value="ah_sync_item">
			<input type="hidden" name="ah[post_id]" value="<?php echo $this->post_id; ?>">
			
			<input type="hidden" name="ah[values]" value="<?php echo esc_attr(json_encode( $this->new_values )); ?>">
		
			<?php
			// Display any warnings
			if ( $warnings = $this->fields->get_warnings() ) {
				?>
				<div class="postbox compare-warnings">
					<div class="inside">
						<h3>Warnings:</h3>
						
						<?php
						foreach( $warnings as $warning ) {
							echo '<div class="compare-warning">';
							
							echo wpautop($warning['message']);
							
							if ( $warning['data'] !== null ) {
								echo '<pre class="compare-data">';
								echo esc_html( print_r( $warning['data'], true ) );
								echo '</pre>';
							}
							
							echo '</div>';
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
			
			<div class="postbox">
				<div class="inside">
					<table class="compare-fields">
						
						<thead>
						<tr>
							<th class="field-title field-header"></th>
							<th class="field-value value-old field-header">Current</th>
							<th class="field-value value-new field-header">Smartsheet</th>
							<th class="field-actions field-header">Actions</th>
						</tr>
						</thead>
						
						<tbody>
						<?php
						// Get the value of each field using its callback
						foreach( $field_list as $field ) {
							
							$value = $this->new_values[ $field['meta_key'] ];
							$old_value = $this->old_values[ $field['meta_key'] ];

							$this->display_field( $field, $value, $old_value );
							
						}
						?>
						</tbody>
					</table>
					
					<div class="compare-field-submit">
						<input type="submit" value="Update Selected" class="button button-primary">
						
						<a href="<?php echo get_edit_post_link($this->post_id); ?>" class="button button-secondary">Go Back</a>
					</div>
				
				</div> <!-- .inside -->
			</div> <!-- .postbox -->
		
		</form>
		
		<script type="text/javascript">
		jQuery(function() {
			jQuery('.compare-fields').on('change click', 'input[type="checkbox"]', function() {
				let $row = jQuery(this).closest('tr');
				
				if ( this.checked ) {
					$row.addClass('selected');
				} else {
					$row.removeClass('selected');
				}
			});
		});
		</script>
		<?php
	}
	
	private function display_field( $field, $new_value = null, $old_value = null ) {
		$type = $field['type'];
		
		switch( $type ) {
			case 'repeater':
				$this->display_repeater_rows_as_fields( $field, $new_value, $old_value );
				break;
			
			case 'textarea':
			case 'editor':
				$this->display_field_preview_textarea( $field, $new_value, $old_value );
			break;
			
			case 'text':
				$this->display_field_preview_text( $field, $new_value, $old_value );
				break;
				
		}
		
	}
	
	private function display_repeater_rows_as_fields( $field, $new_value, $old_value ) {
		if ( ! is_array($new_value) ) $new_value = array();
		if ( ! is_array($old_value) ) $old_value = array();
		
		// Count max number of rows in the old and new repeater
		$num_rows = max( count($new_value), count($old_value) );
		
		// Always display one row
		if ( $num_rows < 1 ) $num_rows = 1;
		
		// Loop through each row
		for ( $row_i = 0; $row_i < $num_rows; $row_i++ ) {
			if ( ! isset($new_value[$row_i]) ) $new_value[$row_i] = array();
			if ( ! isset($old_value[$row_i]) ) $old_value[$row_i] = array();
			
			$first_col = true;
			
			// Display each column as a field to easily compare line by line
			foreach( $field['repeater_row_template'] as $col_key => $default_value ) {
				$new_col_value = $new_value[$row_i][$col_key] ?? $default_value;
				$old_col_value = $old_value[$row_i][$col_key] ?? $default_value;
				
				if ( $first_col ) {
					$title = $field['title'] . ' [' . ($row_i + 1) . ']';
					$first_col = false;
				}else{
					$title = '&nbsp;';
				}
				
				// Skip empty rows
				if ( ! $new_col_value && ! $old_col_value ) continue;
				
				$this->display_field_preview_repeater_row( $field, $new_col_value, $old_col_value, $title, $row_i, $col_key );
			}
			
		}
		
	}
	
	/**
	 * Compare two variables and return true if they match. Strings will ignore different line breaks.
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return bool
	 */
	private function compare_values( $a, $b ) {
		$a_str = preg_replace( '/(\r\n|\r|\n)+/', "\n", (string) $a );
		$b_str = preg_replace( '/(\r\n|\r|\n)+/', "\n", (string) $b );
		return $a_str === $b_str;
	}
	
	/**
	 * Display a row of field columns
	 *
	 * @param array $field
	 * @param string $field_title
	 * @param string $new_html
	 * @param string $old_html
	 * @param int $row_i
	 * @param string $col_key
	 *
	 * @return void
	 */
	private function display_field_columns( $field, $field_title, $new_html, $old_html, $row_i = null, $col_key = null ) {
		$field_name = 'ah[fields][' . $field['meta_key'] . ']';
		if ( $row_i !== null ) $field_name .= '[' . $row_i . ']';
		if ( $col_key !== null ) $field_name .= '[' . $col_key . ']';
		
		$values_match = $this->compare_values( $new_html, $old_html);
		
		$checked = false;
		if ( $new_html && ! $values_match ) $checked = true;
		
		$classes  = 'key-' . $field['meta_key'];
		$classes .= ' type-' . $field['type'];
		$classes .= $values_match ? ' values-match' : ' values-changed';
		$classes .= $old_html ? ' has-old-value' : ' no-old-value';
		$classes .= $new_html ? ' has-new-value' : ' no-new-value';
		if ( $row_i !== null ) $classes .= ' row-' . $row_i;
		if ( $col_key !== null ) $classes .= ' col-' . $col_key;
		if ( $checked ) $classes .= ' selected';
		?>
		<tr class="<?php echo esc_attr($classes); ?>">
			<th class="field-title" scope="row">
				<?php echo $field_title; ?>
			</th>
			
			<td class="field-value value-old">
				<div class="content"><?php echo $old_html; ?></div>
			</td>
			
			<td class="field-value value-new">
				<div class="content"><?php echo $new_html; ?></div>
			</td>
			
			<td class="field-actions">
				<label><input type="checkbox" name="<?php echo esc_attr($field_name) ?>" value="1" <?php checked($checked); ?>> Update</label>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Display the value of a single-line text field
	 *
	 * @param $field
	 * @param $new_value
	 * @param $old_value
	 *
	 * @return void
	 */
	private function display_field_preview_text( $field, $new_value, $old_value = null ) {
		$new_html = $new_value ? esc_html( $new_value ) : '';
		$old_html = $old_value ? esc_html( $old_value ) : '';
		$this->display_field_columns( $field, $field['title'],$new_html, $old_html );
	}
	
	private function display_field_preview_textarea( $field, $new_value, $old_value = null ) {
		$new_html = $new_value ? wpautop( $new_value ) : '';
		$old_html = $old_value ? wpautop( $old_value ) : '';
		$this->display_field_columns( $field, $field['title'], $new_html, $old_html );
	}
	
	private function display_field_preview_repeater_row( $field, $new_value, $old_value, $title, $row_i, $col_key ) {
		// Format post IDs as links (for hikes, villages, etc)
		if (
			( $field['meta_key'] == 'villages' && $col_key == 'village' ) || // villages -> village
			( $field['meta_key'] == 'villages' && $col_key == 'hotel' ) ||   // villages -> hotel
			( $field['meta_key'] == 'hikes' && ( $col_key == 'hike' ) )      // hikes    -> hike
		) {
			$new_value = $this->get_preview_post_id_html( $new_value );
			$old_value = $this->get_preview_post_id_html( $old_value );
		}
		
		if ( $new_value ) {
			$new_html = '<div class="repeater-col-name">'. $col_key . '</div><div class="repeater-col-value">' . $new_value . '</div>';
		}else{
			$new_html = '';
		}
		
		if ( $old_value ) {
			$old_html = '<div class="repeater-col-name">'. $col_key . '</div><div class="repeater-col-value">' . $old_value . '</div>';
		}else{
			$old_html = '';
		}
		
		$this->display_field_columns( $field, $title,$new_html, $old_html, $row_i, $col_key );
	}
	
	private function get_preview_post_id_html( $post_id ) {
		if ( $post_id ) {
			$url = get_edit_post_link( $post_id );
			$title = get_the_title( $post_id );
			
			if ( $url && $title ) {
				return ah_create_html_link( $url, $title, false );
			}else{
				return '#' . $post_id;
			}
		}
		
		return false;
	}
	
	/*
	private function display_field_preview_schedule( $field, $new_value, $old_value = null ) {
		pre_dump($new_value);
	}
	
	private function display_field_preview_villages( $field, $new_value, $old_value = null ) {
		pre_dump($new_value);
	}
	
	private function display_field_preview_hikes( $field, $new_value, $old_value = null ) {
		pre_dump($new_value);
	}
	*/
	
}