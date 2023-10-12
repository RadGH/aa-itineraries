<?php

class Class_AH_Smartsheet_Warnings {
	
	// Warning system to show when a village/hike/etc from smartsheet is not present on the website, or other issues
	private $warnings = array();
	
	/** Add a sync warning */
	public function add_warning( $message, $data = null ) {
		$bt = aa_get_backtrace(1);
		
		$this->warnings[] = array(
			'message' => $message,
			'data' => $data,
			'source' => array(
				'file' => str_replace( AH_PATH, '', $bt[0]['file'] ),
				'line' => $bt[0]['line'],
				'function' => $bt[0]['function'],
				'args' => $bt[0]['args'] ?? null,
				'class' => $bt[0]['class'] ?? null,
			),
		);
	}
	
	public function display_warnings( $debug_info = null ) {
		$warnings = $this->warnings;
		if ( ! $warnings ) return;
		
		?>
		<div class="postbox compare-warnings">
			<div class="inside">
				<h3>Warnings:</h3>
				
				<?php
				foreach( $warnings as $i => $warning ) {
					$id = 'warning-' . $i;
					
					echo '<div class="compare-warning">';
					
					echo '<input type="checkbox" class="screen-reader-text ah-details-cb" id="' . $id . '">';
					echo '<label for="' . $id . '"><span class="collapse">-</span><span class="expand">+</span></label>';
					
					echo '<div class="compare-message">';
					echo wpautop('<label for="' . $id . '">' . $warning['message'] . '</label>');
					echo '</div>';
					
					echo '<div class="compare-details" style="display: none;">';
					
					if ( $warning['data'] !== null ) {
						echo '<pre class="compare-data">';
						echo esc_html( print_r( $warning['data'], true ) );
						echo '</pre>';
					}
					
					echo '<pre class="compare-source">';
					$src = $warning['source'];
					echo $src['file'] . ':' . $src['line'];
					if ( $src['class'] || $src['function'] ) {
						echo '<br>';
						if ( $src['class'] ) echo $src['class'] . '::';
						if ( $src['function'] ) echo $src['function'] . '(';
						//if ( $src['args'] ) echo esc_html(' "' . implode('", "', $src['args']) . '" ');
						if ( $src['function'] ) echo ')';
					}
					echo '</pre>';
					
					echo '</div>';
					
					echo '</div>';
				}
				
				
				if ( $debug_info ) {
					?>
					<div class="ah-accordion ah-collapsed" id="debugging-information">
						<div class="ah-handle">
							<a href="#debugging-information">Debugging Information</a>
						</div>
						<div class="ah-content">
							<?php
							foreach( $debug_info as $key => $data ) {
								echo '<h3>', esc_html($key), '</h3>';
								echo '<pre>';
								echo esc_html( print_r( $data, true ) );
								echo '</pre>';
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			
			</div>
		</div>
		
		<style>
			.compare-source {
				opacity: 0.5;
			}

			.ah-details-cb:checked + label {
				font-weight: 500;
			}
			
			.ah-details-cb:not(:checked) + label .collapse {
				display: none;
			}
			
			.ah-details-cb:checked + label .expand {
				display: none;
			}
			
			.ah-details-cb:checked + label + .compare-message + .compare-details {
				display: block !important;
			}

			.compare-warning > label {
				float: left;
				width: 20px;
				display: block;
				text-align: center;
			}

			.compare-details {
				margin-left: 20px;
			}
		</style>
		<?php
	}
	// End warning system
	
}