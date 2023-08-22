<?php

// [ah_itineraries]

// [ah_has_itineraries] ... [/ah_has_itineraries]
// [ah_no_itineraries] ... [/ah_no_itineraries]

function shortcode_ah_itineraries( $atts, $content = '', $shortcode_name = 'ah_itineraries' ) {
	$atts = shortcode_atts(array(
		'most_recent' => false,
	), $atts, $shortcode_name);
	
	$show_most_recent = $atts['most_recent'] === 'true' || $atts['most_recent'] === '1';
	$itineraries = AH_Itinerary()->get_user_itineraries();
	
	if ( ! $itineraries->have_posts() ) {
		return 'You currently have no itineraries.';
	}
	
	ob_start();
	?>
	<div class="ah-itineraries">
		
		<div class="itinerary-list count-<?php echo $itineraries->found_posts; ?>">
			
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
				
				if ( $show_most_recent ) break;
			}
			?>
			
		</div>
		
		<?php
		// Link to all itineraries
		// Lazily hard-coded the post ID here, feel free to change later
		if ( $show_most_recent && $itineraries->found_posts > 1 ) {
			?>
			<div class="button-row">
				<p><a href="<?php echo get_permalink(6391); ?>" class="button button-outline">View More Itineraries &rightarrow;</a></p>
			</div>
			<?php
		}
		?>
		
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ah_itineraries', 'shortcode_ah_itineraries' );


function shortcode_ah_has_itineraries( $atts, $content = '', $shortcode_name = 'ah_has_itineraries' ) {
	$atts = shortcode_atts(array(
	), $atts, $shortcode_name);
	
	$itineraries = AH_Itinerary()->get_user_itineraries();
	$has_itineraries = $itineraries->have_posts();
	
	// reverse logic for [ah_no_itineraries]
	if ( $shortcode_name == 'ah_no_itineraries' ) $has_itineraries = ! $has_itineraries;
	
	return $has_itineraries ? do_shortcode( $content ) : '';
}
add_shortcode( 'ah_has_itineraries', 'shortcode_ah_has_itineraries' );
add_shortcode( 'ah_no_itineraries', 'shortcode_ah_has_itineraries' );