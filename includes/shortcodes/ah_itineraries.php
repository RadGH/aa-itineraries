<?php

function shortcode_ah_itineraries( $atts, $content = '', $shortcode_name = 'ah_itineraries' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	$itineraries = AH_Itinerary()->get_user_itineraries();
	
	if ( ! $itineraries->have_posts() ) {
		return 'You currently have no itineraries.';
	}
	
	ob_start();
	?>
	<div class="ah-itineraries">
		
		<div class="itinerary-list">
			
			<?php
			foreach( $itineraries->posts as $p ) {
				$post_id = $p->ID;
				
				$title = get_field( 'title', $post_id );
				if ( !$title ) $title = $p->post_title;
				
				$subtitle = get_field( 'subtitle', $post_id );
				$date_range = get_field( 'date_range', $post_id );
				
				if ( $subtitle && $date_range ) $subtitle .= '<br>';
				if ( $date_range ) $subtitle .= $date_range;
				
				$view_link = get_permalink( $post_id );
				$pdf_link = untrailingslashit($view_link) . '/download/';
				?>
				<div class="itinerary-item">
					
					<h3 class="title">
						<a href="<?php echo esc_attr($view_link); ?>"><?php echo $title; ?></a>
					</h3>
					
					<?php if ( $subtitle ) { ?>
					<h5 class="subtitle"><?php echo $subtitle; ?></h5>
					<?php } ?>
					
					<div class="button-row">
						
						<a href="<?php echo esc_attr($view_link); ?>" class="button button-primary">View Itinerary</a>
						
						<a href="<?php echo esc_attr($pdf_link); ?>" class="button button-outline" target="_blank">Download PDF</a>
						
					</div>
					
				</div>
				<?php
			}
			?>
			
		</div>
		
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ah_itineraries', 'shortcode_ah_itineraries' );